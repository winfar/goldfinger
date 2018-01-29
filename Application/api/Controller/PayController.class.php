<?php
/**
 * @deprecated 支付方法整合相关功能
 * @author gengguanyi
 * @date 2016-09-05
 **/
namespace api\Controller;

use Think\Controller;
use Think\Cache\Driver\RedisCache;

class PayController extends BaseController{
	
	//Ping++
	private $pay_type = array('gold','alipay', 'wx', 'upacp', 'yeepay_wap');    //支付方式(alipay支付宝、wx微信、upacp银联支付、yeepay_wap易宝)
	
	protected $md5_key = "busonline";
	
	public function _initialize()
    {
        parent::_initialize();
        vendor("Pay.init");
    }
	
    //生成订单信息
    public function generateorder(){

		$request_s = file_get_contents("php://input");
        //测试
        //	$request_s = '{"pid":"11","shopname":"12","price":"1","productdesc":""}';
        //记录LOG
        recordLog($request_s, "生成订单generateorder");

        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        
        $tokenid = $request['tokenid'];
		//判断是否登录 并且获取相关信息
        $userInfo = isLogin($tokenid);
        if ( !$userInfo ) {
            returnJson('', 100, '请登录！');
        }
        //生成订单号
        $mch_tradeno = uuid();
        $data = array(
            'pid' => $request['pid'],
            'shopname' => urldecode($request['shopname']) . "",
            "price" => $request['price'],//支付金额 改为购买次数
            "md5key" => think_md5($mch_tradeno, $this->md5_key),
            'mch_tradeno' => $mch_tradeno,
        );
        returnJson($data, 200, 'success');
    }
    
    
    //获取订单结果
    public function orderDetails($orderid,$pcode=""){
    	if ( isEmpty($orderid) ) {
            returnJson('', 401, '参数不能为空');
        }

        //判断是否有订单
        $shop_order = M("shop_order")->where('order_id='.$orderid)->find();
        
        if($shop_order){
            if($shop_order['cash']==0 && $shop_order['gold']>0){
                $data = D('Pay') -> pay_result($orderid,$pcode);
                returnJson($data, 200, 'success');
            }
        }

        $count = 0;
        while(!$shop_order){
            $shop_order = M("shop_order")->where('order_id='.$orderid)->find();
            $count++;

            if($count>=1){
                returnJson('', 404, '未查询到订单数据，可能与网络环境因素有关，请联系客服人员核对。');
            }
            sleep(1);
        }

        $data = D('Pay') -> pay_result($orderid,$pcode);
		if($data['order']==null && $data['shop']==null){
			returnJson($data, 504, '未查询到订单数据');
		}
		else{
        	returnJson($data, 200, 'success');
		}
    }

    /*
     * @deprecated 检查商品与价格
     * @author zhangran
     * @param $pid 		商品ID
     * 		  $price 	商品价格
     * @date 2016-07-07
     */
    protected function pay_check($pid, $price,$uid)
    {
        $price = abs(intval($price));
        if ( !is_numeric($price) ) {
            return '请输入数字';
        }
        if ( $pid > 0 ) {

            $info = M('shop_period')->table('__SHOP__ shop,__SHOP_PERIOD__ period')->field('shop.price,shop.ten,period.number')->where('shop.id=period.sid and period.id=' . intval($pid))->find();
            $ten = M('ten')->where(array('id' => $info["ten"], 'status' => 1))->find();
            $unit = $info["ten"] ? $ten['unit'] : 1;

            // if ( $ten["restrictions"] ) {
            //     $user_num = M('shop_record')->where(array('uid' => $uid, 'pid' => intval($pid)))->sum('number');
            //     if ( ($price + $user_num) > ($ten["restrictions_num"]) ) {
            //         return '购买数量超过限购数量';
            //     }
            // }
            
            if ( ($info['price']/$unit - $info['number']) == $price/$unit ) {
                return $price;
            } else {
                if ( $price % $unit == 0 ) {
                    return $price;
                } else {
                    return '购买数量错误';
                }
            }
        } else {
            return $price;
        }
    }

