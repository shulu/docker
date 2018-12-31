"use strict";
/**
 * Helper
 * @authors PanXu
 * @date    2017-02-16 11:04:17
 * @version 1.1
 */
var Tools = {
    filter: function (str) {return str.replace(/(^\s+)|(\s+$)/g,"");},
    value: function (className) { return $(className).val().replace(/(^\s+)|(\s+$)/g,"");},
	empty: function (str) { if(str.length == 0 || /\s/.test(str)) return true;return false;},
	string: function (expression, str) {var pattern = new RegExp(expression);if(!pattern.test(str)) return true;return false;},
    compare: function (str1, str2) {if(str1 == str2) return true;return false;},
    tips: function (className, message, bool) { $(className).empty().html(message);return bool;},
    ajax: function (type, url, data, dataType) {var result; $.ajax({url: url,data: data,async: false,type: type,dataType: dataType,success: function(data) {result = data;}});return result;},
    createTag: function (tagName, className) {var tag = $('<' + tagName + '></' + tagName + '>');tag.addClass(className);return tag;},
    clear: function (divClass, tipsClass) {$(divClass + " input").each(function(){$(this).focus(function(){$(tipsClass).empty().html("");});});},
    hide_show: function (hide_class_name, show_class_name) {$(hide_class_name).css("display","none");$(show_class_name).css("display","block");},
    getCookie: function(cookie_name) { if (document.cookie.length>0) {var start = document.cookie.indexOf(cookie_name + "="); if (start != -1){ start = start + cookie_name.length + 1 ; var end = document.cookie.indexOf(";", start); if (end == -1) end = document.cookie.length; return unescape(document.cookie.substring(start, end)); } } return "" },
    getUrlParameter : function (name) { if(window.location.search.substr(1).match(new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"))!=null)return unescape(window.location.search.substr(1).match(new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"))[2]); return null;},
};

