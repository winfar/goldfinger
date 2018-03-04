<?php

namespace api\Controller;

use Think\Controller;

class WechatController extends BaseController
{   
    protected $host = '';
    protected $callbackHost = 'http://www.molijinbei.com';
	protected $callbackUrl = '/h5/pkshare/index.html';// 回调地址

    protected function _initialize()
    {
        parent::_initialize();

        $this->$host = $_SERVER['HTTP_HOST'];

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

                $map['trade_no'] = $result['out_trade_no'];

                $rechargeOrder = M('ShopOrder')->where($map)->find();

                if($rechargeOrder && $rechargeOrder['recharge']==1 && $rechargeOrder['order_status']==0){
                    $rechargeOrder['msg'] = '充值成功';
                    $rechargeOrder['code'] = 'OK';
                    $rechargeOrder['order_status'] = 1;
                    $rechargeOrder['order_status_time'] .= ','. time();
                    $rs = M('ShopOrder')->save($rechargeOrder);
                }
            }

        }catch(\Exception $e){
            recordLog($e->getMessage(),'微信支付回调异常');
        }
        // echo 'result:'.$result;
    }
}	