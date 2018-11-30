<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\UploadError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;

/**
 * @Route("/upload")
 */
class UploadController extends Controller {

    /**
     * @Route("/qiniu/token")
     * @Method("GET")
     * @ApiDoc(
     *   section="upload"
     * )
     */
    public function qiniuTokenAction() {
        $accessKey = $this->container->getParameter('qiniu_access_key');
        $secretKey = $this->container->getParameter('qiniu_secret_key');
        $bucket = $this->container->getParameter('qiniu_bucket');

        $expireAt = time() + 3600;
        $policy = array(
            'scope' => $bucket,
            'deadline' => $expireAt,
            'insertOnly' => 1,
            'callbackUrl' => '',
            'callbackBody' => '',
            'returnUrl' => '',
            'returnBody' => '',
            'endUser' => '',
            'detectMime' => '',
            'fsizeLimit' => 10 * 1024 * 1024, //10mb
            'saveKey' => 'upload/$(etag)',
            'persistentOps' => '',
            'persistentNotifyUrl' => '',
            'mimeLimit' => 'image/*'
        );
        $policy = array_filter($policy, 'strlen');
        $token = $this->qiniuSignPolicy($policy, $accessKey, $secretKey);

        return $this->dataResponse(array(
            'bucket' => $bucket,
            'token' => $token,
            'expires_at' => $expireAt
        ));
    }

    /**
     * @Route("/qiniu")
     * @Method("POST")
     * @ApiDoc(
     *   section="upload",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="image", "dataType"="file", "required"=true}
     *   }
     * )
     */
    public function qiniuUploadAction(Request $request) {
        $accessKey = $this->container->getParameter('qiniu_access_key');
        $secretKey = $this->container->getParameter('qiniu_secret_key');
        $bucket = $this->container->getParameter('qiniu_bucket');

        /** @var UploadedFile $image */
        $image = $request->files->get('image');
        \Qiniu_SetKeys($accessKey, $secretKey);
        $putPolicy = new \Qiniu_RS_PutPolicy($bucket);
        $putPolicy->MimeLimit = 'image/*';
        $putPolicy->SaveKey = 'upload/$(etag)';
        $upToken = $putPolicy->Token(null);
        $putExtra = new \Qiniu_PutExtra();
        $putExtra->Crc32 = 1;
        list($ret, $err) = Qiniu_PutFile($upToken, null, $image->getPathname(), $putExtra);

        if ($err !== null) {
            return $this->dataResponse(array('error' => $err['Err']));
        } else {
            $url = 'http://'.$bucket.'.qiniudn.com/'.$ret['key'];
            return $this->dataResponse(array('url' => $url, 'ret' => $ret));
        }
    }

    private function qiniuSignPolicy($policy, $accessKey, $secretKey) {
        $encoded = $this->qiniuEncode(json_encode($policy));
        $signed = hash_hmac('sha1', $encoded, $secretKey, true);
        return $accessKey . ':' . $this->qiniuEncode($signed) . ':' . $encoded;
    }

