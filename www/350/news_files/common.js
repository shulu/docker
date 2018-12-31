/**
 * 公共JS文件
 */
var config = {
    img_domain      : 'images.350.com',
    domain          : 'sdkapi.350.com',
    hp              : 'http://',
    user_api        : this.hp + this.domain + '/api/user.php',
    game_api        : this.hp + this.domain + 'api/game.php',
    default_agent_id: 100,
    defailt_site_id : 100
};
/*************  函数模块 ************/
var cFunction = (function () {
    return {
        // 是否空值
        isNull        : function (arg1) {
            return !arg1 && arg1 !== 0 && typeof arg1 !== "boolean" ? true : false;
        },
        isEmpty       : function (arg) {
            if (!this.isNull(arg)) return false;
            return arg ? false : true;
        },
        GetQueryString: function (name) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
            var r = window.location.search.substr(1).match(reg);
            if (r !== null)return unescape(r[2]);
            return null;
        },
        include       : function (path) {
            var a = document.createElement("script");
            a.type = "text/javascript";
            a.src = path;
            var head = document.getElementsByTagName("head")[0];
            head.appendChild(a);
        },
        toUrl         : function (url) {
            window.location.href = url;
        },
        alertObj      : function (obj) {
            var output = "";
            for (var i in obj) {
                var property = obj[i];
                output += i + " = " + property + "\n";
            }
            alert(output);
        },
        chkParam      : function (params, name) {
            if (typeof name === 'undefined' || name === '') {
                params = {'name': params};
                name = 'name';
            }
            if (typeof params === 'undefined' || typeof params[name] === 'undefined' || params[name] == '' || params[name] == 0 || params[name] == false || params[name] == 'null') {
                return false;
            }
            return true;
        },
        chkPhone      : function (phone) {
            if (this.isEmpty(phone)) return false;
            return true;
        },
        chkEmail      : function (email) {
            if (this.isEmpty(email)) return false;
            return true;
        },
        chkUserName   : function (userName) {
            if (this.isEmpty(userName)) return false;
            return true;
        },
        chkPassword   : function (password) {
            if (this.isEmpty(password)) return false;
            return true;
        },
        tips          : function (className, message, bool) {
            $(className).empty().html(message);
            return bool;
        },
        hide_show     : function (hide_class_name, show_class_name) {
            $(hide_class_name).css("display", "none");
            $(show_class_name).css("display", "block");
        },
        timing : function (select, time, callback) {
            var oldBackground = $(select).css("background");
            var interval      = setInterval(function(){
                $(select).css("background","#999");
                $(select).empty().html('重新发送('+--time +'s)');
                if (time <= 0) {
                    mark = true;
                    $(select).css("background",oldBackground);
                    $(select).empty().html("发送验证码");
                    clearInterval(interval);
                    callback();
                }
            }, 1000);
        }
    };
})();
/*************  Class模块 ************/
var commonClass = function () {
}