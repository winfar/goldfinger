<?php
namespace api\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;
use Vendor\Tools;
use Com\Alidayu\AlidayuClient as Client;
use Com\Alidayu\Request\SmsNumSend;
use Think\Storage;

class UserModel extends Model
{
    protected $_validate = array(
        array('oldpassword', 'require', '请输入原密码！', self::EXISTS_VALIDATE, 'regex', self::MODEL_UPDATE),
        array('password', '6,30', '密码长度必须在6-30个字符之间！', self::EXISTS_VALIDATE, 'length')
    );
	
    protected $_auto = array(
        array('password', 'think_ucenter_md5', self::MODEL_BOTH, 'function'),
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('login_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('status', '1')
    );
    
    /***
     * 生成tokenId
     * @return string
     */
    private function getTokenId()
    {
        return $tokenId = "tokenId:" . time();
    }

    public function randString($len = 6)
    {
        $chars = str_repeat('0123456789', $len);
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
        return $str;
    }

    public function smsSendCode($mobile)
    {
        try {

            $client = new Client;
            $request = new SmsNumSend;

            // $client = new Com\Alidayu\AlidayuClient;
            // $request = new Com\Alidayu\Request\SmsNumSend;

            $code = $this->randString(4);
            $title = C("WEB_SITE_TITLE");
            $smsParams = array(
                'code' => $code
            );
            // 设置请求参数
            $req = $request->setSmsTemplateCode('SMS_13230323')
                ->setRecNum($mobile)
                ->setSmsParam(json_encode($smsParams))
                ->setSmsFreeSignName('摸金达人')
                ->setSmsType('normal')
                ->setExtend('reg');
            $request = $client->execute($req);

            $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];

            if ( $reqResult === true ) {
                $redisCache = new \Think\Cache\Driver\RedisCache();
                $redisCache->set($mobile, $code, 600);//短信保存至缓存十分钟
                return $request;
            } else {
                recordLog($request,'验证码短信');
                return false;
            }
            
        } catch ( \Excetion $e ) {
            recordLog($e->getMessage(), '验证码短信');
            returnJson('',500,'短信发送失败');
        }
    }

    /**
     * @deprecated 我的晒单
     * @author zhangkang
     * @date 2016-7-6
     **/
    public function getShared($tokenid, $pageindex, $pagesize)
    {
        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的晒单记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }

        $displays = D('User')->displaysNew($pageindex, $map['uid'], $pagesize);

        return $displays;
    }

    /**
     * @deprecated 我的晒单列表合并pk
     * @author gengguanyi
     * @date 2016-10-17
     **/
    public function getSharedNew($tokenid, $pageindex, $pagesize)
    {
        return $this->getShared($tokenid, $pageindex, $pagesize);
    }

    /**
     * @deprecated 晒单列表
     * @author zhangkang
     * @date 2016-7-6
     **/
//    public function getShareList($pageindex, $pagesize, $tokenid)
//    {
//        $uid = "";
//        if ( $tokenid ) {
//            $user = isLogin($tokenid);
//            $uid = $user['uid'];
//        }
//        $displays = D('User')->displays($pageindex, $uid, $pagesize, '');
//
//        return $displays;
//    }

    /**
     * 记录用户访问日志
     * @author wenyuan
     * @date 2017-2-14
     **/
    public function addUserAccessLog($uid)
    {
        $rs = false;
        $map = array();
        $map['uid'] = $uid;
        $map['create_time'] = array(array('egt',strtotime(date('Y-m-d'))),array('lt',strtotime(date('Y-m-d'))+86400)); 
        $model = M('user_access_log');
        $user = $model->field("uid")->where($map)->find();

        // exit($model->getLastSql());

        if($user === null){
            $data = array();
            $data['uid'] = $uid;
            $data['create_time'] = NOW_TIME;
            $data['ip'] = getIP();
            $data['url'] = $_SERVER['REQUEST_URI'];

            $rs = $model->add($data);
        }

        return $rs;
    }
	
    
    //新增或更新用户信息 
    public function addUserInfo($param = array())
    {
        $data = array();
        $data['id'] = $param['uid'];
        $data['nickname'] = $param['nickname'];
        $data['username'] = $param['username'];
        $data['phone'] = $param['phone'];
        $data['password'] = $param['password'];
        $data['headimgurl'] = $param['headimgurl'];
        $data['channelid'] = $param['channelid'];

        $data['status'] = 1;
        $data['create_time'] = NOW_TIME;
        $data['login_ip'] = getIP();
        $data['login_time'] = NOW_TIME;
        $data['activation'] = 1;

        
        try{
            $liveUser = D('api/PlatformApi')->getUserInfo($param['uid']);
            if($liveUser['code']=='1000'){
                $data['phone'] = $liveUser['result']['bandPhone'];
                if(empty($data['phone'])){
                    if(preg_match("/^1[34578]{1}\d{9}$/",$liveUser['result']['username'])){  
                        $data['phone'] = $liveUser['result']['username'];
                    }
                }
                $data['spread'] = $liveUser['result']['isAnchor'];//是否主播
                $data['passport_uid'] = $liveUser['result']['organizationId'];//工会id
            }
            else{
                recordLog(json_decode($liveUser),'同步LIVE用户数据失败');
            }
        }
        catch(Exception $e){
            recordLog(json_decode($e),'同步LIVE用户数据异常');
        }
        

        $map = array();
        $map['id'] = $param['uid'];
        $model = M('User');
        $user = $model->field("id")->where($map)->find();
        $new_uid = 0;
        if ( !$user ) {
            //判断分销渠道（分公司CPS结算）
            // if ( $param['channelid'] ) {
            //     $data['channelid'] = $param['channelid'];
            // } else {
            //     $data['channelid'] = 1;
            // }

            //应用市场下载渠道
            // $data['market_channel'] = "guanfang";
            
            $model->startTrans();

            $new_uid = M('User')->add($data);

            if ( $new_uid ) {
                //注册送金币,等运营配置，暂时不上,commit记得判断
                //$goldRecord_rs = D('GoldRecord')->register($new_uid, 1);

                //注册送积分
                //$pointRs = D('Point')->addPointByUid(1000, 101, $new_uid);
            } else {
                recordLog(json_decode($data), '同步用户数据失败-addUserInfo');
            }

            if($new_uid>0){
                $model->commit();
                return $new_uid;
            }
            else {
                $model->rollback();
                return false;
            }
        } else {
            $data1['nickname'] = $param['nickname'];
            $data1['headimgurl'] = $param['headimgurl'];
            // $data1['channelid'] = $param['channelid'];
            $data1['login_time'] = NOW_TIME;

            $data1['spread']= $data['spread'];
            $data1['passport_uid']= $data['passport_uid'];
            if(!empty($data['phone'])){
                $data1['phone']= $data['phone'];
            }

            $new_uid = $model->where('id=' . $user['id'])->save($data1);
            return $user['id'];
        }
    }

     //新增或更新用户信息---机器码登录 
    public function addUserInfoNew($param = array())
    {
        $data = array();
        $data['passport_uid'] = $param['passport_uid'];
        $data['nickname'] = $param['nickname'];
        $data['username'] = $param['username'];
        $data['phone'] = $param['phone'];
        $data['password'] = $param['password'];
        $data['create_time'] = NOW_TIME;
        $data['login_ip'] = getIP();
        $data['status'] = 1;

        $data['headimgurl'] = $param['headimgurl'];

        $data['activation'] = 1;
        $map = array();
        $map['passport_uid'] = $param['passport_uid'];

        recordLog(json_decode($data), '同步用户数据失败1');
        
        $model = M('User');
        $user = $model->field("id")->where($map)->find();
        $new_uid = 0;
        if ( !$user ) {
            //判断分销渠道（分公司CPS结算）
            if ( $param['channelid'] ) {
                $data['channelid'] = $param['channelid'];
            } else {
                $data['channelid'] = 1;
            }
            //应用市场下载渠道
            $data['market_channel'] = $param['market_channel'];
            
            $model->startTrans();

            recordLog(json_decode($data), '同步用户数据失败2');

            $new_uid = M('User')->add($data);

            if ( $new_uid ) {
                //注册送金币,等运营配置，暂时不上,commit记得判断
                //$goldRecord_rs = D('GoldRecord')->register($new_uid, 1);
                //注册送积分
                //$pointRs = D('Point')->addPointByUid(1000, 101, $new_uid);
            } else {
                recordLog(json_decode($data), '同步用户数据失败-addUserInfoNew');
            }

            if($new_uid>0){
                $model->commit();
                return $new_uid;
            }
            else {
                $model->rollback();
                return false;
            }
        } else {
            $new_uid = $model->where('id=' . $user['id'])->setField('passport_uid', $param['passport_uid']);
            return $new_uid;
        }
    }

    //新增促销活动赠送红包+赠送金币+赠送积分
    public function  addSaleRegister($uid){
        $redis_array = array();
        $now_time = time();
        $register_sales_promotion = M('sales_promotion')->where('type=3 AND begin_time <='.$now_time.' AND end_time >='.$now_time)->field(true)->find();
        if($register_sales_promotion){
            $range_ids = $register_sales_promotion['red_ids'];//红包ids
            $remark = json_decode($register_sales_promotion['remark']);//获取赠送金币+赠送积分
            if(!empty($range_ids)){
                $red_envelope_list = M('red_envelope')->where('begin_time <='.$now_time.' AND end_time >='.$now_time.' AND id in('.$range_ids.')')->field('id,quantity,name')->select();
                if($red_envelope_list){
                    foreach($red_envelope_list as $k=>$v){
                        $quantity_count = M('red_envelope_record')->where('red_envelope_id='.$v['id'])->count();
                        if($quantity_count > $v['quantity']){
                            //添加注册红包
                            $red_date = array();
                            $red_date['red_envelope_id'] = $v['id'];
                            $red_date['uid'] = $uid;
                            $red_date['status'] = 0;
                            $red_date['create_time'] = time();
                            M('red_envelope_record')->add($red_date);
                            $redis_array[] = array('type'=>2,'title'=>$v['name']);
                        }
                    }
                }
            }
            //获取赠送金币
            $black = $remark[0]->赠送金币;
            //判断注册成是否送过金币
            $map_gold = array();
            $map_gold['uid'] = $uid;
            $map_gold['typeid'] = 11;
            $if_register_gold = M('gold_record')->where($map_gold)->find();
            if($if_register_gold){
            }else{
                //注册送金币
                $goldRs = D('Point')->addGoldByUid($black, 11, $uid);
                $redis_array[] = array('type'=>1,'title'=>$black);
            }
            //获取赠送积分
            $point = $remark[0]->赠送积分;
            //判断是否有注册积分
            $map_point = array();
            $map_point['user_id'] = $uid;
            $map_point['type_id'] = 101;
            $if_register_point = M('Point_record')->where($map_point)->find();
            if($if_register_point){
            }else{
                //注册送积分
                $pointRs = D('Point')->addPointByUid($point, 101, $uid);
            }
            $redisCache = new RedisCache();
            $redisRs = $redisCache->set($uid, $redis_array,30);
        }
    }

    public function getUserId($passportuid)
    {
        $map = array();
        $map['passport_uid'] = $passportuid;
        $uid = M('User')->field("id")->where($map)->find();
        return $uid['id'];
    }

    public function update()
    {
        if ( !$data = $this->create() ) {
            return false;
        }
        $data['id'] = UID;
        unset($data['password']);
        $res = $this->save($data);
        return $res;
    }

    public function password()
    {
        if ( !$data = $this->create() ) {
            return false;
        }
        if ( I('post.password') !== I('post.repassword') ) {
            $this->error = '您输入的新密码与确认密码不一致！';
            return false;
        }
        if ( !$this->verifyUser(UID, I('post.oldpassword')) ) {
            $this->error = '验证出错：密码不正确！';
            return false;
        }
        $this->id = UID;
        $res = $this->save();
        return $res;
    }

    public function getBlack()
    {
        return M('User')->where("id=" . UID)->getField('black');
    }

    public function addOrderAddress($param = array())
    {
        $tokenid = $param['tokenid'];
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $data = array();
        $data['order_status'] = 100;
        $data['contacts'] = $param['contacts'];
        $data['phone'] = $param['phone'];
        $data['address'] = $param['address'];
        $data['order_status_time'] = time();

        $shoporder = M('shop_period')->where("uid=" . $user["uid"] . " and id=" . $param["pid"])->save($data);

        //$shoporder = M('shop_order')->where("uid=" . $user["uid"] . " and pid=" . $param["pid"])->save($data);
        if ( $shoporder >= 0 ) {
            returnJson('', '200', 'success');
        } else {
            returnJson('', '1', '确认地址失败');
        }
    }

    public function updateOrderStatus($param = array())
    {
        $tokenid = $param['tokenid'];
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

//        $orderStatus = 0;
//        switch ( $param['order_status'] ) {
//            case 1:
//                $orderStatus = 100;
//                break;
//            case 2:
//                $orderStatus = 101;
//                break;
//            case 3:
//                $orderStatus = 102;
//                break;
//            case 4:
//                $orderStatus = 103;
//                break;
//        }
        $rs = D("Shop")->updateOrderStatus($user["uid"],$param["pid"],$param['order_status']);
        if ( $rs >= 0 ) {
            returnJson('', '200', 'success');
        } else {
            returnJson('', '1', '更新状态失败');
        }

        // $order = M('shop_period')->where("uid=" . $user["uid"] . " and id=" . $param["pid"])->find();
        // //$order = M('shop_order')->where("uid=" . $user["uid"] . " and pid=" . $param["pid"])->find();
        // if ( $order ) {
        //     $data = array();
        //     $data['order_status'] = $param['order_status'];
        //     $data['order_status_time'] = $order['order_status_time'] . ',' . time();
        //     $shoporder = M('shop_period')->where("uid=" . $user["uid"] . " and id=" . $param["pid"])->save($data);
        //     //$shoporder = M('shop_order')->where("uid=" . $user["uid"] . " and pid=" . $param["pid"])->save($data);
        //     if ( $rs >= 0 ) {
        //         returnJson('', '200', 'success');
        //     } else {
        //         returnJson('', '1', '更新状态失败');
        //     }
        // } else {
        //     returnJson('', '1', '更新状态失败');
        // }
    }

    public function updateUserPwd($param = array())
    {
        $data = array();
        $data['password'] = $param['password'];
        M('User')->where("passport_uid=" . $param["passport_uid"])->save($data);
    }

    public function  resetUserPwd($param = array())
    {
        $data = array();
        $data['password'] = $param['password'];
        M('User')->where("phone=" . $param["phone"])->save($data);
    }

    /**
     * @deprecated 我的夺宝记录接口
     * @author zhangran
     * @date 2016-07-06
     **/
    public function records($tokenid, $pageindex = 1, $pagesize = 20, $state)
    {

        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的夺宝记录
            $id = $tokenid;
            $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $id = $user['uid'];    //用户ID
            //  $id=101601;
            $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        }

        //  $id=101601;
