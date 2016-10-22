<?php
class WeChat{
    private $appID;
    private $appsecret;
    private $token;
    private $ticket;//自己的票据
    const QR_SCENE=1;   //临时二维码
    const QR_LIMIT_SCENE=2;   //永久id标识二维码
    const QR_LIMIT_STR_SCENE=3;  //永久字符串标识二维码

    public function __construct($appID,$appsecret,$ticket) {
        $this->appID=$appID;
        $this->appsecret=$appsecret;
        $this->ticket=$ticket;
    }
    
    /**
     * 获取token
     */
    private function getToken(){
        
        //如果token写入文件从文件中读取
        if(file_exists('./token') && time()-  filemtime('./token')<7200){
            return file_get_contents('./token');
        }
        //请求url获取token
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appID}&secret={$this->appsecret}";
        $obj=  json_decode($this->getCurl($url));
        file_put_contents('./token', $obj->access_token);
        return $obj->access_token;      
    }
    
    /**
     * 获取二维码ticket
     * @param int $id 二维码标识id
     * @param int $type 二维码类型
     * @param int $expire  二维码有效时间 ----$type=1
     */
    private function  getQR($id,$type=2,$expire=604800){
        $access_token=$this->getToken();
        $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
        $type_list=array(
            self::QR_SCENE  =>'QR_SCENE',
            self::QR_LIMIT_SCENE=>'QR_LIMIT_SCENE',
            self::QR_LIMIT_STR_SCENE=>'QR_LIMIT_STR_SCENE'
        );
        //json数据  POST数据例子：{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}
        $data['action_name']=$type_list[$type];
        switch ($type){
            case 1:
            $data['expire_seconds']=$expire;
            $data['action_info']['scene']['scene_id']=$id;
            break;
            case 2:
            $data['action_info']['scene']['scene_id']=$id;
            break;
            case 3:
            $data['action_info']['scene']['scene_str']=$id;
        }
       
       $obj=json_decode($this->getCurl($url,  json_encode($data)));
       return $obj->ticket;
       
        
    }
    
    /**
     * 获取二维码
     * @param int $id 二维码标识id
     * @param int $type 二维码类型
     * @param int $expire  二维码有效时间 ----$type=1
     */
    public function QR($id,$type=2,$expire=604800){
        $ticket=$this->getQR($id,$type,$expire);
        $ticket=  urlencode($ticket);
      
        $url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
        return $this->getCurl($url);
    }
    
    /**
     * curl请求
     */
    public function getCurl($url,$data=''){
        $curl=  curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        /******必须添加，否则出现41500错误*******/
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
       
        #设置重定向
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
       
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
       
        if($data){
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            
        }
        //curl请求错误
        if(!$res=curl_exec($curl)){
            var_dump(curl_error($curl),  curl_errno($curl));
            exit;
        }
        return  $res;
        
    }
    
    /**
     * 接受微信公众平台的消息推送
     */
    public function response(){
        //获取微信信息
        $postStr=$GLOBALS['HTTP_RAW_POST_DATA'];
        if (!empty($postStr)){
                //将post转化为simplexml对象
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);           
        }
        switch($postObj->MsgType){
            case 'event':
                switch($postObj->Event){
                    case 'subscribe':
                         $this->_doSub($postObj);
                    case 'CLICK':
                        $content=  file_get_contents('./text/git');
                        $this->_doText($content, $postObj);
                        
                }
                
                                 
            break;
            case 'text':
                if('图片'==$postObj->Content){
                    $media_id="Q7_YeVF2qGFr6yRnOE9GiGtjBjmBhPRh1fuJ-jz0y_T-YHXuprjJ8ALcvqQE9xXB";
                    $this->_doImage($postObj,'',$media_id);
                    break;
                }
                if('新闻'==$postObj->Content){
                    $item=array(
                        array('第一条','新闻一','http://www.weixin.cjqy.pub/1.jpg','www.chinanews.com/gn/2016/10-22/8040251.shtml'),
                        array('第二条','新闻二','http://www.weixin.cjqy.pub/2.jpg','http://www.chinanews.com/gn/2016/10-22/8040251.shtml'),
                    );
                    $this->_doNews($postObj, $item);
                    break;
                }
                if('git'==$postObj->Content)
                    $content=  file_get_contents('./text/git');
                else {
                    $content='暂时没有该服务哦';
                }    
                $this->_doText ($content, $postObj);               
            break;
            case "location":
                $this->_doLocation($postObj);
                break;
        }
               
    }
    
    /**处理订阅时间
     * 
     */
    public function _doSub($obj){
        $content='欢迎光临，您可以根据菜单中的选项获取想应服务';
        $this->_doText($content,$obj);
    }
    
    /**处理位置事件
     * 
     */
    private function _doLocation($obj){      
            $url="http://api.map.baidu.com/place/v2/search?query=建设银行&page_size=10&page_num=0&scope=1&location={$obj->Location_X},{$obj->Location_Y}&radius=2000&output=json&ak=yhe0lGeussLyg6e3EhraXys3PaelddQz";
            $res=  json_decode($this->getCurl($url));
            
            $res=$res->results;
            $address=$res[count($res)-1]->address;
            $this->_doText("离你最近的建设银行在$address", $obj);
    }
    
    /**
     * 回复文本时间
     */
    private function _doText($content,$obj){
        $spl="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
           $str=  printf($spl,$obj->FromUserName,$obj->ToUserName,time(),$content);
           echo $str;
    }
    
    
    /**
     * 首次验证url合法性
     */
    public function firstValid(){
        if($this->_check()){
            echo $_GET['echostr'];
        }
      
    }
    
    /**
     * 验证请求是否合法
     */
    private function _check(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token=$this->ticket;
      
        $tmpArr = array($token, $timestamp, $nonce);
	sort($tmpArr, SORT_STRING);
	$tmpStr = implode( $tmpArr );
	$tmpStr = sha1( $tmpStr );
	
	if( $tmpStr == $signature ){
		return true;
	}else{
		return false;
	}
    }
    
    /**
     * 上传图片素材
     */
     public function uploadFile($file='./test.jpg',$type='image',$temp=1){
        if($temp)
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$this->getToken()}&type=$type";
        else{
            $url="https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$this->getToken()}&type=$type";
        }
        #发送数据
        $data['media']='@'.$file;    
       return json_decode($this->getCurl($url,$data))->media_id;
           
      }
      
      /**
       * 回复图片消息
       * @param string filename 回复文件的地址
       * @param  string $media 图片的id标识
       */
      public function _doImage($obj,$filename,$media){
          $tpl="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[image]]></MsgType>
            <Image>
            <MediaId><![CDATA[%s]]></MediaId>
            </Image>
            </xml>";
          if(!$media){
              $media=$this->uploadFile($filename);
          }
          $msg=  sprintf($tpl,$obj->FromUserName,$obj->ToUserName,time(),$media);
          echo $msg;
          
      }
      
      /**
       * 回复图文消息
       * 
       */
      private function _doNews($obj,$item=array()){
          $tpl="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>
             %s
            </Articles>
            </xml>";
          $item_tpl=" <item>
            <Title><![CDATA[%s]]></Title> 
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
          #拼凑item
          foreach($item as $k=>$v){
              $items.=sprintf($item_tpl,$v[0],$v[1],$v[2],$v[3]);
          }
          echo sprintf($tpl,$obj->FromUserName,$obj->ToUserName,time(),count($item),$items);
      }
      
      /**
       * 删除菜单
       */
      public function delMenu(){
          $url="https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$this->getToken()}";
          $obj=json_decode($this->getCurl($url));
          echo $obj->errmsg;
          
      }
      
      /**
       * 设置菜单
       * 设置的菜单，json字符串
       */
      public function setMenu($data){
          $url= "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$this->getToken()}";
          $obj=json_decode($this->getCurl($url,$data));
          echo $obj->errmsg;
      }
}
