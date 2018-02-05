<?php
namespace Shop\Controller;
class IndexController extends BaseController {

    public function _initialize()
    {
        parent::_initialize();

        ini_set('date.timezone','Asia/Shanghai');

        // vendor("phpexcel.Classes.PHPExcel");

        vendor("Wechat.Pay.WxPay.JsApiPay");
        vendor("Wechat.Pay.log");

        // require_once "../lib/WxPay.Api.php";
        // require_once "WxPay.JsApiPay.php";
        // require_once 'log.php';

        //初始化日志
        $logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
        $log = Log::Init($logHandler, 15);
    }

    /**
     * 用户头部钻石公共页面
     * 
     * @return [type]        [description]
     */
    public function header()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $user = D('api/User')->getUserInfoByUid($this->uid);

        $this->assign('user', $user);
        $this->display($this->tplpath."header.html");
    }

    /**
     * 底部导航公共页面
     * 
     * @return [type] [description]
     */
    public function footer()
    {
        $this->display($this->tplpath."footer.html");
    }
    
    /**
     * 首页
     * 
     * @author liuwei
     * @param  integer $p   [description]
     * @param  integer $num [description]
     * @return [type]       [description]
     */
    public function index($p=1,$num=20){
        $is_close = 0;
        if (cookie('index_close_time')) {
            $cookie_time = cookie('index_close_time');//点击关闭时记录的时间
            $time = time().'000';//现在时间 毫秒
            $close_time = 60000 * 1;//间隔分钟数
            if (($time - $cookie_time) <= $close_time) {
                $is_close = 1;
            }

        }
        $p = 1;
        $number = C('LIST_ROWS');

        //$this->web_title = "我的商城"; 

        // $au = D('api/JuHe')->getAuShangHai(); 

        //用户信息
        $user = D('api/User')->getUserInfoByUid($this->uid);

        //用户金币余额
        // $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        // $pay_json = D('api/Pay')->getCoins($uid);//钻石
        // $pay = json_decode($pay_json, true);
        // $gold = empty($pay['data']['amount_coin']) ? 0 : $pay['data']['amount_coin'];
        $gold = !isset($user['gold_coupon']) ? 0 : $user['gold_coupon']; 
    	//分类列表
        // $cate_list = D('api/Category')->getTree(0, 'id,title'); 

        //中奖信息列表
        $msg_list = D('api/Message')->msglist(20);
        //是否有未读消息
        $notification_map = array();
        $notification_map['uid'] = $uid;
        $notification_map['isread'] = 0;
        $notification = M('message_user')->where($notification_map)->count();
        //分类id
        // $cate_id = empty(cookie('bo_cate_id')) ? 0 : cookie('bo_cate_id');

        //首页轮播图
        // $slider_list = D('api/Shop')->slider();

        //开奖周期
        $period_map['sid'] = 1;
        $period_map['state'] = 0;
        $period = D('api/Period')->periodInfo($period_map);
        $this->assign('is_close', $is_close);
        // $this->assign('au', $au);
        $this->assign('user', $user);
        $this->assign('gold', $gold);
        $this->assign('period', $period);
        $this->assign('msg_list', $msg_list);
        $this->assign('notification', $notification);
        // $this->assign('cate_list', $cate_list);
        // $this->assign('slider_list', $slider_list);
        // $this->assign('cate_id', $cate_id);
        $this->display($this->tplpath."index.html");
    }
    /**
     * 物流信息
     * 
     * @return [type] [description]
     */
    public function getselect()
    {
        $purchaseno = I('purchaseno');
        $list = D('api/order')->orderTrack($purchaseno);
        echo json_encode($list);
    }
    /**
     * ajax获取商品列表
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function ajaxshoplist()
    {
        //商品列表
        $cid = I('cid');//分类id
        // cookie('cate_id',$cid,array('expire'=>86400,'prefix'=>'bo_','path'=>'/'));
        // echo json_encode('200');
        $p = 1;
        $number = C('LIST_ROWS');
        
        $shop_list = D('api/Shop')->period($cid,$p,$number,'hits');
        echo json_encode($shop_list);
    }
    /**
     * 参与详情
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function detail(){
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        //开奖周期
        $period_map['sid'] = 1;
        $period_map['pid'] = I('pid');
        $period = D('api/Period')->periodInfo($period_map);
        $this->assign('uid',$uid);
        $this->assign('period',$period);
        $this->web_title = "参与详情";
        $this->display($this->tplpath."detail.html");
    }
    /**
     * ajax 参与详情
     * @return [type] [description]
     */
    public function ajaxdetail()
    {
        $map = array();
        $map['sid'] = 1;
        $map['pid'] = I('pid');
        $map['uid'] = $this->uid;
        $list = D('api/Period')->periodInfoList($map); 
        echo json_encode($list);  
    }
    /**
     * 参与详情
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function info(){
        //开奖周期
        $period_map['sid'] = 1;
        $period_map['pid'] = I('pid');
        $period = D('api/Period')->periodInfo($period_map);
        $this->assign('period',$period);
        $this->web_title = "计算详情";
        $this->display($this->tplpath."cal_detail.html");
    }
    /**
     * 往期揭晓
     * @return [type] [description]
     */
    public function announce()
    {
        $data = array();
        //开奖周期
        $period_map = array();
        $period_map['sid'] = 1;
        $period_map['state'] = 0;
        $list = D('api/Period')->periodInfo($period_map);
        
        //最新一期没有开奖的
        $map = array();
        $map['sid'] = 1;
        $map['state'] = 1;
        $period = D('api/Period')->periodInfo($map); 
        if (!empty($list)) {
            $data = $list;
            if ($list['down_time']>0 and !empty($period)) {
                $data = $period;
            }
        }
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id

        $this->web_title = "往期揭晓";
        $this->assign('uid',$uid);
        $this->assign('list',$data);
        $this->assign('period',$period);
        $this->display($this->tplpath."announce.html");
    }
    /**
     * ajax获取最新揭晓列表
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function ajaxannouncedlist()
    {
        $p = 1;
        $size = C('LIST_ROWS');
        $sid = 1;
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $list = D('api/Shop')->history($p,$size,$sid,$uid);
        echo json_encode($list);
    }

    /**
     * 购买
     * @return [type] [description]
     */
    public function ajaxaddorder()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $pid = I('pid');
        $sid = I('shopid');
        $price = I('price');
        $type = I('type');
        $gold = I('gold');
        $list = D('api/Pay')->pay($pid,$sid,$price,$uid,$type,$gold);
    }
    /**
     * 金价曲线图
     * @return [type] [description]
     */
    public function goldprice()
    {
        // $this->web_title = "金价资讯";
        $this->display($this->tplpath."gold_price.html");
    }
    
    /**
     * 通知
     * @return [type] [description]
     */
    public function notice(){
        $this->web_title = "通知";
        $this->display($this->tplpath."notice.html");
    }
    /**
     * ajax 获取通知列表
     * @return [type] [description]
     */
    public function ajaxnotice()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $p = 1;
        $size = C('LIST_ROWS');
        $list = D('api/Message')->getListByUid($p, $size, $uid);
        $data = array();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data[] = $value;
                $data[$key]['msg_date'] = date('Y.m.d', $value['msg_time']);
            }
        }
        echo json_encode($data);
    }
    /**
     * 提取黄金
     *
     * @author wenyuan
     * @return [type] [description]
     */
    public function draw(){
        // $shopid = empty($_GET['shopid']) ? 0 : I('shopid');//商品id
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        // $pay_json = D('api/Pay')->getCoins($uid);//钻石
        // $pay = json_decode($pay_json, true);
        // $gold = empty($pay['data']['amount_coin']) ? 0 : $pay['data']['amount_coin'];
        //用户信息
        $user = D('api/User')->getUserInfoByUid($this->uid);
        $channel_id = 0;
        if (!empty($user['channelid'])) {
            $channel_id = $user['channelid'];
        }
        $gold = !isset($user['gold_coupon']) ? 0 : $user['gold_coupon']; 
        //渠道信息
        $channel_info = D('api/User')->getChannelInfo($channel_id);
        $address_info = D('api/User')->address_item($uid);//收货地址详情

        $gold_price = getGoldprice();
        $gold_fee_1 = ceil($gold_price * $channel_info['extract_gold_persent'] / 100 + $channel_info['extract_gold_extra_expenses']) * $channel_info['proportion']; 
        $gold_fee = ceil($channel_info['cash_first_gold'] * $gold_price * $channel_info['extract_gold_persent'] / 100 + $channel_info['extract_gold_extra_expenses']) * $channel_info['proportion'];
        $this->web_title = "提取黄金";
        $this->assign('address_info', $address_info);
        $this->assign('gold', $gold);
        $this->assign('user', $user);
        $this->assign('channel_info', $channel_info);
        $this->assign('gold_fee', $gold_fee);
        $this->assign('gold_fee_1', $gold_fee_1);
        $this->display($this->tplpath."draw.html");
    }
    /**
     * 提金操作
     * @return [type] [description]
     */
    public function ajaxdraw()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $sid = empty($_POST['sid']) ? 1 : I('sid');//商品id
        $cash_number = empty($_POST['cash_number']) ? 0 : I('cash_number');//提金克数
        echo $r = D('api/Gold')->draw($uid,$cash_number,$sid);//用户id，提金数量(g)，商品id
    }
    /**
     * 黄金提现页面
     * @return [type] [description]
     */
    public function drawcash()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $pay_json = D('api/Pay')->getCoins($uid);//钻石
        $pay = json_decode($pay_json, true);
        $gold = empty($pay['data']['amount_coin']) ? 0 : $pay['data']['amount_coin'];
        //用户信息
        $user = D('api/User')->getUserInfoByUid($this->uid);
        $channel_id = 0;
        if (!empty($user['channelid'])) {
            $channel_id = $user['channelid'];
        }
        //渠道信息
        $channel_info = D('api/User')->getChannelInfo($channel_id);
        $address_info = D('api/user')->address_item($uid);//收货地址详情
        $this->assign('address_info', $address_info);
        $this->assign('gold', $gold);
        $this->assign('user', $user);
        $this->assign('channel_info', $channel_info);
        $this->web_title = "黄金提现";
        $this->display($this->tplpath."draw_cash.html");
    }
    /**
     * 提现成功
     * @return [type] [description]
     */
    public function ajaxdrawcash()
    {
        $param = I();
        $param['uid'] = empty($this->uid) ? 0 : $this->uid;//用户id
        $param['sid'] = empty($_POST['sid']) ? 1 : I('sid');//商品id
        echo $r = D('api/Gold')->drawcash($param);//用户id，提金数量(g)，商品id
    }
    /**
     * 提现成功
     * @return [type] [description]
     */
    public function drawcashsuc()
    {
        $this->web_title = "提现成功";
        $this->display($this->tplpath."cash_suc.html");
    }
    /**
     * 通知
     * @return [type] [description]
     */
    public function cashlist(){
        $this->web_title = "提现纪录";
        $this->display($this->tplpath."cash_list.html");
    }
    /**
     * ajax 获取通知列表
     * @return [type] [description]
     */
    public function ajaxcashlist()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $field = "id,order_no,number,create_time";
        $list = D('api/User')->getCashList($uid,$field);
        echo json_encode($list);
    }
    /**
     * 提金成功页面
     * @return [type] [description]
     */
    public function drawsuc()
    {

        $this->web_title = "提金成功";
        $this->display($this->tplpath."get_suc.html");
    }
    /**
     * 获取是否已开奖
     * @return [type] [description]
     */
    public function getperiod()
    {
        $pid = I('pid');
        $state = M('shop_period')->where('id='.$pid)->getField('state');
        echo json_encode($state);
    }
    /**
     * 参与记录
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function record(){
        $uid = $this->uid;//用户id
        //开奖周期
        $period_map['sid'] = 1;
        $period_map['state'] = 0;
        $period = D('api/Period')->periodInfo($period_map);
        $this->assign('uid', $uid);
        $this->assign('period', $period);
        $this->web_title = "参与记录";
        $this->display($this->tplpath."record.html");
    }
    /**
     * ajax获取夺宝记录
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function ajaxresultlist()
    {
        //商品列表
        $uid = $this->uid;//用户id
        $cid = I('cid');//分类id
        $p = 1;
        $size = C('LIST_ROWS');
        $list = D('api/user')->recordsShopNew($uid,$p,$size,$cid);
        echo json_encode($list);
    }

    /**
     * 中奖号码
     * 
     * @author liuwei
     * @return [type] [description]
     */
    public function usernumbers()
    {
        $uid = empty($_POST['uid']) ? 0 : I('uid');//用户id
        $pid = empty($_POST['pid']) ? 0 : I('pid');//期数id
        $oid = empty($_POST['oid']) ? 0 : I('oid');//期数id
        $item = D('api/shop')->user_num_one($uid,$pid,$oid);
        echo json_encode($item);
    }
    /**
     * 收货地址
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function address(){
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        // $pay_json = D('api/Pay')->getCoins($uid);//钻石
        // $pay = json_decode($pay_json, true);
        // $gold = empty($pay['data']['amount_coin']) ? 0 : $pay['data']['amount_coin'];
        $user = D('api/User')->getUserInfoByUid($this->uid);
        $gold = !isset($user['gold_coupon']) ? 0 : $user['gold_coupon'];
        $info = D('api/user')->address_item($uid);//收货地址详情
        $this->web_title = "收货地址";
        $this->assign('gold', $gold);
        $this->assign('user', $user);
        $this->assign('uid', $uid);
        $this->assign('info', $info);
        $this->display($this->tplpath."address.html");
    }
    /**
     * 收货地址编辑
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function addressedit()
    {
        $data = array();
        $data['code'] = 101;
        $data['msg'] = "未知错误";
        if (IS_POST) {
            $post = $_POST;
            if (empty($post['uid'])) {
                $data['msg'] = "未登录";
            } elseif (empty($post['nickname'])) {
                $data['msg'] = "请填写收货人";
            } elseif(empty($post['tel'])) {
                $data['msg'] = "请填写手机号码";
            } elseif(empty($post['address'])) {
                $data['msg'] = "请填写详细地址";
            } else {
                $nickname_number = 25;//收货人最大长度
                $nickname_length = mb_strlen(I('nickname'), 'utf8');//收货人填写长度
                $address_number = 500;//收货地址最大长度
                $address_length = mb_strlen(I('address'), 'utf8');//收货地址填写长度
                $email_number = 80;//email最大长度
                $email_length = empty($post['email']) ? 0 : mb_strlen(trim(I('email')), 'utf8');//email填写长度
                $tel_code = isMobile($post['tel']);//手机验证 200正确
                $email_code = empty($post['email']) ? 200  : isEmail(trim($post['email']));//邮箱验证 200正确
                $model = D('api/user');
                if ($nickname_length>$nickname_number) {
                    $data['msg'] = "收货人长度不超过".$nickname_number."个字符";
                } elseif ($tel_code !=200){
                    $data['msg'] = "手机号不正确";
                } elseif ($address_length>$address_number){
                    $data['msg'] = "详细地址长度不超过".$address_number."个字符";
                } elseif ($email_code !=200){
                    $data['msg'] = "邮箱格式不正确";
                } elseif ($email_length>$email_number){
                    $data['msg'] = "邮箱长度不超过".$email_number."个字符";
                } else {
                   $result = $model->address_edit();
                    if (200==$result['code']) {
                        if (!empty($post['pid'])) {
                            $pid = $post['pid'];
                            $uid = $post['uid'];
                            $model->editOrderStatus($pid,0,$uid);//收货地址
                        }
                        $data['code'] = 200;
                        $data['msg'] = "添加地址成功";
                    } else {
                        $data['msg'] = $result['msg'];
                    } 
                }
                
            } 

        }
        echo json_encode($data);
    }
    /**
     * 提金明细
     * 
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function goldlist()
    {
        $this->web_title = "提金明细";
        $this->display($this->tplpath."gold_list.html");
    }
    /**
     * ajax - 提现明细
     * @return [type] [description]
     */
    public function ajaxgoldlist()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $field = "id,order_id,number,create_time,order_status,order_status_time";
        $list = D('api/User')->getGoldList($uid,$field);
        echo json_encode($list);
    }
    /**
     * 提金说明
     * 
     * @return [type] [description]
     */
    public function explain()
    {
        $this->web_title = "提金说明";
        $this->display($this->tplpath."explain.html");
    }
    /**
     * 参与及玩法技巧
     * 
     * @return [type] [description]
     */
    public function playIntro()
    {
        $this->web_title = "参与及玩法技巧";
        $this->display($this->tplpath."play_intro.html");
    }
    /**
     * 参与成功
     * @return [type] [description]
     */
    public function suc()
    {
        $this->assign('pid', I('pid'));
        $this->web_title = "参与成功";
        $this->display($this->tplpath."suc.html");
    }
    /**
     * 提金协议
     * @return [type] [description]
     */
    public function agreement()
    {
        $this->web_title = "提金协议";
        $this->display($this->tplpath."agreement.html");
    }
    /**
     * 确认收货
     * @return [type] [description]
     */
    public function editstatus()
    {
        $uid = $this->uid;//用户id
        $id = I('id');//提现id
        $status = I('status');//状态
        $result = D('api/user')->editCashStatus($id,$status,$uid);
        echo json_encode($result);
    }
    /**
     * 参与
     * @return [type] [description]
     */
    public function virtualkeyboard()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        //用户信息
        $user = D('api/User')->getUserInfoByUid($this->uid);
        $channel_id = 0;
        if (!empty($user['channelid'])) {
            $channel_id = $user['channelid'];
        }
        //渠道信息
        $channel_info = D('api/User')->getChannelInfo($channel_id);
        //开奖周期
        $period_map['sid'] = 1;
        $period_map['state'] = 0;
        $period_map['uid'] = $uid;
        $info = D('api/Period')->periodInfo($period_map);

        $this->assign('info', $info);
        $this->assign('channel_info', $channel_info);
        $this->display($this->tplpath."virtualkeyboard.html");
    }
    /**
     * 获取 md5串 和 实时金价
     * @return [type] [description]
     */
    public function ajaxmd5()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        //金价 & md5
        $pre_order = D('api/Pay')->preOrder($uid);
        echo json_encode($pre_order);
    }
    /**
     * 购买
     * @return [type] [description]
     */
    public function ajaxpay()
    {
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        $pid = I('pid');//期号
        $sid = empty($_POST['sid']) ? 1 : I('sid');//商品id
        $price = I('price');//数量
        $type = 0;//夺宝
        $gold = I('gold');//钻石
        $md5 = I('htmlmd5');//钻石
        $log_html = 'pid='.$pid.' and sid='.$sid.' and price='.$price.' and uid='.$uid.' and type='.$type.' and gold='.$gold.' and md5='.$md5;
        $list = D('api/Pay')->pay($pid,$sid,$price,$uid,$type,$gold,$md5);
    }

    public function recharge(){
        $uid = empty($this->uid) ? 0 : $this->uid;//用户id
        //用户信息
        $user = D('api/User')->getUserInfoByUid($uid);
        if($user){
            $map = ['status'=>1];
            $list = M('ExchangeRecharge')->where($map)->select();

            $this->assign('user', $user);
            $this->assign('_list', $list);
        }

        $this->display($this->tplpath."recharge.html");
    }

    public function test()
    {
        echo getGoldprice();
    }

    public function ajaxGetGoldPrice()
    {
        echo getGoldprice();
    }

    public function config(){
        $conifg=array(
            "app_title"=>$this->web_title,
            "app_currency"=>$this->web_currency,
            "app_wx_appid"=>C('APP_WX_APPID'),
            "app_ali_partnerid"=>C('APP_ALI_PAY_PARTNER'),
            "app_ali_selleremail"=>C('APP_ALI_PAY_SELLER_EMAIL'),
            "app_ali_paykey"=>C('APP_ALI_PAY_KEY'),
            "app_qq_appid"=>C('APP_QQ_APPID')
        );
        $this->ajaxReturn($conifg);
    }
}