//        $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        $where_state = "";
        if ( is_numeric($state) && $state >= 0 ) {
            if ( $state == 2 ) {
                $where_state = ' and period.state>=' . $state;
            } else {
                $where_state = ' and period.state=' . $state;
            }
        }
        $period = M('shop_period')
            ->table($subQuery . ' record,__SHOP_PERIOD__ period')
            ->field('period.*')
            ->where('record.pid=period.id' . $where_state)
            ->group('record.pid')
            ->page($pageindex, $pagesize)
            ->order('period.state asc,record.create_time desc')
            ->select();

        //echo M()->getLastSql();exit();

        if ( $period ) {
            foreach ( $period as $k => $v ) {
                $info = M('shop')->field(true)->find($v["sid"]);

                $list[$k]['next_pid'] = getLatestPeriodByShopId($info['id']);

                $list[$k]['pid'] = $v["id"];

                //限制条件 
                $list[$k]['restrictions'] = getRestrictions($info['ten']);

                if ( $list[$k]['restrictions'] ) {
                    $list[$k]["price"] = $info["price"] / $list[$k]['restrictions']['unit'];
                } else {
                    $list[$k]["price"] = $info["price"];
                }

                //$list[$k]['price'] = $info["price"];                    //总参与人数
                $list[$k]['number'] = $v['number'];                     //已参与人数
                $list[$k]["surplus"] = $list[$k]["price"] - $v["number"];   //剩余人数
                $list[$k]['state'] = $v['state'];        //状态
                $list[$k]['no'] = $v['no'];                //期号
                $list[$k]["path"] = get_cover($info["cover_id"], "path");    //商品图片
                $list[$k]['name'] = $info['name'];        //商品名称
                $list[$k]['user_name'] = get_user_name($id);    //获奖者名称
                $numbers = D('Shop')->user_num($id, $v["id"]);

                $list[$k]["proc_type"] = $info['proc_type'];//处理类型 shop=实物相关处理，card=虚拟卡相关处理，goldbag=金袋类型处理 ,
                $list[$k]["count"] = count($numbers);//我参与的总次数

                if ( $v['state'] == 2 ) {
                    $list[$k] = D('Shop')->overChange($info, $v);
                    $list[$k]['user'] = $this->userChange($v);
                    $list[$k]['count'] = M('shop_record')->where("uid=" . $id . " and pid=" . $v["id"])->sum('number');
                    $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

                    if ( $exchangeConfigStatus <= 0 ) {
                        $list[$k]['status_gold'] = -1;//商品兑换比例配置 停用
                    } else {
                        $goldSql = "SELECT gold.* FROM hx_gold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where tradetype.`code`=1006 AND gold.pid=".$v["id"];
                        $goldInfo = $this->query($goldSql, false);
                        if ( $v['status_gold'] ) {//有值就是已经兑换过商品
                            $list[$k]['status_gold'] = 1;
                            $list[$k]['gold'] = floor($goldInfo[0]['gold']);
                            $bid = $info['brand_id']; //品牌id；
                            $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                            $list[$k]['rate_gold'] = $rate;
                        } else {
                            $list[$k]['status_gold'] = 0;//未兑换
                            $bid = $info['brand_id']; //品牌id；
                            $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                            if( $info['proc_type'] == 'goldbag'){
                                $list[$k]['rate_gold'] = 100;
                                $list[$k]['gold'] = $info["price"];
                            }else{
                                $list[$k]['rate_gold'] = $rate;
                                $list[$k]['gold'] = floor(($rate/100)*intval($info['buy_price']));

                            }
                        }
                    }
                }

                if ( $v["uid"] > 0 && $v["uid"] != $id ) {
                    $otheruser_name = get_user_name($v["uid"]);
                    $list[$k]['otheruser_name'] = $otheruser_name;
                    $number = D('Shop')->user_num($v["uid"], $v["id"]);
                    $list[$k]["othercount"] = count($number);//其他人参与的总次数

                    //$shoporder = M('shop_period')->field(true)->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->find();
                    //$shoporder = M('shop_order')->field(true)->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->find();
                } else {
                    //$shoporder = M('shop_period')->field(true)->where("uid=" . $id . " and pid=" . $v["id"])->find();
                    //$shoporder = M('shop_order')->field(true)->where("uid=" . $id . " and pid=" . $v["id"])->find();
                }

                // if ( $shoporder ) {
                //     $list[$k]['order_status'] = $shoporder['order_status'];
                //     $list[$k]['contacts'] = $shoporder['contacts'];
                //     $list[$k]['phone'] = $shoporder['phone'];
                //     $list[$k]['address'] = $shoporder['address'];

                //     $order_status_time = explode(",", $shoporder['order_status_time']);
                //     array_unshift($order_status_time, $v['kaijang_time']);
                //     $list[$k]['order_status_time'] = array_filter($order_status_time);
                // }

                $list[$k]['order_status'] = $v['order_status'];
                $list[$k]['contacts'] = $v['contacts'];
                $list[$k]['phone'] = $v['phone'];
                $list[$k]['address'] = $v['address'];

                $order_status_time = explode(",", $v['order_status_time']);
                array_unshift($order_status_time, $v['kaijang_time']);
                $list[$k]['order_status_time'] = $order_status_time;

                if ( $v['state'] == 2 ) {
                    // //虚拟卡信息
                    // $list[$k]['card'] = $this->getCard($info, $v);

                    //虚拟物品
                    $visualGoods = $this->getVisualGoods($info,$v);
                    $list[$k]['card']=$visualGoods['card'];
                    $list[$k]['order_status']=$visualGoods['order_status'];
                    $list[$k]['order_status_time']=$visualGoods['order_status_time'];
                }

                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description'], $list[$k]['shopinterval'], $list[$k]['periodnumber'], $list[$k]['shopstock']);
            }
        }
        return $list;
    }
    /**
     * @deprecated 我的夺宝记录合并pk接口
     * @author 耿贯一
     * @date 2016-10-14
     **/
    public function recordsShopNew($uid=0, $pageindex = 1, $pagesize = 20, $state)
    {
        $kaijiang_time = empty(C("KAIJANG_TIME")) ? 1 : C("KAIJANG_TIME");//倒计时提前几分钟 默认1分钟
        $where_state = " and record.uid=".$uid;
        $time = time()-$kaijiang_time*60;//现在时间戳
        if ($state==1) {//进行中
            $where_state .= ' and period.state in (0,1)';
        } elseif($state==2) {//已开奖
            $where_state .= ' and period.state=2';
        } elseif($state==3) {//中奖纪录
            $where_state = " and period.uid=".$uid." and period.state=2";
        }
        $sql = "SELECT period.*,(select (case when sum(number) is NUll then 0 else ABS(sum(number)) end ) from bo_shop_record where pid = period.id) as total_number,(select (case when sum(buy_gold) is NUll then 0 else ABS(sum(buy_gold)) end ) from bo_shop_order where pid = period.id) as total_buy_gold,(select (case when sum(number) is NUll then 0 else ABS(sum(number)) end ) from bo_shop_record where pid = period.id and uid= ".$uid.") as user_total_number FROM bo_shop_record record,bo_shop_period period WHERE ( period.id = record.pid ".$where_state." )";
        $offsize = ($pageindex-1)*$pagesize;
        $sql .= " GROUP BY record.pid ORDER BY period.state asc,record.create_time desc limit ".$offsize.",".$pagesize;
        $period = $this->query($sql, false);
        $list = array();    
        if ( $period ) {
            foreach ( $period as $k => $v ) {
                $list[] = $v;
                //判断是否中奖
                $list[$k]['iswin'] = $uid == $v["uid"] ? 1 : 0; 
                //用户信息
                $list[$k]['uid'] = $uid;
                $list[$k]['nickname'] = get_user_name($uid);
                $list[$k]["img"] = get_user_pic_passport($uid);
                $list[$k]['next_pid'] = getLatestPeriodByShopId($info['id']);
                $list[$k]['kaijang_date'] = date("m.d H:i:s",$v['kaijang_time']);
                $rate = ($v['user_total_number']/$v['total_number'])*100;
                $list[$k]['rate'] = is_int($rate) ? $rate :sprintf("%.2f", $rate);
                $list[$k]['total_buy_gold'] /= 1000;

            }
        }
        return $list;
    }

    /**
     * @deprecated 我的夺宝记录合并pk接口
     * @author 耿贯一
     * @date 2016-10-14
     **/
    public function recordsNew($tokenid, $pageindex = 1, $pagesize = 20, $state)
    {

        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的夺宝记录
            $id = $tokenid;
            $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $id = $user['uid'];    //用户ID
            //  $id=101601;
            $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        }

        //  $id=101601;
