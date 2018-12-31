// 配置幻灯
var navactive, actlocation = navactive ? navactive : 0 ; 
if (actlocation == '0') {
	$('#slide_box').slideBox({
		mode : 'fade',
		nextBtn : false,
		prevBtn : false,
		delay: 4,
		easing : 'swing'
	});  
	var sobj = $('#slide_box .slide-nav');
	var sw = sobj.outerWidth(true);
	sobj.css('margin-left', -1*sw/2+'px');
};
if (parseInt(actlocation) >= 0) {
	$('.g-menu a').eq(actlocation).addClass('active');
};

// 置顶
$('#totop').click(function(){
	$('html,body').animate({scrollTop:0},300);
	console.log('t')
	
});



//开服列表
var service_ul = $(".service_table ul");
var service_ul_num = $(".service_table ul").size();
var cur_page = 1;

$('.cur_page').html(cur_page+'/'+service_ul_num);
$('.page_next').click(function(){
	if (cur_page<service_ul_num) {
		$(".service_table ul").eq(cur_page-1).hide();
		$(".service_table ul").eq(cur_page).show();
		cur_page++;
		$('.cur_page').html(cur_page+'/'+service_ul_num);
	}
})
$('.page_prev').click(function(){
	if (cur_page>1) {
		$(".service_table ul").eq(cur_page-1).hide();
		$(".service_table ul").eq(cur_page-2).show();
		cur_page--;
		$('.cur_page').html(cur_page+'/'+service_ul_num);
	}	
})

//点赞
$('.down_gift a:nth-child(1)').click(function(){
	$(this).addClass('animated fadeInUp').css({"color":"#ffb018"});
	$(this).next().show().addClass('animated fadeOutUp');
})