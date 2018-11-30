<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/12/15
 * Time: 2:14 PM
 */

namespace Lychee\Component\Storage;


/**
 * Class QiniuStorage
 * @package Lychee\Component\Storage
 */
class QiniuStorage implements StorageInterface {

    /**
     * @var
     */
    private $bucket;

    /**
     * @var
     */
    private $token;

    /**
     * @var
     */
    private $host;

    /**
     * @var null|string
     */
    private $prefix = '';

    /**
     * @var int
     */
    private $putFileSizeLimit = 0;

    /**
     * @var
     */
    private $freezeBucket;

    /**
     * @param $accessKey
     * @param $secretKey
     * @param $host
     * @param $bucket
     * @param null $prefix
     */
    public function __construct($accessKey, $secretKey, $host, $bucket, $prefix = null, $freezeBucket = null) {
        Qiniu_SetKeys($accessKey, $secretKey);
        $this->bucket = $bucket;
        $this->host = $host;
        $this->freezeBucket = $freezeBucket;
        if (null !== $prefix) {
            $this->prefix = $prefix;
        }
        $this->setPutFileSizeLimit(2 * 1024 * 1024);
    }

    /**
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     */
    public function setPutFileSizeLimit($putFileSizeLimit) {
        $this->putFileSizeLimit = $putFileSizeLimit;
        return $this;
    }

    /**
     *
     */
    public function genToken() {
        $putPolicy = new \Qiniu_RS_PutPolicy($this->bucket);
        $putPolicy->SaveKey = $this->prefix . '$(etag)';
        $putPolicy->FsizeLimit = $this->putFileSizeLimit;
        $this->token = $putPolicy->Token(null);
        return $this->token;
    }

    /**
     * @param string $file
     * @param null $key
     * @return string
     * @throws StorageException
     */
    public function put($file, $key = null) {
        if (!$this->token) {
            $this->genToken();
        }
        $putExtra = new \Qiniu_PutExtra();
        $putExtra->Crc32 = 1;
        list($ret, $err) = Qiniu_PutFile($this->token, $key, $file, $putExtra);
        if (null !== $err) {
            throw new StorageException($err->Err, $err->Code);
        }

        return sprintf("%s/%s", $this->host, $ret['key']);
    }

    /**
     * 上传文件，会自动续期token
     * @param $file
     * @param null $key
     * @return bool|string
     */
    public function putWithAutoRenewToken($file, $key = null) {
        $retry = 1;
        while ($retry) {
            try {
                return $this->put($file, $key);
            } catch (\Lychee\Component\Storage\StorageException $e) {
                if (401==$e->getCode()) {
                    // 重新生成token
                    $this->genToken();
                    $retry=0;
                } else {
                    throw new $e;
                }
            }
        }
        return false;
    }

    /**
     * @param string $keyOrUrl
     * @return bool
     * @throws StorageException
     */
    public function delete($keyOrUrl) {
        if (preg_match('/^[^:]+:\/\/[^\/]+\/(.*)/i', $keyOrUrl, $match)) {
            $key = $match[1];
        } else {
            $key = $keyOrUrl;
        }
        $client = new \Qiniu_MacHttpClient(null);
        $err = Qiniu_RS_Delete($client, $this->bucket, $key);
        if (null !== $err) {
            throw new StorageException($err->Err);
        }

        return true;
    }

    /**
     * @param $keyOrUrl
     * @return bool
     * @throws StorageException
     */
    public function stat($keyOrUrl) {
        $key = $this->url2key($keyOrUrl);
        $client = new \Qiniu_MacHttpClient(null);
        list($statRet, $err) = Qiniu_RS_Stat($client, $this->bucket, $key);
        if (null !== $err) {
            throw new StorageException($err->Err);
        }
        return $statRet;
    }

    public function freeze($keyOrUrl) {
        $key = $this->url2key($keyOrUrl);
        $client = new \Qiniu_MacHttpClient(null);
        $err = Qiniu_RS_Move($client, $this->bucket, $key, $this->freezeBucket, $key);
        if (null !== $err) {
            throw new StorageException($err->Err);
        }
        return true;
    }

    public function unfreeze($keyOrUrl) {
        $key = $this->url2key($keyOrUrl);
        $client = new \Qiniu_MacHttpClient(null);
        $err = Qiniu_RS_Move($client, $this->freezeBucket, $key, $this->bucket, $key);
        if (null !== $err) {
            throw new StorageException($err->Err);
        }
        return true;
    }

    public function privateUrl($url, $expires = 3600) {
        $getPolicy = new \Qiniu_RS_GetPolicy();
        $getPolicy->Expires =$expires;
        $privateUrl = $getPolicy->MakeRequest($url, null);
        return $privateUrl;
    }

    public function refreshDirs($dirs) {
        return $this->refreshUrlsAndDirs([], $dirs);
    }

    public function refreshUrls($urls) {
        return $this->refreshUrlsAndDirs($urls);
    }


    public function refreshUrlsAndDirs($urls, $dirs=[]) {
        $params = [];
        $params['urls'] = $urls;
        $params['dirs'] = $dirs;
        $params = json_encode($params);
        $apiUrl = 'http://fusion.qiniuapi.com/v2/tune/refresh';
        $client = new \Qiniu_MacHttpClient(null);
        $contentType = 'application/json';
        $err = Qiniu_Client_CallWithForm($client, $apiUrl, $params, $contentType);
        $err = reset($err);
        if (200 != $err['code']) {
            throw new StorageException($err['error'], $err['code']);
        }
        return true;
    }


    private function url2key($keyOrUrl) {
        $key = $keyOrUrl;
        if (preg_match('/^[^:]+:\/\/[^\/]+\/(.*)/i', $keyOrUrl, $match)) {
            $key = $match[1];
        }
        return $key;
    }


}