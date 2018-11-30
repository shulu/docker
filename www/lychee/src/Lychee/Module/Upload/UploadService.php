<?php
namespace Lychee\Module\Upload;

use Lychee\Module\Upload\ImageFilter\ResizeFilter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UploadService {

    /**
     * @var string
     */
    private $uploadedDir;

    /**
     * @var string
     */
    private $imageDomain;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $routeName;

    /**
     * @param string $uploadedDir
     * @param string $imageDomain
     * @param Router $router
     * @param string $routeName
     */
    public function __construct($uploadedDir, $imageDomain, $router, $routeName) {
        $this->uploadedDir = $uploadedDir;
        $this->imageDomain = $imageDomain;
        $this->router = $router;
        $this->routeName = $routeName;
    }

    /**
     * @param UploadedFile $imageFile
     * @return string url
     * @throws FileException
     */
    public function saveImage($imageFile) {
        $hash = $this->generateHash();
        $savePath = $this->getSavePathOfHash($hash);
        $imageFile->move(dirname($savePath), $hash);
        $imageUrl = $this->getUrlOfHash($hash);
        return $imageUrl;
    }

    /**
     * @param string $hash
     * @param string $filter
     * @param string $filterParam
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     */
    public function requestImage($hash, $filter = null, $filterParam = null) {
        try {
            $path = $this->getSavePathOfHash($hash);

            if ($filter) {
                if ($filter == 'thumbnail') {
                    $filter = new ResizeFilter();
                    $result = $filter->apply($path, $filterParam);
                    if ($result !== false) {
                        return new Response($result, 200, array(
                            'Content-Type' => $result->getImageMimeType(),
                            'Content-Length' => strlen($result)
                        ));
                    }
                } else if ($filter == 'resize') {
                    $filter = new ResizeFilter();
                    $result = $filter->apply($path, $filterParam);
                    if ($result !== false) {
                        return new Response($result, 200, array(
                            'Content-Type' => $result->getImageMimeType(),
                            'Content-Length' => strlen($result)
                        ));
                    }
                }
            }

            $response = new Response();
            $response->headers->set('X-Accel-Redirect', $this->getSendFilePathOfHash($hash));
            $file = new File($path);
            $response->headers->set('Content-Type', $file->getMimeType());
            $response->headers->set('Content-Length', $file->getSize());
            $response->setCache(array('public' => true));
            return $response;
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException('image not found', $e);
        }
    }

    private function generateHash() {
        $uniqueId = uniqid('1', true);
        $hash = hash('sha256', $uniqueId, true);
        $base64ed = rtrim(base64_encode($hash), '=');
        $encoded = str_replace(array('/', '+'), array('-', '_'), $base64ed);
        return $encoded;
    }

    private function getSavePathOfHash($hash) {
        $oneDir = substr($hash, 0, 2);
        $twoDir = substr($hash, 2, 2);
        return implode(DIRECTORY_SEPARATOR, array($this->uploadedDir, $oneDir, $twoDir, $hash));
    }

    private function getSendFilePathOfHash($hash) {
        $oneDir = substr($hash, 0, 2);
        $twoDir = substr($hash, 2, 2);
        return implode('/', array('/upload-internal', $oneDir, $twoDir, $hash));
    }

    private function getUrlOfHash($hash) {
        $uri =  $this->router->generate($this->routeName, array('hash' => $hash), Router::ABSOLUTE_PATH);
        return 'http://'.$this->imageDomain.$uri;
    }
} 