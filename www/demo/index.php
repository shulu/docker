<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pattern = '^((25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))$';
$content = '61.140.27.8';
$pattern_two = "/^61\.140\.\d{1,3}\.\d{1,3}$/";
if (preg_match($pattern_two, $content, $matches) > 0)
{
	var_dump ($matches);
}
exit();
$content = ' @不是程序员的程序员 234543656';

$pattern = '/(?>@([^\p{C}\p{Z}@]{2,36}))(?: |$)/u';
if (preg_match_all($pattern, $content, $matches) > 0)
{
	#$userNames = $matches[1];
	echo json_encode ($matches, JSON_UNESCAPED_UNICODE);
} else {
	echo 'no match';
}
exit();
$phone = '13570274240';
echo turnCharToNum ($phone);

function turnCharToNum($phone)
{
	$charactor = array(
		'A', 'B', 'C', 'D', 'E',
		'F', 'G', 'H', 'I', 'J',
		'K', 'L', 'M', 'N', 'O',
		'P', 'Q', 'R', 'S', 'T',
		'U', 'V', 'W', 'X', 'Y',
		'Z'
	);
	$phone_arr = str_split ($phone);
	$phone_char = '';
	foreach ($phone_arr as $num)
	{
		$rand_n = rand (0,9);
		if ($rand_n < 5)
		{
			$phone_char .= strtolower ($charactor[$num]);
		}else{
			$phone_char .= $charactor[$num];
		}
	}
	return $phone_char;
}
exit();
$str = preg_replace('#[^\x{4e00}-\x{9fa5}A-Za-z0-9]#u','','你sd#^*)好12@3Ab+=45');
echo $str;    //输出'你sd好123Ab45'
#exit();
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

function post_curl($post_url, $post_arr)
{
	$postdata = get_urlencoded_string($post_arr);
	$ch       = curl_init();
	curl_setopt($ch, CURLOPT_URL, $post_url);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	$result = curl_exec($ch);
	curl_close($ch);
	unset($ch);
	
	return $result;
}
function get_urlencoded_string($params) {
	$normalized = array();
	foreach ($params as $key => $val) {
		$normalized[] = $key . "=" . rawurlencode($val);
	}
	
	return implode("&", $normalized);
}

//sms
$code = random_pwd(6,2);
$content = " 【多娱互动】 {$code} (手机绑定验证码)，请在20分钟内完成绑定。如非本人操作，请忽略。";
$ori_coding = mb_detect_encoding($content);
$content = mb_convert_encoding($content, 'utf-8', $ori_coding);
$content = urlencode($content);
$mobile = 15707685324;
$un=400178;
$pw = 400178;
$api_url = "http://61.129.57.153:7891/mt";
#$api_url = "http://61.129.57.20:7891/mt";
$info = "dc=15&da={$mobile}&un={$un}&pw={$pw}&tf=3&rf=2&sm={$content}";
$options =[
	'http' => [
		'method' => 'GET',
		'header' => 'Content-type:application/x-www-form-urlencoded',
		'content' => '',
		'timeout' => 60 // 超时时间（单位:s）
	]
];
#$context = stream_context_create($options);
#$result = file_get_contents("$api_url?{$info}", false, $context);
$result = curl($api_url, $info, '', '', 20, false);
echo json_encode(['$result'=>$result]);
#$sms_url = "http://61.129.57.153:7891/mt?dc=15&da={$phone}&un={$un}&pw={$pw}&tf=3&rf=2&sm={$content}";
#$ch=curl_init($sms_url);
#curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
#curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
#$output=curl_exec($ch);
exit;

echo md5 ('13987100520').PHP_EOL;exit();

echo md5 ('61.140.24.26reg_forbidden').PHP_EOL;

echo md5 ('318654321').PHP_EOL;

echo usertab ('yu54294535', 1);

function usertab($uname, $s = TRUE) {
	$uname = strtolower($uname);
	$c1    = substr($uname, 0, 1);
	$c2    = substr($uname, -1);
	$n     = ord($c1) + ord($c2);
	$l     = strlen($uname);
	$n     += $l * $l;
	if ($s) {
		return 'user_' . $n % 20;
	} else {
		return $n % 20;
	}
}
exit();
$url = "http://logselect.gznuoer.com/getAgentinfo_sql.php?uname=sarcasme3&sign=".md5('sarcasme3#gr*%com#');
echo $url;

