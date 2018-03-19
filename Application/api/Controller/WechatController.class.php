<?php

namespace api\Controller;

class WechatController extends BaseController
{
    //充值通知-模板ID
    const MSG_TEMPLATE_ID_RECHARGE = 'TyQNnLbXKCWMLgRr-Vonhz8qGSHAQWzjHLSpOhTcbUU';
    const MSG_TEMPLATE_ID_LOTTERY = 'JNGbnNQNuv6CBScG0fnAKkxfGrG42wSThs7SujAlmqU';

    protected $host = '';
    protected $callbackHost = 'http://www.molijinbei.com';
	protected $callbackUrl = '/h5/pkshare/index.html';// 回调地址

    protected function _initialize()
    {
        parent::_initialize();

//        $this->$host = $_SERVER['HTTP_HOST'];

        // if(strpos($_SERVER['HTTP_HOST'],"www.molijinbei.com")==0){
        //     $this->callbackHost = "http://1.busonline.com";
        // }
        // else{
        //     $this->callbackHost = "http://onlinetest.1.busonline.com";
        // }

        // $this->callbackUrl = $this->callbackHost . $this->callbackUrl;
    }

    //微信登录回调
    public function oauth2($code){
        // echo "extras:" . $extras;

        vendor("Wechat.class_weixin_adv");
        
		$weixin= new \class_weixin_adv(C('WEIXINPAY_CONFIG')['APPID'],C('WEIXINPAY_CONFIG')['APPSECRET']);

		if ($code){
            
			$res = $weixin->get_access_token($code);

            $res2 = $weixin->check_token($res['access_token'],$res['openid']);

            if($res2['errcode'] == 0){//access_token没过期

            }else{
                $res3 = $weixin->refresh_token($res['refresh_token']);

                if(empty($res3['errcode'])){
                    $res['access_token'] = $res3['access_token'];
                }else{
                    var_dump($res3);
                    returnJson('', 401, '授权出错,请重新授权!');
                }
            }

			$row = $weixin->get_user_info($res['access_token'],$res['openid']); 

			if ($row['openid']) {
                // $url = $this->callbackUrl."?code=200&extras=".$extras."&avatar=".urlencode($row['headimgurl'])."&nickname=".$row['nickname'];
                // // echo "url:" . $url;
                // header("Location: $url");\
                
                return $row;
			}else{
                // $url = $this->callbackUrl."?&code=401";
                // header('Location: '.$url);

                returnJson('', 402, '授权出错,用户信息获取错误!');
			}
        }else{
            // $url = $this->callbackUrl."?&code=402";
            // header('Location: '.$url);
            
            returnJson('', 403, '参数错误');
        }

        return false;
    }

    /**
     * 暂且不用
     * 公众号支付 必须以get形式传递 out_trade_no 参数
     * 示例请看 /Application/Home/Controller/IndexController.class.php
     * 中的weixinpay_js方法
     */
    public function pay(){
        // 导入微信支付sdk
        Vendor('Wechat.Pay.Weixinpay');
        $wxpay=new \Weixinpay();
        // 获取jssdk需要用到的数据
        $data=$wxpay->getParameters();
        // 将数据分配到前台页面
        $assign=array(
            'data'=>json_encode($data)
            );
        $this->assign($assign);
        $this->display();
    }

    //微信jsapi支付回调
    public function wxcallbacknotify(){
        // wechat_notify();

        try{

            // 导入微信支付sdk
            Vendor('Wechat.Pay.Weixinpay');
            $wxpay=new \Weixinpay();
            $result=$wxpay->notify();

            recordLog($result,'微信支付回调结果');

            if ($result) {
                // 验证成功 修改数据库的订单状态等 $result['out_trade_no']为订单号

                $map['order_id'] = $result['out_trade_no'];

                $rechargeOrder = M('shop_order')->where($map)->find();

                recordLog($rechargeOrder,'微信支付回调订单查询');

                if($rechargeOrder && $rechargeOrder['recharge']==1 && $rechargeOrder['order_status']==0){
                    $rechargeOrder['msg'] = '充值成功';
                    $rechargeOrder['code'] = 'OK';
                    $rechargeOrder['order_status'] = 1;
                    $rechargeOrder['order_status_time'] .= ','. time();
                    $rechargeOrder['exchange_transaction'] = $result['transaction_id'];
                    $rs = M('shop_order')->save($rechargeOrder);

                    recordLog($rs,'微信支付回调订单更新');

                    if($rs){
                        $rs_gcoupon_record = D('Admin/GcouponRecord')->addRecord($rechargeOrder['uid'],$activity_type=0,$rechargeOrder['gold'],0,0,$result['transaction_id']);
                        $rs_user = M('user')->where(array('id'=>$rechargeOrder['uid']))->setInc('gold_coupon',$rechargeOrder['gold']);

                        if($rs_gcoupon_record && $rs_user){

                            recordLog($rs_gcoupon_record,'微信支付回调用户增加虚拟币明细');
                            recordLog($rs_user,'微信支付回调用户增加虚拟币');

                            //发送微信公众号模板消息-充值
                            $rs_msg =  $this->sendTplMsgRecharge($rechargeOrder);

                            recordLog(json_encode($rs_msg),'发送微信公众号模板消息结果');
                        }
                    }
                }
            }
        }catch(\Exception $e){
            recordLog($e->getMessage(),'微信支付回调异常');
        }
        // echo 'result:'.$result;
    }

