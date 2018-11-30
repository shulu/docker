# 第三方信息发布接口的说明 #

**注意：所有的接口都需要使用UTF-8编码**

## 授权

url: http://203.195.154.161:8082/auth/signin/mobile  
method: post  
parameters:  

name     | type | required | description
-------- | ---- | -------- | -----------
area_code|string| true     | 区号，请以纯数字提交，且没有0前缀。例如，中国，86。
phone    |string| true     | 登录用的电话号码
password |string| true     | 登录用的密码

response:  
	mime-type: application/json  
	format:  
成功返回时：**其中授权码在后续api调用时需要带上。**
>			{
>			  "access_token": "f1bc50a0a4f637a17608ca237b33fdbb5394e3e5",	//授权码
>			  "expires_in": 2592000,		//在指定的时间内授权超时，单位为秒
>			  "expires_at": 1412405184,		//在指定的时间点授权超时，为unix时间戳。跟expires_in相对应。
>			  "account": {					//登录的帐号信息
>			    "id": "75",					//帐号id
>			    "nickname": "今日关注",		//帐号昵称
>			    "signature": null,			//帐号签名
>			    "avatar_url": null,			//帐号头像url
>			    "cover_url": null,			//帐号profile页封面图url
>			    "create_time": 1407737847,	//帐号创建时间，unix时间戳
>			    "gender": null,				//帐号性别，可能的值为"male"(男)，"female"(女)， null(未设置)
>			    "my_follower": false,		//是否是当前登录用户的粉丝，这里一定是false
>			    "my_followee": false,		//是否是当前登录用户关注的对象，这里一定是false
>			    "followers_count": 0,		//粉丝总数
>			    "followees_count": 0,		//关注对象总数
>			    "following_topics_count": 0 //关注的话题总数
>			  }
>			}
  
失败返回时：  
>			{
>			  "errors": [									//错误列表，注意是一个数组
>			    {
>			      "code": 20103,							//错误码
>			      "message": "Account Or Password Error"	//错误信息
>			    }
>			  ]
>			}

## 发布

url: http://203.195.154.161:8082/post/create  
method: post  
parameters:  

name         | type    | required | description
------------ | ------- | -------- | -----------
access_token | string  | true     | 授权码
topic_id     | integer | false    | 话题id，**填21**
title        | string  | false    | 标题
content      | string  | true     | 内容
image_url    | string  | false    | 如果有附图的话，填上附图的url
video_url    | string  | false    | 如果有视频的话，填上视频的url
audio_url    | string  | false    | 如果有音频的话，填上音频的url
longitude    | float   | false    | 客户端上报的GPS经度
latitude     | float   | false    | 客户端上报的GPS纬度
address      | string  | false    | 客户端上报的位置信息

response:  
	mime-type: application/json  
	format:  
成功返回时：
>			{
>			  "id": 6036531837953,			//post的id
>			  "author": {					//帐号信息
>			    "id": "75",
>			    "nickname": "今日关注",
>			    "signature": null,
>			    "avatar_url": null,
>			    "cover_url": null,
>			    "create_time": 1407737847,
>			    "gender": null
>			  },
>			  "create_time": 1409814485,	//创建的时间
>			  "content": "测试",				//内容
>			  "latest_likers": [],			//最新的赞这个post的几个用户信息
>			  "liked_count": 0,				//被赞总数
>			  "commented_count": 0,			//被评论的总数
>			  "reposted_count": 0,			//被转发的总数
>			  "liked": false				//是否被当前用户赞
>			}

失败返回时：
>			{
>			  "errors": [									//错误列表，注意是一个数组
>			    {
>			      "code": 20103,							//错误码
>			      "message": "Account Or Password Error"	//错误信息
>			    }
>			  ]
>			}


