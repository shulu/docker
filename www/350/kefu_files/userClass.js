/**
 * 用户接口类
 *
 * 依赖 ../common.js
 */

if (typeof include === 'function') {
    include(config.hp + config.img_domain + '/js/libs/md5.js');
}

var userClass = (function (config, func) {
    var apiUrl = config.hp + config.domain + '/api/user.php';
    var cleanLocalUserInfo = function (userInfo) {
        localStorage.removeItem('username');
        localStorage.removeItem('uid');
        localStorage.removeItem('token');
        localStorage.removeItem('last_login');
        localStorage.removeItem('last_login');
        localStorage.removeItem('f_email');
        localStorage.removeItem('f_mobile');
        localStorage.removeItem('bindPhone');
        if (!func.isNull(userInfo)) {
            setLocalUserInfo(userInfo);
        }
    };
    var setLocalUserInfo = function (userInfo) {
        if (!func.isEmpty(userInfo.username)) {
            localStorage.setItem('username', userInfo.username);
        }
        if (!func.isEmpty(userInfo.uid)) {
            localStorage.setItem('uid', userInfo.uid);
        }
        if (!func.isEmpty(userInfo.token)) {
            localStorage.setItem('token', userInfo.token);
        }
        if (!func.isEmpty(userInfo.f_mobile)) {
            localStorage.setItem('f_mobile', userInfo.f_mobile);
        }
        if (!func.isEmpty(userInfo.f_email)) {
            localStorage.setItem('f_email', userInfo.f_email);
        }
        if (!func.isEmpty(userInfo.last_login)) {
            localStorage.setItem('last_login', userInfo.last_login);
        }
        if (typeof userInfo.bindPhone !== 'undefined') {
            localStorage.setItem('bindPhone', userInfo.bindPhone);
        }
        if (typeof userInfo.bindEmail !== 'undefined') {
            localStorage.setItem('bindEmail', userInfo.bindEmail);
        }
    };
    var apiArr = {
        'login'               : false,
        'status'              : false,
        'logout'              : false,
        'reg'                 : false,
        'regByPhoneCode'      : false,
        'regByPhone'          : false,
        'bindEmailCode'       : false,
        'bindEmail'           : false,
        'bindPhoneCode'       : false,
        'bindPhone'           : false,
        'alterPwd'            : false,
        'forgetPwdCode'       : false,
        'forgetPwd'           : false,
        'alterPhoneCode'      : false,
        'alterPhone'          : false,
        'alterEmailCode'      : false,
        'alterEmail'          : false,
        'gameListAtUserCenter': false,
        'verifyPhoneCode': false,
        'verifyPhone': false
    };
    var login = function (loginData, success, fail) {
        if (!func.chkUserName(loginData.user_name)) return fail('请输入用户名');
        if (!func.chkPassword(loginData.password)) return fail('请输入密码');
        $.getJSON(
            apiUrl + '?callback=?', loginData,
            function (response, status) {
                if (status === 'success') {
                    if (response.status === 200) {
                        cleanLocalUserInfo(response.data);
                        success(response);
                    } else {
                        fail(response.msg);
                    }
                } else {
                    fail(response);
                }
            }
        );
    };
    var status = function (reqData, success, fail) {
        // if (func.isEmpty(localStorage.username)) return fail('未登陆');
        // if (func.isEmpty(localStorage.last_login)) return fail('未登陆');
        reqData.user_name = localStorage.username;
        reqData.last_login = localStorage.last_login;
        if (!func.isEmpty(localStorage.token)) {
            reqData.token = localStorage.token;
        }
        $.getJSON(
            apiUrl + '?callback=?',
            reqData,
            function (response, status) {
                if (status === 'success') {
                    if (response.status === 200) {
                        setLocalUserInfo(response.data);
                        success(response);
                    } else {
                        fail(response.msg);
                    }
                } else {
                    fail(response);
                }
            }
        );
    };
    var logout = function (reqData, success, fail) {
        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                console.log(status);
                if (status === 'success') {
                    console.log(status);
                    cleanLocalUserInfo();
                    success(response);
                }
            }
        );
    };
    var reg = function (regData, success, fail) {
        if (func.isNull(regData.user_name)) return fail('用户名1不能为空');
        if (func.isNull(regData.password)) return fail('密码不能为空');
        if (func.isNull(regData.repassword)) return fail('确认密码不能为空');
        if (func.isNull(regData.true_name)) return fail('姓名不能为空');
        if (func.isNull(regData.id_card)) return fail('身份证号码不能为空');
        if (regData.password !== regData.repassword) return fail('两次输入的密码不一致');
        if (func.isNull(regData.agent_id)) regData.agent_id = config.default_agent_id;
        if (func.isNull(regData.site_id)) regData.site_id = config.site_id;
        if (typeof md5 === 'function') {
            regData.pass_type = 2;
            regData.password = regData.repassword = md5(regData.password);
        }
        $.getJSON(
            apiUrl + '?callback=?', regData,
            function (response, status) {
                if (status === 'success') {
                    if (response.status === 200) {
                        cleanLocalUserInfo(response.data);
                        success(response.msg);
                    } else {
                        fail(response.msg);
                    }
                } else {
                    fail('网络出了点问题，请稍后再试!');
                }
            }
        );

    };
    var regByPhoneCode = (function () {
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('短信发送过于频繁，请稍后再试!');
            if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#check_code", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        }
    })();
    var regByPhone = function (regData, success, fail) {
        if (!func.chkPhone(regData.phone)) return fail('手机号不能为空');
        if (func.isNull(regData.password)) return fail('密码不能为空');
        if (func.isNull(regData.true_name)) return fail('姓名不能为空');
        if (func.isNull(regData.id_card)) return fail('身份证号码不能为空');
        if (func.isNull(regData.code)) return fail('验证码不能为空');
        if (func.isNull(regData.agent_id)) regData.agent_id = config.default_agent_id;
        if (func.isNull(regData.site_id)) regData.site_id = config.site_id;
        if (typeof md5 === 'function') {
            regData.pass_type = 2;
            regData.password = regData.repassword = md5(regData.password);
        }
        $.getJSON(
            apiUrl + '?callback=?', regData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                cleanLocalUserInfo(response.data);
                success('注册成功');
            }
        );
    };
    var bindEmailCode = (function () {
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('');
            if (!func.chkEmail(reqData.email)) return fail('请输入正确格式的邮箱');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    console.log(response);
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#check_code_email", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        };

    })();
    var bindEmail = function (reqData, success, fail) {
        if (func.isNull(reqData.code)) return fail('请输入邮箱验证码');
        if (!func.chkEmail(reqData.email)) return fail('请输入正确格式的邮箱!');
        if (!func.chkPassword(reqData.password)) return fail('密码格式错误');

        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                localStorage.setItem('bindEmail', 1);
                success('绑定邮箱号码成功');
            }
        );
    };
    var alterEmailCode = (function () {
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('');
            if (!func.chkEmail(reqData.email)) return fail('请输入正确格式的邮箱');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#check_code_email", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        };

    })();
    var alterEmail = function (reqData, success, fail) {
        if (func.isNull(reqData.code)) return fail('请输入邮箱验证码');
        if (!func.chkEmail(reqData.email)) return fail('请输入正确格式的邮箱!');
        if (!func.chkPassword(reqData.password)) return fail('密码格式错误');

        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                localStorage.setItem('bindEmail', 1);
                success('绑定邮箱号码成功');
            }
        );
    };
    var bindPhoneCode = (function () {
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('短信发送过于频繁，请稍后再试!');
            if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#check_code", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        };
    })();
    var bindPhone = function (reqData, success, fail) {
        if (func.isNull(reqData.code)) return fail('请输入手机验证码');
        if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
        if (!func.chkPassword(reqData.password)) return fail('密码格式错误');

        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                localStorage.setItem('bindPhone', 1);
                success('绑定手机号码成功');
            }
        );
    };
    var verifyPhoneCode = (function (){
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('短信发送过于频繁，请稍后再试!');
            if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#verify_code", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        };
    })();
    var verifyPhone = function (reqData, success, fail) {
        if (func.isNull(reqData.code)) return fail('请输入手机验证码');
        if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');

        $.getJSON(
            apiUrl + '?callback=?',
            reqData,
            function (response, status) {
                console.log(response)
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                success(response.msg);
            }
        );
    };
    var alterPhoneCode = (function () {
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('短信发送过于频繁，请稍后再试!');
            if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#check_code", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        }
    })();
    var alterPhone = function (reqData, success, fail) {
        if (func.isNull(reqData.code)) return fail('请输入手机验证码');
        if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
        if (!func.chkPassword(reqData.password)) return fail('密码格式错误');

        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                success(response.data);
            }
        );
    };
    var alterPwd = function (reqData, success, fail) {
        if (!func.chkPassword(reqData.old_password)) return fail('老密码格式错误');
        if (!func.chkPassword(reqData.new_password)) return fail('新密码格式错误');
        if (!func.chkPassword(reqData.renew_password)) return fail('确认密码格式错误');

        if (typeof md5 === 'function') {
            reqData.pass_type = 2;
            reqData.old_password = md5(reqData.old_password);
            reqData.new_password = reqData.renew_password = md5(reqData.new_password);
        }

        if (!func.isNull(localStorage.getItem('token'))) {
            reqData.long_login = 1;
        }

        reqData.do = 'alterPwd';
        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                setLocalUserInfo(response.data);
                success('修改密码成功');
            }
        );
    };
    var forgetPwdCode = (function () {
        var mark = false;
        return function (reqData, success, fail) {
            if (mark) return fail('');
            if (!func.chkPhone(reqData.phone)) return fail('请输入正确格式的手机号码');
            if (!func.chkUserName(reqData.user_name)) return fail('请输入正确格式的用户名');
            $.getJSON(
                apiUrl + '?callback=?', reqData,
                function (response, status) {
                    if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                    if (response.status !== 200) return fail(response.msg);
                    mark = true;
                    func.timing("#code", 60, function () {
                        mark = false;
                    });
                    success('发送成功');
                }
            );
        };
    })();
    var forgetPwd = function (reqData, success, fail) {
        if (!func.chkUserName(reqData.user_name)) return fail('请输入正确格式的用户名');
        if (!func.chkUserName(reqData.phone)) return fail('请输入正确格式的手机号码');
        if (func.isNull(reqData.code)) return fail('请输入手机验证码');
        if (!func.chkUserName(reqData.new_password)) return fail('密码格式错误');
        if (!func.chkUserName(reqData.renew_password)) return fail('确认密码格式错误');
        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                success('重设密码成功,请重新登陆');
            }
        );
    };
    var gameListAtUserCenter = function (reqData, success, fail) {
        $.getJSON(
            apiUrl + '?callback=?', reqData,
            function (response, status) {
                if (status !== 'success') return fail('网络出了点问题，请稍后再试!');
                if (response.status !== 200) return fail(response.msg);
                success(response.data);
            }
        );
    };

    var api = function (action, reqData, success, fail) {
        if (typeof apiArr[action] === 'undefined') return fail('未知操作-' + action);
        if (apiArr[action]) return fail(action + '请求中,请勿重复操作');
        reqData.do = action;
        apiArr[reqData.do] = true;
        action = eval(action);//转为函数
        action(reqData, function (response) {
            apiArr[reqData.do] = false;
            success(response);
        }, function (response) {
            apiArr[reqData.do] = false;
            fail(response);
        });
    };

    return {
        api         : api,
        dealHeader  : function () {

        },
        getUserLevel: function () {
            // level等级规则
            // 默认0，带数字+1，带小写字母+1，带大写字母+1，绑定(手机|邮箱)+1
            var level = 0;
            if (/\d+/.test(localStorage.username)) level++;
            if (/[a-z]+/.test(localStorage.username)) level++;
            if (/[A-Z]+/.test(localStorage.username)) level++;
            if (parseInt(localStorage.bindPhone) === 1) level++;
            if (parseInt(localStorage.bindEmail) === 1) level++;
            return level;
        },
        getUserInfo : function (key) {
            if (func.isEmpty(key)) {
                return false;
            }
            if (func.isEmpty(localStorage.key)) {
                return false;
            }
            return localStorage.getItem(key);
        },
        goUserUrl   : function (mark) {
            if (mark === 'login') return func.toUrl('/html/user/login/');
            if (mark === 'index') return func.toUrl('/html/user/');
            if (mark === 'forget') return func.toUrl('/html/user/forgetpwd/');
            if (mark === 'changephone') return func.toUrl('/html/user/changephone/');
            if (mark === 'bindphone') return func.toUrl('/html/user/bindphone/');
        }
    };
})(config, cFunction);



