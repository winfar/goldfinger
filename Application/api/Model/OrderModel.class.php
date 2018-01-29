<?php
namespace api\Model;

use Think\Model;

class OrderModel extends Model {

	private $app_key;//应用的app_key
	private $app_secret;//即创建应用时的Appsecret（从JOS控制台->管理应用中获取）

	private $expires_in;//失效时间（从当前时间算起，单位：秒）
	private $access_token;//JOS 所回传的access_token值
	private $refresh_token;//即授权时获取的刷新令牌
	private $time;//授权的时间点（UNIX时间戳，单位：毫秒）

	private $jd_client ;
	private $server_url;
	private $flag_test  = true;

//    public function __construct()
//    {
//        Vendor('Jos.jd.JdClient');
//        $model = new JosModel();
////        $res = $model->getData();
////        $info = $res[0];
//        $info ['app_key'] = 'E2Yh6MbtTBTlnKaRCymq';
//        $info ['app_secret'] = 'O1AQEFuieTrBFHSVpdZK';
//        $info ['expires_in'] = '86400';
//        $info ['access_token'] = '';
//        $info ['refresh_token'] = '';
//        $info ['time'] = '';
//
//
//        $this->app_key = $info['app_key'];
//        $this->app_secret = $info['app_secret'];
//        $this->expires_in = $info['expires_in'];
//
//        $this->access_token = $info['access_token'];
//        $this->refresh_token = $info['refresh_token'];
//        $this->time = $info['time'];
//
//        $this->jd_client = new \JdClient();
//        $this->server_url = "https://api.jd.com/routerjson";

//    }


	public function oauth(){

		$code = $_GET['code'];
		$appKey = 'E2Yh6MbtTBTlnKaRCymq';
		$appSecret = 'O1AQEFuieTrBFHSVpdZK';
		$url = "http://www.xxxx.com/m/Jos/oauth.html";

		$toUrl ="https://oauth.jd.com/oauth/token?grant_type=authorization_code&client_id="
			.$appKey
			."&client_secret="
			.$appSecret ."&scope=read&redirect_uri="
			.$url."&code="
			.$code."&state=1234";

		if(!$code){
			//数据处理 此处其实是无法处理数据的，你问我，我问谁去啊？！！！
			echo 'hahahahhahahahah';

		}else{
			header("Location:".$toUrl);
		}
	}

	public function test(){
		$appKey = 'DExxxxxxxxxxxxxxxxxxxxxx83';
		$url = 'http://www.xxxx.com/m/Jos/oauth.html';
		$toUrl = 'https://oauth.jd.com/oauth/authorize?response_type=code&client_id='
			.$appKey.'&redirect_uri='
			.$url.'&state=123';

		header("Location:".$toUrl);
	}

	/**
	 * 将获取到的token等信息 添加到数据库  下面的为获取的其中一次数据 注意时效性
	 */
	public function addData(){
		$data = array();
		$data['access_token'] = '24xxxxxxxxxxxxxxxxxxxxae0';
		$data['expires_in'] = '24xxxxxxxxxxxxxxxxxxxxxxxxe0';
		$data['refresh_token'] = 'edxxxxxxxxxxxxxxxxxxxxxxxxxxx0f';
		$data['time'] = '14xxxxx87475';

		$model = new JosModel();
		$res = $model->addData($data);
		echo $res;
	}

	/**
	 * 查询京东快递物流跟踪信息
	 */
	public function getTrace(){
		//获取订单号
		//$waybillCode = $_POST['waybillCode'];

		//事例京东订单号
		$waybillCode = "23457562180";
		//https://api.jd.com/routerjson  注：以后统一都使用https方式调用，之前使用http方式的请尽快切换一下入口地址。
		Vendor('Jos.jd.request.EtmsTraceGetRequest');
		$this->jd_client->appKey = $this->app_key;
		$this->jd_client->appSecret = $this->app_secret;
		$this->jd_client->accessToken = $this->access_token;
		$this->jd_client->serverUrl = $this->server_url;//SERVER_URL;
		$req = new \EtmsTraceGetRequest();

		$req->setWaybillCode($waybillCode);
		$resp = $this->jd_client->execute($req, $this->jd_client->accessToken);

		var_dump($resp);
	}

	/**
	 * 360buy.order.get      获取单个订单
	 */
	public function getSingleOrder(){
		Vendor('Jos.jd.request.OrderGetRequest');

		$this->jd_client->appKey = $this->app_key;
		$this->jd_client->appSecret = $this->app_secret;
		$this->jd_client->accessToken = $this->access_token;
		$this->jd_client->serverUrl = $this->server_url;
		$req = new \OrderGetRequest();
		//事例京东订单号
		$waybillCode = "23457562180";

		$req->setOrderId($waybillCode);
		//$req->setOptionalFields( "jingdong" );
		//$req->setOrderState( "jingdong" );
		$resp = $this->jd_client->execute($req, $this->jd_client->accessToken);
		var_dump($resp);

	}

