<?php
namespace Lychee\Module\Authentication\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Authentication\TokenIssuer;

class TokenIssuerTest extends ModuleAwareTestCase {


    /**
     * @var TokenIssuer
     */
    private $tokenIssuer;

    protected function setUp() {
        parent::setUp();
        $this->tokenIssuer = new TokenIssuer($this->container()->get('doctrine'), $this->container()->get('memcache.default'));
    }

    public function testIssueToken() {
        $this->tokenIssuer->issueToken(31728, 4, null, 'test', 10000);
        $tokens = $this->tokenIssuer->getTokensByUser(31728);
        var_dump($tokens);
    }

    public function testGetTokenByAccessToken() {
        $token = $this->tokenIssuer->getTokenByAccessToken('7087182135c28bb63ef47aaa4af14c6c729c37fa');
        var_dump($token);
    }

//    public function testExpire() {
//        $token = $this->tokenIssuer->issueToken(31728, 4, null, 'test', 10);
//        $accessToken = $token->accessToken;
//        var_dump($this->tokenIssuer->getTokenByAccessToken($accessToken));
//    }

    public function testDelete() {
        $this->tokenIssuer->revokeTokensByUser(31728);
        $tokens = $this->tokenIssuer->getTokensByUser(31728);
        var_dump($tokens);
    }
    
}