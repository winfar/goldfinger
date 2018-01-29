<?php
namespace api\Controller;
use Think\Controller;
class UserController extends BaseController
{

    protected $Quath2Url = 'http://1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w';// 回调地址
    protected $passportUrl = 'https://passport.busonline.com';//接口地址
    protected function _initialize()
    {
        parent::_initialize();
        vendor("weixin.class_weixin_adv");
    }

    /**
     * @deprecated 发送手机验证码接口
     * @author wenyuan
     * @date 20161201
     **/
    public function smsSendCode($mobile,$sign)
    {   
        if(empty($mobile) || empty($sign)){
            returnJson('', 401, '参数错误');
        }

        if($sign != md5(APP_PRIVATE_KEY . $mobile . date('Ymd'))){
            returnJson('', 402, '参数错误');
        }

        $rs = D('user')->smsSendCode($mobile);

        if($rs){
            returnJson($rs, 200, 'success');
        }
        else{
            returnJson($rs, 410, '短信发送失败');
        }
    }

    /**
     * @deprecated 登录
     * @author zhangkang
     * @date  2016-7-5
     **/
    public function login()
    {
        $result = file_get_contents('php://input');   
        //file_get_contetns  1:读取POST数据 2：不能用于multipart/form-data类型  3
        
        
        recordLog($result, 'login');//记录
        $json = json_decode($result, true);
		
        //$json['credential'] = '123456';
		
        $param = array();
        $param['code'] = $json['code'];
        $param['identity'] = $json['identity'];//身份  (username,email,phone,wechat,qq,weibo...)
        $param['identifier'] = $json['identifier'];//标识  (username,email,phone,thirdpatyopenid...)
        $param['credential'] = $json['credential'];//凭证(password,token)
        $param['deviceid'] = $json['deviceid'];//邮编地址
        $param['regid'] = $json['regid'];
        $param['imei'] = $json['imei'];//国际移动电话设备识别码（International Mobile Equipment Identity）；手机串号
        $param['os'] = $json['os'];//设备
        $param['osversion'] = $json['osversion'];//版本号
        $param['brand'] = $json['brand'];//商标品牌
        D('UserPassport')->login($param);
    }

