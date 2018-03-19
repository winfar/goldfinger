<?php
namespace Shop\Controller;
use Think\Controller;

class BaseController extends Controller {

	public function _empty(){
		//$this->redirect("./Web/index.html");
	}

    protected function _initialize(){

		$wechat_appid = C('WEIXINPAY_CONFIG')['APPID'];
		$wechar_redirect_uri = getHost() . $_SERVER["REQUEST_URI"];//"/shop.php";//$_SERVER["REQUEST_URI"];

		//加密验证
		// $param['channelid'] = I('cid');
		
		$param['sid'] = I('sid');
		$param['avatar'] = I('avatar');
		$param['name'] = I('name');
		$param['cid'] = I('cid');
		$param['time'] = I('time');

		$sign = I('sign');

		// $redisCache = new \Think\Cache\Driver\RedisCache(); 

		if(!empty($param['sid']) && !empty($param['avatar']) && !empty($param['name']) && !empty($param['cid']) && !empty($param['time']) && !empty($sign)){

			$res = $this->validate_sign($param, $sign);

			if($res === true){
				
				$data['uid'] = $param['sid'];
				$data['nickname'] = $param['name'];
				$data['username'] = $param['sid'];
				// $data['phone'] = $param['phone'];
				$data['password'] = '7f916d5410154531d90af271570666dc';
				$data['headimgurl'] = $param['avatar'];	
				$data['channelid'] = $param['cid'];
				
				$this->uid = D('api/User')->addUserInfo($data);

				cookie('uid',$this->uid,array('expire'=>86400,'prefix'=>'bo_','path'=>'/'));

				// $redisCache->set($this->uid, $this->uid, 86400);

				//记录访问日志
				D('api/User')->addUserAccessLog($this->uid);
				
				//开放非正式环境记录日志
				// if(!isHostProduct()){
				// 	D('api/User')->addUserAccessLog($this->uid);
				// }

				if($this->uid == 0){
					echo "用户更新错误";
					exit();
				}
			}
			else{

				// header('Location: ./404.php');
				// exit();
				exit('参数错误');
			}
		} else {
			// $redis_uid = $redisCache->get($this->uid);
			// if (!empty($redis_uid)) {
			// 	$this->uid = $redis_uid;
			// }

			$cookie_uid = cookie('bo_uid');
			if (!empty(cookie('bo_uid'))) {
				$this->uid = cookie('bo_uid');
			}
			else{
				// $this->redirect('Remind/index');
				//header('Location: ./404.php');
				// exit();

				$code = I('code',0);
				if($code == 0){
					$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$wechat_appid."&redirect_uri=".urlencode($wechar_redirect_uri)."&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
					header('Location: '.$url);
					exit();
				}
				else{					
					$oauth_res = A('api/Wechat')->oauth2($code);

					if($oauth_res){
						$userInfo = M('User')->where(['username'=>$oauth_res['openid']])->find();

						if($userInfo){
							$data['id'] = $userInfo['id'];
							$data['nickname'] = $oauth_res['nickname'];
							$data['headimgurl'] = $oauth_res['headimgurl'];	
							M('User')->save($data);

							$this->uid=$data['id'];
						}
						else{
							// $data['uid'] = ;
							$data['nickname'] = $oauth_res['nickname'];
							$data['username'] = $oauth_res['openid'];
							$data['openid'] = $oauth_res['openid'];
							// $data['phone'] = $param['phone'];
							$data['password'] = '7f916d5410154531d90af271570666dc';
							$data['headimgurl'] = $oauth_res['headimgurl'];	
							$data['channelid'] = 1000;
							
							$this->uid = D('api/User')->addUserInfo($data);
						}

						cookie('uid',$this->uid,array('expire'=>86400,'prefix'=>'bo_','path'=>'/'));

						// $redisCache->set($this->uid, $this->uid, 86400);

						//记录访问日志
						// D('api/User')->addUserAccessLog($this->uid);
					}
				}
			}
		}		

		$config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =  config_lists();
            S('DB_CONFIG_DATA',$config);
        }
        C($config);


        // if ( !C('WEB_SITE_CLOSE') ) {
        //     $this->error('站点已经关闭，请稍后访问~');
        // }

        $tpl = C('TMPL_PATH')."/Shop/";
        // $this->uid = 101899;
        // $this->username = '我就是我';
        $this->size = 100;
		$this->goback = 0;
		$this->web_path=__ROOT__."/";
		$this->web_title = C("WEB_SITE_TITLE");
		// $this->web_logo="/".C('TMPL_PATH')."/Web/images/".C("WEB_LOGO");
		$this->web_keywords=C("WEB_SITE_KEYWORD");
		$this->web_description=C("WEB_SITE_DESCRIPTION");
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
		$this->tplpath="./".$tpl;
		$this->web_tplpath=$this->web_path.$tpl;
		// define('UID',is_login());
		// $user_auth=session('user_auth');
		// $this->username=$user_auth['username']; 

		//访问频率限制
		//$this->rate_limit();
		
    }

	private function validate_sign($param,$sign){
		$result = false;

		if(count($param)==0 || empty($sign)){
			return false;
		}
		else{
			$stringToSign = param_signature($_SERVER['REQUEST_METHOD'],$param);

			$stringToSign = str_replace("+", " ",$stringToSign);

			if($sign){
				if($sign == $stringToSign){
					$result = true;
				}
			}

			return $result;
		}
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
