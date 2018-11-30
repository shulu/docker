<?php
namespace Lychee\Module\Authentication;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Component\Foundation\HttpUtility;
use Lychee\Module\Authentication\Entity\PhoneCode;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class PhoneVerifier {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $container;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct($registry, $container) {
        $this->em = $registry->getManager();
        $this->container = $container;
    }

    /**
     * @return string
     */
    private function generateCode() {
        $bytes = openssl_random_pseudo_bytes(4);
        $dec = hexdec(bin2hex($bytes));
        $code = $dec % 1000000;
        return sprintf('%06d', $code);
    }

    private function sendSMSCode($areaCode, $phone, $code) {

        if ('dev'==$this->container->get('kernel')->getEnvironment()) {
            return true;
        }

        $result = HttpUtility::postJson('https://api.mysubmail.com/message/xsend.json', array(
            'appid' => 10235,
            'signature' => '1127e747f18ea87ad455f895087b6469',
            'project' => 'xiJAe1',
            'to' => $phone,
            'vars' => '{"code":"'.$code.'"}'
        ));
        if ($result) {
            return $result['status'] == 'success';
        } else {
            return false;
        }
    }

    public function sendCode($areaCode, $phone) {
        $code = $this->generateCode();
        $time = time();
        $codeEntity = new PhoneCode();
        $codeEntity->areaCode = $areaCode;
        $codeEntity->phone = $phone;
        $codeEntity->createTime = $time;
        $codeEntity->code = $code;

        try {
            $this->em->persist($codeEntity);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            //以前发送过短信验证码
            $sql = 'UPDATE phone_code SET code = ?, create_time = ?
                WHERE area_code = ? AND phone = ? AND create_time < ? - 60';
            $conn = $this->em->getConnection();
            $updatedCount = $conn->executeUpdate($sql, array($code, $time, $areaCode, $phone, $time),
                array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT));
            if ($updatedCount == 0) {
                return false;
            }
        }

        return $this->sendSMSCode($areaCode, $phone, $code);
    }

    public function verify($areaCode, $phone, $code) {
        if (!is_numeric($code)) {
            return false;
        }
        $sql = 'DELETE FROM phone_code
            WHERE area_code = ? AND phone = ? AND create_time >= ? - 300 AND code = ?';
        $conn = $this->em->getConnection();
        $deletedCount = $conn->executeUpdate($sql, array($areaCode, $phone, time(), $code),
            array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR));
        return $deletedCount > 0;
    }


    public function getCode($areaCode, $phone) {
        $sql = 'SELECT * FROM phone_code WHERE area_code = ? AND phone = ?';
        $conn = $this->em->getConnection();
        $statement = $conn->executeQuery($sql, [$areaCode, $phone]);
        $r = $statement->fetch(\PDO::FETCH_ASSOC);
        if (empty($r)) {
            return '';
        }
        return $r['code'];
    }

    /**
     * sendMultiMessage
     * 单个手机号一天不要超过50条同内容的短信
     *
     * @param $multi   [{
     *       "to":"15*********",
     *      "vars":{
     *          "name":"kevin",
     *          "code":123456
     *      }
     *    },{
     *      "to":"18*********",
     *      "vars":{
     *          "name":"jacky",
     *          "code":236554
     *      }
     *    },{
     *       "to":"13*********",
     *      "vars":{
     *          "name":"tom",
     *          "code":236554
     *      }
     *   ]
     * @param $content 【次元社】您好，@var(name)，您的取货码为 @var(code)
     * @param $batchCount 单次发送的短信条数 , >50按照推广类短信计费
     * @param $tag 标记
     *
     *
     * @return  [
     *            {
     *               "status":"error",
     *              "to":"15*********",
     *              "code":1xx,
     *              "msg":"error message",
     *          },{
     *              "status":"success",
     *              "to":"18*********",
     *              "send_id":"093c0a7df143c087d6cba9cdf0cf3738",
     *              "sms_credits":14196
     *          }
     *    ]
     */
    public function sendMultiMessage($multi , $content , $batchCount = 50 , $tag = ''){
        $url = "https://api.mysubmail.com/message/multisend.json";
        $appId = 10235;
        $signature = '1127e747f18ea87ad455f895087b6469';

        $count = count($multi);
        $max = $batchCount;

        $return = [];
        //[{"to":"13570338511","vars":{"nickname":"\u5927\u98de"}},{"to":"13430118344","vars":{"nickname":"mmfei"}}]

        while($count > 0){
            $a = array_slice($multi , 0, $max);
            $multi = array_slice($multi , $max );
            $count -= $max;
            $response = HttpUtility::postJson( $url,[
                'appid' => $appId,
                'signature' => $signature,
                'multi' => $a,
                'content' => $content,
                'tag' => $tag,
            ]);
            $return = array_merge($return , $response);
        }
        return $return;

    }
}