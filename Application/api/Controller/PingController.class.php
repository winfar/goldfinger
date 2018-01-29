<?php
/**
 * @deprecated Ping++支付相关功能
 * @author zhangran
 * @date 2016-07-07
 **/
namespace api\Controller;

use Think\Controller;
use Think\Cache\Driver\RedisCache;

class PingController extends BaseController
{
    private $pay_type = array('gold','alipay', 'wx', 'upacp', 'yeepay_wap');    //支付方式(alipay支付宝、wx微信、upacp银联支付、yeepay_wap易宝)
    private $md5_key = "busonline";                //MD5传输KEY

    
    public function _initialize()
    {
        parent::_initialize();
        vendor("Pay.init");
        
        
    }

    /**
     * @deprecated 生成订单信息
     * @author zhangran
     * @date 2016-07-07
     */
    public function generateorder()
    {
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

    /**
     * @deprecated 充值
     * @author wenyuan
     * @date 2016-09-01
     **/
    public function recharge()
    {
        try{
            
            $request_s = file_get_contents("php://input");
            //记录LOG
            recordLog($request_s, "ping充值");

            //测试
            //$request_s = '{"tokenid":"d30dd34b49740c5b1bdda077b848429f","mch_tradeno":"771314096443273216","md5key":"dd12603093f94187c54e97281ff9fce8","channel":"alipay","cash":1}';

            $input_data = json_decode($request_s, true);
            
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

            //检查现金类型
            $cash = $input_data['cash'];
            if ( !is_numeric($cash) && $cash <= 0) {
                returnJson('', 205, '现金类型错误');
            }

            //写入临时表记录 orderno、pid、uid、shopname、price、gold、cash、time
            $dataArr = array(
                'order_id' => $input_data['mch_tradeno'],
                'pid' => 0,
                'uid' =>  $userInfo['uid'],
                'shopname' => "充值-ping++",
                'price' => 0,
                'gold' => 0,
                'cash' => $cash,
                'create_time' => time()
            );

            $res = M('temporary_order')->add($dataArr);

            $orderno = $input_data['mch_tradeno'];            //订单号
            $amount = $cash;                //支付金额

            //subject: required
            //商品的标题，该参数最长为 32 个 Unicode 字符，银联全渠道（upacp/upacp_wap）限制在 32 个字节。
            //$subject = urldecode($input_data['shopname']);    //商品名称
            $subject = urldecode('金币');    //商品名称 
            $body = $subject;                                //备注
            $subject = mb_substr($subject, 0, 15,'utf-8');      //截取16位
            $r = $this->payOrder($channel, $orderno, $amount, $subject, $body,$userInfo['uid']);
            if($r['status']==200){
                returnJson($r['msg'], 200, 'success');
            }else{
                returnJson($r['msg'], 500, 'error');
            }
        }catch(\Exception $e){
            returnJson($e->getMessage(), 500, 'error');
        }
    }

    /**
     * @deprecated Ping++支付
     * @author zhangran
     * @date 2016-07-07
     **/
    public function pay()
    {
        try{
            $request_s = file_get_contents("php://input");
            //记录LOG
            recordLog($request_s, "支付订单pay");
            $input_data = json_decode($request_s, true);

            if ( isEmpty($input_data['tokenid']) ) {
                returnJson('', 401, 'tokenid不能为空！');
            }

            $tokenid = $input_data['tokenid'];
            $userInfo = isLogin($tokenid);
            if ( !$userInfo ) {
                returnJson('', 100, '请登录！');
            }

            //测试
            // 		$input_data_s = '{"pid":"98","price":"1","channelid":"2","channel":"alipay","mch_tradeno":"740459516197416960","shopname":"%E7%89%A9%E5%93%81%E5%90%8D%E7%A7%B0","uid":"123","md5key":"ae645b8f0d191a253e5fb282584c1e07","extendinfo":""}';
            //		$input_data = json_decode($input_data_s, true);

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
                returnJson('', 264, '请勿重复支付，请在我的摸金记录确认购买状态，如有问题请联系我们的客服人员进行核对!');
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
                    returnJson($result, 411, '支付失败！！！');
                }
            }
            else {
                if($cash<1){
                    returnJson('支付金额不足1元', 455, '支付金额不足1元');
                }
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
            
        }catch(\Exception $e){
            returnJson($e->getMessage(), 500, 'error');
        }
    }