//        $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);

        $where_state = "";
        if ( is_numeric($state) && $state >= 0 ) {
            if ( $state == 2 ) {
                $where_state = ' and period.uid>0 and period.kaijang_time>0 and period.state>=' . $state;//我的摸金记录-已揭晓-如果是已下架，返回已开奖的数据
            } else {
                $where_state = ' and period.state=' . $state;
            }
        }

        $period = M('shop_period')
            ->table($subQuery . ' record,__SHOP_PERIOD__ period')
            ->field('period.*')
            ->where('record.pid=period.id' . $where_state)
            ->group('record.pid')
            ->page($pageindex, $pagesize)
            ->order('period.state asc,record.create_time desc')
            ->select();

        //echo M()->getLastSql();exit();

        if ( $period ) {
            foreach ( $period as $k => $v ) {

                //判断商品是pk商品还是普通摸金
                $is_common = $v['iscommon'];
                $pkinfo = array();
                $info = M('shop')->field(true)->find($v["sid"]);
                if ($is_common != 1) {
                    //获取pk商品相关信息
                    $pkinfo = M('house_manage')
                    ->table('__HOUSE_MANAGE__ house,__PKCONFIG__ pkconfig')
                    ->field('house.ispublic,house.id as houseid,house.no as room_no,house.uid as ownerid,pkconfig.id as pkid,pkconfig.peoplenum,pkconfig.amount')
                    ->where('house.id='.$v['house_id'].' AND house.pksetid=pkconfig.id')
                    ->find();
                }
                if ( $v['state'] == 2 ) {
                    if($is_common == 1){//普通商品
                        $list[$k] = D('Shop')->overChange($info, $v);
                    }else{//pk商品
                        //增加用到的参数
                        $v['amount'] = $pkinfo['amount'];//pk商品总价钱
                        $v['peoplenum'] = $pkinfo['peoplenum'];//pk购买人数
                        $list[$k] = D('Shop')->pkoverChange($info, $v);
                    }
                    $list[$k]['user'] = $this->userChange($v);
                    $list[$k]['count'] = M('shop_record')->where("uid=" . $id . " and pid=" . $v["id"])->sum('number');
                    $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

                    if ( $exchangeConfigStatus <= 0 ) {
                        $list[$k]['status_gold'] = -1;//商品兑换比例配置 停用
                    } else {
                        $goldSql = "SELECT gold.* FROM hx_gold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where tradetype.`code`=1006 AND gold.pid=".$v["id"];
                        $goldInfo = $this->query($goldSql, false);
                        if ( $v['status_gold'] ) {//有值就是已经兑换过商品
                            $list[$k]['status_gold'] = 1;
                            $list[$k]['gold'] = floor($goldInfo[0]['gold']);
                            $bid = $info['brand_id']; //品牌id；
                            $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                            $list[$k]['rate_gold'] = $rate;
                        } else {
                            $list[$k]['status_gold'] = 0;//未兑换
                            $bid = $info['brand_id']; //品牌id；
                            $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                            if( $info['proc_type'] == 'goldbag'){
                                $list[$k]['rate_gold'] = 100;
                                $list[$k]['gold'] = $info["price"];
                            }else{
                                $list[$k]['rate_gold'] = $rate;
                                $list[$k]['gold'] = floor(($rate/100)*intval($info['buy_price']));

                            }
                        }
                    }
                }
                $list[$k]['iscommon'] = $v['iscommon'];

                $list[$k]['next_pid'] = getLatestPeriodByShopId($info['id']);

                $list[$k]['pid'] = $v["id"];

                //限制条件 
                $list[$k]['restrictions'] = getRestrictions($info['ten']);

                if($list[$k]['iscommon'] == 1){//普通商品
                    if ( $list[$k]['restrictions'] ) {
                        $list[$k]["price"] = $info["price"] / $list[$k]['restrictions']['unit'];
                    } else {
                        $list[$k]["price"] = $info["price"];
                    }

                    $list[$k]['number'] = $v['number'];                     //已参与人数
                    $list[$k]["surplus"] = $list[$k]["price"] - $v["number"];   //剩余人数

                }else{//pk商品
                    $list[$k]['houseid'] = $pkinfo['houseid'];//房间id
                    $list[$k]['pkid'] = $pkinfo['pkid'];//pkid
                    $list[$k]['ownerid'] = $pkinfo['ownerid'];//房主id
                    $list[$k]['room_no'] = $pkinfo['room_no'];//房间编号
                    $list[$k]['ispublic'] = $pkinfo['ispublic'];//房间公开与否0：公开房间 1：私密房间
                    $list[$k]['price'] = $pkinfo['peoplenum'];//总需要参与人数

                    if ( $list[$k]['restrictions'] ) {
                        $list[$k]['number'] = $v['number']/($pkinfo['amount']/$list[$k]['restrictions']['unit']/$pkinfo['peoplenum']);                     //已参与人数
                        $list[$k]["surplus"] = $list[$k]["price"] - $list[$k]["number"];   //剩余人数
                    }else{
                        $list[$k]['number'] = $v['number']/($pkinfo['amount']/$pkinfo['peoplenum']);                     //已参与人数
                        $list[$k]["surplus"] = $list[$k]["price"] - $list[$k]["number"];   //剩余人数
                    }
                    

                }

                //$list[$k]['price'] = $info["price"];                    //总参与人数
                
                $list[$k]['state'] = $v['state'];        //状态
                $list[$k]['no'] = $v['no'];                //期号
                $list[$k]["path"] = get_cover($info["cover_id"], "path");    //商品图片
                $list[$k]['name'] = $info['name'];        //商品名称
                $list[$k]['user_name'] = get_user_name($id);    //获奖者名称
                $numbers = D('Shop')->user_num($id, $v["id"]);

                $list[$k]["proc_type"] = $info['proc_type'];//处理类型 shop=实物相关处理，card=虚拟卡相关处理，goldbag=金袋类型处理 ,

                if($list[$k]['iscommon'] == 1){//普通商品
                    $list[$k]["count"] = count($numbers);//我参与的总次数

                }else{//pk商品
                    if ( $list[$k]['restrictions'] ) {
                        $list[$k]["count"] = $pkinfo['amount']/$list[$k]['restrictions']['unit']/$pkinfo['peoplenum'];        
                    }else{
                        $list[$k]["count"] = $pkinfo['amount']/$pkinfo['peoplenum']; //我参与的总次数
                    }
                }

                

                if ( $v["uid"] > 0 && $v["uid"] != $id ) {
                    $otheruser_name = get_user_name($v["uid"]);
                    $list[$k]['otheruser_name'] = $otheruser_name;
                    $number = D('Shop')->user_num($v["uid"], $v["id"]);
                    $list[$k]["othercount"] = count($number);//其他人参与的总次数

                    //$shoporder = M('shop_period')->field(true)->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->find();
                    //$shoporder = M('shop_order')->field(true)->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->find();
                } else {
                    //$shoporder = M('shop_period')->field(true)->where("uid=" . $id . " and pid=" . $v["id"])->find();
                    //$shoporder = M('shop_order')->field(true)->where("uid=" . $id . " and pid=" . $v["id"])->find();
                }

                // if ( $shoporder ) {
                //     $list[$k]['order_status'] = $shoporder['order_status'];
                //     $list[$k]['contacts'] = $shoporder['contacts'];
                //     $list[$k]['phone'] = $shoporder['phone'];
                //     $list[$k]['address'] = $shoporder['address'];

                //     $order_status_time = explode(",", $shoporder['order_status_time']);
                //     array_unshift($order_status_time, $v['kaijang_time']);
                //     $list[$k]['order_status_time'] = array_filter($order_status_time);
                // }

                $list[$k]['order_status'] = $v['order_status'];
                $list[$k]['contacts'] = $v['contacts'];
                $list[$k]['phone'] = $v['phone'];
                $list[$k]['address'] = $v['address'];

                $order_status_time = explode(",", $v['order_status_time']);
                array_unshift($order_status_time, $v['kaijang_time']);
                $list[$k]['order_status_time'] = $order_status_time;

                if ( $v['state'] == 2 ) {
                    // //虚拟卡信息
                    // $list[$k]['card'] = $this->getCard($info, $v);

                    //虚拟物品
                    $visualGoods = $this->getVisualGoods($info,$v);
                    $list[$k]['card']=$visualGoods['card'];
                    $list[$k]['order_status']=$visualGoods['order_status'];
                    $list[$k]['order_status_time']=$visualGoods['order_status_time'];
                }

                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description'], $list[$k]['shopinterval'], $list[$k]['periodnumber'], $list[$k]['shopstock']);
            }
        }
        return $list;
    }

     /**
     * @deprecated 我的pk夺宝记录接口
     * @author zhangran
     * @date 2016-07-06
     **/
     public function pkrecords($tokenid, $pageindex = 1, $pagesize = 20, $state){
         if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的pk夺宝记录
            $id = $tokenid;
            $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $id = $user['uid'];    //用户ID
            //  $id=101601;
            $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        }

        //  $id=101601;