    /**
     * 微信公众号模板消息-充值
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1433751277
     *
     * {{first.DATA}}
     *
     * {{accountType.DATA}}：{{account.DATA}}
     * 充值金额：{{amount.DATA}}
     * 充值状态：{{result.DATA}}
     * {{remark.DATA}}
     *
     * @param $rechargeOrder [uid,cash,msg,remark]
     * @return mixed
     */
    public function sendTplMsgRecharge($rechargeOrder){
        $color = '#173177';

        $user = M('user')->where(array('id'=>$rechargeOrder['uid']))->find();
        if($user) {
            $dataRechargeMsg['first'] = ['value' => '您好，您已成功进行'.C("WEB_CURRENCY").'充值。', 'color' => $color];
            $dataRechargeMsg['accountType'] = ['value' => '用户'];
            $dataRechargeMsg['account'] = ['value' => $user['nickname'], 'color' => $color];
            $dataRechargeMsg['amount'] = ['value' => $rechargeOrder['cash'].'元', 'color' => $color];
            $dataRechargeMsg['result'] = ['value' => $rechargeOrder['msg'], 'color' => $color];
            $dataRechargeMsg['remark'] = ['value' => '备注：如有疑问，请致电18500395177联系我们。', 'color' => $color];

            $data['touser'] = $user['username'];//openid
            $data['template_id'] = self::MSG_TEMPLATE_ID_RECHARGE;//模板ID
            //$data['url'] = '';//模板跳转链接
            $data['data'] = $dataRechargeMsg;

            $rs = D('api/Wechat')->sendTemplateMessage($data);

            return $rs;
        }

        return false;
    }

    /**
     * 微信公众号模板消息-开奖
     * {{first.DATA}}
     *
     * 揭晓商品：{{keyword1.DATA}}
     * 幸运号：{{keyword2.DATA}}
     * 幸运人：{{keyword3.DATA}}
     * 参与次数：{{keyword4.DATA}}
     * 揭晓时间：{{keyword5.DATA}}
     * {{remark.DATA}}
     *
     * @param $options
     * @return bool
     */
    public function sendTplMsgLottery($pid,$uid){
        $color = '#173177';

        $period_map['sid'] = 1;
        $period_map['pid'] = $pid;
        $period_map['uid'] = $uid;
        $period_map['state'] = 2;//已开奖
        $period = D('api/Period')->periodInfo($period_map);

        recordLog($period,'周期'); 

        if($period){
            $options['good_name'] = $period['total_buy_gold'].'黄金';
            $options['lucky_num'] = $period['kaijang_num'];
            $options['nickname'] = get_user_name($period['uid']);
            $options['buy_times'] = $period['user_number'].'次';//中奖人购买次数
            $options['date'] = date("Y年m月d日 H:i:s",$period['kaijang_time']);

            //循环购买人，逐个通知
            // select u.uid,u.nickname,sum(o.number) buy_times 
            // from bo_shop_order o
            // LEFT JOIN bo_user u ON o.uid=u.id
            // WHERE pid=4097
            // GROUP BY u.uid,u.nickname
            // ORDER BY o.create_time DESC
            $rs_order = M('shop_order')->alias('o')->join('LEFT JOIN __USER__ u ON o.uid = u.id ')
                    ->field('u.id,u.username,u.nickname,sum(o.number) buy_times')//个人购买次数
                    ->where(['pid'=>$pid])
                    ->group('u.id,u.username,u.nickname')
                    ->order('o.create_time DESC')
                    ->select();

            recordLog($rs_order,'购买人列表');                    

            foreach ($rs_order as $key => $value) {
                $options['openid'] = $value['username'];

                $dataParams['first'] = ['value' => '尊敬的会员，您参与的点金已出结果。', 'color' => $color];
                $dataParams['keyword1'] = ['value' => $options['good_name'], 'color' => $color];//XXX克黄金
                $dataParams['keyword2'] = ['value' => $options['lucky_num'], 'color' => $color];//幸运号 11000012
                $dataParams['keyword3'] = ['value' => $options['nickname'], 'color' => $color];//昵称 XXX
                $dataParams['keyword4'] = ['value' => $options['buy_times'], 'color' => $color];//次数 23
                $dataParams['keyword5'] = ['value' => $options['date'], 'color' => $color];//揭晓时间 2014年7月21日 18:36
                $dataParams['remark'] = ['value' => '请登录公众号查看参与详情。', 'color' => $color];

                $data['touser'] = $options['openid'];//openid
                $data['template_id'] = self::MSG_TEMPLATE_ID_LOTTERY;//模板ID
                $data['url'] = getHost() . '/shop.php?s=/Index/detail/pid/'.$period_map['pid'];//模板跳转链接
                $data['data'] = $dataParams;

                recordLog($data,'发送模板消息');                    

                $rs = D('api/Wechat')->sendTemplateMessage($data);
            }
        }

        return false;
    }
}	