	public function getAccesstoken(){
		// https://bizapi.jd.com/oauth2/accessToken
//        grant_type=access_token&client_id=E2Yh6MbtTBTlnKaRCymq&username=bjbszx2017&password=745404feaba9fb037e01b4a91c6ddbeb×tamp=2017-01-13 11:01:46&sign=29EE3DD5A199987AA3C4EB59F43600DD
		$grant_type='access_token';
		$client_id='E2Yh6MbtTBTlnKaRCymq';
		$clientSecret = 'O1AQEFuieTrBFHSVpdZK';
		$username='bjbszx2017';
//        $password  = md5('jd123456') ;
		$password='745404feaba9fb037e01b4a91c6ddbeb';
		$timestamp  = date('Y-m-d H:i:s');
		$sign = $clientSecret . $timestamp.$client_id.$username.$password.$grant_type.$clientSecret;
		//echo  '<br>sign->'.$sign;
		//2.	将上述拼接的字符串使用MD5加密，加密后的值再转为大写
		$sign = strtoupper(md5($sign));
		$url = 'https://bizapi.jd.com/oauth2/accessToken';

		$post_field  = array('grant_type'=>$grant_type,'client_id'=>$client_id,'username'=>$username,'password'=>$password,'timestamp'=>$timestamp,'sign'=>$sign);
//        $data ="grant_type=access_token" .
//            "&client_id=" .$client_id.
//            "&username=" .$username .
//            "&password=".$password.
//            "&timestamp=" . $timestamp .
//            "&sign=".$sign;
		$data =  post($url ,$post_field);

		$jtoken = json_decode($data['data']);
//        var_dump($jtoken);
		if($jtoken && $jtoken->data->resultCode = '0000'){
			$access_token =  $jtoken->result->access_token;
			return $access_token;
		}
		return '';
	}

	/**
	 * 订单物流查询
	 * @param string $jdOrderId
	 * @throws \Exception
	 */
	public function orderTrack($jdOrderId = ''){
		$access_token = $this->getAccesstoken();
		$url = 'https://bizapi.jd.com/api/order/orderTrack';
		$data =  post($url,array('token'=>$access_token,'jdOrderId'=>$jdOrderId));
		if($this->flag_test){
			$data = $this->getMockorderTrack();
		}
		$jdata = json_decode($data) ;
		if($jdata->success ){
			$jresult = $jdata->result;
			if($jresult){
				$_jdOrderId =  $jresult->jdOrderId;
				$_orderTrack =  $jresult->orderTrack;
				if($_orderTrack){
					$_orderTrack = array_reverse($_orderTrack);
//					var_dump($_orderTrack);
					return $_orderTrack;
				}
			}
		}

		return array();
	}

	/**
	 * 订单物流收寄状态查询
	 * 7.7 查询京东订单信息接口
	 * https://bizapi.jd.com/api/order/selectJdOrder
	 * @param string $jdOrderId
	 * @throws \Exception
	 */
	public function selectJdOrder($jdOrderId = ''){
		$access_token = $this->getAccesstoken();


		$url = 'https://bizapi.jd.com/api/order/selectJdOrder';
		$data =  post($url,array('token'=>$access_token,'jdOrderId'=>$jdOrderId));
		if($this->flag_test){
			$data = $this->getMockselectJdOrder();
		}
		$jdata = json_decode($data) ;

		$_state = '';
		$_orderState = '';
		$_submitState = '';
//		var_dump($data);
		if($jdata->success ){
			$jresult = $jdata->result;
			if($jresult){
				$_state =  $jresult->state ; // state		物流状态 0= 是新建  1=是妥投   2=是拒收

				$_orderState =  $jresult->orderState ;
				$_submitState =  $jresult->submitState ;
				return $_state;
			}
		}

		return 0;

	}

	protected function getMockselectJdOrder(){
		return '{
    "success": true,
    "resultMessage": "",
    "resultCode": "0000",
    "result": {
        "pOrder": 0,
        "orderState": 1,
        "jdOrderId": 42596254319,
        "state": 1,
        "freight": 0,
        "submitState": 0,
        "orderPrice": 124.5,
        "baseFreight ": "基础运费",
        "orderNakedPrice": 106.41,
        "sku": [
            {
                "skuId": 852431,
                "num": 1,
                "category": 690,
                "price": 124.5,
                "name": "罗技（Logitech） M545 无线鼠标 黑色",
                "tax": 17,
                "taxPrice": 18.09,
                "nakedPrice": 106.41,
                "type": 0,
                "oid": 0
            },
            {
                "skuId": 852431,
                "num": 1,
                "category": 690,
                "price": 0,
                "name": "罗技（Logitech） M545 无线鼠标 黑色",
                "tax": 0,
                "taxPrice": 0,
                "nakedPrice": 0,
                "type": 2,
                "oid": 852431
            }
        ],
        "type": 2,
        "orderTaxPrice": 18.09
    }
}';
	}

	protected function getMockorderTrack(){
		return '{"success": true,"resultMessage": "", "resultCode": "0000","result":{" jdOrderId ":"111111","orderTrack":'.
		'[{"msgTime":"2013-09-25 09:03:53","content":"您提交了订单，请等待系统确认", "operator":"客户"},'.
		'{"msgTime":"2013-09-26 10:05:53", "content":"您的货物已到达北辰自提点，请上门自提", "operator":"代小娟"},'.
		'{"msgTime":"2013-09-25 09:04:22", "content":"您的订单已经进入北京1号库准备出库，不能修改", "operator":"系统" },'.
		'{"msgTime":"2013-09-25 09:04:22", "content":"您的订单已经从北京1号库发出，不能修改", "operator":"系统" },'.
		'{"msgTime":"2013-09-25 09:04:22", "content":"到达朝阳区，不能修改", "operator":"系统" },'.
		'{"msgTime":"2013-09-25 09:04:22", "content":"快递员从站点出发", "operator":"系统" },'.
		'{"msgTime":"2013-09-25 09:04:22", "content":"已签收,欢迎下次光临", "operator":"系统" }]}}';
	}
}