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
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
       
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
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
            
}
