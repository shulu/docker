<?php
namespace app\module\authentication;

use app\api\error\ErrorsException;
use app\common\model\PhoneCode;
use think\Db;
use think\Exception;

class PhoneVerifier {
	
	
	public function __construct()
	{
	
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
	
	private function sendSMSCode($areaCode, $phone, $code)
	{
		$content = " 【一校先生】 {$code} (手机绑定验证码)，请在60S内完成绑定。如非本人操作，请忽略。";
		$ori_coding = mb_detect_encoding($content);
		$content = mb_convert_encoding($content, 'utf-8', $ori_coding);
		$content = urlencode($content);
		$un=400196;
		$pw = 400196;
		$api_url = "http://61.129.57.153:7891/mt";
		$info = "dc=15&da={$phone}&un={$un}&pw={$pw}&tf=3&rf=2&sm={$content}";
		$result = $this->curl($api_url, $info, '', '', 20, false);
		$result = json_decode ($result, true);
		if ($result['success'])
		{
			return true;
		} else {
			return false;
		}
	}
	
	public function sendCode($areaCode, $phone)
	{
		$code = $this->generateCode();
		$time = time();
		$phone_code = PhoneCode::where('phone', $phone)->find();
		if ($phone_code)
		{
			$result = $phone_code->save([
				'code'  => $code,
				'create_time' => $time
			],['phone' => $phone]);
			//以前发送过短信验证码
			if (!$result) { return false; }
		}else{
			PhoneCode::Create([
				'area_code' => $areaCode,
				'phone' => $phone,
				'create_time' => $time,
				'code' => $code
			]);
		}
		
		return $this->sendSMSCode($areaCode, $phone, $code);
	}
	
	public function verify($areaCode, $phone, $code)
	{
		if (!is_numeric($code)) { return false; }
		$times = time()-300;
		$deletedCount = PhoneCode::where('area_code', $areaCode)->where('phone', $phone)->where('create_time', '>=', "{$times}")->where('code', $code)->delete();
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
	
	# 返回 随机密码 默认10位
	function random_pwd($len = 10, $type = 1)
	{
		switch ($type) {
			case 2:
				$chars = '0123456789';
				break;
			case 3:
				$chars = 'abcdefghijklmnopqrstuvwxyz';
				break;
			case 4:
				$chars = 'ABDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 5:
				$chars = 'abcdefghijklmnopqrstuvwxyzABDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			default:
				$chars = 'abcdefghijklmnopqrstuvwxyzABDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
				break;
		}
		
		$password = '';
		for ($i = 0; $i < $len; $i++) {
			$password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
		}
		
		return $password;
	}
	
	/**
	 * @description
	 * @name_en curl
	 * @author shulu
	 * @Date: 2018年12月9日21:00:05
	 * @param $url
	 * @param $info
	 * @param string $time
	 * @param string $act
	 * @param int $timeout
	 * @param int $post
	 * @return string
	 */
	function curl($url, $info, $time = '', $act = '', $timeout = 8, $post = 1)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		if ($post) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($post == 2) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $info);
			} else {//为了兼容之前的post请求方式
				curl_setopt($ch, CURLOPT_POSTFIELDS, "act=$act&" . $info . "&time=" . $time . "&sign=" . md5($time . "#gr*%com#"));
			}
			#curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEJAR);
		} else {
			#curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
			curl_setopt($ch, CURLOPT_URL, $url . '?' . $info);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		
		ob_start();
		curl_exec($ch);
		$contents = ob_get_contents();
		ob_end_clean();
		curl_close($ch);
		
		return $contents;
	}
	
}