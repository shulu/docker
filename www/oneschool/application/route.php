<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;
Route::get('/',function(){
	return 'Hello,world!';
});
Route::get('news/:id','index/Index/read');	//查询
Route::post('index','index/Index/add'); 		//新增
Route::put('news/:id','index/Index/update'); //修改

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
];
