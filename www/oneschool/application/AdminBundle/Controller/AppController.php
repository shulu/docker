<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Httpful\Test\requestTest;
use Lychee\Component\Storage\StorageException;
use Lychee\Module\ContentManagement\Domain\DomainType;
use Lychee\Module\ContentManagement\Entity\AppLaunchImage;
use Lychee\Module\ContentManagement\Entity\DomainWhiteListItem;
use Lychee\Module\ContentManagement\Entity\Sticker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AppController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/app")
 */
class AppController extends BaseController
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return '系统设置';
    }

    /**
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('lychee_admin_app_sharelinkwhitelist'));
    }

    /**
     * @Route("/share_link_white_list")
     * @Template
     * @param Request $request
     * @return array
     */
    public function shareLinkWhiteListAction(Request $request) {
        $page = $request->query->get('p', 1);
        $count = 30; // 每页50条记录
        $domainWhiteListService = $this->domainWhiteList();
        $pages = ceil($domainWhiteListService->getCount() / $count);
        $data = $domainWhiteListService->getItemsByPage($page, $count);

        $reflector = new \ReflectionClass(DomainType::class);
        $types = $reflector->getConstants();

        return $this->response('分享网站白名单', array(
            'page' => $page,
            'pages' => $pages,
            'data' => $data,
            'types' => $types,
        ));
    }

    /**
     * @Route("/add_share_link")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addShareLinkAction(Request $request) {
        $name = $request->request->get('name');
        $domain = $request->request->get('domain');
        $type = $request->request->get('type');

        $this->domainWhiteList()->add($name, $domain, $type);

        return $this->redirect($this->generateUrl('lychee_admin_app_sharelinkwhitelist'));
    }

    /**
     * @Route("/remove_share_link")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeShareLinkAction(Request $request) {
        $id = $request->request->get('id');

        $item = $this->getDoctrine()->getRepository(DomainWhiteListItem::class)->find($id);
        if (null !== $item) {
            $this->domainWhiteList()->remove($item);
        }

        return $this->redirect($this->generateUrl('lychee_admin_app_sharelinkwhitelist'));
    }

    /**
     * @Route("/sticker")
     * @Template
     *
     * @return array
     */
    public function stickerAction() {
        $stickers = $this->sticker()->getStickers();

        return $this->response('贴纸管理', [
            'stickers' => $stickers,
        ]);
    }

    /**
     * @Route("upload_sticker")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function uploadStickerAction(Request $request) {
        if (!$request->files->has('zip_file')) {
            throw new \Exception('No upload file');
        }
        $uploadFile = $request->files->get('zip_file');
        $zip = new \ZipArchive();
        $result = $zip->open($uploadFile);
        if (false === $result) {
            echo 'open file failed';
            exit;
        }
        $tmpDir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'biaoqing', md5(uniqid())]);
        $zip->extractTo($tmpDir);
        $zip->close();

        $dh = opendir($tmpDir);
        if (false === $dh) {
            echo 'could not open dir';
            exit;
        }
        while (false !== ($file = readdir($dh))) {
            if (0 === strncmp($file, 'biaoqing', 8)) {
                $biaoqingDir = implode(DIRECTORY_SEPARATOR, [$tmpDir, $file]);
                break;
            }
        }
        if (!isset($biaoqingDir)) {
            throw new \Exception('Directory "biaoqing" Not Found');
        }
        closedir($dh);
        $dh = opendir($biaoqingDir);
        $coverImg = null;
        while(false !== ($file = readdir($dh))) {
            if (0 === strncmp($file, '1.', 2)) {
                $coverImg = implode(DIRECTORY_SEPARATOR, [$biaoqingDir, $file]);
                break;
            }
        }
        closedir($dh);
        if (null === $coverImg) {
            echo 'Could not find cover.';
            exit;

        }

        // Upload to qiniu
        /**
         * var \Lychee\Component\Storage\StorageInterface $storage
         */
        $storage = $this->get('lychee.component.storage');
        $em = $this->getDoctrine()->getManager();
        /**
         * @var \Symfony\Component\Validator\Validator $validator
         */
        $validator = $this->get('validator');
        $keyPrefix = 'sticker/';
        $stickerName = $request->request->get('sticker_name');
        $isNew = $request->request->get('is_new', 0);
        $stickerId = $request->request->get('sticker_id');
        if ($stickerId) {
            /**
             * @var $sticker \Lychee\Module\ContentManagement\Entity\Sticker|null
             */
            $sticker = $this->sticker()->fetchById($stickerId);
            if (null !== $sticker) {
                // Update
                $errors = $validator->validate($sticker);
                if (count($errors) > 0) {
                    return $this->redirect($this->generateUrl('lychee_admin_app_stickerdetail', [
                        'errors' => $errors->__toString(),
                    ]));
                }
                try {
                    $storage->delete($sticker->thumbnailUrl);
                } catch (StorageException $e) {

                }
                try {
                    $storage->delete($sticker->url);
                } catch (StorageException $e) {

                }

                $sticker->thumbnailUrl = $storage->put($coverImg, $keyPrefix . $stickerId);
                $sticker->url = $storage->put($uploadFile, $keyPrefix . 'biaoqing_' . $stickerId . '.zip');
                $sticker->lastModifiedTime = new \DateTime();
                $this->sticker()->flush($sticker);
            } else {
                throw $this->createNotFoundException('Sticker not found.');
            }
        } else {
            $sticker = new Sticker();
            $sticker->name = $stickerName;
            $sticker->isNew = $isNew;

            $errors = $validator->validate($sticker);
            if (count($errors) > 0) {
                return $this->redirect($this->generateUrl('lychee_admin_app_sticker', [
                    'errors' => $errors->__toString(),
                ]));
            }
            $sticker->lastModifiedTime = new \DateTime();
            $this->sticker()->addSticker($sticker);

            $sticker->thumbnailUrl = $storage->put($coverImg, $keyPrefix . $sticker->id);
            $sticker->url = $storage->put($uploadFile, $keyPrefix . 'biaoqing_' . $sticker->id . '.zip');
            $em->flush();
        }

        return $this->redirect($this->generateUrl('lychee_admin_app_sticker'));
    }

    /**
     * @Route("/sticker/{stickerId}", requirements={"stickerId" = "\d+"})
     * @Template
     * @param $stickerId
     * @return array
     */
    public function stickerDetailAction($stickerId) {
        $sticker = $this->sticker()->fetchById($stickerId);

        if (null === $sticker) {
            throw $this->createNotFoundException('Sticker Not Found.');
        }

        return $this->response('编辑贴纸', [
            'sticker' => $sticker,
        ]);
    }

    /**
     * @Route("/sticker/delete")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteStickerAction(Request $request) {
        $id = $request->request->get('sticker_id');
        if (!$this->sticker()->delete($id)) {
            throw $this->createNotFoundException('Sticker Not Found.');
        }

        return $this->redirect($this->generateUrl('lychee_admin_app_sticker'));
    }

    /**
     * @param $path
     * @return bool
     */
    private function unlinkPath($path) {
        if (false === is_dir($path)) {
            if (file_exists($path)) {
                unlink($path);

                return true;
            }
            return false;
        }
        $dh = opendir($path);
        while(false !== ($file = readdir($dh))) {
            if ('.' !== $file && '..' !== $file) {
                if (is_file($file)) {
                    unlink($file);
                } else {
                    return $this->unlinkPath($file);
                }
            }
        }
        closedir($path);
        rmdir($path);

        return true;
    }

    /**
     * @Route("/android_download_link")
     * @Template
     * @return array
     */
    public function androidDownloadLinkAction() {
        return $this->response('Android下载链接', [
            'androidDownloadLink' => $this->contentManagement()->androidDownloadLink(),
        ]);
    }

    /**
     * @Route("/android/link/save")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function saveAndroidDownloadLinkAction(Request $request) {
        $link = $request->request->get('link');
        if (preg_match('@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@', $link)) {
            $this->contentManagement()->updateAndroidDownloadLink($link);
        }

        return $this->redirect($this->generateUrl('lychee_admin_app_androiddownloadlink'));
    }

    /**
     * @param $folder
     */
    private function deleteFolder($folder) {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                if (is_file($filePath)) {
                    @unlink($filePath);
                } elseif (is_dir($filePath)) {
                    $this->deleteFolder($filePath);
                }
            }
        }
        closedir($handle);
        rmdir($folder);
    }


    /**
     * @Route("/android_auto_update")
     * @Template
     */
    public function androidAutoUpdateAction()
    {
        $apps = $this->androidAutoUpdate()->getAllUpdate();
        return $this->response('Android自动更新', array(
            'apps' => $apps
        ));
    }

    /**
     * @param Request $request
     * @Route("/android_auto_update/edit")
     */
    public function editUpdate(Request $request) {
        $version = $request->request->get('version');
        $versionCode = $request->request->get('version_code');
        $log = $request->request->get('log');
        $id = $request->request->get('update_id');
        $url = $request->request->get('link');
        $size = number_format(get_headers($url, 1)['Content-Length']/(1024*1024), 2, '.', '').'M';
        $uploadeDate = $request->request->get('upload_date');
        $uploadeDate = new \DateTime($uploadeDate);
        $this->androidAutoUpdate()->editUpdate($id, $version, $log, $url, $size, $uploadeDate,$versionCode);

        return $this->redirect($this->generateUrl('lychee_admin_app_androidautoupdate'));
    }
    /**
     * @param Request $request
     * @Route("/android_auto_update/edit_state")
     */
    public function editState(Request $request) {
        $id = $request->request->get('update_id');
        $state = $request->request->get('state');
        $this->androidAutoUpdate()->editState($id, $state);

        return $this->redirect($this->generateUrl('lychee_admin_app_androidautoupdate'));
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
				'redirect' => $this->generateUrl('lychee_admin_app_emoticon')
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
				'redirect' => $this->generateUrl('lychee_admin_app_emoticon'),
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

		return $this->redirect($this->generateUrl('lychee_admin_app_expressionpackage'));
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

		return $this->redirect($this->generateUrl('lychee_admin_app_expressionpackage'));
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