    //支付
    public function pay()
    {
        try{
            $request_s = file_get_contents("php://input");
            //记录LOG
            recordLog($request_s, "支付订单pay");
            $input_data = json_decode($request_s, true);

            if ( isEmpty($input_data['pay_channel']) ) {
                returnJson('', 402, 'pay_channel不能为空！');
            }
            $pay_channel = $input_data['pay_channel'];

            if ( isEmpty($input_data['tokenid']) ) {
                returnJson('', 401, 'tokenid不能为空！');
            }

            $tokenid = $input_data['tokenid'];
            $userInfo = isLogin($tokenid);
            if ( !$userInfo ) {
                returnJson('', 100, '请登录！');
            }

            $md5key = think_md5($input_data['mch_tradeno'], $this->md5_key);
            if ( $md5key != $input_data['md5key'] ) {
                returnJson('', 201, '签名错误');
            }

            $channel = $input_data['channel'];        //支付方式 alipay、wx
            if ( !in_array($channel, $this->pay_type) ) {
                returnJson('', 202, '支付方式参数错误');
            }

            //检查金币类型
            $gold = $input_data['gold'];
            if ( !is_numeric($gold) && $gold < 0) {
                returnJson('', 204, '金币类型错误');
            }

            //检查现金类型
            $cash = $input_data['cash'];
            if ( !is_numeric($cash) && $cash < 0) {
                returnJson('', 205, '现金类型错误');
            }

            if($cash==0 && $gold==0){
                returnJson('', 206, '现金与金币不能同时为0');
            }

            //检查用户金币余额
            if($gold > 0){
                $black=M('User')->where('id='.$userInfo['uid'])->getField('black');
                //recordLog($black.'#'.$gold, "pay order");
                if($black<$gold){
                    returnJson('', 207, '金币余额不足!');
                }
            }

            //检查商品与购买数量
            $checkInfo = $this->pay_check($input_data['pid'], (intval($gold)+intval($cash)), $userInfo['uid']);
            if ( !is_numeric($checkInfo) ) {
                returnJson('', 203, $checkInfo);
            }

            //写入临时表记录 orderno、pid、uid、shopname、price、gold、cash、time
            $dataArr = array(
                'order_id' => $input_data['mch_tradeno'],
                'pid' => $input_data['pid'],
                'uid' =>  $userInfo['uid'],
                'shopname' => urldecode($input_data['shopname']),
                'price' => 0,
                'gold' => $gold,
                'cash' => $cash,
                'create_time' => time()
            );

            $res = M('temporary_order')->add($dataArr);

            $orderno = $input_data['mch_tradeno'];            //订单号

            $shop_order = M('shop_order')->where('order_id='.$orderno)->find();

            if($shop_order){
                returnJson('', 264, '该订单已生成，请在我的摸金记录确认购买状态，如有问题请联系我们的客服人员进行核对!');
            }

            //金币支付
            if($cash<=0 && $gold>0){
                //$this->rechargeDeal($orderno, $orderno, true, $channel);

                //限制条件 
                $unit = getUnit($input_data['pid']);

                $result = D('Pay')->payadd($input_data['pid'], $orderno, ($gold/$unit), $userInfo['uid'], 1, $orderno,$gold,$cash);
                if($result){
                    returnJson('', 200, 'success');
                }
                else {
                    returnJson('支付失败！！！', 411, 'success');
                }
            }
            else {
                if($cash<1){
                    returnJson('支付金额不足1元', 455, '支付金额不足1元');
                }

                if($pay_channel == "ping++"){
                    //subject: required
                    //商品的标题，该参数最长为 32 个 Unicode 字符，银联全渠道（upacp/upacp_wap）限制在 32 个字节。
                    //$subject = urldecode($input_data['shopname']);    //商品名称
                    $subject = urldecode('金币');    //商品名称 
                    $body = $subject;                                //备注
                    $subject = mb_substr($subject, 0, 15,'utf-8');      //截取16位
                    $r = $this->payOrder($channel, $orderno, $cash, $subject, $body,$userInfo['uid']);

                    if($r['status']==200){
                        returnJson($r['msg'], 200, 'success');
                    }else{
                        returnJson($r['msg'], 500, 'error');
                    }
                }
            }
            
        }catch(\Exception $e){
            returnJson($e->getMessage(), 500, 'error');
        }
    }
}


