<?php
namespace api\Model;
use Think\Model;
use Think\Cache\Driver\RedisCache;
use Com\Alidayu\AlidayuClient as Client;
use Com\Alidayu\Request\SmsNumSend;
use Think\Storage;
/**
 * 通知模型
 */
class NotificationModel extends Model{

    //JPush
    protected $app_key = '1332f6f2c55665e4010fae13';// '06c61e785e5745ff9c52c751';
    protected $master_secret = '22bef63563b88d158c3d861e';//'c6dad8a64ebfe1d19c8726ce';

    protected function _initialize()
    {        
        $config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =  config_lists();
            S('DB_CONFIG_DATA',$config);
        }
        C($config);

        vendor("JPush.JPush");
    }

    public function sendSmsWarning($mobile){
        //向指定手机发卡密
        $client = new Client;
        $request = new SmsNumSend;
        $smsParams = array(
            'name' => '程序异常，请检查！',
            'card' => '0',
            'cardpassport' => '0',
        );
        // 设置请求参数
        $req = $request->setSmsTemplateCode('SMS_13880391')
            ->setRecNum($mobile)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName(C("WEB_NAME"))
            ->setSmsType('normal')
            ->setExtend('warning');
        $request = $client->execute($req);
        return $request;
    }

    /**
     * 推送
     * @param array $regIdArr regId数组
     * @param string $title 推送标题
     * @param string $content 推送内容
     * @param bool|true $productionEnv 是否生产环境
     * @param $extras 扩展信息
     */
    public function pushNotification($platform='ios',$regIdArr=array(), $title='',$content='',$productionEnv=true,$extras,$debug=false){

        if(isHostOnlineTest()){
            $debug==true;
        }

        if($debug==false){
            //非正式站不发送推送
            if(!isHostProduct()){
                return false;
            }
        }
        
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);
        switch ($platform) {
            case 'ios':
                // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
                $result = $client->push()
                    ->setPlatform(array($platform))
                    ->addRegistrationId($regIdArr)
                    //->setNotificationAlert($this->alert)
                    ->addIosNotification($content, 'sound', \JPush::DISABLE_BADGE, true, 'iOS category', $extras)//alert,sound,badge,content-available,category,extras
                    ->setMessage($content, $title, 'type', $extras)
                    ->setOptions(100000, 3600, null, $productionEnv)
                    ->send();
                break;
            case 'android':
                $result = $client->push()
                    ->setPlatform(array($platform))
                    ->addRegistrationId($regIdArr)
                    //->setNotificationAlert($this->alert)
                    ->addAndroidNotification($content, $title, 1, $extras)//alert,title,builder_id,extras
                    ->setMessage($content, $title, 'type', $extras)
                    ->setOptions(100000, 3600, null, false)
                    ->send();
                break;
            default:
                $result=false;
                break;
        }

        return $result;
    }

    /***
     *  商品达成开奖条件后，通知参与该周期的用户：即将揭晓！
     */
    public function sendNotificationByPeriodComplate($pid,$info){
        try{
            $uids = M('shop_record')->distinct(true)->field('uid')->where('pid='.$pid)->order('uid')->select();
            // SELECT DISTINCT uid FROM hx_shop_record sr
            // where sr.pid =990
            // ORDER BY uid
            //recordLog(count($uidArr),"addAllUserMessage count");
            //发送站内信息
            $message_rs = D('Message')->addAllUserMessage(104,$pid,$uids);
            //recordLog('message_rs:'.json_encode($message_rs), "pay flow");
            //发推送
            $notification_rs = $this->pushToPeriodComplate($pid,$info);
            //recordLog('notification_rs:'.json_encode($notification_rs), "pay flow");
        }catch(\Exception $e){
            recordLog('Exception:'.json_encode($e->getMessage()), "PeriodComplate notification error");
        }
    }

    /***
     * 通过商品周期的中奖信息发送中奖通知
     */
    public function sendNotificationByPid($pid)
    {
        try{
            $period = M('shop_period')->where("id=".$pid)->field('sid,uid,no')->find();
            if($period){
                $userName=get_user_name($period['uid']);
                $shop=M('shop')->where("id=".$period['sid'])->find();
                // $shopName='【（第'.$period['no'].'期）'.$shopName.'】';
                if($shop){
                    $shopName=$shop['name'];
                    $pic = get_shop_pic($shop['cover_id']);
                }
            }

            $message_str = '【（第'.$period['no'].'期）'.$shopName.'】';

            $no = $period['no'];
            $msg['shopName']=$shopName;
            $msg['no']=$no;

            //jpush 扩展
            $extras = array("type" => 1, "data" => array("no"=>$period['no'],"title"=>$shopName,"pid"=>$pid,"username"=>$userName,"imgurl"=>$pic));

            //站内消息
            //M('User')->where
            try{
                $rs = D('Message')->addUserMessage($period['uid'],102,$pid);
                echo '<br> Message result'.$rs;
            }catch(\Exception $e){
                recordLog($e->getMessage(), "站内消息失败");
            }

            $uid = M('user')->where("id=".$period['uid'])->getField('passport_uid');
            
            $map['uid'] = $uid;
            $user = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field('phone,email')->where($map)->find();
            //$email = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $uid . ' and identity=102')->getField('identifier');
            if ( !empty($user['phone']) ) {                
                try{
                    $this->winningSendMobile($user['phone'], $message_str);
                }catch(\Exception $e){
                    recordLog($e->getMessage(), "发送短信失败");
                }
            }
            if ( !empty($user['email']) ) {
                try{
                    $this->winningSendEmail($user['email'], $message_str);
                }catch(\Exception $e){
                    recordLog($e->getMessage(), "发送email失败");
                }
            }

            //非正式站不发送推送
            if(isHostProduct()){
                try{
                    //推送
                    $deviceList = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $uid . ' and regid is not null and os is not null')->select();
                    foreach ( $deviceList as $k => $v ) {
                        if(!empty($deviceList[$k]['regid']))
                        {
                            if ( $deviceList[$k]['os'] == 'Android' ) {
                                $this->JpushAndroid($deviceList[$k]['regid'], $msg,$extras);
                            } else {
                                $this->JpushIos($deviceList[$k]['regid'], $msg,$extras);
                            }
                        }
                    }
                }catch(\Exception $e){
                    recordLog($e->getMessage(), "push失败");
                }
            }
            // return  M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($map)->find();
        }catch(\Exception $e){
            recordLog($e->getMessage(), "sendNotification失败");
        }
    }
    
    /***
     * 发送中奖邮箱与短信
     */
    public function sendPhoneAndEmail($uid, $shopName)
    {
        $map['uid'] = $uid;
        $user = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field('phone,email')->where($map)->find();
        //$email = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $uid . ' and identity=102')->getField('identifier');
        if ( !empty($user['phone']) ) {
            recordLog($user['phone'], "推送phone");
            $this->winningSendMobile($user['phone'], $shopName);
        }
        if ( !empty($user['email']) ) {
            recordLog($user['email'], "推送email");
            $this->winningSendEmail($user['email'], $shopName);
        }

        //推送
        $deviceList = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $uid . ' and regid is not null and os is not null')->select();
        foreach ( $deviceList as $k => $v ) {
            if(!empty($deviceList[$k]['regid']))
            {
                recordLog($$deviceList[$k]['regid'], "推送regid");
                if ( $deviceList[$k]['os'] == 'Android' ) {
                    $this->JpushAndroid($deviceList[$k]['regid'], $shopName);
                } else {
                    $this->JpushIos($deviceList[$k]['regid'], $shopName);
                }
            }
        }
        // return  M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($map)->find();
    }

    //给中奖用户发送邮箱
    public function winningSendEmail($email, $shopName)
    {
        $title = C("WEB_SITE_TITLE") . '中奖提示';
        $content = '<div class="wrapper" style="margin: 20px auto 0; width: 600px; padding-top:16px; padding-bottom:10px;">
                <br style="clear:both; height:0">
                <div class="content" style="background: none repeat scroll 0 0 #FFFFFF; border: 1px solid #E9E9E9; margin: 2px 0 0; padding: 30px;">
                     <p>尊敬的用户您好: </p>
                     <p style="border-top: 1px solid #DDDDDD;padding-top:6px; margin: 15px 0 15px;line-height:30px;text-indent:2em;">【' . C("WEB_SITE_TITLE") . '】亲~恭喜您成为' . $shopName . '的获奖者！请您及时登录“' . C("WEB_SITE_TITLE") . '”客户端，并在“个人页面-我的幸运单”完善收货地址！</p>
                     <p style="border-top: 1px solid #DDDDDD; padding-top:6px; margin-top:6px; color:#838383;">
                     <p style="text-align:right;margin:5px 0 0 0;">如有疑问，请联系' . C("WEB_SITE_TITLE") . '客服：' . C('MAIL_USERNAME') . '</p></p></div></div>';


        echo '<br>'.sendMail($email, $title, $content);
    }

    //给手机发送中奖通知 $recNum手机号，$shopName商品标题
    public function winningSendMobile($recNum, $shopName)
    {
        //$client = new Client(C('AlidayuAppKey'),C('AlidayuAppSecret'));
        $client = new Client();
        $request = new SmsNumSend;
        // $code = D('UserPassport')->randString();
        // D('UserPassport')->cell_code($code);
        $title = "LIVE商城";
        $smsParams = array(
            //'name' => $title,
            'product' => $shopName
        );
        // 设置请求参数
        //$req = $request->setSmsTemplateCode('SMS_13240378')
        //$req = $request->setSmsTemplateCode('SMS_26150364')
        $req = $request->setSmsTemplateCode('SMS_47475157')
            ->setRecNum($recNum)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName($title)
            ->setSmsType('normal')
            ->setExtend('zj');
        $request = $client->execute($req);

        $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];
        
        // echo json_encode($request);
        // $redisCache = new RedisCache();
        // $redisCache->set($recNum, $code, 600);
        // echo '<br>'.$request;

        return $reqResult;
    }

    public function JpushAll($msg)
    {
        /**
         * 该示例主要为JPush Push API的调用示例
         * HTTP API文档:http://docs.jpush.io/server/rest_api_v3_push/
         * PHP API文档:https://github.com/jpush/jpush-api-php-client/blob/master/doc/api.md#push-api--构建推送pushpayload
         */

        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);
        // 简单推送示例
        $result = $client->push()
            ->setPlatform('all')
            ->addAllAudience()
            ->setNotificationAlert($msg)
            ->send();
        echo '<br> Result=' . json_encode($result);
    }

    public function  JpushIos($regid, $msg,$extras)
    {
        $name = '【（第'.$msg['no'].'期）'.$msg['shopName'].'】';
        $title = C("WEB_SITE_TITLE");
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('ios'))
            ->addRegistrationId($regid)
            //->setNotificationAlert($this->alert)
            ->addIosNotification('【'.$title.'】亲~恭喜您成为'.$name.'的获奖者！请您在 “个人页面-我的幸运单”中完善收货地址！', 'sound', \JPush::DISABLE_BADGE, true, 'iOS category', $extras)
            ->setMessage('【'.$title.'】亲~恭喜您成为'.$name.'的获奖者！请您在 “个人页面-我的幸运单”中完善收货地址！', '中奖通知', 'type', $extras)
            ->setOptions(100000, 3600, null, true)
            ->send();

        echo '<br> Result=' . json_encode($result);
    }

    public function JpushAndroid($regid, $msg,$extras)
    {
        $name = '【（第'.$msg['no'].'期）'.$msg['shopName'].'】';
        $title = C("WEB_SITE_TITLE");
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('android'))
            ->addRegistrationId($regid)
            // ->setNotificationAlert($this->alert)
            ->addAndroidNotification('【' . $title . '】亲~恭喜您成为' . $name . '的获奖者！请您在 “个人页面-我的幸运单”中完善收货地址！', '中奖通知', 1, $extras)
            ->setMessage('【'.$title.'】亲~恭喜您成为'.$name.'的获奖者！请您在 “个人页面-我的幸运单”中完善收货地址！', '中奖通知', 'type', $extras)
            ->setOptions(100000, 3600, null, false)
            ->send();

        echo '<br> Result=' . json_encode($result);
    }

    /**
     * 发送虚拟卡密短信
     * @param $no
     * @param $mobile
     * @return bool
     */
    public function sendCardSN($tokenid,$no,$mobile=''){
        //TODO 适配安卓，ios暂时当tokenid有值时进行登录验证
        if($tokenid != ''){
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $data = M('user')->where('id='.$user['uid'].' and passport_uid='.$user['passportuid'])->find();
            $mobile = $data['phone'] ;
        }
        if(isHostOnlineTest()){
            returnJson('', 200, '暂时无法发送卡密短信，稍后再试！');
        }

        //判断是否已经兑换过（未兑换过的才能进行短信发送）
        $ShopPeriod = D('ShopPeriod');
        $isExchangeGold = $ShopPeriod->isExchangeGold();
        if(is_null($isExchangeGold) || $isExchangeGold > 0){
            returnJson('', 2, '商品已经兑换过或者暂时不可用！');
        }

        //通过no获取卡号信息
        $Shop = D('Shop');
        $map['c.no'] = $no;
        $result = $Shop->alias('s')->join(' LEFT JOIN __CARD__ c ON s.id = c.type ')->where($map)->field('c.password,s.name,c.id')->find();

        $password = $result['password'] ;
        $name = $result['name'];
        $card_id = $result['id'];

        //检查卡号是否为用户中奖
        $Period = D('ShopPeriod');
        $rs2 = $Period->where('uid = "'.$user['uid'].'" and card_id = "'.$card_id.'"')->find();
        if(!$rs2){
            return false;
        }

        //向指定手机发卡密
        $client = new Client;
        $request = new SmsNumSend;
        $smsParams = array(
            'name' => $name,
            'card' => $no,
            'cardpassport' => $password,
        );
        // 设置请求参数
        $req = $request->setSmsTemplateCode('SMS_13880391')
            ->setRecNum($mobile)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName(C("WEB_NAME"))
            ->setSmsType('normal')
            ->setExtend('card');
        $request = $client->execute($req);
        $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];

        if($reqResult===true){
            //设置发送状态为已发送
            $Card = D('Card');
            $data = array('issend'=>'1','send_time'=>time());
            $Card->where( array('no'=>$no))->setField($data);
            returnJson($request, 200, 'success');
        }
        else{
            return false;
        }
    }


    /* *即将揭晓发送推送**/
	public function pushToPeriodComplate($pid,$info){
  		//添加整体极光推送开始
		//查询本期购买者
		$now_period_users = M('shop_record')->
							table('__USER__ user,__SHOP_RECORD__ record')
							->field('user.passport_uid')
							->where("record.pid=".$pid ." AND user.id = record.uid")
							->select();
							
		for($i=0;$i<count($now_period_users);$i++){
			$arr[] = $now_period_users[$i]['passport_uid'];
		}
		
		//多个
		//获取用户regid 与 os 用于极光推送
		$arr = array_unique($arr);
        $arr = array_merge($arr);
		foreach ($arr as $k=>$v){
			$if_have_regid = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid=". $v ." and regid is not null and regid !='' and os is not null")->field('regid,os')->select();
			
			for($i=0;$i<count($if_have_regid);$i++){
                if($if_have_regid[$i]['os']=="iOS"){
                    $iosregid[]=$if_have_regid[$i]['regid'];
                }else{
                    $androidregid[]=$if_have_regid[$i]['regid'];
                }
            }
		}
        
        $period = M('shop_period')->where(array('id'=>$pid))->find();

        //商品详情
        $msg = array(
            'shopName'=>$info['name'],
            'no'=>$info['no'],
			'pid'=>$pid,
            'iscommon'=>$period['iscommon'],
            'house_id'=>$period['house_id']
        );
        //发送推送
        recordLog(json_encode($msg), "即将揭晓商品信息");
        recordLog(json_encode($iosregid), "即将揭晓ios用户");
        recordLog(json_encode($androidregid), "即将揭晓安卓用户");
        $result = $this->JpushComplate($iosregid,$androidregid,$msg,true);
        return $result;
  	}

    //极光推送指定用户推送
    public function JpushComplate($iosmembers,$androidmembers,$msg,$productionEnv=true){
        $content = "您参与的".$msg['shopName']."（第".$msg['no']."期） 即将揭晓！";
        $title = "即将揭晓";

        $ispk = $msg['iscommon'] == 2 ? true : false;

        $extras = array("type" => 2, "data" => array("pid" => $msg['pid'], "ispk" => $ispk, "houseid" => $msg['house_id']));

        $notification = $content;
        $message = $content;

        if(count($iosmembers)>0){
            $result = $this->pushNotification('ios',$iosmembers, $title, $content,$productionEnv,$extras);
        }
        if(count($androidmembers)>0){
            $result = $this->pushNotification('android',$androidmembers, $title, $content,$productionEnv,$extras);
        }

        return $result;
    }


    /**
     * 创建房间发送
     * @param $uids
     * @param string $shopname
     * @param $houseid
     * @param $invitecode
     * @param bool $productionEnv
     * @return array|bool|object
     */
    public function push4CreateRoom($uids,$pid,$shopname='',$houseid, $invitecode){

        $_uids = explode(',',$uids);
        foreach ($_uids as $k=>$v){
            $if_have_regid = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid=". $v ." and regid is not null and regid !='' and os is not null")->field('regid,os')->select();

            for($i=0;$i<count($if_have_regid);$i++){
                if($if_have_regid[$i]['os']=="iOS"){
                    $iosregid[]=$if_have_regid[$i]['regid'];
                }else{
                    $androidregid[]=$if_have_regid[$i]['regid'];
                }
            }

            $content = "你创建的“".$shopname."”PK房间发布成功！房间号".$houseid."，邀请码".$invitecode."。";
            $title = "房间通知";

            if(count($iosregid)>0){
                $result = $this->pushNotification('ios',$iosregid, $title, $content,$productionEnv=true,null,$debug=true);
            }
            if(count($androidregid)>0){
                $result = $this->pushNotification('android',$androidregid, $title, $content,$productionEnv=true,null,$debug=true);
            }

            D('Message')->addUserMessage($v, 106, $pid);
        }


        return $result;
    }


    public function push4DissolveRoom($uid,$pid,$houseno, $houseissue){

        $if_have_regid = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid=". $uid ." and regid is not null and regid !='' and os is not null")->field('regid,os')->select();

        for($i=0;$i<count($if_have_regid);$i++){
            if($if_have_regid[$i]['os']=="iOS"){
                $iosregid[]=$if_have_regid[$i]['regid'];
            }else{
                $androidregid[]=$if_have_regid[$i]['regid'];
            }
        }

        $content = "由于72小时内".$houseno."房间第".$houseissue."期参与人数未满已解散，您参与的金额已退还到您金币账户!";
        $title = "房间解散通知";

        if(count($iosregid)>0){
            $result = $this->pushNotification('ios',$iosregid, $title, $content,$productionEnv=true,null,$debug=true);
        }
        if(count($androidregid)>0){
            $result = $this->pushNotification('android',$androidregid, $title, $content,$productionEnv=true,null,$debug=true);
        }

        D('Message')->addUserMessage($uid, 107, $pid);

        return $result;
    }
    public function sendStraight($phone,$name)
    {
        //向指定手机发卡密
        $client = new Client;
        $request = new SmsNumSend;
        $smsParams = array(
            'name' => $name
        );
        // 设置请求参数
        $req = $request->setSmsTemplateCode('SMS_50090065')
            ->setRecNum($phone)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName("LIVE商城")
            ->setSmsType('normal')
            ->setExtend('card');
        $request = $client->execute($req);
        $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];

        if($reqResult===true){
            return 200;
        }
        else{
            return 101;
        }
    }
    /**
     * 发送虚拟卡密短信
     * @param $no
     * @param $mobile
     * @return bool
     */
    public function sendCardSnNew($pid,$no,$mobile=''){

        //判断是否已经兑换过（未兑换过的才能进行短信发送）
        $ShopPeriod = D('api/ShopPeriod');
        $isExchangeGold = $ShopPeriod->isExchangeGold($pid);
        if(is_null($isExchangeGold) || $isExchangeGold > 0){
            //returnJson('', 2, '商品已经兑换过或者暂时不可用！');
            return 102;
        } else {
            $uid = M('ShopPeriod')->where('id='.$pid)->getField('uid');//中奖者id
            //通过no获取卡号信息
            $Shop = D('Shop');
            $map['c.no'] = $no;
            $result = $Shop->alias('s')->join(' LEFT JOIN __CARD__ c ON s.id = c.type ')->where($map)->field('c.password,s.name,c.id')->find();
            $password = $result['password'] ;
            $name = $result['name'];
            $card_id = $result['id'];

            //检查卡号是否为用户中奖
            $Period = D('ShopPeriod');
            $rs2 = $Period->where('uid = "'.$uid.'" and card_id = "'.$card_id.'"')->find();

            if(!$rs2){
                //returnJson('', 2, '卡号不正确！');
                return 103;
            } else {
                //向指定手机发卡密
                $client = new Client;
                $request = new SmsNumSend;
                $smsParams = array(
                    'name' => $name,
                    'card' => $no,
                    'cardpassport' => $password,
                );
                // 设置请求参数
                $req = $request->setSmsTemplateCode('SMS_13880391')
                    ->setRecNum($mobile)
                    ->setSmsParam(json_encode($smsParams))
                    ->setSmsFreeSignName("LIVE商城")
                    ->setSmsType('normal')
                    ->setExtend('card');
                $request = $client->execute($req);
                $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];

                if($reqResult===true){
                    //设置发送状态为已发送
                    $Card = D('Card');
                    $data = array('issend'=>'1','send_time'=>time());
                    $Card->where( array('no'=>$no))->setField($data);
                    return 200;
                }
                else{
                    return 101;
                }
            }
        }
    }

    /**
     *
     */

    /**
     * 
     * 推送给所有当前期的购买用户
     * @param $pid
     * @param $send_uid 逗号分隔，例如：1001,1002,1003,1004
     * @param string $title
     * @param string $description
     * @param int $type 1=只显示小红点 2=发送消息 3=小红点+发送消息
     */
    public function push2User($pid='',$send_uid='',$title='',$description='',$type=1){
        if(!empty($pid)){
            $Shoporder = M('shop_order');
            $map['pid']=$pid;
            $uids = $Shoporder->where($map)->group('uid')->getField('uid',true);
        }
        if(!empty($send_uid)){
            $uids = explode(',',$send_uid);
        }

        $passThrough = 0;
        if(empty($title) && empty($description)){
            $passThrough = 1;
        }

        $i  =0 ;
        $s_uid = '';
        foreach ($uids as $uid){
            $i++;

            if($i%50== 1){
                $s_uid = $uid;
            }else{
                $s_uid = $s_uid. ','.$uid;
            }

            if($i%50== 0){
                //发送开奖消息推送
                //$uids,$title,$description
                $r = D('api/PlatformApi')->sendMsg($s_uid,$title,$description,1);
                if($type > 1){
                    $r = D('api/PlatformApi')->sendMsg($s_uid,$title,$description,0);    
                }
                $s_uid = '';
            }
        }
        if(!empty($s_uid)){
            //发送开奖消息推送
            $r = D('api/PlatformApi')->sendMsg($s_uid,$title,$description,1);
            if($type > 1){
                $r = D('api/PlatformApi')->sendMsg($s_uid,$title,$description,0);
            }
            $s_uid = '';
        }
    }

    /**
     * 推送通知所有用户
     */
    public function pushAllUser(){
        $User = M('user');
        $arr_user = $User->getField('id',true);
        $arr_user = implode(',',$arr_user);
        $this->push2User('',$arr_user,'','description');
    }
}