    /**
     * @deprecated 获取订单结果
     * @author wenyuan
     * @date 2016-08-01
     */
    public function orderDetails($orderid)
    {
        if ( isEmpty($orderid) ) {
            returnJson('', 401, '参数不能为空');
        }

        //判断是否有订单
        $shop_order = M("shop_order")->where('order_id='.$orderid)->find();
        // if($shop_order){
        //     $data = D('Pay') -> pay_result($orderid);
        // }
        // else {

        //     sleep(1);
        //     $this->orderDetails($orderid);
        // }

        if($shop_order){
            if($shop_order['cash']==0 && $shop_order['gold']>0){
                $data = D('Pay') -> pay_result($orderid);
                returnJson($data, 200, 'success');
            }
        }
        
        $count = 0;
        while(!$shop_order){
            //echo '-- while begin --';

            $shop_order = M("shop_order")->where('order_id='.$orderid)->find();
            $count++;

            if($count>=5){
                returnJson('', 404, '未查询到订单数据，可能与网络环境因素有关，请联系客服人员核对。');
            }
            sleep(1);
            //echo '\n -- sleep --'.$count;
        }
        $data = D('Pay') -> pay_result($orderid);
        returnJson($data, 200, 'success');
    }


    /**
     * @deprecated 订单支付封装
     * @author zhangran
     * @date 2016-07-07
     **/
    function payOrder($channel, $orderNo, $amount, $subject, $body, $uid)
    {
        //拦截微信支付提示网关维护
        if($channel == 'wx'){
            returnJson('', 251, '由于支付网关维护，暂不可用，请选择其他支付方式支付');
        }

        // if($channel == 'wx' || $channel == 'alipay'){
        //     returnJson('', 251, '由于支付网关维护，暂不可用，请选择其他支付方式支付');
        // }

        $ping_config = C('PingPay_SDK');
        \Pingpp\Pingpp::setPrivateKeyPath($ping_config['privateCacert']);
        $api_key = $ping_config['api_key'];
        $app_id = $ping_config["app_id"];
        /**
         * $extra 在使用某些渠道的时候，需要填入相应的参数，其它渠道则是 array()。
         * 以下 channel 仅为部分示例，未列出的 channel 请查看文档 https://pingxx.com/document/api#api-c-new
         */

        /** https://www.pingxx.com/guidance/config
        *yeepay_wap 适用于移动端网页支付，需要与易宝当地分公司签署「易宝一键支付」服务协议。
        *发起支付请求需要额外的参数 product_category、identity_id、identity_type、terminal_type、terminal_id、user_ua 和 result_url。
        *product_category 为商品类别码，详见商品类型码表；
        *identity_id 为用户标识,商户生成的用户账号唯一标识，最长 50 位字符串；
        *identity_type 为用户标识类型，详见用户标识类型码表；
        *terminal_type 为终端类型，对应取值 0:IMEI, 1:MAC, 2:UUID, 3:other；
        *terminal_id 为终端 ID；
        *user_ua 为用户使用的移动终端的 UserAgent 信息；
        *result_url 为前台通知地址。
        *Ping++ 把这七个参数放在了 Charge 对象的 extra 字段里。在发起交易请求的时候需要在 extra 里填写 product_category、identity_id、identity_type、terminal_type、terminal_id、user_ua、result_url。
         */
        $extra = array();
        switch ( $channel ) {
            case  "yeepay_wap":    //易宝
                $extra['product_category']=20;
                $extra['identity_id']='10013633040';
                $extra['identity_type']=2;
                $extra['terminal_type']=3;
                $extra['terminal_id']=$uid;
                $extra['user_ua']=$_SERVER['HTTP_USER_AGENT'];
                $extra['result_url']='http://passport.busonline.com/api.php?s=/Ping/payHooks';
                break;
        }

        // 设置 API Key
        \Pingpp\Pingpp::setApiKey($api_key);
        try {
            $ch = \Pingpp\Charge::create(
                array(
                    'subject' => $subject,
                    'body' => $body,
                    'amount' => floatval($amount) * 100,
                    'order_no' => $orderNo,
                    'currency' => 'cny',
                    'extra' => $extra,
                    'channel' => $channel,
                    'client_ip' => $_SERVER['REMOTE_ADDR'],
                    'app' => array('id' => $app_id)
                )
            );
            $rs = array("status" => 200, "msg" => $ch);
            return $rs;
        } catch ( \Pingpp\Error\Base $e ) {
            $rs = array("status" => 500, "msg" => $e->getMessage());
            return $rs;
        }
    }

