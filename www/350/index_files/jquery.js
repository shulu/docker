/*
 * 幻灯片插件
 */
 
(function($){
	// 幻灯
	$.fn.slideBox = function(options){
		var defaults = {
			mode : 'left', // 滚动效果 left,top,fade,show
			duration : 0.4,	// 过渡时间, 秒
			easing : 'swings', // 过渡效果 swing, linear
			delay : 4,	// 间隔, 秒
			optevent: 'mouseenter', // 操作事件：click, mouseenter
			navCell : true, // 导航按钮
			nextBtn : false, // 下一页按钮
			prevBtn : false, // 上一页按钮
			autoPlay : true, // 自动播放
			viewItem : 1 // 滚动数量
		};
		// 计算数据
		var settings = $.extend(defaults, options || {});
		var wrapper = $(this), 
			ul = wrapper.find('.slide_bd'), 
			lis = ul.find('li'), 
			first_pic = lis.first().find('img'), 
			li_num = lis.length, 
			li_width = 0, 
			li_height = 0, 
			index = 0,
			navlis = '';
		// 初始化
		var init = function(){
			if (!wrapper.length) return false;
			wrapper.data('out', 1);

			li_width = lis.first().outerWidth(true);
			li_height = lis.first().outerHeight(true);

			var fd = $('<div class="slide_fd"></div>').appendTo(wrapper); // 操作按钮div
			// 导航按钮
			if (settings.navCell) {
				var navs = $('<ul class="slide-nav"></ul>').appendTo(fd);
				var navs_html = '';
				for( var i = 1; i <= li_num; i++){
					navs_html += '<li>'+i+'</li>';
				}
				navs.append(navs_html);
				navlis = navs.find('li');
				navlis.bind( settings.optevent, function(){
					index = $(this).index();
					slide();
				})
			};
			// 下一页
			if (settings.nextBtn || settings.prevBtn) {
				var opt_btn = $('<div class="slide-opt"></div>').appendTo(fd);
			};
			if (settings.nextBtn) {
				var nextbtn = $('<a href="javascript:;" class="slide-next"></a>').appendTo(opt_btn);
				nextbtn.bind('click', function(){
					index++;
					slide();
				});

				if (settings.mode == 'left' || settings.mode == 'top') { 
					for( var i = 0; i < settings.viewItem; i++){
						lis.eq(i).clone().addClass('li-clone').appendTo(ul);
					}
				}
			};
			if (settings.prevBtn) {
				var prevbtn = $('<a href="javascript:;" class="slide-prev"></a>').appendTo(opt_btn);
				prevbtn.bind('click', function(){
					index--;
					slide();
				});

				if ((settings.mode == 'left' || settings.mode == 'top')) {
					lis.last().clone().addClass('li-clone').prependTo(ul);
				}
			};

			lis = ul.find('li');
			if (settings.mode == 'left') {
				ul.css({'width': lis.length * li_width, 'left': -li_width});
			}else if(settings.mode == 'top'){
				ul.css({'height': lis.length * li_height, 'top': -li_height});
			}

			lis.length > 0 && slide() && settings.autoPlay && start();
		};
		// 执行
		var slide = function(){
			
			if (settings.mode == 'fade' || settings.mode == 'show') {
				if (index >= li_num) {
					index = 0;
				}else if(index < 0){
					index = li_num - 1;
				}
			};

			if (settings.mode == 'fade') {
				lis.eq(index).fadeIn().siblings().fadeOut();
			}else if (settings.mode == 'show') {
				lis.eq(index).show().siblings().hide();
			}else if (settings.mode == 'left') {
				if(index >= li_num){
					var param = {'left': -(index+1) * li_width};
					ul.stop(true,false).animate(param, settings.duration*1000, settings.easing, function(){
						ul.css({'left': -li_width});
					});
					index = 0;
				}else if(index < 0){
					var param = {'left': '0px'};
					ul.stop(true,false).animate(param, settings.duration*1000, settings.easing, function(){
						ul.css({'left': -li_width*li_num})
					});
					index = li_num-1;
				}else{
					var param = {'left': -(index+1) * li_width};
					ul.stop(true,false).animate(param, settings.duration*1000, settings.easing);
				}
			}else if (settings.mode == 'top') {
				if(index >= li_num){
					var param = {'top': -(index+1) * li_height};
					ul.stop(true,false).animate(param, settings.duration*1000, settings.easing, function(){
						ul.css({'top': -li_height})
					});
					index = 0;
				}else if(index < 0){
					var param = {'top': '0px'};
					ul.stop(true,false).animate(param, settings.duration*1000, settings.easing, function(){
						ul.css({'top': -li_height*li_num})
					});
					index = li_num-1;
				}else{
					var param = {'top': -(index+1) * li_height};
					ul.stop(true,false).animate(param, settings.duration*1000, settings.easing);
				}
			};

			if (settings.navCell) {
				navlis.eq(index).addClass('active').siblings().removeClass('active');
			};

			wrapper.data('out') && settings.autoPlay && start();
		}
		// 自动
		var auto = function(){
			index++;
			slide();

		}
		// 开始
		var start = function(){
			wrapper.data('timeid', window.setTimeout(auto, settings.delay*1000));
		};
		// 停止
		var stop = function(){
			window.clearTimeout(wrapper.data('timeid'));
		}
		// 鼠标经过事件
		if (settings.autoPlay) {
			wrapper.hover(function(){
				wrapper.data('out', 0);
				stop();
			},function(){
				wrapper.data('out', 1);
				start();
			})
		};

		init();
	}
})(jQuery)