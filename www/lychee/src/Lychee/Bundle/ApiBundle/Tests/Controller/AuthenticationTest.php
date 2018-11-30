<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;

/**
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\AuthenticationController
 */

class AuthenticationTest extends Base {

    /**
     *
     * 手机登录成功
     *
     * /auth/signin/mobile
     *
     * @group prod
     * @covers ::signupWithPhoneAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSiginWithPhoneSucceed() {
        $uri = '/auth/signin/mobile';
        $params = [];
        $params['area_code'] = 86;
        $params['phone'] = '15812431454';
        $params['password'] = 'yskj666';
        $this->handlePost($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('access_token', $content, $makeMsg($content));
            $this->assertArrayHasKey('account', $content, $makeMsg($content));
        });
    }

    /**
     *
     * 手机登录失败
     *
     * /auth/signin/mobile
     *
     * @group prod
     * @covers ::signupWithPhoneAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSigninWithPhoneFail() {
        $uri = '/auth/signin/mobile';
        $params = [];
        $params['area_code'] = 86;
        $params['phone'] = '15812431454';
        $params['password'] = '123456';
        $this->handlePost($uri, $params, function ($content, $makeMsg){
            $this->assertEquals('20103', $content['errors'][0]['code'], $makeMsg($content));
        });
    }

    /**
     *
     * 手机登录不允许GET请求
     *
     * /auth/signin/mobile
     *
     * @group prod
     * @covers ::signupWithPhoneAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSigninWithPhoneNotAllowGet() {
        $uri = '/auth/signin/mobile';
        $params = [];
        $params['area_code'] = 86;
        $params['phone'] = '15812431454';
        $params['password'] = '123456';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertEquals('10104', $content['errors'][0]['code'], $makeMsg($content));
        });
    }
}