    /**
     * @deprecated Ping++支付后回调
     * @author zhangran
     * @date 2016-07-07
     **/
    public function payHooksTest()
    {
        try{
            $raw_data = file_get_contents('php://input');
            recordLog($raw_data, "支付回调payhooksTest");
            //测试
            //$raw_data = '{"id":"evt_eYa58Wd44Glerl8AgfYfd1sL","created":1434368075,"livemode":true,"type":"charge.succeeded","data":{"object":{"id":"ch_bq9IHKnn6GnLzsS0swOujr4x","object":"charge","created":1434368069,"livemode":true,"paid":true,"refunded":false,"app":"app_vcPcqDeS88ixrPlu","channel":"wx","order_no":"2015d019f7cf6c0d","client_ip":"140.227.22.72","amount":100,"amount_settle":0,"currency":"cny","subject":"An Apple","body":"A Big Red Apple","extra":{},"time_paid":1434368074,"time_expire":1434455469,"time_settle":null,"transaction_no":"1014400031201506150354653857","refunds":{"object":"list","url":"/v1/charges/ch_bq9IHKnn6GnLzsS0swOujr4x/refunds","has_more":false,"data":[]},"amount_refunded":0,"failure_code":null,"failure_msg":null,"metadata":{},"credential":{},"description":null}},"object":"event","pending_webhooks":0,"request":"iar_Xc2SGjrbdmT0eeKWeCsvLhbL"}';

            //$raw_data = '{"id":"evt_lSAQm5ouwnNONiKjyK1ATBnW","created":1470140125,"livemode":true,"type":"charge.succeeded","data":{"object":{"id":"ch_SaLOaLSGm1S0jr1K8CDivXXT","object":"charge","created":1470140114,"livemode":true,"paid":true,"refunded":false,"app":"app_44ivb9GuHyPSDqHG","channel":"yeepay_wap","order_no":"760448863717912576","client_ip":"111.202.112.196","amount":100,"amount_settle":100,"currency":"cny","subject":"Apple iPhone6s","body":"Apple iPhone6s 64G 颜色随机 中奖即发","extra":{"product_category":20,"identity_id":"10013633040","identity_type":2,"terminal_type":0,"terminal_id":"did","user_ua":"UserAgent","result_url":"http://www.baidu.com"},"time_paid":1470140125,"time_expire":1470226514,"time_settle":null,"transaction_no":"411608027291569212","refunds":{"object":"list","url":"/v1/charges/ch_SaLOaLSGm1S0jr1K8CDivXXT/refunds","has_more":false,"data":[]},"amount_refunded":0,"failure_code":null,"failure_msg":null,"metadata":{},"credential":{},"description":null}},"object":"event","pending_webhooks":0,"request":"iar_T0ePaDXjTuX15yDK0GaD48i5"}';

    
            if ( !isset($raw_data) ) {
                http_response_code(400);    //数据不能为空
            }

            $result = $this->pingHooksDeal($raw_data);
            if ( $result["code"] != 200 ) {
                http_response_code(500);    //签名验证失败
            } else {
                $rs = $result["msg"];
                if ( $result['type'] == "charge" ) {        //支付回调
                    //回调处理
                    $this->rechargeDeal($rs["order_no"], $rs["id"], $rs['paid'], $rs['channel'], intval($rs['amount']/100));
                } elseif ( $result['type'] == "refund" ) {    //退款回调

                }
            }
        } catch(\Exception $e){
            recordLog($e->getMessage(), "支付回调payhooks Exception");
            http_response_code(500);
        }
    }

