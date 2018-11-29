<?php
/**
 * -------------------------
 *
 * -------------------------
 * User: shulu
 * Date: 2018/11/29
 * Time: 14:25
 */

return [
	'1' => ['name' => '测试文档', 'id' => '1', 'parent' => '0', 'class' => '', 'readme' => ''],//下面有子列表为一级目录
	'2' => ['name' => '说明', 'id' => '2', 'parent' => '1', 'class' => '', 'readme' => '/doc/md/auth.md'],//没有接口的文档，加载markdown文档
	'3' => ['name' => '用户接口', 'id' => '3', 'parent' => '1', 'readme' => '', 'class' => \app\index\controller\User::class],//User接口文档
];