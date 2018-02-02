<?php
namespace api\Controller;
use Think\Controller;

class BaseController extends Controller {

	public function _empty(){
		//$this->redirect("./Web/index.html");
	}

    protected function _initialize(){
		
		// $header_app = $_SERVER['HTTP_APPVERSION'];		
		
		// $passUris = array();
		// $passVersions = array();

		// array_push($passUris
		// 	,'ping/payhooks'
		// 	,'ipay/payhooksios'
		// 	,'ipay/payhooksandroid'
		// 	,'ipay/payhooksh5'
		// 	,'shengpay/payhooks'
		// 	,'myuser/emailvalidate'
		// 	,'notification/winningsendcode'
		// 	,'notification/sendnotificationbypid'
		// 	,'public/winningsendcode'
		// 	,'public/orderurl'
		// 	,'public/getconfig'
		// 	,'wechat/oauth2'
		// 	,'user/oauth2'
		// 	,'usertest/pkdissolve'
		// );

		//array_push($passVersions
			// ,md5('1.7' . APP_PRIVATE_KEY)
			// ,md5('1.7.34' . APP_PRIVATE_KEY)
			// ,md5('1.7.40' . APP_PRIVATE_KEY)
			// ,md5('1.7.50' . APP_PRIVATE_KEY)
			// ,md5('1.7.55' . APP_PRIVATE_KEY)
			// ,md5('1.7.58' . APP_PRIVATE_KEY)
			// ,md5('1.7.59' . APP_PRIVATE_KEY)
			// ,md5('1.7.60' . APP_PRIVATE_KEY)
			// ,md5('1.7.70' . APP_PRIVATE_KEY)
			// ,md5('1.7.71' . APP_PRIVATE_KEY)
			// ,md5('1.7.80' . APP_PRIVATE_KEY)
			// ,md5('1.7.81' . APP_PRIVATE_KEY)
			// ,md5('1.7.82' . APP_PRIVATE_KEY)
		//);			
		//巴士互娱微信公众号使用版本1.7.81，如果不兼容本版本，请通知巴士互娱人员更新最新的APPVERSION		

		//85167e3a11e85f255d2596073829382a  1.7
		//475d514697e0aee1d56c24a1332a3cd8  1.7.34
		//1c33160437ce04d180cb76248d86eaad  1.7.40
		//cf1a03f1f8095a6d72e5c6487fe83f69  1.7.50
		//a8f6cbbc81bed5b9f8c91df9b7877548  1.7.55
		//5ab42ed908c353d32f3a37556a09e25c  1.7.58
		//737ce284b9e5ba4550978db1caa2dc98  1.7.59
		//3c97cd5d3bd3bc303cb33ee8989e3d8a  1.7.60
		//917bd41707e0a6289f8db92c8a0e3c41  1.7.70
		//2dced1352b36b19f1462f1bdba849611  1.7.71	
		//a390f5831548ec38fdd48d0e8d9e0e5c  1.7.80
		//d9b8e53e21b8729508eb381606be0252  1.7.81		

		// $path = strtolower($_SERVER['PATH_INFO']);
		// $pathflag = false;
		
		//排除不检测版本的uri
		// foreach ($passUris as $key => $value) {
		// 	if(strpos($path,$value) === 0){
		// 		$pathflag = true;
		// 	}
		// }

		//如果访问地址不在通过列表中，则判断APPVERSION参数是否兼容
		// if(!$pathflag){
		// 	//如果版本不兼容
		// 	if(!in_array($header_app,$passVersions)){
		// 		returnJson($header_app, 999, '重磅新增【PK专场】【达人榜】，赶快更新，让你离达人梦想更近！');
		// 	}
		// }		
		
		//检查用户状态（是否冻结）
		// $input_data = json_decode(file_get_contents("php://input"), true);

		// if ( !isEmpty($input_data['tokenid']) ) {
		// 	$lUser = isLogin($input_data['tokenid']);
		// 	if($lUser){
		// 		if(!$this->checkUserStatus($lUser['uid'])){
		// 			returnJson($header_app, 998, '账户被冻结，请过10分钟后尝试或联系客服进行解决。');
		// 		}
		// 	}
		// }

		$config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =  config_lists();
            S('DB_CONFIG_DATA',$config);
        }
        C($config);

        C('CACHE_PATH',RUNTIME_PATH."/Cache/".MODULE_NAME."/Web/");

