"use strict";
/**
 *
 * @authors PanXu
 * @date    2017-02-24 09:56:48
 * @version 1.0
 */
$(function(){new GiftCenter;});

function GiftCenter ()
{
    this.receive_gift_url 	  = "http://sdkapi.350.com/api/h5/games.php?do=game_detail";
    this.initialize();


};

GiftCenter.prototype = {

    initialize : function ()
    {
        this.giftbag_id = $('.gift_detail_main').attr('gift-id');
        this.game_id = $('.gift_detail_main').attr('game-id');
        this.gift_number= $("#gift_left_"+this.giftbag_id).html();
        this.listenEvent();
    },
    receive : function (gift_id, game_id)
    {
        var self  = this;
        // 判断用户是否登陆,依赖 ./class/userClass.js
         userClass.api('status', {}, function (response) {
                // 登陆成功状态 跳转首页
                if (response.status === 200) {
                    var uid = response.data.uid;
                    //领取礼包
                    var receive = Tools.ajax('get', 'http://sdkapi.350.com/api/h5/games.php?do=userGetGiftbag&giftbag_id='+self.giftbag_id+'&user_id='+uid, {platform:1}, 'json');
                    if (receive.status == 200) {
                        $(".popup").css("display","block");
                        $(".get_fail").css("display","none");
                        new Clipboard('.copy_btn');
                        $('.copy_btn').click(function(){$(this).hide();$('.copy_tip').fadeIn()});
                        $('.closed_btn').click(function(){$('.popup').fadeOut()});
                        $(".gift_num #content").empty().html(receive.giftbagcode);
                        $("#gift_left_"+self.giftbag_id).empty().html(self.gift_number-1);
                    } else if (receive.status == 201) {
                        $(".popup").css("display","block");
                        $(".popup_inner").css("display","none");
                        $(".get_fail").css("display","block");
                        $(".get_fail .gift_num").empty().html(receive.msg);
                        $('.closed_btn').click(function(){$('.popup').fadeOut()});
                    }
                }
            },function (response) {
            //未登录跳转去登录
            if (response.status !== 200) {
                userClass.goUserUrl('login');
            }
        });

    },

    listenEvent : function ()
    {
        var self = this;
        $(".get_gift_btn").bind('click', function(){
            self.receive($(".gift_detail_main").attr("gift-id"),$(".gift_detail_main").attr("game-id"));
        });
    },
    //获取礼包余量
    getGiftBagRemain : function (giftbag_id, game_id) {
        var receive = Tools.ajax('get', 'http://sdkapi.350.com/api/h5/games.php?do=game_detail&gameid='+game_id, {platform:1}, 'json');
        // var receive = Tools.ajax('get', 'http://sdkapi.350.com/api/h5/games.php?do=game_detail&game_id='+self.game_id, {platform:1}, 'json');
        $(receive.giftbag_list).each(function(i,item){
            //设置礼包剩余量/总量
            if(giftbag_id == item.giftbag_id){
                $("#gift_left_"+giftbag_id).empty().html(item.remain);
                $("#gift_total_"+giftbag_id).empty().html(item.total);
            }

        });
    }
};

