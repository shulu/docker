<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 4:16 PM
 */

namespace Lychee\Module\ContentAudit;


use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\Storage\StorageException;
use Lychee\Component\Storage\StorageInterface;
use Lychee\Module\ContentAudit\Entity\ImageReview;
use Lychee\Module\ContentAudit\Entity\ImageReviewAuditConfig;
use Lychee\Module\ContentAudit\Entity\ImageReviewSource;
use Lychee\Module\Post\PostService;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Kernel;

class ImageReviewService {

    /**
     * @var Logger
     */
    private $logger;

    private $doctrine;

    /**
     * @var Kernel $kernel
     */
    private $kernel;

    private $tupuSecretId;

    private $environment;

    /**
     * @var PostService $postService
     */
    private $postService;

    /**
     * @var StorageInterface $storageService
     */
    private $storageService;

    public function __construct($doctrine, $logger, $kernel, $tupuSecretId, $environment, $storageService, $postService) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->kernel = $kernel;
        $this->tupuSecretId = $tupuSecretId;
        $this->environment = $environment;
        $this->storageService = $storageService;
        $this->postService = $postService;
    }

    /**
     * 筛选图片列表,返回已审核过的图片和未审核过的图片
     * @param array $images
     * @return array
     */
    private function filterImages($images = []) {
        if (empty($images)) {
            return [[], []];
        }
        /**
         * @var $em \Doctrine\ORM\EntityManager
         */
        $em = $this->doctrine->getManager();
        $imageReviewRepo = $em->getRepository(ImageReview::class);
        $imageReviews = $imageReviewRepo->findBy(['image' => $images]);
        $reviewedImages = array_map(function($img) { return $img->image; }, $imageReviews);
        $unReviewImages = array_diff($images, $reviewedImages);

        return [$reviewedImages, $unReviewImages];
    }

    public function review($images, $sourceType, $sourceId) {
        list($reviewedImages, $unReviewImages) = $this->filterImages($images);
        $compressImages = array_map(function ($img) {
            return ImageUtility::resize($img, 256, 256);
        }, $unReviewImages);
        $compressImages = array_filter($compressImages, function ($img) {
            if ($img) {
                return true;
            }
        });
        $this->tupuAudit($compressImages, $sourceType, $sourceId);
        $this->reviewOldImage($reviewedImages, $sourceType, $sourceId);
    }

    /**
     * 处理已复审过的图片
     * @param $images
     * @param $sourceType
     * @param $sourceId
     * @return null
     */
    private function reviewOldImage($images, $sourceType, $sourceId) {
        if (empty($images)) {
            return null;
        }
        $lastReviewTime = new \DateTime();
        /**
         * @var $em \Doctrine\ORM\EntityManager
         */
        $em = $this->doctrine->getManager();
        $imageReviewRpo = $em->getRepository(ImageReview::class);
        $imageReviews = $imageReviewRpo->findBy([
            'image' => $images
        ]);
        if ($imageReviews) {
            /**
             * @var $ir ImageReview
             */
            foreach ($imageReviews as $ir) {
                $ir->lastReviewTime = $lastReviewTime;
                if (
                    $ir->label == ImageReview::LABEL_PORN && $ir->review == false ||
                    $ir->reviewResult == ImageReview::RESULT_REJECT && $this->environment === 'prod'
                ) {
                    try {
                        $this->storageService->delete($ir->image);
                    } catch (StorageException $e) {
                    }
                }
            }
            $em->flush();
        }
        foreach ($images as $img) {
            $imgReview = $em->getRepository(ImageReview::class)
                ->findOneBy([
                    'image' => $img
                ]);
            if ($imgReview) {
                $this->saveImageReviewSource($imgReview->id, $sourceType, $sourceId, $img, $imgReview->label);
                $this->logger->info(sprintf("[Reviewed past]%s\t%s\t%d\t%d", $img, $imgReview->label, $sourceType, $sourceId));
            }
        }

    }

    private function tupuAudit($images, $sourceType, $sourceId) {
        $fileList = $this->sendToTupu($images);
        foreach ($fileList as $file) {
            $fileName = $file['name'];
            $label = $file['label'];
            $review = $file['review'];
            $rate = $file['rate'];
            $originImg = strstr($fileName, '?', true);
            $this->saveReview($originImg, $label, $review, $rate, $sourceType, $sourceId, ImageReview::SOURCE_AUTO);
        }
    }

    private function sendToTupu($images) {
        if (empty($images)) {
            return [];
        }
        $secretId = $this->tupuSecretId;
        $timestamp = time();
        $nonce = mt_rand(100, 999999);
        $taskUrl = 'http://api.open.tuputech.com/v3/recognition/' . $secretId;
        $signString = $secretId . ',' . $timestamp . ',' . $nonce;
        $rootDir = $this->kernel->getRootDir();
        $privateKeyPem = file_get_contents($rootDir . '/../admin/cert/tupu_rsa_pkey.pem');
        $pkeyId = openssl_get_privatekey($privateKeyPem);
        openssl_sign($signString, $signature, $pkeyId, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);
        $data = [
            'image' => $images,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $taskUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $this->curl_setopt_custom_postfields($ch, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($output, true);
        if ($data) {
            $this->logger->info($output);
//            $signature = $data['signature'];
//            $publicKeyPem = file_get_contents($rootDir . '/../admin/cert/tupu_rsa_pubkey.pem');
//            $pkeyId2 = openssl_get_publickey($publicKeyPem);
//            $result = openssl_verify($json, base64_decode($signature), $pkeyId2, OPENSSL_ALGO_SHA256);
//            if (1 === $result) {
//                echo '验证成功' . $json;
//            } else {
//                echo '验证失败' . $json;
//            }
            $json = $data['json'];
            $auditResult = json_decode($json, true);
            $taskId = '54bcfc6c329af61034f7c2fc';
            if (isset($auditResult[$taskId])) {
                $pornAuditResult = $auditResult[$taskId];
                $fileList = $pornAuditResult['fileList'];

                return $fileList;
            }
        }

        return [];
    }

    private function curl_setopt_custom_postfields($ch, $postfields, $headers = null) {
        $algos = hash_algos();
        $hashAlgo = null;
        foreach ( array('sha1', 'md5') as $preferred ) {
            if ( in_array($preferred, $algos) ) {
                $hashAlgo = $preferred;
                break;
            }
        }
        if ( $hashAlgo === null ) { list($hashAlgo) = $algos; }
        $boundary =
            '----------------------------' .
            substr(hash($hashAlgo, 'cURL-php-multiple-value-same-key-support' . microtime()), 0, 12);

        $body = array();
        $crlf = "\r\n";
        $fields = array();
        foreach ( $postfields as $key => $value ) {
            if ( is_array($value) ) {
                foreach ( $value as $v ) {
                    $fields[] = array($key, $v);
                }
            } else {
                $fields[] = array($key, $value);
            }
        }
        foreach ( $fields as $field ) {
            list($key, $value) = $field;
            if ( strpos($value, '@') === 0 ) {
                preg_match('/^@(.*?)$/', $value, $matches);
                list($dummy, $filename) = $matches;
                $body[] = '--' . $boundary;
                $body[] = 'Content-Disposition: form-data; name="' . $key . '"; filename="' . basename($filename) . '"';
                $body[] = 'Content-Type: application/octet-stream';
                $body[] = '';
                $body[] = file_get_contents($filename);
            } else {
                $body[] = '--' . $boundary;
                $body[] = 'Content-Disposition: form-data; name="' . $key . '"';
                $body[] = '';
                $body[] = $value;
            }
        }
        $body[] = '--' . $boundary . '--';
        $body[] = '';
        $contentType = 'multipart/form-data; boundary=' . $boundary;
        $content = join($crlf, $body);
        $contentLength = strlen($content);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Length: ' . $contentLength,
            'Expect: 100-continue',
            'Content-Type: ' . $contentType,
        ));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    }

    /**
     * 保存审核过的图片
     * @param $image
     * @param $label
     * @param $review
     * @param $rate
     * @param $sourceType
     * @param $sourceId
     */
    private function saveReview($image, $label, $review, $rate, $sourceType, $sourceId, $reviewSource=1) {
        $lastReviewTime = new \DateTime();
        /** @var \PDO $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO image_review(image,label,review,rate,last_review_time,review_result,review_source)
            VALUE(:image, :label, :review, :rate, :last_review_time, :review_result, :review_source)"
        );
        $stmt->bindValue(':image', $image);
        $stmt->bindValue(':label', $label);
        $stmt->bindValue(':review', $review);
        $stmt->bindValue(':rate', $rate);
        $stmt->bindValue(':last_review_time', $lastReviewTime->format('Y-m-d H:i:s'));
        $stmt->bindValue(':review_result', ImageReview::RESULT_PASS);
        $stmt->bindValue(':review_source', $reviewSource);
        if ($stmt->execute()) {
            $reviewId = $conn->lastInsertId();
            $this->saveImageReviewSource($reviewId, $sourceType, $sourceId, $image, $label);
            $this->logger->info(
                sprintf(
                    "[Review by tupu] URL: %s\tLabel: %s\tType: %d\tID: %d",
                    $image,
                    $label,
                    $sourceType,
                    $sourceId
                )
            );
        }
    }

    /**
     * @param $imageReviewId
     * @param $sourceType
     * @param $sourceId
     * @param $imgPath
     * @param $reviewLabel
     */
    private function saveImageReviewSource($imageReviewId, $sourceType, $sourceId, $imgPath, $reviewLabel) {
        /** @var \PDO $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare(
            'INSERT IGNORE INTO image_review_source(review_id,source_type,source_id)
                    VALUE(:reviewId,:sourceType,:sourceId)'
        );
        $stmt->bindParam(':reviewId', $imageReviewId);
        $stmt->bindParam(':sourceType', $sourceType);
        $stmt->bindParam(':sourceId', $sourceId);
        $stmt->execute();
    }

    /**
     * 返回审核结果
     * @param $sourceType
     * @param $sourceId
     * @return array
     */
    public function getReviewResult($sourceType, $sourceId) {
        /**
         * @var $em \Doctrine\ORM\EntityManager
         */
        $em = $this->doctrine->getManager();
        $imgReviewSources = $em->getRepository(ImageReviewSource::class)->findBy([
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
        ]);
        $reviewIds = array_map(function($item) { /* @var $item ImageReviewSource */ return $item->reviewId; }, $imgReviewSources);
        if (!empty($reviewIds)) {
            $reviews = $em->getRepository(ImageReview::class)->findBy([
                'id' => $reviewIds
            ]);
            
            return $reviews;
        }
        
        return [];
    }

    /**
     * 设置审核结果
     * @param ImageReview $imageReview
     * @param $result
     */
    public function setReviewResult(ImageReview $imageReview, $result) {
        $imageReview->reviewResult = $result;
        $this->doctrine->getManager()->flush($imageReview);
    }

    /**
     * @param \DateTime $reviewTime
     * @param $cursor
     * @param int $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchImages(\DateTime $reviewTime, $cursor, &$nextCursor = null, $count = 30, $isreject=0) {
        $startTime = $reviewTime;
        $endTime = clone($startTime);
        $endTime->modify('+1 day');

        if ($isreject) {
            $reviewResult = ImageReview::RESULT_REJECT;
        } else {
            $reviewResult = ImageReview::RESULT_PASS;
        }

        /**
         * @var $em EntityManager
         */
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository(ImageReview::class);
        $query = $repo->createQueryBuilder('i')
            ->where('i.id<:cursor')
            ->andWhere('i.lastReviewTime>=:startTime')
            ->andWhere('i.lastReviewTime<:endTime')
            ->andWhere('i.reviewResult=:reviewResult')
            ->andWhere('i.label<>:legal')
            ->orderBy('i.id', 'DESC')
            ->setParameters([
                'cursor' => $cursor,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'reviewResult' => $reviewResult,
                'legal' => ImageReview::LABEL_LEGAL
            ])
            ->setMaxResults($count)
            ->getQuery();
        $result = $query->getResult();
        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[$count - 1]->id;
        }
        
        return $result;
    }

    /**
     * @param $id
     * @return ImageReview
     */
    public function deleteImage($id, $reviewSource=1) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        /** @var ImageReview $imageReview */
        $imageReview = $em->getRepository(ImageReview::class)->find($id);
        if ($imageReview) {
            $imageReview->reviewResult = ImageReview::RESULT_REJECT;
            $imageReview->reviewSource = $reviewSource;
            // Delete photo and post
            try {
                $this->storageService->freeze($imageReview->image);
            } catch (StorageException $e) {
            }
            $em->flush();
        }
        
        return $imageReview;
    }


    /**
     * @param $id
     * @return ImageReview
     */
    public function recoverImage($id) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        /** @var ImageReview $imageReview */
        $imageReview = $em->getRepository(ImageReview::class)->find($id);
        if ($imageReview) {
            $imageReview->reviewResult = ImageReview::RESULT_PASS;
            // Delete photo and post
            try {
                $this->storageService->unfreeze($imageReview->image);
            } catch (StorageException $e) {
            }
            $em->flush();
        }

        return $imageReview;
    }


    /**
     * @param $reviewId
     * @return array
     */
    public function deleteReviewSource($reviewId) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $sourceRepo = $em->getRepository(ImageReviewSource::class);
        $result = $sourceRepo->findBy([
            'reviewId' => $reviewId
        ]);
        if ($result) {
            foreach ($result as $source) {
                /** @var ImageReviewSource $source */
                if ($source->sourceType == ImageReviewSource::TYPE_POST) {
                    $this->postService->delete($source->sourceId);
                }
            }
            return $result;
        } else {
            return [];
        }
    }


    /**
     * @param $reviewId
     * @return array
     */
    public function recoverReviewSource($reviewId) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $sourceRepo = $em->getRepository(ImageReviewSource::class);
        $result = $sourceRepo->findBy([
            'reviewId' => $reviewId
        ]);
        if ($result) {
            foreach ($result as $source) {
                /** @var ImageReviewSource $source */
                if ($source->sourceType == ImageReviewSource::TYPE_POST) {
                    $this->postService->undelete($source->sourceId);
                }
            }
            return $result;
        } else {
            return [];
        }
    }

    public function removeSource($sourceType, $sourceId) {
    	/** @var EntityManager $em */
    	$em = $this->doctrine->getManager();
	    $query = $em->createQueryBuilder()->delete(ImageReviewSource::class, 's')
		    ->where('s.sourceType = :type AND s.sourceId = :id')
		    ->setParameter('type', $sourceType)
		    ->setParameter('id', $sourceId)
		    ->getQuery();
	    $query->execute();
    }

    /**
     * @param $reviewId
     * @return array
     */
    public function fetchSourcesWithReviewId($reviewId) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $sources = $em->getRepository(ImageReviewSource::class)->findBy([
            'reviewId' => $reviewId
        ]);
        
        return $sources;
    }

    /**
     * @param $reviewId
     * @return null|object
     */
    public function fetchOneImageReview($reviewId) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $imageReview = $em->getRepository(ImageReview::class)->find($reviewId);
        
        return $imageReview;
    }

    public function reviewGIF($images, $sourceType, $sourceId) {
        list($reviewedImages, $unReviewImages) = $this->filterImages($images);
        $cacheDir = $this->kernel->getCacheDir();
        $gifDir = implode(DIRECTORY_SEPARATOR, [$cacheDir, 'gif_cache']);
        !is_dir($gifDir) && @mkdir($gifDir, 0777, true);
        $unReviewFrames = array_reduce($unReviewImages, function($result, $gif) use ($gifDir, $sourceId) {
            !is_array($result) && $result = [];
	        try {
		        $images = new \Imagick($gif);
	        } catch (\ImagickException $e) {
	        	return $result;
	        }
            $imgCount = count($images);
            $frameIndexes = [0, floor($imgCount / 2), $imgCount - 1];
            foreach ($frameIndexes as $index) {
                $framePath = implode(DIRECTORY_SEPARATOR, [$gifDir, $sourceId . '_' . $index . '.jpg']);
                if (!file_exists($framePath)) {
                    foreach ($images as $img) {
                        if ($img->getImageIndex() == $index) {
                            $img->writeImage($framePath);
                            $result[$gif][] = '@' . $framePath;
                            break;
                        }
                    }
                }
            }

            return $result;
        });

        $this->gifAudit($unReviewFrames, $sourceType, $sourceId);
        $this->reviewOldImage($reviewedImages, $sourceType, $sourceId);
    }

    public function gifAudit($images, $sourceType, $sourceId) {
    	if (!is_array($images)) {
    		return null;
	    }
        $frames = array_reduce($images, function($result, $gif) {
            !is_array($result) && $result = [];
            foreach ($gif as $frame) {
                $result[] = $frame;
            }
            return $result;
        });
        $result = [];
        $fileList = $this->sendToTupu($frames);
        foreach ($fileList as $file) {
            $originImg = null;
            $fileName = $file['name'];
            foreach ($images as $image => $frames) {
                if ($originImg) {
                    break;
                }
                foreach ($frames as $frame) {

                    if (substr(strrchr($frame, '/'), 1) === $fileName) {
                        $originImg = $image;
                        break;
                    }
                }
            }
            $label = $file['label'];
            $review = $file['review'];
            $rate = $file['rate'];
            $newResult = compact('label', 'review', 'rate');
            if (isset($result[$originImg])) {
                if ($label == ImageReview::LABEL_PORN) {
                    if (
                        $review == false ||
                        $review == true && $result[$originImg]['label'] != ImageReview::LABEL_PORN
                    ) {
                        $result[$originImg] = $newResult;
                    }
                } elseif ($label == ImageReview::LABEL_SEXY) {
                    if (
                        $review == false && $result[$originImg]['label'] != ImageReview::LABEL_PORN ||
                        $review == true && $result[$originImg]['label'] != ImageReview::LABEL_PORN && $result[$originImg]['review'] != false
                    ) {
                        $result[$originImg] = $newResult;
                    }
                }
            } else {
                $result[$originImg] = $newResult;
            }
        }
        foreach ($result as $gif => $value) {
            $this->saveReview($gif, $value['label'], $value['review'], $value['rate'], $sourceType, $sourceId);
        }
        foreach ($frames as $f) {
            @unlink(substr($f, 1));
        }
    }

    public function updateAuditConfigs($configs) {
        if (empty($configs)) {
            return false;
        }
        $sql = [];
        $sqlParams = [];
        foreach ($configs as $id => $val) {
            $sql[] = "UPDATE image_review_audit_config SET value=?, update_time=? WHERE id=?";
            $sqlParams[] = $val;
            $sqlParams[] = date('Y-m-d H:i:s');
            $sqlParams[] = $id;
        }
        $sql = implode(';', $sql);
        $conn = $this->doctrine->getManager()->getConnection();
        $updated = $conn->executeUpdate($sql, $sqlParams);
        return $updated;
    }

    public function getAuditConfigs($configIds) {
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository(ImageReviewAuditConfig::class);
        $r = $repo->findBy(['id' => $configIds]);
        foreach ($r as $item) {
            $ret[$item->id] = $item->value;
        }
        $defaultConfigs = ImageReviewAuditConfig::getDefaultConfigs();
        foreach ($configIds as $configId) {
            if (isset($ret[$configId])
            || !isset($defaultConfigs[$configId])) {
                continue;
            }
            $ret[$configId] = $defaultConfigs[$configId][1];
        }
        return $ret;
    }

    /**
     * 初始化审核配置
     */
    public function initAuditConfig() {
        $configs = ImageReviewAuditConfig::getDefaultConfigs();
        $conn = $this->doctrine->getManager()->getConnection();
        $sqlParams = [];
        $inserts = [];
        foreach ($configs as $id => $config) {
            $inserts[] = '(?, ?, ?, ?, ?)';
            list($title, $value, $description) = $config;
            $sqlParams[] = $id;
            $sqlParams[] = $title;
            $sqlParams[] = $value;
            $sqlParams[] = $description;
            $sqlParams[] = date('Y-m-d H:i:s');
        }
        $sql = "INSERT IGNORE INTO image_review_audit_config (id, title, value, description, update_time) VALUES ".implode(',', $inserts);
        $updated = $conn->executeUpdate($sql, $sqlParams);
    }


    public function isRejectAudit(ImageReview $imageReview) {

        $configIds = [];
        $configIds[] = ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID;
        $configIds[] = ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID;
        $configIds[] = ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID;
        $configIds[] = ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID;
        $configs = $this->getAuditConfigs($configIds);

        $rate = $imageReview->rate*100;

//        确定色情评分
        if (ImageReview::LABEL_PORN == $imageReview->label
            && empty($imageReview->review)
            && $rate >= $configs[ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID]) {
            return true;
        }

//        疑似色情评分
        if (ImageReview::LABEL_PORN == $imageReview->label
            && $imageReview->review
            && $rate >= $configs[ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID]) {
            return true;
        }


//        确定性感评分
        if (ImageReview::LABEL_SEXY == $imageReview->label
            && empty($imageReview->review)
            && $rate >= $configs[ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID]) {
            return true;
        }

//        疑似性感评分
        if (ImageReview::LABEL_SEXY == $imageReview->label
            && $imageReview->review
            && $rate >= $configs[ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID]) {
            return true;
        }

        return false;
    }

}