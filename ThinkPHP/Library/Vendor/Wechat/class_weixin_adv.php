<?php
/**
 * 微信SDK 
 */
class class_weixin_adv
{
    var $appid = "wxa6fea15278d6e77a";
    var $appsecret = "1d933ea4ddde1da122875951dbc9878d";

    //正式
    // var $appid = "wx05fd5e570f8b7b42";
    // var $appsecret = "16282b078b0f4d0e9b070565fdb1cef1";

  //构造函数，获取Access Token
  public function __construct($appid = NULL, $appsecret = NULL)
  {  	
    if($appid){
      $this->appid = $appid;
    }
    if($appsecret){
      $this->appsecret = $appsecret;
    }
    /*
    
    $this->lasttime = 1395049256;
    $this->access_token = "nRZvVpDU7LxcSi7GnG2LrUcmKbAECzRf0NyDBwKlng4nMPf88d34pkzdNcvhqm4clidLGAS18cN1RTSK60p49zIZY4aO13sF-eqsCs0xjlbad-lKVskk8T7gALQ5dIrgXbQQ_TAesSasjJ210vIqTQ";
    if (time() > ($this->lasttime + 7200)){
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
      $res = $this->https_request($url);
      $result = json_decode($res, true);
      $this->access_token = $result["access_token"];
      $this->lasttime = time();
    }
    
    */
  }

  //通过code换取网页授权access_token
  public function get_access_token($code){
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
		$res = $this->https_request($url);

    // echo "get_access_token:".$res;

    return json_decode($res, true);
  }

  //验证access_token是否过期
  public function check_token($access_token,$openid){
    $url = "https://api.weixin.qq.com/sns/auth?access_token=".$access_token."&openid=".$openid;
    $res = $this->https_request($url);

    // echo "check_token:".$res;

    return json_decode($res, true);
  }

  //刷新access_token
  public function refresh_token($refresh_token){
    $url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=".$this->appId."&grant_type=refresh_token&refresh_token=".$refresh_token;
    $res =  $this->https_request($url);

    // echo "refresh_token:".$res;

    return json_decode($res, true);
  }

  //获取用户基本信息
  public function get_user_info($access_token,$openid)
  {
    //$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
   
    $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
    $res = $this->https_request($url);

    // echo "get_user_info:".$res;
    
    return json_decode($res, true);
  }

//https请求
  public function https_request($url, $data = null)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
  }
}