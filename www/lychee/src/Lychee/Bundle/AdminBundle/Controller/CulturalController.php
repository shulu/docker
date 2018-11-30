<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/09/2016
 * Time: 11:53 AM
 */

namespace Lychee\Bundle\AdminBundle\Controller;


use Lychee\Module\ContentManagement\Entity\AppLaunchImage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/cultural")
 * Class CulturalController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class CulturalController extends BaseController {

	public function getTitle() {
		return '文化运营';
	}

	/**
	 * @Route("/")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function indexAction()
	{
		return $this->redirect($this->generateUrl('lychee_admin_cultural_emoticon'));
	}

	/**
	 * @Route("/emoticon")
	 * @Template
	 * @param Request $request
	 * @return mixed
	 */
	public function emoticonAction(Request $request) {
		$env = $this->get('kernel')->getEnvironment();
		$qnKey = $this->container->getParameter('qiniu_access_key');
		$qnSecret = $this->container->getParameter('qiniu_secret_key');
		$bucket = $this->container->getParameter('qiniu_bucket');

		Qiniu_SetKeys($qnKey, $qnSecret);
		$putPolicy = new \Qiniu_RS_PutPolicy($bucket);
		$resourceKey = 'emoticon.json';
		if ('dev' === $env) {
			$resourceKey = 'dev/' . $resourceKey;
		}
		$returnUrl = sprintf(
			"%s://%s:%s%s",
			$request->getScheme(),
			$request->getHost(),
			$request->getPort(),
			$this->generateUrl('lychee_admin_upload_success', [
				'redirect' => $this->generateUrl('lychee_admin_cultural_emoticon')
			])
		);
		$putPolicy->ReturnUrl = $returnUrl;
		$uploadToken = $putPolicy->Token(null);

		return $this->response('上传颜文字', array(
			'token' => $uploadToken,
			'resourceKey' => $resourceKey,
		));
	}

	/**
	 * @Route("upload_emoticon")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function uploadEmoticonAction(Request $request) {
		$resourceKey = 'emoticon.json';
		$env = $this->get('kernel')->getEnvironment();
		if ('dev' === $env) {
			$resourceKey = 'dev/' . $resourceKey;
		}
		if (true === $request->files->has('file')) {
			$jsonFile = $request->files->get('file');
			$qnKey = $this->container->getParameter('qiniu_access_key');
			$qnSecret = $this->container->getParameter('qiniu_secret_key');
			$bucket = $this->container->getParameter('qiniu_bucket');

			Qiniu_SetKeys($qnKey, $qnSecret);
			$client = new \Qiniu_MacHttpClient(null);
			$err = Qiniu_RS_Delete($client, $bucket, $resourceKey);
//            if (null !== $err) {
//                ;
//            }
			$putPolicy = new \Qiniu_RS_PutPolicy($bucket);
			$upToken = $putPolicy->Token(null);
			$putExtra = new \Qiniu_PutExtra();
			$putExtra->Crc32 = 1;
			list($ret, $err) = Qiniu_PutFile($upToken, $resourceKey, $jsonFile, $putExtra);
			if (null !== $err) {
				print_r($err);
				exit;
			}

			return $this->redirect($this->generateUrl('lychee_admin_upload_success', [
				'redirect' => $this->generateUrl('lychee_admin_cultural_emoticon'),
			]));
		}
	}

	/**
	 * @Route("/expression_package")
	 * @Template
	 * @param Request $request
	 * @return array
	 */
	public function expressionPackageAction(Request $request) {
		$page = $request->query->get('page', 1);
		$expressionPackages = $this->expression()->fetchAllExpressionPackages($page);

		return $this->response('表情包管理', [
			'expressionPackages' => $expressionPackages,
		]);
	}

	/**
	 * @Route("/expression/detail/{packageId}", requirements={"packageId" = "\d+"})
	 * @Template
	 * @param $packageId
	 * @return array
	 */
	public function showExpressionsAction($packageId) {
		$package = $this->expression()->fetchOnePackage($packageId);
		if (!$package) {
			throw $this->createNotFoundException('Package Not Found');
		}
		$expressions = $this->expression()->fetchExpressionsByPackageId($packageId);

		return $this->response('表情详情', [
			'package' => $package,
			'expressions' => $expressions
		]);
	}

	/**
	 * @Route("/expression/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createExpressionPackageAction(Request $request) {
		$packageId = $request->request->get('package_id');
		$name = $request->request->get('package_name');
		$coverImage = $request->files->get('cover_image');
		$packageFile = $request->files->get('package_file');

		$this->storage()->setPrefix('biaoqing/');
		// Upload Cover Image
		$coverUrl = null;
		if ($coverImage) {
			$coverUrl = $this->storage()->put($coverImage);
		}

		// Upload Zip File
		$packageUrl = null;
		if ($packageFile) {
			try {
				list($tmpDir, $biaoqingDir) = $this->validateExpressionPackageZip($packageFile);
			} catch (\Exception $e) {
				return $this->redirect($this->generateUrl('lychee_admin_error', [
					'errorMsg' => $e->getMessage(),
					'callbackUrl' => $request->headers->get('referer'),
				]));
			}
			$expressions = $this->uploadExpressions($biaoqingDir);
			if (!$packageId) {
				$package = $this->expression()->addExpressionPackage($name, $coverUrl === null? '':$coverUrl, '');
				$packageId = $package->getId();
			}
			$this->updateExpressionBiaoqingDir($biaoqingDir, $packageId);
			$packageUrl = $this->zipAndUploadPackage($tmpDir);
			$this->expression()->addExpressionsByPackageId($packageId, $expressions);
		}
		$this->expression()->updatePackage($packageId, $name, $packageUrl, $coverUrl);

		return $this->redirect($this->generateUrl('lychee_admin_cultural_expressionpackage'));
	}

	/**
	 * @param $packageZipFile
	 * @return array
	 * @throws \Exception
	 */
	private function validateExpressionPackageZip($packageZipFile) {
		$zip = new \ZipArchive();
		$result = $zip->open($packageZipFile);
		if (false === $result) {
			throw new \Exception('解压zip包失败');
		}
		$tmpDir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'expression', md5(uniqid())]);
		$zip->extractTo($tmpDir);
		$zip->close();

		$dh = opendir($tmpDir);
		if (false === $dh) {
			throw new \Exception('创建临时文件夹失败');
		}
		while (false !== ($file = readdir($dh))) {
			if (0 === strncmp($file, 'biaoqing', 8)) {
				$biaoqingDir = realpath(implode(DIRECTORY_SEPARATOR, [$tmpDir, $file]));
				break;
			}
		}
		if (!isset($biaoqingDir)) {
			throw new \Exception('无法找到"biaoqing"目录');
		}
		closedir($dh);

		return [$tmpDir, $biaoqingDir];
	}

	/**
	 * @param $biaoqingDir
	 * @return array
	 */
	private function uploadExpressions($biaoqingDir) {
		$dh = opendir($biaoqingDir);
		$coverImg = null;
		$files = [];
		while(false !== ($file = readdir($dh))) {
			$pathParts = pathinfo($file);
			$ext = $pathParts['extension'];
			if ($pathParts['basename'] === '.DS_Store') {
				@unlink(implode(DIRECTORY_SEPARATOR, [$biaoqingDir, $file]));
			} elseif ($ext) {
				$fileName = basename($file, '.' . $ext);
				list($expressionNum, $expressionName) = explode('.', $fileName);
				if ($expressionName) {
					$encoding = mb_detect_encoding($expressionName);
					if (strtolower($encoding) != 'utf-8') {
						$expressionName = iconv($encoding, 'utf-8', $expressionName);
					}
					$filePath = $biaoqingDir . '/' . $file;
					$imageUrl = $this->storage()->put($filePath);
					$newFilePath = implode(DIRECTORY_SEPARATOR, [$biaoqingDir, $expressionNum . '.' . $ext]);
					$files[$expressionNum] = [$imageUrl, $expressionName, basename($newFilePath)];
					rename($filePath, $newFilePath);
				}
			}
		}
		ksort($files);
		closedir($dh);

		return $files;
	}

	/**
	 * @param $biaoqingDir
	 * @param $packageId
	 */
	private function updateExpressionBiaoqingDir($biaoqingDir, $packageId) {
		$pathinfo = pathinfo($biaoqingDir);
		rename($biaoqingDir, implode(DIRECTORY_SEPARATOR, [$pathinfo['dirname'], $packageId]));
	}

	/**
	 * @param $packagePath
	 * @return bool
	 */
	private function zipAndUploadPackage($packagePath) {
		$packagePath = realpath($packagePath);
		$z = new \ZipArchive();
		$outZipPath = implode(DIRECTORY_SEPARATOR, [$packagePath, 'out.zip']);
		$z->open($outZipPath, \ZipArchive::CREATE);
		$this->folderToZip($packagePath, $z, strlen("$packagePath/"));
		$z->close();
		$packageUrl = $this->storage()->put($outZipPath);
		$this->deleteFolder($packagePath);

		return $packageUrl;
	}

	/**
	 * @param $folder
	 * @param \ZipArchive $zipFile
	 * @param $exclusiveLength
	 */
	private function folderToZip($folder, \ZipArchive &$zipFile, $exclusiveLength) {
		$handle = opendir($folder);
		while (false !== $f = readdir($handle)) {
			if ($f != '.' && $f != '..') {
				$filePath = "$folder/$f";
				// Remove prefix from file path before add to zip.
				$localPath = substr($filePath, $exclusiveLength);
				if (is_file($filePath)) {
					$zipFile->addFile($filePath, $localPath);
				} elseif (is_dir($filePath)) {
					// Add sub-directory.
					$zipFile->addEmptyDir($localPath);
					$this->folderToZip($filePath, $zipFile, $exclusiveLength);
				}
			}
		}
		closedir($handle);
	}

	/**
	 * @Route("/package/remove")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removePackageAction(Request $request) {
		$packageId = $request->request->get('package_id');
		$this->expression()->removePackage($packageId);

		return $this->redirect($this->generateUrl('lychee_admin_cultural_expressionpackage'));
	}

	/**
	 * @Route("/package/recover")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function recoverPackageAction(Request $request) {
		$packageId = $request->request->get('package_id');
		$this->expression()->recoverPackage($packageId);

	}
}