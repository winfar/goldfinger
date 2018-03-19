<?php
namespace api\Model;
use Think\Model;

/**
 * Class WechatModel
 * @package api\Model
 */
class WechatModel extends Model{
    //获取access_token https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';
    const API_URL_ACCESSTOKON = '/token?grant_type=client_credential&';
    const API_URL_MSG_TEMPLATE_SEND = '/message/template/send?';

    private $token;
    private $encodingAesKey;
    private $encrypt_type;
    private $appid;
    private $appsecret;
    private $access_token;
    public $errCode = 40001;
    public $errMsg = "no access";
    public $logcallback;

    /**
     * WechatModel constructor.
     * @param $options
     */
    public function __construct()
    {
        // $this->token = isset($options['token'])?$options['token']:'';
        // $this->encodingAesKey = isset($options['encodingaeskey'])?$options['encodingaeskey']:'';
        // $this->appid = isset($options['appid'])?$options['appid']:'';
        // $this->appsecret = isset($options['appsecret'])?$options['appsecret']:'';
        // $this->debug = isset($options['debug'])?$options['debug']:false;
        // $this->logcallback = isset($options['logcallback'])?$options['logcallback']:false;

        $this->appid = C('WEIXINPAY_CONFIG')['APPID'];
        $this->appsecret = C('WEIXINPAY_CONFIG')['APPSECRET'];
    }

    /**
     * GET 请求
     * @param $url
     * @return bool|mixed
     */
	private function http_get($url){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}
	/**
	 * POST 请求
	 * @param string $url
	 * @param array $param
	 * @param boolean $post_file 是否文件上传
	 * @return string content
	 */
	private function http_post($url,$param,$post_file=false){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
        if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
                $is_curlFile = true;
        } else {
            $is_curlFile = false;
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
            }
        }
		if (is_string($param)) {
            $strPOST = $param;
        }elseif($post_file) {
            if($is_curlFile) {
                foreach ($param as $key => $val) {
                    if (substr($val, 0, 1) == '@') {
                        $param[$key] = new \CURLFile(realpath(substr($val,1)));
                    }
                }
            }
			$strPOST = $param;
		} else {
			$aPOST = array();
			foreach($param as $key=>$val){
				$aPOST[] = $key."=".urlencode($val);
			}
			$strPOST =  join("&", $aPOST);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST,true);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

    /**
     * 微信api不支持中文转义的json结构
     * @param $arr
     * @return string
     */
    static function json_encode($arr) {
        if (count($arr) == 0) return "[]";
        $parts = array ();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys ( $arr );
        $max_length = count ( $arr ) - 1;
        if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ( $arr as $key => $value ) {
            if (is_array ( $value )) { //Custom handling for arrays
                if ($is_list)
                    $parts [] = self::json_encode ( $value ); /* :RECURSION: */
                else
                    $parts [] = '"' . $key . '":' . self::json_encode ( $value ); /* :RECURSION: */
            } else {
                $str = '';
                if (! $is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (!is_string ( $value ) && is_numeric ( $value ) && $value<2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes ( $value ) . '"'; //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode ( ',', $parts );
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }

    /**
     * 设置缓存，按需重载
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename,$value,$expired){
        //TODO: set cache implementation
        return S($cachename,$value,$expired);
        //return false;
    }

    /**
     * 获取缓存，按需重载
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename){
        //TODO: get cache implementation
        return S($cachename);
        //return false;
    }

    /**
     * 清除缓存，按需重载
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename){
        //TODO: remove cache implementation
        return S($cachename,null);
        //return false;
    }

    /**
     * * 获取access_token
     * @param string $appid 如在类初始化时已提供，则可为空
     * @param string $appsecret 如在类初始化时已提供，则可为空
     * @return bool|string
     */
	public function getAccessToken($appid='',$appsecret=''){
		if (!$appid || !$appsecret) {
			$appid = $this->appid;
			$appsecret = $this->appsecret;
		}
		// if ($token) { //手动指定token，优先使用
		//     $this->access_token=$token;
		//     return $this->access_token;
        // }
        
		$authname = 'wechat_access_token'.$appid;
		if ($rs = $this->getCache($authname))  {
			$this->access_token = $rs;
			return $this->access_token;
        }
        
		$result = $this->http_get(self::API_URL_PREFIX.self::API_URL_ACCESSTOKON.'appid='.$appid.'&secret='.$appsecret);
		if ($result){
			$json = json_decode($result,true);
			if (!$json || isset($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			$this->access_token = $json['access_token'];
			$expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 7200;
			$this->setCache($authname,$this->access_token,$expire);
			return $this->access_token;
		}
		return false;
    }
    
    /**
     * 发送模板消息
     * http请求方式: POST
     * https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=ACCESS_TOKEN
     *
     *｛
     *   "touser":"OPENID",
     *   "template_id":"ngqIpbwh8bUfcSsECmogfXcV14J0tQlEpBO27izEYtY",
     *   "url":"http://weixin.qq.com/download",
     *   "topcolor":"#FF0000",
     *   "data":{}
     * @param array $data 消息结构
     * @return boolean|array
     */
    public function sendTemplateMessage($data){
        recordLog($result,'微信公众号发送模板消息开始');
        if (!$this->access_token && !$this->getAccessToken()) return false;
        recordLog($this->access_token,'微信公众号发送模板消息access_token');
        $result = $this->http_post(self::API_URL_PREFIX.self::API_URL_MSG_TEMPLATE_SEND.'access_token='.$this->access_token,self::json_encode($data));
        recordLog($result,'微信公众号发送模板消息结果');
        if($result){
            $json = json_decode($result,true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }
}