    /**
     * @deprecated Ping++支付后回调
     * @author zhangran
     * @date 2016-07-07
     **/
    public function payHooks()
    {
        try{
            $raw_data = file_get_contents('php://input');
            recordLog($raw_data, "支付回调payhooks");
            //测试
            //$raw_data = '{"id":"evt_eYa58Wd44Glerl8AgfYfd1sL","created":1434368075,"livemode":true,"type":"charge.succeeded","data":{"object":{"id":"ch_bq9IHKnn6GnLzsS0swOujr4x","object":"charge","created":1434368069,"livemode":true,"paid":true,"refunded":false,"app":"app_vcPcqDeS88ixrPlu","channel":"wx","order_no":"2015d019f7cf6c0d","client_ip":"140.227.22.72","amount":100,"amount_settle":0,"currency":"cny","subject":"An Apple","body":"A Big Red Apple","extra":{},"time_paid":1434368074,"time_expire":1434455469,"time_settle":null,"transaction_no":"1014400031201506150354653857","refunds":{"object":"list","url":"/v1/charges/ch_bq9IHKnn6GnLzsS0swOujr4x/refunds","has_more":false,"data":[]},"amount_refunded":0,"failure_code":null,"failure_msg":null,"metadata":{},"credential":{},"description":null}},"object":"event","pending_webhooks":0,"request":"iar_Xc2SGjrbdmT0eeKWeCsvLhbL"}';

            //$raw_data = '{"id":"evt_lSAQm5ouwnNONiKjyK1ATBnW","created":1470140125,"livemode":true,"type":"charge.succeeded","data":{"object":{"id":"ch_SaLOaLSGm1S0jr1K8CDivXXT","object":"charge","created":1470140114,"livemode":true,"paid":true,"refunded":false,"app":"app_44ivb9GuHyPSDqHG","channel":"yeepay_wap","order_no":"760448863717912576","client_ip":"111.202.112.196","amount":100,"amount_settle":100,"currency":"cny","subject":"Apple iPhone6s","body":"Apple iPhone6s 64G 颜色随机 中奖即发","extra":{"product_category":20,"identity_id":"10013633040","identity_type":2,"terminal_type":0,"terminal_id":"did","user_ua":"UserAgent","result_url":"http://www.baidu.com"},"time_paid":1470140125,"time_expire":1470226514,"time_settle":null,"transaction_no":"411608027291569212","refunds":{"object":"list","url":"/v1/charges/ch_SaLOaLSGm1S0jr1K8CDivXXT/refunds","has_more":false,"data":[]},"amount_refunded":0,"failure_code":null,"failure_msg":null,"metadata":{},"credential":{},"description":null}},"object":"event","pending_webhooks":0,"request":"iar_T0ePaDXjTuX15yDK0GaD48i5"}';

    
            if ( !isset($raw_data) ) {
                http_response_code(400);    //数据不能为空
            }

            $result = $this->pingHooksDeal($raw_data);

            //recordLog(var_dump($result), "pay result");

            if ( $result["code"] != 200 ) {
                http_response_code(500);    //签名验证失败
            } else {

                //recordLog($result["msg"], "pay msg");

                $rs = $result["msg"];
                if ( $result['type'] == "charge" ) {        //支付回调
                    //回调处理
                    $this->rechargeDeal($rs["order_no"], $rs["id"], $rs['paid'], $rs['channel'],intval($rs['amount']/100));
                } elseif ( $result['type'] == "refund" ) {    //退款回调

                }
            }
        } catch(\Exception $e){
            recordLog($e->getMessage(), "支付回调payhooks Exception");
            http_response_code(500);
        }
    }

    /**
     * @deprecated pingWebHooks通知
     * @author zhangran
     * @date 2016-07-07
     **/
    function  pingHooksDeal($raw_data)
    {
        $ping_config = C('PingPay_SDK');
        $headers = \Pingpp\Util\Util::getRequestHeaders();
        // 签名在头部信息的 x-pingplusplus-signature 字段
        //测试
        //$signature = 'BX5sToHUzPSJvAfXqhtJicsuPjt3yvq804PguzLnMruCSvZ4C7xYS4trdg1blJPh26eeK/P2QfCCHpWKedsRS3bPKkjAvugnMKs+3Zs1k+PshAiZsET4sWPGNnf1E89Kh7/2XMa1mgbXtHt7zPNC4kamTqUL/QmEVI8LJNq7C9P3LR03kK2szJDhPzkWPgRyY2YpD2eq1aCJm0bkX9mBWTZdSYFhKt3vuM1Qjp5PWXk0tN5h9dNFqpisihK7XboB81poER2SmnZ8PIslzWu2iULM7VWxmEDA70JKBJFweqLCFBHRszA8Nt3AXF0z5qe61oH1oSUmtPwNhdQQ2G5X3g==';
        //$pub_key_path = $ping_config['test_publickCacert'];

        $signature = isset($headers['X-Pingplusplus-Signature']) ? $headers['X-Pingplusplus-Signature'] : NULL;
        
        $pub_key_path = $ping_config['publickCacert'];
        $result = $this->verify_signature($raw_data, $signature, $pub_key_path);

        //recordLog($signature .' # ' . $result, "支付回调签名与结果");

        $arr = array();
        if ( $result === 1 ) {
            $event = json_decode($raw_data, true);
            $charge = $event['data']['object'];
            if ( $event['type'] == 'charge.succeeded' ) {    //支付成功回调
                return array('type' => 'charge', 'code' => '200', 'msg' => $charge);
            } elseif ( $event['type'] == 'refund.succeeded' ) {    //退款成功回调
                return array('type' => 'refund', 'code' => '200', 'msg' => $charge);
            }
        } elseif ( $result === 0 ) {
            return array('code' => '201', 'msg' => '签名验证失败');
        } else {
            return array('code' => '201', 'msg' => '系统其它错误');
        }
    }

