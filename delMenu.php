<?php
require './WeChat.class.php';
$appID="wx1c6aa21456a83417";
$appsecret="417687840fa6fb2593740b782d468a92";
$ticket='cjqy';
$ak=''; //百度地图密钥
$wechat=new WeChat($appID, $appsecret,$ticket);
$wechat->delMenu();