    /**
     * @deprecated 退出登录
     * @author zhangkang
     * @date  2016-7-25
     **/
    public function exitLogin()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'exitLogin');
        $json = json_decode($result, true);
        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        D('UserPassport')->exitLogin($json);
    }

    //更新用户regid
    public function setRegId(){
        $result = file_get_contents('php://input');
        recordLog($result, 'exitLogin');
        $json = json_decode($result, true);
        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $tokenid = $json['tokenid'];
        $deviceid = $json['deviceid'];
        $regid = $json['regid'];

        D('UserPassport')->setRegId($tokenid,$deviceid,$regid);
    }
	
    /**
     * @deprecated 注册
     * @author zhangkang
     * @date  2016-7-5
     **/
    public function register()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'register');
        $json = json_decode($result, true);

        if ( isEmpty($json['identifier']) ) {
            returnJson('', 1, '标识不能为空！');
        }
        
        if ( isEmpty($json['identity']) ) {
            returnJson('', 1, '身份不能为空！');
        }

        if($json['identity'] == 101){
            if ( isEmpty($json['code']) ) {
                returnJson('', 1, '验证码不能为空！');
            }
            $json['credential'] = '123456';
        }else{
            //$json['credential'] = '123456';
            if ( isEmpty($json['credential']) ) {
                returnJson('', 1, '凭证不能为空！');
            }
        }

        //微信注册 获取头像 昵称 
        $param = array();
        $param['identifier'] = $json['identifier'];//登录标识(username,email,phone,thirdpatyopenid)
        $param['identity'] = $json['identity'];//登录身份(username,emial,phone,wechat,qq,weibo)
        $param['credential'] = $json['credential'];//登录凭证(password,token)
        $param['code'] = $json['code'];
        $param['channel'] = $json['channel'];

        //判断是否非第三方登录
        if($json['identity'] < 200 || $json['identity'] == 401){
            $param['avatar'] = empty($json['avatar'])?'/Picture/default/'.rand(1,300).'.jpg': $json['avatar'];
        }
        else{
            $param['avatar'] = $json['avatar'];
        }

        $param['nickname'] = $json['nickname'];
        $param['deviceid'] = $json['deviceid'];
        //$param['phone'] = $json['phone'];
        //$param['password'] = $json['password'];
        //$param['gender'] = $json['gender'];
        //$param['birthday'] = $json['birthday'];
        //$param['province'] = $json['province'];
        //$param['city'] = $json['city'];
        //$param['county'] = $json['county'];

        D('UserPassport')->register($param);
    }

    /**
     * @deprecated 签到接口
     * @author 
     * @date 
     **/
    public function sign()
    {
        //  $request_s = '{"tokenid":"5557317bc551033abfc89c297a94c9cc"}';
        $request_s = file_get_contents("php://input");
        recordLog($request_s, '用户签到request');
        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        //是否签到
        $is_point = D('Point')->getPointInfo($request['tokenid']);
        if ( $is_point ) {
            returnJson('', 2, '今天已签到');
        }

        // $points = array();
        // $points['gold']=0;
        // $point=10;
        // if(!empty($request['id'])){
        //     $signInfo = M('sign_basepoint')->where("id=" . $request['id'] )->find();
        //     if($signInfo){
        //         $point=$signInfo['point'];
        //         $points['gold']=$signInfo['gold'];
        //     }
        // }
        $points['point'] =10 ;//$request['point'];
        $points['tokenid'] = $request['tokenid'];
        $points['type_id'] = 102;
        //$points['remark'] = "签到送积分";
        $rs = D('Point')->addPoint($points);
    }

    /**
     * @deprecated 签到接口
     * @author zhangran
     * @date 2016-07-06
     **/
    public function sign_error()
    {
        //  $request_s = '{"tokenid":"5557317bc551033abfc89c297a94c9cc"}';
        $request_s = file_get_contents("php://input");
        recordLog($request_s, '用户签到request');
        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        //是否签到
//        $is_point = D('Point')->getPointInfo($request['tokenid']);
//        if ( $is_point ) {
//            returnJson('', 2, '今天已签到');
//        }

//        $points = array();
//        $points['gold']=0;
//        $point=10;
//        if(!empty($request['id'])){
//            $signInfo = M('sign_basepoint')->where("id=" . $request['id'] )->find();
//            if($signInfo){
//                $point=$signInfo['point'];
//                $points['gold']=$signInfo['gold'];
//            }
//        }
//        $points['point'] =$point ;//$request['point'];
//        $points['tokenid'] = $request['tokenid'];
//        $points['type_id'] = 102;
//        //$points['remark'] = "签到送积分";
//        $rs = D('Point')->addPoint($points);

        $points['tokenid'] = $request['tokenid'];
        $rs = D('Point')->addPoint($points);
    }

     /**
     * @deprecated 签到接口
     * @author richie.hao
     * @date 2016-10-18
     **/
    public function checkin()
    {
        //  $request_s = '{"tokenid":"5557317bc551033abfc89c297a94c9cc"}';
        $request_s = file_get_contents("php://input");
        recordLog($request_s, '用户签到request');
        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $points['tokenid'] = $request['tokenid'];
        $rs = D('CheckinRecord')->checkin($points['tokenid'] );
    }
    
    /**
     * @deprecated 获取签到信息接口
     * @author richie.hao
     * @date 2016-10-18
     **/
    public function checkinInfo($tokenid)
    {
        if ( isEmpty($tokenid) ) {
            return returnJson('', 1, '您还未登录！');
        }
        $rs = D('CheckinRecord')->getCheck($tokenid);
    }
    
    /**
     * @deprecated 获取用户信息
     * @author
     * @date
     **/
    public function userinfo($tokenid=0,$uid=0)
    {
        $rs = D('UserPassport')->userinfo($tokenid,$uid);
        returnJson($rs, 200, 'success');
    }

    /**
     * @deprecated 通过手机号删除用户信息
     * @author
     * @date
     **/
    public function deleteUserByUid()
    {
        if($_SERVER['HTTP_HOST']!='passport.busonline.com'){
            $uid = I('post.uid');
            $rs = D('UserPassport')->deletePassportUserByUid($uid);
            //returnJson($rs, 200, 'success');
        }
    }

    /**
     * @deprecated 我的夺宝记录接口
     * @author zhangran
     * @date 2016-07-06
     **/
    public function getRecords()
    {
        //state(1-即将揭晓、0-进行中、2已揭晓)
        $request_s = file_get_contents('php://input');
        //记录LOG
        recordLog($request_s, "我的夺宝记录request");
        $request = json_decode($request_s, true);
        $records = D('User')->records($request['tokenid'], $request['pageindex'], $request['pagesize'], $request['state']);
        returnJson($records, 200, 'success');
    }

    /**
     * @deprecated 用户晒单
     * @author wenyuan
     * @date 2016-09-03
     **/
    public function luckyshow(){
        $result = @file_get_contents("php://input");
        $json = json_decode($result, true);

        //recordLog($json, "luckyshow");

        if(empty($json['pid']) || empty($json['pic'])){
            return returnJson('', 401, '参数错误');
        }

        $tokenid=$json['tokenid'];

        if ( isEmpty($tokenid) ) {
            return returnJson('', 1, '您还未登录！');
        }

        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '用户未登录或者登录已超时！');
        }
        
        $rs = D('User')->luckyshow($user['uid'],$json['pid'],$json['content'],$json['pic']);
    }

    /**
     * @deprecated 用户多图晒单
     * @author zhangkang
     * @date 2016-7-8
     **/
    public function shared()
    {
        $result = @file_get_contents("php://input");
        $json = json_decode($result, true);
        if ( isEmpty($json['tokenid']) ) {
            return returnJson('', 1, '您还未登录！');
        }
        $rs = D('User')->shared_update($json);
    }

    /**
     * @deprecated 用户点赞
     * @author zhangkang
     * @date 2016-7-8
     **/
    public function userUp()
    {
        $result = @file_get_contents("php://input");
        recordLog($result, 'userUp');
        $json = json_decode($result, true);
        if ( isEmpty($json['tokenid']) ) {
            return returnJson('', 1, '您还未登录！');
        }
        $rs = D('User')->userUp($json);
    }


     /**
     * @deprecated 机器码登录
     * @author gengguanyi
     * @date  2016-9-8
     **/
     public function newlogin()
     {
        
        $result = file_get_contents('php://input');   
        //file_get_contetns  1:读取POST数据 2：不能用于multipart/form-data类型  3

        recordLog($result, 'login');//记录
        $json = json_decode($result, true);


        //登录密码
        $json['credential'] = '123456';
        $param = array();

        $param['identity'] = $json['identity'];//身份  (username,email,phone,wechat,qq,weibo...);
        $param['identifier'] = $json['identifier'];//标识  (username,email,phone,thirdpatyopenid...)
        $param['credential'] = $json['credential'];//凭证(password,token)
        $param['deviceid'] = $json['deviceid'];//设备唯一id
        $param['regid'] = $json['regid'];//第三方服务商(jpush)提供的唯一id ,
        $param['imei'] = $json['imei'];//手机串号
        $param['os'] = $json['os'];//os (string, optional): 操作系统
        $param['osversion'] = $json['osversion'];//系统版本(9.0)
        $param['brand'] = $json['brand'];//厂商型号(iPhone 6,iPhone 6s,iPad mini...)
        $param['channel'] = $json['channel'];//注册渠道


        //判断用户是否已经注册     判断设备

        $map['identity'] = $json['identity'];
        $map['identifier'] = $json['identifier'];
        $user = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($map)->find();
        
        if($user){//已经注册
        
            D('UserPassport')->newlogin($param);
        }else{//未注册
            
            $this->newregister($param);
        }
        
     }

     //机器码用户注册
     public function newregister($param = array()){

        if ( isEmpty($param['identifier']) ) {
            returnJson('', 1, '标识不能为空1！');
        }
        if ( isEmpty($param['identity']) ) {
            returnJson('', 1, '身份不能为空！');
        }

        if ( isEmpty($param['credential']) ) {
            returnJson('', 1, '凭证不能为空！');
        }

        if ( isEmpty($param['channel'])){
            returnJson('', 1, '注册渠道不能为空！');
        }

        if( isEmpty($param['deviceid'])){
            returnJson('', 1, '设备id不能为空！');    
        }

        //判断是否非第三方登录
        if($json['identity'] < 200 || $json['identity'] == 401){
            $param['avatar'] = empty($json['avatar'])?'/Picture/default/'.rand(1,300).'.jpg': $json['avatar'];
        }
        else{
            $param['avatar'] = $json['avatar'];
        }

        D('UserPassport')->newregister($param);
     }


     //绑定手机号
     public function bindingPhone(){
        $request_s = file_get_contents("php://input");
        recordLog($request_s, '用户签到request');
        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        if ( isEmpty($request['code']) ) {
            returnJson('', 1, '验证码不能为空11！');
        }
        if ( isEmpty($request['phone']) ) {
            returnJson('', 1, '电话不能为空！');
        }

        $param['tokenid'] = $request['tokenid'];
        $param['code'] = $request['code'];
        $param['phone'] = $request['phone'];
        D('UserPassport')->bindingPhone($param);
     }

     
     //微信登录回调
    function oauth2(){
        $weixin= new \class_weixin_adv();

		if (isset($_GET['code'])){
            
			$res = $weixin->get_access_token($_GET['code']);

            $res2 = $weixin->check_token($res['access_token'],$res['openid']);

            if($res2['errcode'] == 0){//access_token没过期

            }else{
                $res3 = $weixin->refresh_token($res['refresh_token']);

                if(empty($res3['errcode'])){
                    $res['access_token'] = $res3['access_token'];
                }else{
                    returnJson('', 1, '授权出错,请重新授权!');
                }
            }

			$row = $weixin->get_user_info($res['access_token'],$res['openid']); 

			if ($row['unionid']) {
                //判断微信用户是否已经注册
                $map = array();
                $map['identifier'] = $row['unionid'];
                $map['identity'] = 201;
                $if_have = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->find();

                $param = array();
                $param['unionid'] = $row['unionid'];
                $param['nickname'] = $row['nickname'];
                $param['headimgurl'] = $row['headimgurl'];
                $param['login_time'] = $_GET['state'];

				if($if_have){
                    $param['uid'] = $if_have['uid'];
                    $param['id'] = $if_have['id'];
                    D('UserPassport')->weChatLogin($param);
				}else{
                    D('UserPassport')->weChatRegister($param);
				}
			}else{
                $url = $this->Quath2Url."?code=402";
                $result_url = $url;
                header('Location: '.$result_url);
			}
        }else{
            $url = $this->Quath2Url."?code=401";
            $result_url = $url;
            header('Location: '.$result_url);
        }
   }

   //qq第三方登录
   function QQoauth2(){
       //应用的APPID
        $app_id = "YOUR_APP_ID";
        //应用的APPKEY
        $app_secret = "YOUR_APP_KEY";
        //成功授权后的回调地址
        $my_url = $this->passportUrl."/api.php?s=/User/QQoauth2";

        //Step1：获取Authorization Code
        $code = $_REQUEST["code"];
         //Step2：通过Authorization Code获取Access Token
        if(!empty($code)) 
        {
             $login_time = $_REQUEST['state']; 
            //拼接URL   
            $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
            . "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
            . "&client_secret=" . $app_secret . "&code=" . $code;
            $response = file_get_contents($token_url);
            if (strpos($response, "callback") !== false)
            {
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                $msg = json_decode($response);
                if (isset($msg->error))
                {
                    //错误
                    $url = $this->Quath2Url."?tokenid=&code=1&time=&skin=#!main";
                    $result_url = $url;
                    header('Location: '.$result_url);
                }
            }
        
            //Step3：使用Access Token来获取用户的OpenID
            $params = array();
            parse_str($response, $params);
            $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=";
            $params['access_token'];
            $str  = file_get_contents($graph_url);
            if (strpos($str, "callback") !== false)
            {
                $lpos = strpos($str, "(");
                $rpos = strrpos($str, ")");
                $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
            }
            $user = json_decode($str);
            if (isset($user->error))
            {
                //错误
                $url = $this->Quath2Url."?tokenid=&code=1&time=&skin=#!main";
                $result_url = $url;
                header('Location: '.$result_url);
            }
            //用户openid
            $openid = $user->openid;
            //判断微信用户是否已经注册
            $map = array();
            $map['identifier'] = $openid;
            $map['identity'] = 202;
            $if_have = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->find();
            $param = array();
            if($if_have){
                $param['uid'] = $if_have['uid'];
                $param['id'] = $if_have['id'];
                $param['openid'] = $openid;
                // $param['nickname'] = $row['nickname'];
                // $param['headimgurl'] = $row['headimgurl'];
                $param['login_time'] = $login_time;
                D('UserPassport')->QQLogin($param);
            }else{
                $param['openid'] = $openid;
                // $param['nickname'] = $row['nickname'];
                // $param['headimgurl'] = $row['headimgurl'];
                $param['login_time'] = $login_time;
                D('UserPassport')->QQRegister($param);
            }

        }else{
            //错误
            $url = $this->Quath2Url."?tokenid=&code=1&time=&skin=#!main";
            $result_url = $url;
            header('Location: '.$result_url);
        }



   }

    /**
     * @deprecated 用户明细查询最新接口
     * @author gengguanyi
     * @date 2016-10-20
     **/
    public function findUserInfo(){
        $result = file_get_contents('php://input');
        $json = json_decode($result, true);

        //recordLog($json, "luckyshow");

        if(empty($json['username'])){
            return returnJson('', 401, '用户名不能为空');
        }
        $username=$json['username'];
        D('User')->findUserInfo($username);
    }
}