//        $subQuery = M('shop_record')->field(true)->where('uid=' . $id)->order('create_time desc')->select(false);
        $where_state = "";
        if ( is_numeric($state) && $state >= 0 ) {
            if ( $state == 2 ) {
                //$where_state = ' and period.state>=' . $state;
                $where_state = ' and period.uid>0 and period.kaijang_time>0 and period.state>=' . $state;
            } else {
                $where_state = ' and period.state=' . $state;
            }
        }
        $period = M('shop_period')
            ->table($subQuery . ' record,__SHOP_PERIOD__ period,__HOUSE_MANAGE__ house,__PKCONFIG__ pkconfig')
            ->field('period.*,pkconfig.id as pkid,pkconfig.peoplenum,pkconfig.amount,pkconfig.inventory,house.id as houseid')
            ->where('record.pid=period.id AND period.iscommon=2 AND house.periodid = record.pid AND house.pksetid = pkconfig.id ' . $where_state)
            ->group('record.pid')
            ->page($pageindex, $pagesize)
            ->order('period.state asc,record.create_time desc')
            ->select();

        //echo M()->getLastSql();exit();

        if ( $period ) {
            foreach ( $period as $k => $v ) {
                $info = M('shop')->field(true)->find($v["sid"]);

                $list[$k]['next_pid'] = getLatestPeriodByShopId($info['id']);

                $list[$k]['pid'] = $v["id"];
                $list[$k]['houseid'] = $v['houseid'];//房间id
                $list[$k]['pkid'] = $v['pkid'];//pkid
                //限制条件 
                $list[$k]['restrictions'] = getRestrictions($info['ten']);

                if ( $list[$k]['restrictions'] ) {
                    $list[$k]["price"] = $v["peoplenum"];//总人数
                    $list[$k]['number'] = $v['number']/($v['amount']/$list[$k]['restrictions']['unit']/$v['peoplenum']);                     //已参与人数
                    $list[$k]["surplus"] = $list[$k]["price"] - $v["number"];   //剩余人数

                } else {
                    $list[$k]["price"] = $v["peoplenum"];//总人数
                    $list[$k]['number'] = $v['number']/($v['amount']/$v['peoplenum']);                     //已参与人数
                    $list[$k]["surplus"] = $list[$k]["price"] - $v["number"];   //剩余人数
                }

                //$list[$k]['price'] = $info["price"];                    //总参与人数
                
                $list[$k]['state'] = $v['state'];        //状态
                $list[$k]['no'] = $v['no'];                //期号
                $list[$k]["path"] = get_cover($info["cover_id"], "path");    //商品图片
                $list[$k]['name'] = $info['name'];        //商品名称
                $list[$k]['user_name'] = get_user_name($id);    //获奖者名称
                $numbers = D('Shop')->user_num($id, $v["id"]);

                $list[$k]["proc_type"] = $info['proc_type'];//处理类型 shop=实物相关处理，card=虚拟卡相关处理，goldbag=金袋类型处理 ,
                $list[$k]["count"] = count($numbers);//我参与的总次数

                if ( $v['state'] == 2 ) {
                    $list[$k] = D('Shop')->pkoverChange($info, $v);
                    $list[$k]['user'] = $this->userChange($v);
                    $list[$k]['count'] = M('shop_record')->where("uid=" . $id . " and pid=" . $v["id"])->sum('number');//我参与的次数
                    $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

                    if ( $exchangeConfigStatus <= 0 ) {
                        $list[$k]['status_gold'] = -1;//商品兑换比例配置 停用
                    } else {
                        $goldSql = "SELECT gold.* FROM hx_gold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where tradetype.`code`=1006 AND gold.pid=".$v["id"];
                        $goldInfo = $this->query($goldSql, false);
                        if ( $v['status_gold'] ) {//有值就是已经兑换过商品
                            $list[$k]['status_gold'] = 1;
                            $list[$k]['gold'] = floor($goldInfo[0]['gold']);
                            $bid = $info['brand_id']; //品牌id；
                            $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                            $list[$k]['rate_gold'] = $rate;
                        } else {
                            $list[$k]['status_gold'] = 0;//未兑换
                            $bid = $info['brand_id']; //品牌id；
                            $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                            if( $info['proc_type'] == 'goldbag'){
                                $list[$k]['rate_gold'] = 100;
                                $list[$k]['gold'] = $info["price"];
                            }else{
                                $list[$k]['rate_gold'] = $rate;
                                $list[$k]['gold'] = floor(($rate/100)*intval($info['buy_price']));

                            }
                        }
                    }
                }

                if ( $v["uid"] > 0 && $v["uid"] != $id ) {
                    $otheruser_name = get_user_name($v["uid"]);
                    $list[$k]['otheruser_name'] = $otheruser_name;
                    $number = D('Shop')->user_num($v["uid"], $v["id"]);
                    $list[$k]["othercount"] = count($number);//其他人参与的总次数

                    //$shoporder = M('shop_period')->field(true)->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->find();
                    //$shoporder = M('shop_order')->field(true)->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->find();
                } else {
                    $list[$k]['otheruser_name'] = $list[$k]['user_name'];
                    $list[$k]["othercount"] = $list[$k]["count"];
                    //$shoporder = M('shop_period')->field(true)->where("uid=" . $id . " and pid=" . $v["id"])->find();
                    //$shoporder = M('shop_order')->field(true)->where("uid=" . $id . " and pid=" . $v["id"])->find();
                }

                // if ( $shoporder ) {
                //     $list[$k]['order_status'] = $shoporder['order_status'];
                //     $list[$k]['contacts'] = $shoporder['contacts'];
                //     $list[$k]['phone'] = $shoporder['phone'];
                //     $list[$k]['address'] = $shoporder['address'];

                //     $order_status_time = explode(",", $shoporder['order_status_time']);
                //     array_unshift($order_status_time, $v['kaijang_time']);
                //     $list[$k]['order_status_time'] = array_filter($order_status_time);
                // }

                $list[$k]['order_status'] = $v['order_status'];
                $list[$k]['contacts'] = $v['contacts'];
                $list[$k]['phone'] = $v['phone'];
                $list[$k]['address'] = $v['address'];

                $order_status_time = explode(",", $v['order_status_time']);
                array_unshift($order_status_time, $v['kaijang_time']);
                $list[$k]['order_status_time'] = $order_status_time;

                if ( $v['state'] == 2 ) {
                    // //虚拟卡信息
                    // $list[$k]['card'] = $this->getCard($info, $v);

                    //虚拟物品
                    $visualGoods = $this->getVisualGoods($info,$v);
                    $list[$k]['card']=$visualGoods['card'];
                    $list[$k]['order_status']=$visualGoods['order_status'];
                    $list[$k]['order_status_time']=$visualGoods['order_status_time'];
                }

                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description'], $list[$k]['shopinterval'], $list[$k]['periodnumber'], $list[$k]['shopstock']);
            }
        }
        return $list;
     }



    /**
     * @deprecated 我的幸运记录
     * @author zhangran
     * @date 2016-07-06
     **/
    public function lottery($tokenid, $pageindex=1, $pagesize = 20){
        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的夺宝记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }

        $map['state'] = 2;
        $periods = M('shop_period')->where($map)->field(true)->page($pageindex, $pagesize)->order('end_time desc')->select();
        if ( $periods ) {
            foreach ( $periods as $k => $v ) {
                $info = M('shop')->field(true)->find($v["sid"]);
                $list[$k] = D('Shop')->overChange($info, $v);
                $list[$k]['user'] = $this->userChange($v);
                $list[$k]["proc_type"] = $info['proc_type'];//处理类型 shop=实物相关处理，card=虚拟卡相关处理，goldbag=金袋类型处理 ,
                $list[$k]['count'] = M('shop_record')->where("uid=" . $map['uid'] . " and pid=" . $v["id"])->sum('number');

                $list[$k]['contacts'] = $v['contacts'];
                $list[$k]['phone'] = $v['phone'];
                $list[$k]['address'] = $v['address'];

                $list[$k]['order_status'] = $v['order_status'];
                $order_status_time = explode(",", ltrim($v['order_status_time'],','));
                array_unshift($order_status_time, $v['kaijang_time']);
                $list[$k]['order_status_time'] = $order_status_time;

                //兑换
                $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

                if ( $exchangeConfigStatus <= 0 ) {
                    $list[$k]['status_gold'] = -1;//商品兑换比例配置 停用
                } else {
                    $goldSql = "SELECT gold.* FROM hx_gold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where tradetype.`code`=1006 AND gold.pid=".$v["id"];
                    $goldInfo = $this->query($goldSql, false);
                    if ( $v['status_gold'] ) {//有值就是已经兑换过商品
                        $list[$k]['status_gold'] = 1;
                        $list[$k]['gold'] = floor($goldInfo[0]['gold']);
                        $bid = $info['brand_id']; //品牌id；
                        $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                        $list[$k]['rate_gold'] = $rate;
                    } else {
                        $list[$k]['status_gold'] = 0;//未兑换
                        $bid = $info['brand_id']; //品牌id；
                        $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');

                        if( $info['proc_type'] == 'goldbag'){
                            $list[$k]['rate_gold'] = 100;
                            $list[$k]['gold'] = $info["price"];
                        }else{
                            $list[$k]['rate_gold'] = $rate;
                            $list[$k]['gold'] =floor(($rate/100)*intval($info['buy_price']));
                        }
                    }
                }

                //虚拟物品
                $visualGoods = $this->getVisualGoods($info,$v);
                $list[$k]['card']=$visualGoods['card'];
                $list[$k]['order_status']=$visualGoods['order_status'];
                $list[$k]['order_status_time']=$visualGoods['order_status_time'];

                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description'], $list[$k]['shopinterval'], $list[$k]['periodnumber'], $list[$k]['shopstock']);

            }
        }
        return $list;
    }

    /**
     * @deprecated 我的幸运记录合并pk
     * @author 耿贯一
     * @date 2016-10-14
     **/
    public function lotteryNew($tokenid, $pageindex=1, $pagesize = 20){
        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的夺宝记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }

        $map['state'] = 2;
        $periods = M('shop_period')->where($map)->field(true)->page($pageindex, $pagesize)->order('end_time desc')->select();
        if ( $periods ) {
            foreach ( $periods as $k => $v ) {
                $info = M('shop')->field(true)->find($v["sid"]);

                //判断商品是pk商品还是普通摸金
                $is_common = $v['iscommon'];
                $pkinfo = array();
                if ($is_common != 1) {
                   //获取pk商品相关信息
                    $pkinfo = M('house_manage')
                    ->table('__HOUSE_MANAGE__ house,__PKCONFIG__ pkconfig')
                    ->field('house.ispublic,house.id as houseid,house.no as room_no,pkconfig.peoplenum,pkconfig.amount')
                    ->where('house.id='.$v['house_id'].' AND house.pksetid=pkconfig.id')
                    ->find();
                }
                //echo M()->getLastSql();exit;
                if($is_common == 1){//普通商品
                    $list[$k] = D('Shop')->overChange($info, $v);
                }else{
                    $v['peoplenum'] = $pkinfo['peoplenum'];//pk商品参与人数
                    $v['amount'] = $pkinfo['amount'];//pk商品价格
                    $list[$k] = D('Shop')->pkoverChange($info, $v);
                    $list[$k]['houseid'] = $pkinfo['houseid'];//房间id
                    $list[$k]['room_no'] = $pkinfo['room_no'];//房间编码
                    $list[$k]['ispublic'] = $pkinfo['ispublic'];//房间公开与否0：公开房间 1：私密房间
                    $list[$k]['price'] = $pkinfo['peoplenum'];//总需要参与人数
                }
                $list[$k]['iscommon'] = $is_common;

                
                $list[$k]['user'] = $this->userChange($v);
                $list[$k]["proc_type"] = $info['proc_type'];//处理类型 shop=实物相关处理，card=虚拟卡相关处理，goldbag=金袋类型处理 ,
                $list[$k]['count'] = M('shop_record')->where("uid=" . $map['uid'] . " and pid=" . $v["id"])->sum('number');

                $list[$k]['contacts'] = $v['contacts'];
                $list[$k]['phone'] = $v['phone'];
                $list[$k]['address'] = $v['address'];

                $list[$k]['order_status'] = $v['order_status'];
                $order_status_time = explode(",", ltrim($v['order_status_time'],','));
                array_unshift($order_status_time, $v['kaijang_time']);
                $list[$k]['order_status_time'] = $order_status_time;

                //兑换
                $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

                if ( $exchangeConfigStatus <= 0 ) {
                    $list[$k]['status_gold'] = -1;//商品兑换比例配置 停用
                } else {
                    $goldSql = "SELECT gold.* FROM hx_gold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where tradetype.`code`=1006 AND gold.pid=".$v["id"];
                    $goldInfo = $this->query($goldSql, false);
                    if ( $v['status_gold'] ) {//有值就是已经兑换过商品
                        $list[$k]['status_gold'] = 1;
                        $list[$k]['gold'] = floor($goldInfo[0]['gold']);
                        $bid = $info['brand_id']; //品牌id；
                        $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                        $list[$k]['rate_gold'] = $rate;
                    } else {
                        $list[$k]['status_gold'] = 0;//未兑换
                        $bid = $info['brand_id']; //品牌id；
                        $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');

                        if( $info['proc_type'] == 'goldbag'){
                            $list[$k]['rate_gold'] = 100;
                            $list[$k]['gold'] = $info["price"];
                        }else{
                            $list[$k]['rate_gold'] = $rate;
                            $list[$k]['gold'] =floor(($rate/100)*intval($info['buy_price']));
                        }
                    }
                }

                //虚拟物品
                $visualGoods = $this->getVisualGoods($info,$v);
                $list[$k]['card']=$visualGoods['card'];
                $list[$k]['order_status']=$visualGoods['order_status'];
                $list[$k]['order_status_time']=$visualGoods['order_status_time'];

                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description'], $list[$k]['shopinterval'], $list[$k]['periodnumber'], $list[$k]['shopstock']);

            }
        }
        return $list;
    }

    /**
     * @deprecated 我的pk幸运记录
     * @author zhangran
     * @date 2016-07-06
     **/
    public function pkLottery($tokenid, $pageindex=1, $pagesize = 20){
        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的夺宝记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }

        $periods = M('shop_period')
                ->table('__SHOP_PERIOD__ period,__HOUSE_MANAGE__ house,__PKCONFIG__ pkconfig')
                ->field('period.*,pkconfig.id as pkid,pkconfig.peoplenum,pkconfig.amount,pkconfig.inventory,house.id as houseid')
                ->where('period.state = 2 AND period.iscommon = 2 AND house.periodid = period.id AND house.pksetid = pkconfig.id')
                ->page($pageindex, $pagesize)
                ->order('period.end_time desc')
                ->select();
        if ( $periods ) {
            foreach ( $periods as $k => $v ) {
                $info = M('shop')->field(true)->find($v["sid"]);
                $list[$k] = D('Shop')->pkoverChange($info, $v);
                $list[$k]['user'] = $this->userChange($v);
                $list[$k]["proc_type"] = $info['proc_type'];//处理类型 shop=实物相关处理，card=虚拟卡相关处理，goldbag=金袋类型处理 ,
                $list[$k]['count'] = M('shop_record')->where("uid=" . $map['uid'] . " and pid=" . $v["id"])->sum('number');

                
                $list[$k]['houseid'] = $v['houseid'];//房间id
                $list[$k]['pkid'] = $v['pkid'];//商品pkid

                $list[$k]['contacts'] = $v['contacts'];
                $list[$k]['phone'] = $v['phone'];
                $list[$k]['address'] = $v['address'];

                $list[$k]['order_status'] = $v['order_status'];
                $order_status_time = explode(",", ltrim($v['order_status_time'],','));
                array_unshift($order_status_time, $v['kaijang_time']);
                $list[$k]['order_status_time'] = $order_status_time;

                //兑换
                $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

                if ( $exchangeConfigStatus <= 0 ) {
                    $list[$k]['status_gold'] = -1;//商品兑换比例配置 停用
                } else {
                    $goldSql = "SELECT gold.* FROM hx_gold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where tradetype.`code`=1006 AND gold.pid=".$v["id"];
                    $goldInfo = $this->query($goldSql, false);
                    if ( $v['status_gold'] ) {//有值就是已经兑换过商品
                        $list[$k]['status_gold'] = 1;
                        $list[$k]['gold'] = floor($goldInfo[0]['gold']);
                        $bid = $info['brand_id']; //品牌id；
                        $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');
                        $list[$k]['rate_gold'] = $rate;
                    } else {
                        $list[$k]['status_gold'] = 0;//未兑换
                        $bid = $info['brand_id']; //品牌id；
                        $rate = M('exchange_virtual')->where("bid=" + $bid)->getField('rate');

                        if( $info['proc_type'] == 'goldbag'){
                            $list[$k]['rate_gold'] = 100;
                            $list[$k]['gold'] = $info["price"];
                        }else{
                            $list[$k]['rate_gold'] = $rate;
                            $list[$k]['gold'] =floor(($rate/100)*intval($info['buy_price']));
                        }
                    }
                }

                //虚拟物品
                $visualGoods = $this->getVisualGoods($info,$v);
                $list[$k]['card']=$visualGoods['card'];
                $list[$k]['order_status']=$visualGoods['order_status'];
                $list[$k]['order_status_time']=$visualGoods['order_status_time'];

                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description'], $list[$k]['shopinterval'], $list[$k]['periodnumber'], $list[$k]['shopstock']);

            }
        }
        return $list;
    }

    protected function getVisualGoods($info,$period){
        $result['order_status'] = $period['order_status'];
        $result['order_status_time'] =explode(",", ltrim($period['order_status_time'],','));
        $result['card'] = null;
        //如果是虚拟物品
        if($info['fictitious']==2){
            //金袋状态处理
            if( $info['proc_type'] == 'goldbag'){
                if ( $period['order_status'] == 0 ) {
                    //修改订单状态
                    $fields['order_status'] = 101; //待兑换
                    $fields['order_status_time'] = time();
                    $rs = M('shop_period')->where('id=' . $period['id'])->setField($fields);
                    if($rs){
                        //更新返回数据
                        $result['order_status'] = $fields['order_status'];
                        $result['order_status_time'] = explode(",", ltrim($fields['order_status_time'],','));
                    }
                }
            }
            else{
                //虚拟卡信息
                $result['card'] = $this->getCard($info, $period);
                if($result['card']['code'] == 200 || $result['card']['code'] == 404){
                    //如果虚拟卡绑定成功，更新订单状态与时间
                    $p = M('shop_period')->where('id='.$period["id"])->field(true)->find();
                    if($p){
                        $result['order_status'] = $p['order_status'];
                        $result['order_status_time'] = explode(",", ltrim($p['order_status_time'],','));
                    }
                }
            }
        }
        return $result;
    }

    public function getCard($shop, $period)
    {
         $fields = array();

        //如果是虚拟商品 and order_staus=0 and card_id 未激活，绑定卡密，修改卡密状态，更新 order_staus 为102已收货
        if ( $shop['fictitious'] == 2 ) {

            if ( $period['order_status'] == 0 || $period['order_status'] == 101 ) {

                //获取一张新卡
                $card = D('Card')->getCardByType($shop['id']);
                if ( $card ) {
                    $result = D('Card')->setCardStatus($card['id'], 1);
                    if ( !$result ) {
                        $card["code"] = 401;
                        $card["msg"] = '卡密绑定失败';
                    } else {
                        //修改订单状态
                        if($period['order_status'] == 0){
                            $fields['order_status_time'] = time();
                        }
                        else{
                            $fields['order_status_time'] .= ',' . time();
                        }

                        $fields['order_status'] = 102; //发卡，已收货
                        $fields['card_id'] = $card['id'];
                        M('shop_period')->where('id=' . $period['id'])->setField($fields);

                        $card["code"] = 200;
                        $card["msg"] = '卡密绑定成功';
                    }
                } else {
                    if ( $period['order_status'] == 0 ) {
                        //修改订单状态
                        $fields['order_status'] = 101; //待发货
                        $fields['order_status_time'] = time();
                        M('shop_period')->where('id=' . $period['id'])->setField($fields);

                        $card["code"] = 404;
                        $card["msg"] = '该类型下未找到新卡密信息';
                    }
                }
            } else {
                $card = D('Card')->getCard($period["card_id"]);
                if ( $card ) {
                    $card["code"] = 200;
                    $card["msg"] = '卡密绑定成功';
                } else {
                    $card["code"] = 402;
                    $card["msg"] = '卡密绑定失败';
                }
            }
        } else {
            $card = null;
        }
        return $card;
    }

    public function  getUserInfoByUid($uid)
    {
        $usermap['id']=$uid;
        $userinfo = array();
        $userinfo = M('User')->where($usermap)->find();//获取用户信息
        $sql = "SELECT SUM(o.buy_gold) as buy_gold FROM bo_shop_order o LEFT JOIN bo_shop_period p ON o.pid = p.id  WHERE p.state <= 3";
        $info = $this->query($sql, false);
        $buy_gold = empty($info) ? 0 : $info[0]['buy_gold'];
        $userinfo['gold_balance'] = empty($userinfo) ? 0.000 : substr(sprintf("%.4f", ($userinfo['gold_balance'] / 1000)),0,-1);
        $userinfo['gold_kg'] = floor($buy_gold / 1000000);
        $userinfo['gold_g'] = floor(($buy_gold-$userinfo['gold_kg']*1000000) / 1000);
        $userinfo['gold_mg'] = floor(($buy_gold-$userinfo['gold_kg']*1000000-$userinfo['gold_g']*1000));
        
        return $userinfo;
    }

    public function  getUserInfo($param = array())
    {
        $subQuery = M('shop_record')->field(true)->where('uid=' . $param['uid'])->order('create_time desc')->select(false);
        $where_state = ' and period.state=2';
        $periodcount = M('shop_period')->table($subQuery . ' record,__SHOP_PERIOD__ period')->where('record.pid=period.id' . $where_state)->count('period.id');//开奖数量

        $usermap['passport_uid'] = $param['passport_uid'];
        //积分和金币；
        $userinfo = M('User')->field('total_point,black')->where($usermap)->find();//获取用户积分

        return array(
            "periodcount" => $periodcount,
            "totalpoint" => $userinfo['total_point'],
            "black" => $userinfo['black']
        );
    }

    //
    public function displays($p = 1, $id, $num = 20, $sid)
    {
        if ( $id ) {
            $sql = ' and shared.uid=' . $id;
        }
        if ( $sid ) {
            $sql .= ' and period.sid=' . $sid;
        }
        $shared = M('shop_shared')
            ->table('__SHOP_SHARED__ shared,__SHOP_PERIOD__ period')
            ->field('shared.id,shared.pic,shared.thumbpic,shared.content,shared.create_time,period.uid,period.number,period.no,period.kaijang_num,period.kaijang_time,period.id as pid,period.sid,period.iscommon')
            ->where('shared.pid=period.id' . $sql)
            ->page($p, $num)
            ->order('shared.create_time desc')
            ->select();

        //echo M()->getLastSql();exit();

        if ( $shared ) {
            foreach ( $shared as $k => $v ) {
                $countShare = M('up')->where('itemid=' . $v['id'])->count('id');//当前项目所有分享总数；

                $list[$k] = $this->userChange($v, 'displays');
                $list[$k]['id'] = $v['id'];
                $list[$k]['shared_id'] = $v['id'];
                $list[$k]['pic'] = getCloudHost($v['pic']);
                //$list[$k]['pic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['pic']));
                //$list[$k]['thumbpic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['thumbpic']));
                $list[$k]['content'] = $v['content'];
                $list[$k]['count'] = M('shop_record')->where("pid=" . $v["pid"] . " and uid=" . $v['uid'])->sum('number');//参与人次
                $list[$k]['sharecount'] = $countShare;
                //$list[$k]['isShare'] = false;
                if ( $id ) {
                    $isShare = M('up')->where('itemid=' . $v['id'] . ' and uid=' . $id)->count('id');
                    $list[$k]['isShare'] = $isShare > 0 ? true : false;
                }
                $list[$k]['iscommon'] = $v['iscommon'];//普通商品还是pk商品
                if ($v['iscommon'] != 1)//pk商品
                {
                    $pk_item = M('house_manage')->where(['periodid'=>$v['pid']])->field('id,no,ispublic')->find();
                    $list[$k]['houseid'] = empty($pk_item['id']) ? '' : $pk_item['id'];//房间id
                    $list[$k]['room_no'] = empty($pk_item['no']) ? '' : $pk_item['no'];//房间号
                    $list[$k]['ispublic'] = empty($pk_item['ispublic']) ? '' : $pk_item['ispublic'];//房间号
                }
            }
            return $list;
        }
    }
    /**
    *获取我的晒单分享合并pk 新增
    *user:gengguanyi
    *time:2016-10-17
    **/
    public function displaysNew($p = 1, $id, $num = 20, $sid)
    {
        if ( $id ) {
            $sql = ' and shared.uid=' . $id;
        }
        if ( $sid ) {
            $sql .= ' and period.sid=' . $sid;
        }
        $shared = M('shop_shared')
            ->table('__SHOP_SHARED__ shared,__SHOP_PERIOD__ period')
            ->field('shared.id,shared.pic,shared.thumbpic,shared.content,shared.create_time,period.uid,period.number,period.no,period.kaijang_num,period.kaijang_time,period.id as pid,period.sid,period.iscommon')
            ->where('shared.pid=period.id' . $sql)
            ->page($p, $num)
            ->order('shared.create_time desc')
            ->select();

        //echo M()->getLastSql();exit();

        if ( $shared ) {
            foreach ( $shared as $k => $v ) {
                $countShare = M('up')->where('itemid=' . $v['id'])->count('id');//当前项目所有分享总数；

                $list[$k] = $this->userChange($v, 'displays');
                $list[$k]['id'] = $v['id'];
                $list[$k]['shared_id'] = $v['id'];
                $list[$k]['pic'] = getCloudHost($v['pic']);
                //$list[$k]['pic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['pic']));
                //$list[$k]['thumbpic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['thumbpic']));
                $list[$k]['content'] = $v['content'];
                $list[$k]['count'] = M('shop_record')->where("pid=" . $v["pid"] . " and uid=" . $v['uid'])->sum('number');//参与人次
                $list[$k]['sharecount'] = $countShare;
                //$list[$k]['isShare'] = false;
   
                if ( $id ) {
                    $isShare = M('up')->where('itemid=' . $v['id'] . ' and uid=' . $id)->count('id');
                    $list[$k]['isShare'] = $isShare > 0 ? true : false;
                }

                $list[$k]['iscommon'] = $v['iscommon'];//订单类型1：普通摸金2：pk

                if($list[$k]['iscommon'] == 1){//普通摸金

                }else{
                    $houseinfo = M('shop_period')->field('h.ispublic,h.pksetid as pkid,h.uid as ownerid,h.id as houseid,h.no as room_no')->table('__SHOP_PERIOD__ p,__HOUSE_MANAGE__ h')->where('p.house_id = h.id and p.id='.$v['pid'])->find();
                    
                    //$houseinfo = M('house_manage')->field('ispublic,pksetid as pkid,uid as ownerid,id as houseid,no as room_no')->where('periodid='.$v['pid'])->find();

                    $list[$k]['ispublic'] = $houseinfo['ispublic'];
                    $list[$k]['pkid'] = $houseinfo['pkid'];
                    $list[$k]['ownerid'] = $houseinfo['ownerid'];
                    $list[$k]['houseid'] = $houseinfo['houseid'];
                    $list[$k]['room_no'] = $houseinfo['room_no'];
                }
            }
            return $list;
        }
    }

    /**
     * 晒单管理 
     * @param  integer $p    [description]
     * @param  [type]  $id   [description]
     * @param  integer $num  [description]
     * @param  [type]  $sid  [description]
     * @param  integer $type [description]
     * @return [type]        1按照人气排行 2按照时间排行
     */
    public function shopdisplays($p = 1, $id, $num = 20, $sid, $type = 1)
    {
        //条件  
        $where = "";
        if ( $sid ) {
            $where .= ' WHERE period.sid=' . $sid;
        }

        $orderby = "";
        //每页开始值
        $offsize = ($p-1)*$num;
        //sql语句
        if ($type == 1) {//人气排行
            $orderby = ' ORDER BY sharecount DESC,shared.create_time DESC';
        } else {//时间倒排
            $orderby = ' ORDER BY shared.create_time DESC';
        }

        $sql = "SELECT shared.id,shared.pic,shared.thumbpic,shared.content,shared.create_time,period.uid,period.number,period.no,period.kaijang_num,period.kaijang_time,period.id AS pid,period.sid,period.iscommon,(SELECT COUNT(*) FROM hx_up WHERE itemid = shared.id) AS sharecount,manage.id AS houseid,manage.ispublic,manage.no AS room_no  FROM hx_shop_shared shared inner join hx_shop_period period on shared.pid=period.id left join hx_house_manage manage on period.house_id = manage.id ".$where.$orderby." limit ".$offsize.",".$num;
        $shared = $this->query($sql, false);//查询列表
        $list = array();
        if ( isEmpty($shared) ) {
            foreach ( $shared as $k => $v ) {
                $list[$k] = $this->userChange($v, 'displays');
                $list[$k]['id'] = $v['id'];
                if ($type == 1) {//人气排行显示排行
                    $list[$k]['ranking'] = $offsize+$k+1;
                }
                $list[$k]['shared_id'] = $v['id'];
                $list[$k]['pic'] = getCloudHost($v['pic']);
                //$list[$k]['pic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['pic']));
                //$list[$k]['thumbpic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['thumbpic']));
                $list[$k]['content'] = $v['content'];
                $list[$k]['count'] = M('shop_record')->where("pid=" . $v["pid"] . " and uid=" . $v['uid'])->sum('number');//参与人次
                $list[$k]['sharecount'] = $v['sharecount'];
                //$list[$k]['isShare'] = false;
                if ( $id ) {
                    $isShare = M('up')->where('itemid=' . $v['id'] . ' and uid=' . $id)->count('id');
                    $list[$k]['isShare'] = $isShare > 0 ? true : false;
                }
                $list[$k]['iscommon'] = $v['iscommon'];
                if ($v['iscommon']!=1) {//pk商品
                    $list[$k]['houseid'] = $v['houseid'];
                    $list[$k]['room_no'] = $v['room_no'];
                    $list[$k]['ispublic'] = $v['ispublic'];
                }
            }
        }
        return $list;
    } 


    /**
     * @deprecated 新用户晒单列表合并pk
     * @author gengguanyi
     * @date 2016-10-17
     **/
     public function shopdisplaysnew($p = 1, $id, $num = 20, $sid)
    {
//        if ( $id ) {
//            $sql = ' and shared.uid=' . $id;
//        }
        $sql = "";
        if ( $sid ) {
            $sql .= ' and period.sid=' . $sid;
        }
        $shared = M('shop_shared')
            ->table('__SHOP_SHARED__ shared,__SHOP_PERIOD__ period')
            ->field('shared.id,shared.pic,shared.thumbpic,shared.content,shared.create_time,period.uid,period.number,period.no,period.kaijang_num,period.kaijang_time,period.id as pid,period.sid,period.iscommon')
            ->where('shared.pid=period.id' . $sql)
            ->page($p, $num)
            ->order('shared.create_time desc')
            ->select();

        //echo M()->getLastSql();exit();

        if ( $shared ) {
            foreach ( $shared as $k => $v ) {
                $countShare = M('up')->where('itemid=' . $v['id'])->count('id');//当前项目所有分享总数；

                $list[$k] = $this->userChange($v, 'displays');
                $list[$k]['id'] = $v['id'];
                $list[$k]['shared_id'] = $v['id'];
                $list[$k]['pic'] = getCloudHost($v['pic']);
                //$list[$k]['pic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['pic']));
                //$list[$k]['thumbpic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $v['thumbpic']));
                $list[$k]['content'] = $v['content'];
                $list[$k]['count'] = M('shop_record')->where("pid=" . $v["pid"] . " and uid=" . $v['uid'])->sum('number');//参与人次
                $list[$k]['sharecount'] = $countShare;
                //$list[$k]['isShare'] = false;
                if ( $id ) {
                    $isShare = M('up')->where('itemid=' . $v['id'] . ' and uid=' . $id)->count('id');
                    $list[$k]['isShare'] = $isShare > 0 ? true : false;
                }
                $list[$k]['iscommon'] = $v['iscommon'];
                //判断分享是普通商品还是pk商品
                if($list[$k]['iscommon'] == 1){//普通商品

                }else{//pk商品
                    //获取房间是私密还是公开
                    $houseinfo = M('house_manage')->field('id as houseid,ispublic,uid,pksetid as pkid')->where('periodid='.$v['pid'])->find();
                    $list[$k]['ispublic'] = $houseinfo['ispublic'];//pk房间类型
                    $list[$k]['pksetid'] = $houseinfo['pkid'];//商品pkid
                    $list[$k]['ownerid'] = $houseinfo['uid'];//pk房间房主id
                }

            }
            return $list;
        }
    }



    public function displays_more($id)
    {
        $shared = M('shop_shared')
            ->table('__SHOP_SHARED__ shared,__SHOP_PERIOD__ period')
            ->field('shared.id,shared.pic,shared.content,period.uid,period.number,period.no,period.kaijang_num,period.kaijang_time,period.id as pid,period.sid')
            ->where('shared.pid=period.id and shared.id=' . $id)
            ->order('shared.id desc')->find();
        if ( $shared ) {
            $list = $this->userChange($shared, 'displays');

            $list['pic'] = getCloudHost($shared['pic']);
            //$list['pic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $shared['pic']));
            $list['content'] = $shared['content'];

            $list['count'] = M('shop_record')->where("uid=" . $list['uid'] . " and pid=" . $list["pid"])->sum('number');//参与人数
//            $list['shareCount'] = M('up')->where("uid=" . $list['uid'] . " AND itemid=" . $list["pid"])->count('id');
//            $isShare = M('up')->where('itemid=' . $list['id'] . ' and uid='.$list['uid'])->count('id');
//            $list['isShare'] = $isShare > 0 ? true : false;
            return $list;
        }
    }

    
    /*晒单详情 合并pk
    *user:gengguanyi
    *time:2016-10-17
    */
    public function displays_more_new($id)
    {
        $shared = M('shop_shared')
            ->table('__SHOP_SHARED__ shared,__SHOP_PERIOD__ period')
            ->field('shared.id,shared.pic,shared.content,period.uid,period.number,period.no,period.kaijang_num,period.kaijang_time,period.id as pid,period.sid,period.iscommon')
            ->where('shared.pid=period.id and shared.id=' . $id)
            ->order('shared.id desc')->find();
        if ( $shared ) {
            $list = $this->userChange($shared, 'displays');

            $list['pic'] = getCloudHost($shared['pic']);
            //$list['pic'] = explode(',', str_replace('/Picture', C('PASSPORT_IMG_URL'), $shared['pic']));
            $list['content'] = $shared['content'];

            $list['count'] = M('shop_record')->where("uid=" . $list['uid'] . " and pid=" . $list["pid"])->sum('number');//参与人数
//            $list['shareCount'] = M('up')->where("uid=" . $list['uid'] . " AND itemid=" . $list["pid"])->count('id');
//            $isShare = M('up')->where('itemid=' . $list['id'] . ' and uid='.$list['uid'])->count('id');
//            $list['isShare'] = $isShare > 0 ? true : false;

            $list['iscommon'] = $shared['iscommon'];//1:普通摸金2：pk商品
            if($list['iscommon'] ==1){
                $list['pkid'] = 0;//商品pkid 
                $list['ownerid'] = 0;//房主用户id
                $list['ispublic'] = 0;//房间类型0:公开房间1:私密房间
                $list['houseid'] = 0;//房间id
                $list['room_no'] = 0;//房间号
            }else{
                $houseinfo = M('house_manage')->field('pksetid as pkid,uid,ispublic,id as houseid,no as room_no')->where('periodid='.$shared['pid'])->find();
                $list['pkid'] = $houseinfo['pkid'];//商品pkid 
                $list['ownerid'] = $houseinfo['uid'];//房主用户id
                $list['ispublic'] = $houseinfo['ispublic'];//房间类型0:公开房间1:私密房间
                $list['houseid'] = $houseinfo['houseid'];//房间id
                $list['room_no'] = $houseinfo['room_no'];//房间号
            }
            return $list;
        }
    }





    /**
     * @deprecated 用户晒单
     * @author wenyuan
     * @date 2016-09-03
     **/
    public function luckyshow($uid,$pid,$content,$picPathArr=array()){
        try{
            //判断用户是否已晒单
            $map['uid'] = $uid;
            $map['pid'] = $pid;
            $shared = M('shop_shared')->where($map)->find();
            if ( $shared ) {
                returnJson('', 405, '您已经晒单！');
            }

            $map['thumbpic'] = '';
            $map['pic'] = implode(',', $picPathArr);
            $map['content'] = $content;
            $map['create_time'] = NOW_TIME;

            M("shop_shared")->startTrans();

            //recordLog(var_dump($map), 'luckyshow');

            //添加晒单数据
            $rs_shared = M('shop_shared')->add($map);
            //更新状态
            $rs_shop = D("Shop")->updateOrderStatus($uid,$pid,103);
            //晒单送积分
            $rs_point = D('Point')->addPointByUid(20, 103, $uid);

            if($rs_shared>0 && $rs_shop>0 && $rs_point>0){
                M("shop_shared")->commit();

                activity(6, $rs_shared, $uid);
                returnJson('', 200, 'success');
            }
            else{
                M("shop_shared")->rollback();
                returnJson('rs_shared:'.$rs_shared.' rs_shop:'.$rs_shop.' rs_point:'.$rs_point, 510, 'error');
            }
        }catch(\Exception $e){
            returnJson('', 500, $e->getMessage());
        }
    }

    /**
     * @deprecated 用户多图晒单
     * @author zhangkang
     * @date 2016-7-8
     **/
    public function shared_update($tmp){
        if ( !$tmp ) {
            returnJson('', 1, '用户未登录或者登录已超时！');
        }

        $tokenId = $tmp["tokenid"];
        $user = isLogin($tokenId);
        if ( !$user ) {
            returnJson('', 100, '用户未登录或者登录已超时！');
        }
        $uid = $user['uid'];

        $pic = $tmp['pic'];
        if ( empty($pic) ) {
            returnJson('', 1, '至少要有一张晒单图片！');
        }

        $pid = $tmp['pid'];

        //判断用户是否已晒单
        $map['uid'] = $uid;
        $map['pid'] = $pid;
        $shared = M('shop_shared')->where($map)->find();
        if ( $shared ) {
            returnJson('', 405, '您已经晒单！');
        }

        $picpath = $this->uploadImages($pic);

        //        $thumbpic = $tmp['thumbpic'];
        //        $thumbpicpath = $this->uploadImages($thumbpic);

        $data = array();
        $data['pid'] = $pid;
        $data['uid'] = $uid;
        $data['content'] = $tmp['content'];
        $data['pic'] = implode(',', $picpath["picpath"]);
        $data['thumbpic'] = implode(',', $picpath["thumbpicpath"]);
        $data['create_time'] = NOW_TIME;
        $res = M('shop_shared')->add($data);
        M('shop_period')->where('id=' . $pid . " and uid=" . $uid)->setField("shared", 0);

        //晒单送积分
        $points = array();
        $points['point'] = 20;//$json['point'];
        $points['tokenid'] = $tokenId;
        $points['type_id'] = 103;
        // $points['remark'] = "晒单送积分";
        $rs = D('Point')->addPoint($points);

        activity(6, $res, $uid);
        returnJson('', 200, 'success');
    }

    private function uploadImages($pic)
    {
        $picpath = array();
        $thumbpicpath = array();
        foreach ( $pic as $key => $value ) {
            $picture = $value['picture'];
            if ( preg_match('/^(data:\s*image\/(\w+);base64,)/', $picture, $result) ) {
                $type = $result[2];
                $new_file = './Picture/shared/' . uniqid() . '.' . $type;
                $new_filei = './Picture/shared/' . uniqid() . '.' . $type;
                if ( Storage::put($new_file, base64_decode(str_replace($result[1], '', $picture))) ) {
                    $picpath[] = substr($new_file, 1);
                    $thumbpicpath[] = substr($new_filei, 1);

                    img2thumb($new_file, $new_filei, $width = 170, $height = 170, $cut = 0, $proportion = 0);
                } else {
                    returnJson('', 1, '上传图片失败！');//上传图片或者文件失败
                }
            }
        }

        return array('picpath' => $picpath,
            'thumbpicpath' => $thumbpicpath);
    }

    public function addressdel($tokenid, $id)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $map = array('id' => array('in', $id));
        if ( M('shop_address')->where($map)->delete() ) {
            returnJson('', 200, 'success');
        } else {
            returnJson('', 1, '删除失败');
        }
    }

    public function addressDelByUid($uid)
    {
        $map = array('uid', $uid);
        if ( M('shop_address')->where($map)->delete() ) {
            returnJson('', 200, 'success');
        } else {
            returnJson('', 1, '删除失败');
        }
    }

    public function address_info($id, $field = true)
    {
        $map = array();
        $map['uid'] = UID;
        if ( is_numeric($id) ) {
            $map['id'] = $id;
        } else {
            $map['nickname'] = $id;
        }
        return M('shop_address')->field($field)->where($map)->find();
    }
	
    
    public function addressList($tokenid)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $map = array();
        $map['uid'] = $user['uid'];
        return M('shop_address')->field('id,nickname,tel,province,city,dist,address,uid,`default` as isdefault')->order('isdefault desc')->where($map)->select();
    }

    public function addressdefault($tokenid, $id)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        //把前面的默认改为非默认；
        $address = M('shop_address')->where('uid=' . $user['uid'])->select();
        if ( $address ) {
            foreach ( $address as $k => $v ) {
                if ( $v['default'] == 1 ) {
                    M('shop_address')->where('uid=' . $v['uid'] . ' AND id=' . $v['id'])->setField('default', 0);
                }
            }
        }

        return M('shop_address')->where('uid=' . $user['uid'] . ' AND id=' . $id)->setField('default', 1);
    }

    public function setHeadimg($uid, $headimgurl)
    {
        return M('user')->where('id=' . $uid)->setField('headimgurl', $headimgurl);
    }

    public function address_update($param)
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $params = array();
        $params['nickname'] = $param['nickname'];
        $params['tel'] = $param['tel'];
        $params['province'] = $param['province'];
        $params['city'] = $param['city'];
        $params['dist'] = $param['dist'];
        $params['address'] = $param['address'];
        $params['uid'] = $user['uid'];
        $params['default'] = isEmpty($param['default']) ? 0 : $param['default'];

        if ( $params['default'] == 1 ) {
            //把前面的默认改为非默认；
            $address = M('shop_address')->where('uid=' . $user['uid'])->select();
            if ( $address ) {
                foreach ( $address as $k => $v ) {
                    if ( $v['default'] == 1 ) {
                        M('shop_address')->where('uid=' . $v['uid'] . ' AND id=' . $v['id'])->setField('default', 0);
                    }
                }
            }
        }

        if ( isEmpty($param['id']) )//添加
        {
            $res = M('shop_address')->add($params);
        } else {
            $params['id'] = $param['id'];
            $res = M('shop_address')->save($params);
        }

        return $res;
    }

    public function recharge_list($p, $paydate)
    {
        if ( $paydate == 1 ) {
            $where = " and FROM_UNIXTIME(create_time,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d')";
        } elseif ( $paydate == 2 ) {
            $where = " and YEARWEEK(FROM_UNIXTIME(create_time,'%Y-%m-%d')) = YEARWEEK(now())";
        } elseif ( $paydate == 3 ) {
            $where = " and FROM_UNIXTIME(create_time,'%Y-%m')=date_format(now(),'%Y-%m')";
        }
        if ( $p ) {
            $list = M('shop_order')->where('type>1 and status=1 and uid=' . UID . $where)->page($p . ',20')->order('id desc')->select();
        } else {
            $list = M('shop_order')->where('type>1 and status=1 and uid=' . UID . $where)->order('id desc')->select();
        }
        if ( $list ) {
            foreach ( $list as $k => $v ) {
                $data[$k]['number'] = $v['number'];
                $data[$k]['order_id'] = $v['order_id'];
                $data[$k]['code'] = $v['code'] == 'OK' ? "已付款" : "未付款";
                $data[$k]['recharge'] = $v['recharge'] == 1 ? "充值" : "购买";
                $data[$k]['paytype'] = get_recharge($v['type']);
                $data[$k]['time'] = time_format($v['create_time'], 'Y-m-d H:i:s');
            }
        }
        return $data;
    }

    public function userChange($val, $type = '')
    {
        $map = array('status' => 1, 'id' => $val['uid']);
        $user = M('user')->field('id,nickname,province,city,headimgurl')->where($map)->find();
        $data["id"] = $user["id"];
        $data["name"] = get_user_name($user["id"]);
        $data["address"] = $user["province"] . $user["city"];
        $data["img"] = get_user_pic_passport($val['uid']);

        // $data['user_url'] = url_change("user/user", array("id" => $user["id"], "name" => 'user'));
        switch ( $type ) {
            case 'record':
                $number = D('Shop')->user_num($val["uid"], $val["pid"]);
                $data["number"] = explode(',', $val["num"]);//$number;
                $data["count"] = $val['number'];
                $data["time"] = time_format(substr($val["create_time"], 0, -3), 'Y-m-d H:i:s') . '.' . substr($val["create_time"], -3);
                break;
            case 'history':
                $data["state"] = $val["state"];
                $data["no"] = $val["no"];
                $data["pid"] = $val["id"];
                // $data["url"] = url_change("shop/over", array("id" => $val["id"], "name" => 'shop'));
                $data["number"] = $val["number"];
                $data["kaijang_num"] = $val["kaijang_num"];
                $data["time"] = time_format($val["kaijang_time"]);
                break;
            case 'displays':
                $data["uid"] = $val["uid"];
                $data["sid"] = $val["sid"];
                $data["pid"] = $val["pid"];
                $data["no"] = $val["no"];
                $data["shop_name"] = get_shop_name($val["sid"]);
                // $data["url"] = url_change("user/displays_more", array("id" => $val["id"], "name" => 'user'));
                $data["number"] = $val["number"];
                $data["kaijang_num"] = $val["kaijang_num"];
                $data["time"] = time_format($val["kaijang_time"]);
                $data['shared_time'] = time_format($val['create_time']);
                break;
            default:
                break;
        }
        return $data;
    }

    protected function verifyUser($uid, $password_in)
    {
        $password = $this->getFieldById($uid, 'password');
        if ( think_ucenter_md5($password_in) === $password ) {
            return true;
        }
        return false;
    }

    /**
     * @deprecated 用户点赞
     * @author zhangkang
     * @date 2016-7-8
     **/
    public function userUp($param)
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $ups = M('up')->where("uid=" . $user['uid'] . " AND itemid=" . $param['itemid'])->find();
        if ( $ups ) {//已经点赞过此项目
            $rs = M('up')->where("uid=" . $user['uid'] . " AND itemid=" . $param['itemid'])->delete();
        } else {
            $data = array();
            $data['itemtype'] = $param['itemtype'];
            $data['itemid'] = $param['itemid'];//晒单ID
            $data['uid'] = $user['uid'];
            $data['create_time'] = time();
            $rs = M('up')->add($data);
        }

        if ( $rs ) {
            returnJson('', 200, 'success');
        } else {
            returnJson('', 1, '点赞失败！');
        }
    }

    /**
     * 获取用户冻结状态
     * 1 = 正常 ； 0 = 冻结状态；
     * @param $uid
     * @return bool  
     */
    public function getUserStatus($uid){
        $status = $this->getFieldById($uid, 'status');
        if ( 1 == $status ) {
            return true;
        }
        return false;
    }


    /**
    *用户明细查询最新接口
    *@param $username
    *genggaunyi
    *2016-10-20
    */
    public function findUserInfo($username){
        $if_have = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("identity=100 AND identifier='".$username."'")->field('uid')->find();
        if($if_have){//用户id
            $userinfo = M('user')->where('passport_uid='.$if_have['uid'])->field('id,username,black,hongbao,total_point')->find();
            $userbylist = M('shop_record')
                        ->table('__SHOP_RECORD__ record,__SHOP_PERIOD__ period,__SHOP__ shop,__SHOP_ORDER__ order')
                        ->field('record.create_time,record.num,record.number,period.state,period.no,period.kaijiang_time,period.kaijiang_num,period.uid,shop.name,shop.cover_id,order.gold,order.cash')
                        ->where('record.uid='.$userinfo['id']." AND record.pid=period.id AND period.sid=shop.id AND record.order_id=order.id")
                        ->select();
            if($userbylist){
                for($i=0;$i<count($userbylist);$i++){
                    //商品图片
                    $userbylist[$i]["path"] = get_cover($userbylist[$i]["cover_id"], "path") == false ? '' : get_cover($userbylist[$i]["cover_id"], "path");
                }
                $userinfo['buylist'] = $userbylist;
            }else{
                $userinfo['buylist'] = array();
            }
            return returnJson($userinfo, 200, 'success');
        }else{
            return returnJson('', 401, '用户名输入错误');
        }

    }
    /**
     * 收货地址详情 - new
     *
     * @author liuwei
     * @param  integer $uid [description]
     * @return [type]       [description]
     */
    public function address_item($uid=0)
    {
        $info = M('shop_address')->where('uid='.$uid)->order('id desc')->find();
        return $info;
    }
    /**
     * 收货地址修改 - new
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function address_edit()
    {
        $data = $_POST;
        $data['default'] = 1;
        $array = array();
        $array['code'] = 101;
        $array['msg'] = "";
        if (empty($data)) {
            $array['msg'] = "修改内容没有任何改变";
        }
        unset($data['pid']);
        if (!empty($data['uid'])) {
            $model = M('shop_address');
            $uid = intval($data['uid']);//用户id
            $item = $model->where('uid='.$uid)->order('id desc')->field('id')->find();//收货地址是否存在
            if (empty($item)) {
                $result = $model->add($data);
                if(!$result){
                    $array['msg'] = "修改内容没有任何改变";
                } else {
                    $array['code'] = 200;
                }
            } else {
                $data['id'] = $item['id'];
                $result = $model->save($data);
                if(false === $result){ 
                    $array['msg'] = "修改内容没有任何改变";
                }else{
                    $array['code'] = 200;
                }
            }

        } else {
            $array['msg'] = "用户不能为空";
        }
        return $array;
    }
    public function editOrderStatus($pid,$status,$uid)
    {
        $result = array();
        $result['code'] = 101;
        $result['msg'] = '订单不存在';
        $period_info = M('shop_period')->where('id='.$pid)->field('sid,card_id')->find();//详情
        if (!empty($period_info)) {
            $msg  = "";
            $success_msg = "";
            $info = array();
            if ($status==0) {
                $old_status = 0;
                $new_status = 100;
                $msg = "订单已经领取过了";
                $success_msg = "请等待发货";
                $info = $this->address_item($uid);//获取收货地址详情
            } else if ($status==1) {
                $old_status = 101;
                $new_status = 102;
                $msg = "订单已经收过货了";
                $success_msg = "收货成功";
            }
            $order_status = M('shop_period')->where('id='.$pid)->getField('order_status');//原来订单状态是否存在
            if ($order_status!=$old_status) {
                $result['msg'] = $msg;
            } else {
                $order_status_time = M('shop_period')->where('id='.$pid)->getField('order_status_time');//原来订单状态是否存在
                $order_time_array = !empty($order_status_time) ? json_decode($order_status_time, true) : array();
                $data = array();
                if ($status==0) {
                    $data['contacts'] = $info['nickname'];
                    $data['phone'] = $info['tel'];
                    $data['email'] = $info['email'];
                    $data['address'] = $info['address'];

                }
                
                $data['id'] = $pid;
                $data['order_status'] = $new_status;
                if ($status==0) {
                    $order_time_array['receive_time'] = time();//领取时间
                } else if ($status==1) {
                    $order_time_array['receipt_time'] = time();//收货时间
                }
                $data['order_status_time'] = json_encode($order_time_array);
                $result_order = M('shop_period')->save($data);
                // $fictitious = M('shop')->where('id='.$period_info['sid'])->getField('fictitious');
                // if ($fictitious==2 and !isHostProduct()) {//虚拟商品
                //     $data['order_status'] = 102;
                //     $order_time_array['receive_time'] = time();//领取时间
                //     $order_time_array['receipt_time'] = time();//收货时间
                //     $data['order_status_time'] = json_encode($order_time_array);
                //     if (!empty($period_info['card_id'])) {
                //         $result_order = M('shop_period')->save($data);
                //         $phone = M('user')->where('id='.$uid)->getField('phone');//手机号
                //         $no = M('card')->where('id='.$period_info['card_id'])->getField('no');//卡号
                //         D('api/Notification')->sendCardSnNew($pid,$no,$phone);//发短信
                //         //已使用
                //         M('card')->where('id='.$period_info['card_id'])->save(array('issend'=>1,'send_time'=>time()));
                //     } else {
                //         $result_order = M('shop_period')->save($data);
                //     }
                    

                // } else {
                //     $data['order_status'] = $new_status;
                //     if ($status==0) {
                //         $order_time_array['receive_time'] = time();//领取时间
                //     } else if ($status==1) {
                //         $order_time_array['receipt_time'] = time();//收货时间
                //     }
                //     $data['order_status_time'] = json_encode($order_time_array);
                //     $result_order = M('shop_period')->save($data);
                // }
                if ($result_order!=false) {
                    $result['code'] = 200;
                    $result['msg'] = $success_msg;
                      
                }  else {
                    $result['msg'] = '系统繁忙';
                }
            }
        } 
        return $result;
    }
    public function editCashStatus($id,$status,$uid)
    {
        $result = array();
        $result['code'] = 101;
        $result['msg'] = '提现单不存在';
        $period_info = M('user_cash')->where('id='.$id)->field('sid')->find();//详情
        if (!empty($period_info)) {
            $msg  = "";
            $success_msg = "";
            $info = array();
            if ($status==101) {
                $old_status = 0;
                $new_status = 100;
                $msg = "提现单已经领取过了";
                $success_msg = "请等待发货";
                $info = $this->address_item($uid);//获取收货地址详情
            } else if ($status==102) {
                $old_status = 101;
                $new_status = 102;
                $msg = "提现单已经收过货了";
                $success_msg = "收货成功";
            }
            $order_status = M('user_cash')->where('id='.$id)->getField('order_status');//原来订单状态是否存在
            if ($order_status!=$old_status) {
                $result['msg'] = $msg;
            } else {
                $order_status_time = M('user_cash')->where('id='.$id)->getField('order_status_time');//原来订单状态是否存在
                $order_time_array = !empty($order_status_time) ? json_decode($order_status_time, true) : array();
                $data = array();
                if ($status==101) {
                    $data['contacts'] = $info['nickname'];
                    $data['phone'] = $info['tel'];
                    $data['email'] = $info['email'];
                    $data['address'] = $info['address'];

                }
                
                $data['id'] = $id;
                $data['order_status'] = $new_status;
                if ($status==101) {
                    $order_time_array[101] = time();//领取时间
                } else if ($status==102) {
                    $order_time_array[102] = time();//收货时间
                }
                $data['order_status_time'] = json_encode($order_time_array);
                $result_order = M('user_cash')->save($data);
                
                if ($result_order!=false) {
                    $result['code'] = 200;
                    $result['msg'] = $success_msg;
                      
                }  else {
                    $result['msg'] = '系统繁忙';
                }
            }
        } 
        return $result;
    }

    /**
     * 获取时间范围内的用户注册数
     * @param $startTime
     * @param $endTime
     */
    public function getUserCount($startTime='',$endTime=''){
        $map['_string'] =  'create_time >= '.$startTime.' AND create_time <= '.$endTime;
        $count = $this->where($map)->count();
        return $count;
    }
    /**
     * 渠道详情
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getChannelInfo($id)
    {
        $info = M('Channel')->where('id='.$id)->find();
        if (!empty($info['cash'])) {
            $info['cash_list'] = explode(',', $info['cash']);
            $info['cash_first_gold'] = $info['cash_list'][0];
            $info['gold_balance'] /= 1000;
        }
        return $info;
    }
    /**
     * 提金明细
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public function getGoldList($uid,$field='*')
    {
        $data = array();
        $list = M('user_cash')->where('uid='.$uid)->field($field)->order('id desc')->select();
        if (!empty($list)) {
            //三天前的时间戳
            $time = 3*60*60*24;
            foreach ($list as $key => $value) {
                $data[] = $value;
                //时间
                if (!empty($value['create_time'])) {
                    $data[$key]['create_date'] = date('Y.m.d H:i:s', $value['create_time']);
                }
                $data[$key]['send_status'] = 0;//三天之内
                //订单状态改变时间
                if (!empty($value['order_status_time'])) {
                    $order_status_time = json_decode($value['order_status_time'], true);
                    //已发货
                    if (!empty($order_status_time[101]) and $order_status_time[101]+$time<=time()) {
                        $data[$key]['send_status'] = 1;//三天之外
                    }
                }
                if ($value['order_status']==100) {
                    $data[$key]['msg'] = "发货中";
                } elseif ($value['order_status']==101) {
                    $data[$key]['msg'] = "已发货";
                } elseif ($value['order_status']==102) {
                    $data[$key]['msg'] = "已收货";
                } else {
                    $data[$key]['msg'] = "其他";
                }
            }
        }
        return $data;
    }
    /**
     * 提现明细
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public function getCashList($uid,$field='*')
    {
        $data = array();
        $data['total_money'] = M('user_extract')->where('uid='.$uid)->sum("total_money");
        $number = M('user_extract')->where('uid='.$uid)->sum("number");
        $data['total_number'] = empty($number) ? 0.000 : substr(sprintf("%.4f", ($number/ 1000)),0,-1);
        $data_list = array();
        $list = M('user_extract')->where('uid='.$uid)->field($field)->order('id desc')->select();
         if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data_list[] = $value;
                //时间
                if (!empty($value['create_time'])) {
                    $data_list[$key]['create_date'] = date('Y.m.d H:i', $value['create_time']);
                }
            }
        }
        $data['list'] = $data_list;
        return $data;
    }

    /**
     * 获取金券总额
     */
    public function getGcouponAmount(){
       return $this->sum('gold_coupon');
    }

    /**
     * 扣减指定用户的金券
     * @param $uid
     * @param $num
     * @return bool
     */
    public function discountGCoupon($uid , $num){
        return $this->where(array('id'=>$uid))->setDec('gold_coupon',$num);
    }
}