        // if ( !C('WEB_SITE_CLOSE') ) {
        //     $this->error('站点已经关闭，请稍后访问~');
        // }

		// $this->web_path=__ROOT__."/";
		// $this->web_title=C("WEB_SITE_TITLE");
		// $this->web_logo="/".C('TMPL_PATH')."/Web/images/".C("WEB_LOGO");
		// $this->web_keywords=C("WEB_SITE_KEYWORD");
		// $this->web_description=C("WEB_SITE_DESCRIPTION");
		// $this->web_icp=C("WEB_SITE_ICP");
		// $this->web_url=C("WEB_URL");
		// $this->web_currency=C("WEB_CURRENCY");
		// $this->wx_pay=C('WX_PAY_MCHID');
        // $this->ali_pay=C('ALI_PAY_PARTNER');
        // $this->band_pay=C('BAND_PAY_MID');
        // $this->yun_pay=C('YUN_PAY_ID');
        // $this->pay_pal=C('PAY_PAL');
		// $this->web_time=NOW_TIME;
		// activity(3,'',UID);
		// $this->tplpath="./".C('TMPL_PATH')."/Web/";
		// $this->web_tplpath=$this->web_path.C('TMPL_PATH')."/Web/";
		// define('UID',is_login());
		// $user_auth=session('user_auth');
		// $this->username=$user_auth['username']; 

		//接口访问频率限制
		// $this->rate_limit();
    }

	/**
	* 接口访问频率限制
	* @return [json]             [description]
	*/
	private function rate_limit(){
		if($_SERVER['REQUEST_METHOD']!='POST' && $_SERVER['REQUEST_METHOD']!='GET'){
			return;
		}

		$ip_address = getIP();
		$uri = strtolower($_SERVER['PATH_INFO']);
		//$key = $ip_address.':'.$uri.':'.time();

		if(strpos($uri,strtolower('myuser/sendcode'))==0){

			$mobile = I('mobile'); 

			if(!empty($mobile)){

				$ip_limit = $this->getRedisValByKey($ip_address);
				if($ip_limit > 0){
					returnJson('',1558,'亲，您操作太快了，休息几天吧');//短信超出频率限制
				}
				
				$key_sendcode = $ip_address.'/'.$uri;
				$rs = $this->rate_limit_rule($key_sendcode,50,86400);//当前url每个ip每天调用的次数

				if($rs === false){
					$rss = $this->rate_limit_rule($ip_address,1,864000);//当前url每个ip每天调用的次数
					if($rss === false){
						returnJson('',1559,'亲，您操作太快了，稍微休息一下');//短信超出频率限制
					}
					returnJson('',1551,'亲，您操作太快了，稍微休息一下');//短信超出频率限制
				}

				$key_sendcode = $uri.'/'.$mobile;
				$rs = $this->rate_limit_rule($key_sendcode,20,86400);

				if($rs === false){
					returnJson('',1551,'亲，您操作太快了，稍微休息一下');//短信超出频率限制
				}
			}
		}
		
		$key = $ip_address.$uri;

		// $rs = $this->rate_limit_rule($key,5,1);

		$rs_range = $this->rate_limit_rule($key.'/range',100,60);

		//if($rs === false || $rs_range === false){
		if($rs_range === false){			
			returnJson('',550,'亲，您操作太快了，稍微休息一下');//接口超出频率限制
		}
	}  

	protected function rate_limit_rule($key,$limit_times,$expire){
		$redisCache = new \Think\Cache\Driver\RedisCache();

		$rs_value = $redisCache->get($key);

		if($rs_value != null && $rs_value + 1 > $limit_times){
			return false;
		}
		else{
			if($rs_value == null){$rs_value=0;}
			$redisRs = $redisCache->set($key,$rs_value+1,$expire);//限制每个ip，每个接口，每秒钟，调用的次数
			return $rs_value;
		}
	}

	protected function getRedisValByKey($key){
		$redisCache = new \Think\Cache\Driver\RedisCache();
		$rs_value = $redisCache->get($key);
		return $rs_value;
	}

	protected function getHttpHeader(){

		$headers = array(); 
		foreach ($_SERVER as $key => $value) { 
			if ('HTTP_' == substr($key, 0, 5)) {
				$headers[str_replace('_', '-', substr($key, 5))] = $value; 
			} 
		}
	}
	
	protected function checkUserStatus($uid){

		if($uid > 0 ){
			$User = D('User');
			return $User->getUserStatus($uid);
		}
		return false;
	}
}
