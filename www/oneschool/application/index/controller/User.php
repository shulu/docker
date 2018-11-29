<?php
namespace app\index\controller;
use think\Cache;
use think\Request;

use app\entity\User as db_user;
use app\entity\UserCounting;
use app\common\controller\Entity;

class User extends Base
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	//是否开启授权认证
	public $apiAuth = true;
	//附加方法
	protected $extraActionList = ['sendCode', 'createWithEmail'];
	//跳过鉴权的方法
	protected $skipAuthActionList = ['sendCode', 'createWithEmail'];
	
	public function __construct ()
	{
		$this->entityManager = Entity::getEntityManager();
	}
	
	/**
	 * @param string|null $email
	 * @param string|null $nickname
	 *
	 * @return User|mixed
	 * @throws Exception\EmailAndNicknameDuplicateException
	 * @throws Exception\EmailDuplicateException
	 * @throws Exception\NicknameDuplicateException
	 * @throws \Exception
	 */
	public function createWithEmail($email = null, $nickname = null)
	{
		$emailIsUsed = $this->fetchOneByEmail($email) !== null;
		$nikenameIsUsed = $this->fetchOneByNickname($nickname) !== null;
		if ($emailIsUsed && $nikenameIsUsed)
		{
			return $this->sendError(100, '邮箱或昵称已经被使用', 404);
		} else if ($emailIsUsed) {
			return $this->sendError(101, '邮箱已经被使用', 404);
		} else if ($nikenameIsUsed) {
			return $this->sendError(102, '昵称已经被使用',404);
		}
		
		$user = new db_user();
		$user->createTime = new \DateTime('now');
		$user->email = $email;
		$user->nickname = $nickname;
		
		try {
			$this->entityManager->beginTransaction();
			$this->entityManager->persist($user);
			$this->entityManager->flush($user);
		
			$counting = new UserCounting();
			$counting->userId = $user->id;
			$this->entityManager->persist($counting);
			$this->entityManager->flush($counting);
			
			$this->entityManager->commit();
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			return $this->sendError(103, '用户创建失败', 404);
		}
		
		return $this->sendSuccess (['message'=>"Created User with ID " .$user->getId()]);
	}
	
	/**
	 * @param string $email
	 *
	 * @return User|null
	 */
	public function fetchOneByEmail($email)
	{
		/** @var User $user */
		$user = $this->entityManager
			->getRepository('app\entity\User')
			->findOneBy(array('email' => $email));
		if ($user === null) {
			return null;
		} else {
			Cache::store ('redis')->set('User_:_'.$user->id, $user);
			return $user;
		}
	}
	
	/**
	 * @param string $nickname
	 *
	 * @return User|null
	 */
	public function fetchOneByNickname($nickname) {
		/** @var User $user */
		$user = $this->entityManager
			->getRepository('app\entity\User')
			->findOneBy(array('nickname' => $nickname));
		if ($user == null) {
			return null;
		} else {
			Cache::store ('redis')->set('User_:_'.$user->id, $user);
			return $user;
		}
	}
	
	/**
	 * @title 发送CODE
	 * @readme /doc/md/method.md
	 */
	public function sendCode()
	{
		//send message
		return $this->sendSuccess(['code'=>123]);
	}
	
	/**
	 * @title 获取列表
	 * @return string name 名字
	 * @return string id  id
	 * @return integer age  年龄
	 * @readme /doc/md/method.md
	 */
	public function index()
	{
		return $this->sendSuccess(self::testUserData());
	}
	
	/**
	 * @title 创建用户
	 * @param Request $request
	 * @return string name 名字
	 * @return string id  id
	 * @return object user  用户信息
	 * @readme /doc/md/method.md
	 * @param  \think\Request $request
	 */
	public function save(Request $request)
	{
		$data = $request->input();
		// db save
		$data['id'] = 4;
		return $this->sendSuccess($data);
	}
	
	/**
	 * @title 获取单个用户信息
	 * @param  int $id
	 * @return string name 名字
	 * @return string id  id
	 * @return object user  用户信息
	 * @readme /doc/md/method.md
	 * @return \think\Response
	 */
	public function read($id)
	{
		//
		$testUserData = self::testUserData();
		return $this->sendSuccess($testUserData[$id]);
	}
	
	/**
	 * 保存更新的资源
	 *
	 * @param  \think\Request $request
	 * @param  int $id
	 * @param  int $id
	 * @title 更新用户
	 * @return string name 名字
	 * @return string id  id
	 * @readme /doc/md/method.md
	 * @return \think\Response
	 */
	public function update(Request $request, $id)
	{
		//
		$testUserData = self::testUserData();
		$data = $testUserData[$id];
		//更新
		$data['age'] = $request->post('age');
		return $this->sendSuccess($data);
	}
	
	/**
	 * 删除指定资源
	 *
	 * @param  int $id
	 * @return object user  用户信息
	 * @title 删除用户
	 * @return \think\Response
	 */
	public function delete($id)
	{
		$testUserData = self::testUserData();
		// delete
		$user = $testUserData[$id];
		unset($testUserData[$id]);
		return $this->sendSuccess(['user' => $user], 'User deleted.');
	}
	
	public static function testUserData()
	{
		return [
			1 => ['id' => '1', 'name' => 'dawn', 'age' => 1],
			2 => ['id' => '2', 'name' => 'dawn1', 'age' => 2],
			3 => ['id' => '3', 'name' => 'dawn3', 'age' => 3],
		];
	}
	
	/**
	 * 参数规则
	 * @name 字段名称
	 * @type 类型
	 * @require 是否必须
	 * @default 默认值
	 * @desc 说明
	 * @range 范围
	 * @return array
	 */
	public static function getRules()
	{
		$rules = [
			'index' => [
			],
			'create' => [
				'name' => ['name' => 'name', 'type' => 'string', 'require' => 'true', 'default' => '', 'desc' => '名称', 'range' => '',],
				'age' => ['name' => 'age', 'type' => 'string', 'require' => 'true', 'default' => '', 'desc' => '年龄', 'range' => '',],
			],
			'sendCode' => [
				'name' => ['name' => 'name', 'type' => 'string', 'require' => 'true', 'default' => '', 'desc' => '名称', 'range' => '',],
				'age' => ['name' => 'age', 'type' => 'string', 'require' => 'true', 'default' => '', 'desc' => '年龄', 'range' => '',],
			]
		];
		//可以合并公共参数
		return $rules;
	}
}
