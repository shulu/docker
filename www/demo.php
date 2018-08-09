<?php

	
	$redis = new redis();
	print_r($redis->connect('192.168.99.100', '6379'));

	$str = '/var/www/html/bg_www/gbeta6/applications/banggood/bg_os';
	echo str_replace ('/bg_os', '', $str);