    /*
     * @deprecated 签名验证
     * @author zhangran
     * @date 2016-07-07
     **/
    function verify_signature($raw_data, $signature, $pub_key_path)
    {
        $pub_key_contents = file_get_contents($pub_key_path);
        // php 5.4.8 以上，第四个参数可用常量 OPENSSL_ALGO_SHA256
        return openssl_verify($raw_data, base64_decode($signature), $pub_key_contents, 'sha256');
    }

    /*
     * @deprecated 回调订单处理
     * @author zhangran
     * @param $pay_order_number 自身订单号
     * 		 $order_number 	ping++支付产生订单号
     * @date 2016-07-07
     */
    function rechargeDeal($pay_order_number, $order_number, $paid, $channel,$amount)
    {
        //更新订单回调状态
        D('TemporaryOrder')->updateCallbackStatus($pay_order_number);
        if($amount<1){
            recordLog('amount:'.$amount, "支付金额不足");
            http_response_code(401); 
        }

        if ( !$pay_order_number || !$order_number ) {
            http_response_code(402);    //订单号或支付订单号为空
        }

        if ( $paid && $amount > 0) {    
            //支付成功

            //查询是否有订单记录，防止未成功的回调多次修改数据
            $shop_order = M("shop_order")->where('order_id='.$pay_order_number)->find();

            if(!$shop_order){
                switch ( $channel ) {
                    case 'gold' :
                        $type = 1;
                        break;
                    case 'wx' :
                        $type = 2;
                        break;
                    case 'alipay' :
                        $type = 3;
                        break;
                    case 'yeepay_wap' :
                        $type = 4;
                        break;
                    default :
                        $type = 20000;
                        break;
                }

                $result = 0;
                $temporaryOrderInfo = M('temporary_order')->field(true)->where(array('order_id' => $pay_order_number))->find();
            
                if($temporaryOrderInfo['pid']==0){
                    //充值
                    $result = D('Pay')->recharge($temporaryOrderInfo['uid'],$pay_order_number,$amount,$type,1,$order_number);
                }
                else{
                    //支付
                    $period = M('shop_period')->where('id='.$temporaryOrderInfo['pid'])->find();
                    $shop = M('shop')->where('status=1 and display=1 and id='.$period['sid'])->find();

                    //限制条件 
                    $restrictions = getRestrictions($shop['ten']);
                    if($restrictions){
                        $shop_count = ($amount+$temporaryOrderInfo['gold']) / $restrictions['unit'];
                    }

                    //增加交易记录
                    $result = D('Pay')->payadd($period['id'], $pay_order_number, $shop_count, $temporaryOrderInfo['uid'], $type, $order_number,$temporaryOrderInfo['gold'],$amount);
                }
                
                if ( $result ) {
                    //确认订单是否成功
                    $shop_order = M("shop_order")->where('order_id='.$pay_order_number)->find();
                    if($shop_order){
                        http_response_code(200);    //success
                    }
                    else{
                        http_response_code(404);    //更新交易记录失败
                    }
                } else {
                    http_response_code(403);    //更新交易记录失败
                }
            }
        } else {
            http_response_code(405);    //支付失败
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
    
    
    
    
    
    
    
	
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}