/**
 * 头部
 */

function showHearderUserTip(userName) {
    if (userName) {
        $('#header_username').html(userName);
        $('#header_user_login').show();
    } else {
        $('#header_user_nologin').show();
    }
}

$(function () {
    $('.regist').bind('click', function () {
        $(".pop").fadeIn();
        $(".login_pop_inner").css({"display": "none"});
        $(".regist_pop_inner").css({"display": "block"});
    });
    $('.regist_link').bind('click', function () {
        $(".login_pop_inner").css({"display": "none"});
        $(".regist_pop_inner").css({"display": "block"});
    });
    $('.login').bind('click', function () {
        $(".pop").fadeIn();
        $(".regist_pop_inner").css({"display": "none"});
        $(".login_pop_inner").css({"display": "block"});
    });
    $('.login_link').bind('click', function () {
        $(".regist_pop_inner").css({"display": "none"});
        $(".login_pop_inner").css({"display": "block"});
    });
    $('.closed').bind('click', function () {
        $(".pop").fadeOut('fast');
    });

    var $getTabbox  = $('.regist_tab'),
        $getTabnote = $getTabbox.find('>div'),
        tabNum      = 0;

    $getTabnote.click(function () {
        tabNum = $(this).index();
        $("#tips").html("");
        $getTabnote.removeClass('cur');
        $(this).addClass('cur');
        $getTabbox.siblings().hide().eq(tabNum).show();
    });

    //输入框样式
    $('.input_border input').focus(function () {
        $(this).parent().css({"border-bottom": "1px solid #059ef5"});
        $(this).prev().css({"color": "#059ef5"});
    }).blur(function () {
        if ($(this).val() !== "") {
            $(this).next().fadeIn("fast");
        } else {
            $(this).next().fadeOut("fast");
        }
        $(this).parent().css({"border-bottom": "1px solid #ededed"});
        $(this).prev().css({"color": "#333"});
    });

    $(".cancel").click(function () {
        $(this).prev().val("");
        $(this).fadeOut("fast");
    });


    // 登陆 h = header
    $('.login-submit').bind('click', function () {
        var loginData = {};
        loginData.user_name = $('input[name="h-username"]').val();
        loginData.password = $('input[name="h-password"]').val();
        loginData.long_login = $('input[name="h-long_login"]').prop("checked");

        userClass.api('login', loginData, function () {
            userClass.goUserUrl('index');
        }, function (msg) {
            cFunction.tips('.login-tips', msg, true);
        })
    });
    // 普通注册 hg = header-register
    $(".number-regist-submit").bind('click', function () {
        var regData = {};
        regData.user_name = $('input[name="hg-username"]').val();
        regData.password = $('#hg-password').val();
        regData.repassword = $('#hg-repassword').val();
        regData.true_name = $('input[name="hg-true_name"]').val();
        regData.id_card = $('input[name="hg-id_card"]').val();
        userClass.api('reg', regData, function (response) {
            userClass.goUserUrl('index');
        }, function (msg) {
            cFunction.tips('.number-regist-tips', msg, true);
        })
    });

    //Phone Register hgp = header-register-phone
    $(".phone-regist-submit").bind('click', function () {
        var regData = {};
        regData.phone = $('input[name="hgp-phone"]').val();
        regData.password = $('#hgp-password').val();
        regData.code = $('#hgp-phone_code').val();
        regData.true_name = $('input[name="hgp-true_name"]').val();
        regData.id_card = $('input[name="hgp-id_card"]').val();
        userClass.api('regByPhone', regData, function (response) {
            userClass.goUserUrl('index');
        }, function (msg) {
            cFunction.tips('.phone-regist-tips', msg, true);
        })
    });

    // 手机注册 - 方法送验证码
    $("#check_code").bind('click', function () {
        var reqData = {};
        reqData.phone = $('input[name="hgp-phone"]').val();
        userClass.api('regByPhoneCode', reqData, function () {
        }, function (msg) {
            cFunction.tips('.phone-regist-tips', msg, true);
        });
    });

    // 隐藏错误提示
    $('input').on('input', function () {
        cFunction.tips('.phone-regist-tips', '', true);
        cFunction.tips('.number-regist-tips', '', true);
        cFunction.tips('.login-tips', '', true);
        cFunction.tips('#bind-phone-tips', '', true);
    });

    //搜索
    $(".search_form").hover(function(){
        $(this).find(".search_button").stop(true).css({"background-position":"0 -30px"})
        $(this).find(".search_area").stop(true).animate({"width":"120px"}).focus()
    })
    $(".search_area").blur(function(){
        $(this).stop(true).animate({"width":"0"},'swing');
        $(this).next().stop(true).css({"background-position":"0 0px"});
    })
    //用户下拉菜单
    $(".user_id").hover(function(){
        $(".user_pop").stop(true).slideToggle("fast")
    })
    //点击收藏
    $(".cellection").click(function(){
        try{
            window.external.addFavorite('http://www.350.com', '350手游平台');
        }
        catch(e) {

            try{ window.sidebar.addPanel('350手游平台', 'http://www.350.com', '');
            }
            catch (e) { alert('加入收藏失败，请使用Ctrl+D进行添加,或手动在浏览器里进行设置.');
            }
        }
    })
});