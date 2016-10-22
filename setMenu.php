<?php
require './WeChat.class.php';
$appID="wx1c6aa21456a83417";
$appsecret="417687840fa6fb2593740b782d468a92";
$ticket='cjqy';
$ak=''; //百度地图密钥
$wechat=new WeChat($appID, $appsecret,$ticket);
$data =<<< JSON
  {
     "button":[
{
           "name":"指令",
           "sub_button":[
            {
               "type":"click",
               "name":"linux",
               "key":"git"
            },
            {
               "type":"click",
               "name":"git",
               "key":"git"
            }]
       }, 
     
      {
           "name":"菜单",
           "sub_button":[
           {	
               "type":"view",
               "name":"搜索",
               "url":"http://www.soso.com/"
            },
            {
               "type":"view",
               "name":"视频",
               "url":"http://v.qq.com/"
            },
            {
               "type":"click",
               "name":"赞一下我们",
               "key":"V1001_GOOD"
            }]
       }, 
       {
            "name": "扫码", 
            "sub_button": [
                {
                    "type": "scancode_waitmsg", 
                    "name": "扫码带提示", 
                    "key": "rselfmenu_0_0", 
                    "sub_button": [ ]
                }, 
                {
                    "type": "scancode_push", 
                    "name": "扫码推事件", 
                    "key": "rselfmenu_0_1", 
                    "sub_button": [ ]
                }
            ]
        }]
 } 
        
JSON;
$wechat->setMenu($data);