    private function qiniuEncode($content) {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($content));
    }

    /**
     * @Route("/image")
     * @Method("POST")
     * @ApiDoc(
     *   section="upload",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="image", "dataType"="file", "required"=true}
     *   }
     * )
     */
    public function uploadImageAction(Request $request) {
        $account = $this->requireAuth($request);

        if ($request->files->has('image') === false) {
            return $this->errorsResponse(CommonError::ParameterMissing('image'));
        }
        /** @var UploadedFile $imageFile */
        $imageFile = $request->files->get('image');
//        if (substr_compare($imageFile->getMimeType(), 'image/', 0, 6) !== 0) {
//            return $this->errorsResponse(UploadError::FileTypeInvalid('image/*'));
//        }
        $fileSize = $imageFile->getSize();
        if ($fileSize > (5 * 1024 * 1024)) {
            return $this->errorsResponse(UploadError::FileSizeTooLarge('5mb'));
        } else if ($fileSize <= 0) {
            return $this->errorsResponse(UploadError::UploadFail());
        }

        try {
            $imageUrl = $this->upload()->saveImage($imageFile);
            return $this->dataResponse(array('url' => $imageUrl));
        } catch (FileException $e) {
            if (($logger = $this->get('logger')) !== null) {
                /** @var LoggerInterface $logger */
                $logger->info('upload exception', array('exception' => $e));
            }
            return $this->errorsResponse(UploadError::UploadFail());
        }
    }

	/**
	 * @Route("/game_record")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="upload",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="game_record", "dataType"="file", "required"=true}
	 *   }
	 * )
	 */
	public function uploadGameRecordAction(Request $request) {
		$account = $this->requireEMAuth($request);

		if ($request->files->has('game_record') === false) {
			return $this->errorsResponse(CommonError::ParameterMissing('game_record'));
		}
		/** @var UploadedFile $gameRecordFile */
		$gameRecordFile = $request->files->get('game_record');
		$fileSize = $gameRecordFile->getSize();
		if ($fileSize > (2 * 1024 * 1024)) {
			return $this->errorsResponse(UploadError::FileSizeTooLarge('2mb'));
		} else if ($fileSize <= 0) {
			return $this->errorsResponse(UploadError::UploadFail());
		}

		$existingRecord = $this->extraMessageService()->getGameRecordbyUserId($account->id);

		$time = new \DateTime();
		try {

			if($existingRecord == null){

				$saveDir = $this->container->getParameter('game_record_save_path');
				$uniqueId = uniqid('1', true);
				$savePath = implode(DIRECTORY_SEPARATOR, array($saveDir, $time->format('Y'), $time->format('m'), $time->format('d')));
				$gameRecordFile->move($savePath, $uniqueId);
				$path = $savePath."/".$uniqueId;

				$this->extraMessageService()->addGameRecord($account->id, $path);

			} else {

				$savePath = $existingRecord->path;
				$saveDir = dirname($savePath);
				$filename = basename($savePath);
				$gameRecordFile->move($saveDir, $filename);
			}

			return $this->sucessResponse();

		} catch (FileException $e) {
			if (($logger = $this->get('logger')) !== null) {
				/** @var LoggerInterface $logger */
				$logger->info('upload game record exception', array('exception' => $e));
			}
			return $this->errorsResponse(UploadError::UploadFail());
		}
	}

    /**
     * @Route("/client_log")
     * @Method("POST")
     * @ApiDoc(
     *   section="upload",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="log", "dataType"="file", "required"=true}
     *   }
     * )
     */
    public function uploadClientLogAction(Request $request) {
        $account = $this->requireAuth($request);

        if ($request->files->has('log') === false) {
            return $this->errorsResponse(CommonError::ParameterMissing('log'));
        }
        /** @var UploadedFile $logFile */
        $logFile = $request->files->get('log');
        $fileSize = $logFile->getSize();
        if ($fileSize > (2 * 1024 * 1024)) {
            return $this->errorsResponse(UploadError::FileSizeTooLarge('2mb'));
        } else if ($fileSize <= 0) {
            return $this->errorsResponse(UploadError::UploadFail());
        }

        /** @var \Predis\Client|\Redis $redis */
        $redis = $this->get('snc_redis.cache');
        $time = new \DateTime();
        $key = 'client_logs_status_' . $time->format('Y-m-d');
        $count = $redis->incr($key);
        if ($count < 10) {
            $midNight = clone $time;
            $midNight->add(new \DateInterval('P1D'))->setTime(0, 0, 0);
            $redis->expireAt($key, $midNight->getTimestamp());
        }
        if ($count > 200) {
            return $this->errorsResponse(UploadError::UploadFail());
        }

        try {
            $saveDir = $this->container->getParameter('client_logs_save_path');
            $uniqueId = uniqid('1', true);
            $savePath = implode(DIRECTORY_SEPARATOR, array($saveDir, $time->format('Y-m-d')));
            $logFile->move($savePath, $uniqueId);
            return $this->sucessResponse();
        } catch (FileException $e) {
            if (($logger = $this->get('logger')) !== null) {
                /** @var LoggerInterface $logger */
                $logger->info('upload exception', array('exception' => $e));
            }
            return $this->errorsResponse(UploadError::UploadFail());
        }
    }

    /**
     * @Route("/client_log_status")
     * @Method("GET")
     * @ApiDoc(
     *   section="upload",
     *   parameters={
     *
     *   }
     * )
     */
    public function uploadClientLogStatusAction() {
        if (rand(0, 2) == 0) {
            return $this->failureResponse();
        }

        /** @var \Predis\Client|\Redis $redis */
        $redis = $this->get('snc_redis.cache');
        $time = new \DateTime();
        $key = 'client_logs_status_' . $time->format('Y-m-d');
        $count = $redis->get($key);
        if ($count > 200) {
            return $this->failureResponse();
        } else {
            return $this->sucessResponse();
        }
    }
}