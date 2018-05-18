<?php
namespace Shop\Controller;
class TestWyController extends \Think\Controller {

    public function index($p=1,$num=20){
        // echo json_encode(intval(getNextSsc()['lottery_issue']));
  		// echo date('Y-m-d H:i:s',$kj_time).'#'.$issue;	
        // $r = get_timestamp();
        // echo $r;

        //用户提金
        // $r = D('api/Gold')->draw(1587,20,1);//用户id，提金数量(g)，商品id
        // echo json_encode($r);

        // $r = D('api/JuHe')->getAuShangHai();
        // echo json_encode($r);

        /*
        $str = urldecode('version=0.85&r0=10,50,100&r1=10,50,100&sid=1977525&st=1&isd=true&roomId=2041977&avatar=http://diyimg.wopaitv.com/data/userHeadPic/2017-01-05/2017-01-05_163714_file.jpg&gameType=1&time=1490173242721&name=%E5%8D%97%E5%9C%A8%E5%8C%97%E6%96%B9&gender=1');
        $parts = [];
        $param = explode('&',$str);
        foreach ($param as $key => $value) {
            $v = explode('=',$value);
            $parts[$v[0]]=$v[1];
        }

        // var_dump($parts);
        echo param_signature("GET", $parts, "08DD1A44B8426B65");
        // exit($param);
        // exit();
        */

        $param['appid'] = 'busonline';
		if(empty($uid))$uid  = 1597;//TODO 固定的用户ID
		$param['uid'] =  $uid ;
		$param['time'] = time().'000';
		$sign = param_signature('GET',$param);
		$param['sign'] = $sign;

        $parts = [];
        foreach ($param as $key => $value) {
            array_push($parts,($key.'='.$value));
        }

        echo "http://apigametest.busonline.cn:16789/api/getcoins?" . join('&',$parts);

        /*
        $param['version'] = 0.85;
        $param['r0'] = "10,50,100";
        $param['r1'] = "10,50,100";
        $param['sid'] = 1977525;//推送类型：1-商城消息
        $param['st'] = 1;//推送人ID，可传多个，用英文逗号分隔（限制50个ID）
        $param['title'] = "testTitle";//推送标题，长度小于16个字符（汉字、字母均算作一个字符）
        $param['description'] = "testDescription";//推送文本，长度小于128个字符（汉字、字母均算作一个字符）
        $param['passThrough'] = 1;//是否透传消息，1表示透传消息，0表示通知栏消息
        //$param['delayed'] = $uid;//延时推送，单位秒（如需实时推送不传此参数）
        $param['signature'] = urlencode(param_signature("GET", $param, "23049SDKFJ98FDF"));

        $parts = [];
        foreach ($param as $key => $value) {
            array_push($parts,($key.'='.$value));
        }
        echo "http://test-app.wopaitv.com/api/push/sendThirdMsg?" . join('&',$parts);
        //echo "http://test-app.wopaitv.com/api/push/sendThirdMsg?platformId=".$param['platformId']."&"
		// exit(json_encode($param));
*/

        // echo 'test';
        // $a = C('TMPL_CACHE_ON');
        // var_dump($a);
        
        // $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        // $pid = I('pid');
        // $sid = I('sid');
        // $price = I('price');
        // $type = I('type');
        // $gold = I('gold');
        // $list = D('api/Pay')->pay($pid,$sid,$price,$uid,$type,$gold);

        //$r = base64_encode(hash_hmac('sha1',"GET&avatar%3Dhttp%3A%2F%2Fbvcs4dev.oss-cn-beijing.aliyuncs.com%2Fdata%2FuserHeadPic%2F2015-01-12%2F2015-01-12_101553_1421028949185.png%26name%3Dken%26sid%3D28%26time%3D1484304418742",'08DD1A44B8426B65',true));

        // $r = base64_encode('F75434F6475328BB083EEAFD7A3F959178DED24B');
        // echo '===>' . $r;

        // Current time
        // echo date('h:i:s') . "\n";
        // sleep(20);
        // echo date('h:i:s') . "\n";

        // $param['platformId'] = "2";
        // $param['nonce'] = strval(time())."000";
        // $param['uid'] = 1573;
        // $param['signature'] = urlencode(param_signature("GET", $param,"23049SDKFJ98FDF"));

        // echo json_encode($param);

        //  $liveUser = json_decode('{"requestId":"102865696195001786522232997","code":"1000","msg":"操作成功","result":{"bandPhone":"18610273478","homePic":"","id":7001006,"isAnchor":0,"name":"一杯浊酒","organizationId":0,"phone":"","pic":"/userHeadPic/2017-01-15/2017-01-15_012754525.jpg","username":"wechatomnOVt7KeooOiLp-isbtZolPwMLM"}}',true);

        //  echo $liveUser['result']['bandPhone'];

        // $r = D('api/PlatformApi')->getExpInfo(1587,10000);
        // echo json_encode($r);

        // $code=1234;
        // $session = array();
        // if($id) {
        //     $session[$id]['verify_code'] = $code; // 把校验码保存到session
        //     $session[$id]['verify_time'] = NOW_TIME;  // 验证码创建时间
        // } else {
        //     $session['verify_code'] = $code; // 把校验码保存到session
        //     $session['verify_time'] = NOW_TIME;  // 验证码创建时间
        // }
        // cookie('abcd', $session);

        // echo json_encode(cookie('abcd'));
        
        // $this->display($this->tplpath."test_wy.html");

    }

    public function validate_sign(){
        
        //http://onlinetest.oneshop.busonline.com/shop.php?sid=28&name=ken&avatar=http://bvcs4dev.oss-cn-beijing.aliyuncs.com/data/userHeadPic/2015-01-12/2015-01-12_101553_1421028949185.png&time=1484303423957&sign=7/7aY788uoxUf9wbGt13aJ/puHc=
		$result = false;

		$param['sid'] = I('sid');
		$param['avatar'] = I('avatar');
		$param['name'] = I('name');
		$param['time'] = I('time');

        $sign = I('sign');

        if(APP_DEBUG){
            $param['sid'] = 28;
            $param['name'] = 'ken';
            $param['avatar'] = 'http://bvcs4dev.oss-cn-beijing.aliyuncs.com/data/userHeadPic/2015-01-12/2015-01-12_101553_1421028949185.png';
            $param['time'] = '1484304418742';
            $sign = '91Q09kdTKLsIPur9ej+VkXje0ks=';
        }

		$stringToSign = param_signature($_SERVER['REQUEST_METHOD'],$param);

		if($sign){
			if($sign == $stringToSign){
				$result = true;
			}
		}

        if(APP_DEBUG){
            echo '<br>error' + $result;
        }
		return '<br>error' .$result;
	}

	public function send(){
        // echo 'send';
    //    A('api/Wechat')->sendTplMsgRecharge(['uid'=>7001010,'cash'=>20,'msg'=>'充值成功']);

    //    $r = A('api/Wechat')->sendTplMsgRigister(28,80);

       returnJson($r);
    }

    public function testadduser(){
        $data['nickname'] = 'nickname';
        $data['username'] = 'openid'.rand(1000,9999);
        $data['openid'] = 'openid';
        // $data['phone'] = $param['phone'];
        $data['password'] = '7f916d5410154531d90af271570666dc';
        $data['headimgurl'] = 'headimgurl';	
        $data['channelid'] = 1000;
        
        // $uid = D('api/User')->addUserInfo($data);
        echo $uid;

        $events_rs = D('api/Events')->activate('register',7001010);

        echo $events_rs;
    }

}