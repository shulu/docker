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

Route::group('v1',function (){
	#=============================注册登录=================================#
	Route::any('auth/signin/mobile/:email/:nickname','index/User/createWithEmail');
	//手机号密码登录
	Route::any('auth/signin/mobile','index/User/createWithEmail');
	//获取验证码
	Route::any('auth/send_sms_code','index/User/createWithEmail');
	//验证码登录
	Route::any('auth/signin/captcha','index/User/createWithEmail');
	//修改密码
	Route::any('account/password/reset','index/User/createWithEmail');
	#============================首页=======================================#
	#关注
	Route::any('post/timeline/following','index/User/createWithEmail');
	#问答
	Route::any('post/timeline/following','index/User/createWithEmail');
	#说说
	Route::any('recommendation/posts/jingxuan','index/User/createWithEmail');
	#搜索
	Route::resource('search','index/User/createWithEmail');
	#==============================活动tag=================================#
	Route::any('recommendation/posts/jingxuan','index/User/createWithEmail');
	#==============================发帖====================================#
	#@好友
	Route::any('search/user_in_topic','index/User/createWithEmail');
	#上传图片 七牛云 先获取七牛token  然后由七牛SDK附带函数上传至七牛云
	Route::any('upload/qiniu/token','index/User/createWithEmail');
	#正式发帖
	Route::any('post/create','index/User/createWithEmail');
	#===========================信息======================================#
	#获取@我的、评论、小助手列表
	Route::any('notification/event/:group','index/User/createWithEmail');
	#获取被赞列表
	Route::any('notification/like','index/User/createWithEmail');
	#获取未读通知内容，包括谁@了我，谁评论了我，谁点赞了我，评论了什么等
	Route::any('notification/event','index/User/createWithEmail');
	#获取未读信息数量，包括@我的、点赞、评论
	Route::any('notification/unread_count','index/User/createWithEmail');
	#========================个人=======================================#
	#个人信息
	Route::any('account/get','index/User/createWithEmail');
	#获取用户自己的帖子
	Route::any('post/plain/timeline/user','index/User/createWithEmail');
	#头像上传
	Route::any('account/profile/update','index/User/createWithEmail');
	#推送设置：先获取用户的推送设置
	Route::any('notification/push/setting','index/User/createWithEmail');
	#修改完推送设置就告诉后台
	Route::any('notification/push/setting/update','index/User/createWithEmail');
	#获取黑名单列表
	Route::any('relation/blacklist/list','index/User/createWithEmail');
	#获取我的粉丝列表
	Route::any('relation/followers','index/User/createWithEmail');
	#获取我关注的人列表
	Route::any('relation/followees','index/User/createWithEmail');
	#获取版本更新
	Route::any('client/update','index/User/createWithEmail');
	#退出登录
	Route::any('auth/signout','index/User/createWithEmail');
	#收藏帖子列表
	Route::any('favorite/posts/list','index/User/createWithEmail');
	#获取被点赞的列表
	Route::any('notification/like','index/User/createWithEmail');
	#=================================公共接口==============================#
	#点赞
	Route::any('post/like','index/User/createWithEmail');
	#取消点赞
	Route::any('post/unlike','index/User/createWithEmail');
	#===============================帖子详情界面=============================#
	#加载帖子信息
	Route::any('post/get','index/User/createWithEmail');
	#评论列表
	Route::any('comment/timeline/post','index/User/createWithEmail');
	#评论帖子
	Route::any('comment/create','index/User/createWithEmail');
	#回复其它人的评论
	Route::any('comment/reply','index/User/createWithEmail');
	#删除自己的评论`
	Route::any('comment/delete','index/User/createWithEmail');
	#帖子点赞或取消点赞的接口和上面一致
	Route::any('post/unlike','index/User/createWithEmail');
	#单条评论点赞
	Route::any('comment/like','index/User/createWithEmail');
	#单条评论取消点赞
	Route::any('comment/unlike','index/User/createWithEmail');
	#========================分享与举报、拉黑================================
	
	
	#=========================================================================#
	Route::any('auth/send_sms_code','index/User/sendCode');
	Route::any('auth/signin/captcha','index/User/sendCode');
	Route::any('account/password/reset','index/User/sendCode');
	Route::resource('user','index/User');
});

Route::any('accessToken','index/auth/accessToken');//Oauth

return [
    '__pattern__' => [
    ],
];