exit();
echo md5('15315421647526e13e25db9a5ce462e0cb72e736bc589').PHP_EOL;
echo md5('1542164752#gr*%com#').PHP_EOL;
echo md5('appid=3&uid=15&uname=sarcasme3&sessionid=4&logotype=1').PHP_EOL;
echo md5('login_successful15sarcasm33');
exit();
echo urlencode('多娱互动');
$str = 'a:1:{s:2:"hf";a:1:{i:0;a:3:{s:7:"game_id";s:1:"2";s:4:"adid";s:4:"1242";s:11:"game_byname";s:4:"xydj";}}}';
$str = 'a:1:{s:2:"hf";a:2:{i:0;a:3:{s:7:"game_id";s:1:"1";s:4:"adid";s:4:"1518";s:11:"game_byname";s:8:"azcsdemo";}i:1;a:3:{s:7:"game_id";s:1:"1";s:4:"adid";s:4:"1002";s:11:"game_byname";s:8:"azcsdemo";}}}';
print_r(unserialize($str));
exit();
echo phpinfo();exit();
class Bim
{
	public function doSomething()
	{
		echo __METHOD__, '|';
	}
}

class Bar
{
	private $bim;
	
	public function __construct(Bim $bim)
	{
		$this->bim = $bim;
	}
	
	public function doSomething()
	{
		$this->bim->doSomething();
		echo __METHOD__, '|';
	}
}

class Foo
{
	private $bar;
	
	public function __construct(Bar $bar)
	{
		$this->bar = $bar;
	}
	
	public function doSomething()
	{
		$this->bar->doSomething();
		echo __METHOD__;
	}
}

class Container
{
	private $s = array();
	
	public function __set($k, $c)
	{
		$this->s[$k] = $c;
	}
	
	public function __get($k)
	{
		// return $this->s[$k]($this);
		return $this->build($this->s[$k]);
	}
	
	/**
	 * 自动绑定（Autowiring）自动解析（Automatic Resolution）
	 *
	 * @param string $className
	 * @return object
	 * @throws Exception
	 */
	public function build($className)
	{
		// 如果是匿名函数（Anonymous functions），也叫闭包函数（closures）
		if ($className instanceof Closure) {
			// 执行闭包函数，并将结果
			return $className($this);
		}
		
		/** @var ReflectionClass $reflector */
		$reflector = new ReflectionClass($className);
		
		// 检查类是否可实例化, 排除抽象类abstract和对象接口interface
		if (!$reflector->isInstantiable()) {
			throw new Exception("Can't instantiate this.");
		}
		
		/** @var ReflectionMethod $constructor 获取类的构造函数 */
		$constructor = $reflector->getConstructor();
		
		// 若无构造函数，直接实例化并返回
		if (is_null($constructor)) {
			return new $className;
		}
		
		// 取构造函数参数,通过 ReflectionParameter 数组返回参数列表
		$parameters = $constructor->getParameters();
		
		// 递归解析构造函数的参数
		$dependencies = $this->getDependencies($parameters);
		
		// 创建一个类的新实例，给出的参数将传递到类的构造函数。
		return $reflector->newInstanceArgs($dependencies);
	}
	
	/**
	 * @param array $parameters
	 * @return array
	 * @throws Exception
	 */
	public function getDependencies($parameters)
	{
		$dependencies = [];
		
		/** @var ReflectionParameter $parameter */
		foreach ($parameters as $parameter) {
			/** @var ReflectionClass $dependency */
			$dependency = $parameter->getClass();
			
			if (is_null($dependency)) {
				// 是变量,有默认值则设置默认值
				$dependencies[] = $this->resolveNonClass($parameter);
			} else {
				// 是一个类，递归解析
				$dependencies[] = $this->build($dependency->name);
			}
		}
		
		return $dependencies;
	}
	
	/**
	 * @param ReflectionParameter $parameter
	 * @return mixed
	 * @throws Exception
	 */
	public function resolveNonClass($parameter)
	{
		// 有默认值则返回默认值
		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}
		
		throw new Exception('I have no idea what to do here.');
	}
}

// ----
$c = new Container();
$c->bar = 'Bar';
$c->foo = function ($c) {
	return new Foo($c->bar);
};
// 从容器中取得Foo
$foo = $c->foo;
$foo->doSomething(); // Bim::doSomething|Bar::doSomething|Foo::doSomething

// ----
$di = new Container();

$di->foo = 'Foo';

/** @var Foo $foo */
$foo = $di->foo;

var_dump($foo);
/*
Foo#10 (1) {
  private $bar =>
  class Bar#14 (1) {
	private $bim =>
	class Bim#16 (0) {
	}
  }
}
*/

$foo->doSomething(); // Bim::doSomething|Bar::doSomething|Foo::doSomething