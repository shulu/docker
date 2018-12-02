<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class Base extends WebTestCase {


    public $hosts = [
        '118.89.20.93:80',
        '123.207.18.60:80',
        '203.195.251.108:80',
        '203.195.211.20:80',
        '111.230.198.180:80'
        ];

    public function handleRequest($uri, $params, $func, $extData=null) {
        $hosts = $this->hosts;
        $response = null;
        foreach ($hosts as $host) {
            $httpClient = new Client([
                'base_uri' => 'http://api.ciyo.cn/',
                'proxy'   => $host.':80'
            ]);
            $response =  $httpClient->get($uri, ['query' => $params]);
            $content = $response->getBody()->getContents();
            $content = json_decode($content, true);
            $func($content, function($content)use ($host, $params){

                return $this->getAssertMsg($host, $params, $content);
            }, $extData);
        }
        return $response;
    }

    public function handlePost($uri, $params, $func) {
        $hosts = $this->hosts;
        $response = null;
        foreach ($hosts as $host) {
            $httpClient = new Client([
                'base_uri' => 'http://api.ciyo.cn/',
                'proxy'   => $host.':80'
            ]);
            $response =  $httpClient->post($uri, ['form_params' => $params]);
            $content = $response->getBody()->getContents();
            $content = json_decode($content, true);
            $func($content, function($content)use ($host, $params){
                return $this->getAssertMsg($host, $params, $content);
            });
        }
        return $response;
    }


    public function getUser() {
        static $user=null;
        if ($user) {
            return $user;
        }
        $httpClient = new Client([
            'base_uri' => 'http://api.ciyo.cn/',
        ]);
        $uri = "/auth/signin/mobile";
        $params = [];
        $params['area_code'] = 86;
        $params['phone'] = '15812431454';
        $params['password'] = 'yskj666';
        $response =  $httpClient->post($uri, ['form_params' => $params]);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);
        $ret = [];
        $ret['uid'] = $content['account']['id'];
        $ret['access_token'] = $content['access_token'];
        return $ret;
    }

    public function getAssertMsg($host, $params, $msg) {
        if (is_array($msg)) {
            $msg = var_export($msg, true);
        }
        return vsprintf("节点： \r\n%s \r\n请求参数：\r\n%s \r\n返回内容：\r\n%s", [$host, var_export($params, true), $msg]);
    }

    public function pre($msg) {
        echo vsprintf("\r\n %s \r\n", $msg);
    }
}