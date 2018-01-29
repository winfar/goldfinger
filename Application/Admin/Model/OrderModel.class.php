<?php
/**
 * Created by PhpStorm.
 * User: ppa
 * Date: 2016/8/2
 * Time: 11:10
 */

namespace Admin\Model;

use Think\Model;
use Think\Storage;
use Com\Alidayu\AlidayuClient as Client;
use Com\Alidayu\Request\SmsNumSend;

class OrderModel extends Model
{
    public function getOrders($param = array())
    {
        $sql = "SELECT channel.root_name root_name,channel.pid channelpid,channel.channel_name,shoporder.create_time,userinfo.username,shoporder.number,shoporder.order_id,shoporder.cash,shoporder.gold,shoporder.type,period.sid,period.id,userinfo.id uid,(select p.kaijang_num from bo_shop_period p where p.uid=userinfo.id and p.id=shoporder.pid and p.kaijang_num>0) kaijang_num,c.channel_name as profit_channel_name,c.root_name as profit_root_name,shoporder.invitation_id_profit from bo_shop_order shoporder left join bo_channel c on shoporder.channel_id_profit=c.id left JOIN bo_shop_period period ON shoporder.pid=period.id left JOIN bo_user userinfo on userinfo.id=shoporder.uid left join bo_channel channel on userinfo.channelid=channel.id  WHERE shoporder.pid=" . $param['pid'] ." and  shoporder.`code` = 'OK' " ;

        //支付方式
        if ( $param['paytype'] ) {
            $sql .= " and shoporder.type=" . $param['paytype'];
        }
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $endTime = strtotime($param['create_time'] . " 23:59:59");
            $sql .= " and shoporder.create_time>=" . $startTime . " and  shoporder.create_time<=" . $endTime;
        }
        //利润归属渠道名称
        if ( isset($param['profitchannelid'])   ) {
            $profitchannelid = $param['profitchannelid'];
            $sql .= " and shoporder.channel_id_profit in (".$profitchannelid.")";
        }
        //利润归属一级渠道
        if ( isset($param['profitid'])   ) {
            $profitid = $param['profitid'];
            $sql .= " and shoporder.channel_id_profit in (".$profitid.")";
        }
        //邀请码
        if ( isset($param['invitation']) ) {
            $sql .= " and shoporder.invitation_id_profit like  '%" . $param['invitation'] . "%' ";
        }

        $sql .= " order by shoporder.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];

        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 活动明细总数 - new
     *
     * @author liuwei 
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getOrdersTotal($param = array())
    {
        $sql = "SELECT count(*) count from bo_shop_order shoporder left JOIN bo_shop_period period ON shoporder.pid=period.id left JOIN bo_user userinfo on userinfo.id=shoporder.uid  WHERE   shoporder.pid=" . $param['pid'] ." and  shoporder.`code` = 'OK' " ;

        //支付方式
        if ( $param['paytype'] ) {
            $sql .= " and shoporder.type=" . $param['paytype'];
        }
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $endTime = strtotime($param['create_time'] . " 23:59:59");
            $sql .= " and shoporder.create_time>=" . $startTime . " and  shoporder.create_time<=" . $endTime;
        }
        //利润归属渠道名称
        if ( isset($param['profitchannelid'])   ) {
            $profitchannelid = $param['profitchannelid'];
            $sql .= " and shoporder.channel_id_profit in (".$profitchannelid.")";
        }
        //利润归属一级渠道
        if ( isset($param['profitid'])   ) {
            $profitid = $param['profitid'];
            $sql .= " and shoporder.channel_id_profit in (".$profitid.")";
        }

        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 活动明细 - new
     *
     * @author liuwei 
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getNewOrders($param = array())
    {
        $sql = "SELECT shoporder.exchange_transaction,shoporder.create_time,userinfo.username,shoporder.number,shoporder.order_id,shoporder.top_diamond,shoporder.recharge_activity,(shoporder.recharge_activity+shoporder.top_diamond) as total,shoporder.type,period.sid,period.id,userinfo.id uid,(select p.kaijang_num from bo_shop_period p where p.uid=userinfo.id and p.id=shoporder.pid) kaijang_num,shoporder.invitation_id_profit,shoporder.exchange_type from bo_shop_order shoporder left JOIN bo_shop_period period ON shoporder.pid=period.id left JOIN bo_user userinfo on userinfo.id=shoporder.uid  WHERE shoporder.pid=" . $param['pid'] ;
        //开始时间
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $sql .= " and shoporder.create_time>=" . $startTime;
        }
        //结束时间
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and shoporder.create_time<=" . $endTime;
        }
        //兑换流水号
        if ( isset($param['keyword']) ) {
            $sql .= " and shoporder.exchange_transaction like  '%" . $param['keyword'] . "%' ";
        }

        $sql .= " order by shoporder.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];

        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 活动明细总数 - new
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getNewOrdersTotal($param = array())
    {
        $sql = "SELECT count(*) count from bo_shop_order shoporder left JOIN bo_shop_period period ON shoporder.pid=period.id left JOIN bo_user userinfo on userinfo.id=shoporder.uid  WHERE   shoporder.pid=" . $param['pid'] ." and  shoporder.`code` = 'OK' " ;

        //开始时间
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $sql .= " and shoporder.create_time>=" . $startTime;
        }
        //结束时间
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and shoporder.create_time<=" . $endTime;
        }
        //兑换流水号
        if ( isset($param['keyword']) ) {
            $sql .= " and shoporder.exchange_transaction like  '%" . $param['keyword'] . "%' ";
        }
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }

    public function getShoprecordInfo($param = array())
    {
        $info = M('shop_record')->field('num,number,order_id,uid')->where('uid=' . $param['uid'] . ' and pid=' . $param['pid'] . ' and order_id="' . $param['order_id'].'"')->find();
        return $info;
    }
    /**
     * 活动明细
     *
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function  getShoprecordNum($param = array())
    {
        $sql = "SELECT sp.id as pid,uid,kaijang_num,kaijang_time,users.nickname as username,exchange_type FROM bo_shop_period  sp INNER JOIN bo_user users ON sp.uid =users.id WHERE  (kaijang_num>0 or exchange_type=1) and sp.id=" . $param['pid'];
        $nums = $this->query($sql, false);
        $uid = $nums[0]['uid'];
        if (!empty($nums[0]['exchange_type']) and $nums[0]['exchange_type']==1) {
            $shopsql = "select id,order_id from bo_shop_order where uid=" . $uid . " and pid=" . $param['pid'];
            $info = $this->query($shopsql, false);
            foreach ( $nums as $index => $num ) {
                $userInfo['exchange_type'] = $num['exchange_type'];
                $userInfo['orderid'] = $info[0]['order_id'];
                $userInfo['pid'] = $num['pid'];
                $userInfo['uid'] = $num['uid'];
                $userInfo['username'] = $num['username'];
            }

        } else {
            $shopsql = "select number,order_id,num,pid from bo_shop_record  sr where sr.uid=" . $uid . " and sr.pid=" . $param['pid'];
            $userkaijianginfo = $this->query($shopsql, false);
            //在所有购买号码中比对当前中奖的号码；
            foreach ( $nums as $index => $num ) {
                $numscount = 0;
                $arrnuminfos = '';
                foreach ( $userkaijianginfo as $ke => $it ) {
                    $numst = explode(',', $it['num']);
                    foreach ( $numst as $index => $numz ) {
                        if ( $num['kaijang_num'] == $numz ) {
                            $userInfo['orderid'] = $it['order_id'];
                            $userInfo['pid'] = $it['pid'];
                        }
                    }
                    $numscount = $numscount + $it['number'];
                    $arrnuminfos .= ',' . $it['num'];
                }
                $userInfo['uid'] = $uid;
                $userInfo['username'] = $num['username'];
                $userInfo['kaijiangnum'] = $num['kaijang_num'];
                $userInfo['numcount'] = $numscount;
                $userInfo['allnumber'] = str_replace(',', '   ', $arrnuminfos);
                $userInfo['exchange_type'] = $num['exchange_type'];
            }
        }
        

        return $userInfo;
    }
     /**
     * 用户中奖详情
     * @param $param['uid'] 用户id
     * @author liuwei
     * @return mixed
     */
    public function lottery_order($param = array())
    {
        $users = array();
        if ( $param['uid'] ) {
             $sql = "select period.id,channel.root_name root_name,channel.pid channelpid,channel.channel_name,period.purchaseorderstatus,period.order_status,shop.`name`,period.id pid,shop.fictitious,period.`no`,period.create_time,users.username,users.phone,period.kaijang_num,period.kaijang_time,shoporder.cash,shoporder.gold,shoporder.type,shoporder.create_time,shoporder.order_id from bo_shop_period period left JOIN bo_shop shop ON period.sid=shop.id RIGHT JOIN bo_shop_order shoporder ON shoporder.pid = period.id left JOIN bo_user users on shoporder.uid = users.id  left join bo_channel channel on users.channelid=channel.id where period.kaijang_num>0 and period.uid=users.id ";
            $sql .= " and period.uid=".$param['uid'];
            $sql .= " GROUP BY shoporder.pid   order by period.kaijang_time desc";
            $users = $this->query($sql, false);
        }
        
        return $users;
    }
    public function lotteryorder($param = array())
    {
        $sql = "SELECT channel.root_name root_name,channel.pid channelpid,channel.channel_name,period.purchaseorderstatus,period.order_status,shop.`name`,period.id pid,shop.fictitious,period.no,users.username,users.phone,period.kaijang_num,period.kaijang_time,shoporder.cash,shoporder.gold,shoporder.type,shoporder.create_time,shoporder.order_id,period.iscommon,m.no AS house_no FROM bo_shop_period period LEFT JOIN bo_shop shop ON period.sid=shop.id LEFT JOIN bo_house_manage m ON period.house_id = m.id RIGHT JOIN bo_shop_order shoporder ON shoporder.pid = period.id WHERE period.kaijang_num>0 AND period.uid=users.id";

        if ( $param['paytype'] ) {
            $sql .= " and shoporder.type=" . $param['paytype'];
        }
        if ( $param['fictitious'] ) {
            $sql .= " and shop.fictitious=" . $param['fictitious'];
        }
        if ( $param['purchaseorderstatus'] ) {
            $sql .= " and period.purchaseorderstatus=" . $param['purchaseorderstatus'];
        }
        if ( $param['order_status'] ) {
            $sql .= " and period.order_status=" . $param['order_status'];
        }
        if ( $param['order_status'] ) {
            $sql .= " and shoporder.order_status=" . $param['order_status'];
        }
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        if ( $param['order_id'] ) {
            $sql .= " and shoporder.order_id like  '%" . $param['order_id'] . "%' ";
        }
        if ( $param['uid'] ) {
            $sql .= " and period.uid=".$param['uid'];
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and period.iscommon =".$iscommon;
            } else {
                $sql .= " and (period.iscommon =".$iscommon." or period.iscommon is null )";
            }
            
        }
        if ( !empty($param['houseno']) ) {
            $sql .= " and m.no=".$param['houseno'];
        }
        if ( isset($param['shopstatus'])) {
            $status = $param['shopstatus'];
            //品牌名称为金袋的品牌id
            $barnd_item = D('Brand')->where("title = '金袋'")->field('id')->find();
             if ($status==2) {
                $sql .= " and shop.fictitious=2";//1.非金袋2.金袋
            }
            if (!empty($barnd_item)) {
                if ($status==1) {
                    $sql .= " and shop.brand_id!=".$barnd_item['id'];
                } else {
                    $sql .= " and shop.brand_id=".$barnd_item['id'];  
                }
            }
        }

        $sql .= " GROUP BY shoporder.pid   order by period.kaijang_time desc  limit " . $param['pageindex'] . "," . $param['pagesize'];
        $users = $this->query($sql, false);
        return $users;
    }

    public function lotteryordertotal($param = array())
    {
        $sql = "select count(*) count from ( select period.id  from bo_shop_period period left JOIN bo_shop shop ON period.sid=shop.id LEFT JOIN bo_house_manage m ON period.house_id = m.id left JOIN bo_shop_order shoporder ON shoporder.pid = period.id left JOIN bo_user users on  shoporder.uid = users.id where period.kaijang_num>0 and period.uid=users.id ";

        if ( $param['paytype'] ) {
            $sql .= " and shoporder.type=" . $param['paytype'];
        }
        if ( $param['fictitious'] ) {
            $sql .= " and shop.fictitious=" . $param['fictitious'];
        }
        if ( $param['purchaseorderstatus'] ) {
            $sql .= " and period.purchaseorderstatus=" . $param['purchaseorderstatus'];
        }

        if ( $param['order_status'] ) {
            $sql .= " and shoporder.order_status=" . $param['order_status'];
        }
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        if ( $param['order_id'] ) {
            $sql .= " and shoporder.order_id like  '%" . $param['order_id'] . "%' ";
        }
        if ( isset($param['shopstatus'])) {
            $status = $param['shopstatus'];
            //品牌名称为金袋的品牌id
            $barnd_item = D('Brand')->where("title = '金袋'")->field('id')->find();
            if ($status==2) {
                $sql .= " and shop.fictitious=2";//1.非金袋2.金袋
            }
            if (!empty($barnd_item)) {
                if ($status==1) {
                    $sql .= " and shop.brand_id!=".$barnd_item['id'];
                } else {
                    $sql .= " and shop.brand_id=".$barnd_item['id'];  
                }
            }
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and period.iscommon =".$iscommon;
            } else {
                $sql .= " and (period.iscommon =".$iscommon." or period.iscommon is null )";
            }
            
        }
        if ( !empty($param['houseno']) ) {
            $sql .= " and m.no=".$param['houseno'];
        }
        $sql .= " GROUP BY shoporder.pid ) t ";
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 开奖订单列表 - new
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function lottery($param = array())
    {
        $sql = "SELECT period.gold_price,period.sid,period.purchaseorderstatus,period.order_status,shop.name,period.id pid,shop.fictitious,period.no,period.uid as uid,user.nickname as username,period.phone,period.kaijang_num,period.kaijang_time,shoporder.top_diamond,shoporder.recharge_activity,shoporder.type,shoporder.create_time,shoporder.order_id,shoporder.exchange_type,shoporder.exchange_transaction,(select sum(buy_gold) from bo_shop_order where pid=period.id and uid=period.uid) as win_number,(select sum(top_diamond) from bo_shop_order where pid=period.id and uid=period.uid) as total_top_diamond,(select sum(recharge_activity) from bo_shop_order where pid=period.id and uid=period.uid) as total_recharge_activity,(SELECT SUM(gold) FROM bo_shop_order where pid=period.id and uid=period.uid) as total_win_price,(SELECT SUM(gold) FROM bo_shop_order where pid=period.id) as total_price,shop.buy_price,period.purchasecash,user.channel_id,user.channel_name,(select sum(buy_gold) from bo_shop_order where pid=period.id) as total_number,(SELECT cast((SUM(gold)/user.proportion) as decimal(18,2)) FROM bo_shop_order where pid=period.id) AS total_gold
        FROM bo_shop_period period 
        LEFT JOIN bo_shop shop ON period.sid=shop.id 
        LEFT JOIN (SELECT u.*,channel.id as channel_id,channel.channel_name,channel.proportion FROM bo_user u LEFT JOIN bo_channel channel ON channel.id = u.channelid) user ON period.uid=user.id 
        LEFT JOIN bo_shop_order shoporder ON shoporder.pid=period.id and shoporder.uid=period.uid 
        LEFT JOIN bo_shop_record record ON shoporder.order_id=record.order_id AND shoporder.uid=record.uid and FIND_IN_SET(  period.kaijang_num,record.num)
        WHERE period.state=2";
        //echo $sql;exit;
//采购状态
        if ( $param['purchaseorderstatus'] ) {
            $sql .= " and period.purchaseorderstatus=" . $param['purchaseorderstatus'];
        }
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',user.id,user.nickname) like '%".$param['name']."%' )";
        }
        //订单id
        if ( $param['keywordorder'] ) {
            $sql .= " and shoporder.order_id like  '%" . $param['keywordorder'] . "%' ";
        }
        //开始时间搜索
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $sql .= " and period.kaijang_time>='" . $startTime . "'";
        }
        //结束时间搜索
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and period.kaijang_time<='" . $endTime . "'";
        }
        //兑换流水号
        if ( $param['exchange_type'] ) {
            $sql .= " and shoporder.exchange_transaction like  '%" . $param['exchange_type'] . "%' ";
        }
        //渠道
        if ( $param['channel'] ) {
            $channel = $param['channel'];
            $sql .= " and user.`channel_id` = ".$channel;
        }
        $sql .= " GROUP BY shoporder.pid   order by period.kaijang_time desc,period.id desc";
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $sql .= " limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        //exit($sql);
        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 开奖订单总数 - new
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function lotterytotal($param = array())
    {
        $sql = "SELECT count(*) count FROM ( SELECT period.id  FROM bo_shop_period period LEFT JOIN bo_shop shop ON period.sid=shop.id LEFT JOIN bo_user user ON period.uid=user.id LEFT JOIN bo_shop_order shoporder ON shoporder.pid=period.id and shoporder.uid=period.uid where period.state=2";
        //采购状态
        if ( $param['purchaseorderstatus'] ) {
            $sql .= " and period.purchaseorderstatus=" . $param['purchaseorderstatus'];
        }
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',user.id,user.nickname) like '%".$param['name']."%' )";
        }
        //订单id
        if ( $param['keywordorder'] ) {
            $sql .= " and shoporder.order_id like  '%" . $param['keywordorder'] . "%' ";
        }
        //开始时间搜索
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $sql .= " and period.kaijang_time>='" . $startTime . "'";
        }
        //结束时间搜索
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and period.kaijang_time<='" . $endTime . "'";
        }
        //兑换流水号
        if ( $param['exchange_type'] ) {
            $sql .= " and shoporder.exchange_transaction like  '%" . $param['exchange_type'] . "%' ";
        }
        //渠道
        if ( $param['channel'] ) {
            $channel = $param['channel'];
            $sql .= " and user.`channelid` = ".$channel;
        }
        $sql .= " GROUP BY shoporder.pid ) t ";
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }

    //订单详情；
    public function orderdetail($param = array())
    {
        $shopperiod = M('shop_period')->where(array('id' => $param['pid']))->field('id,card_id,uid,sid,no,kaijang_num,order_status,contacts,phone,address,express_name,express_no,purchaseno,suppliername,purchasecash,purchaseorderstatus')->find();
        $user = M('user')->where(array('id' => $shopperiod['uid']))->field('username,phone')->find();
        $shoporder = M('shop_order')->where(array('order_id' => $param['order_id']))->field('type,gold,cash,order_id,create_time')->find();
        $shop = M('shop')->where(array('id' => $shopperiod['sid']))->field('name,price')->find();
        if ( $shopperiod['card_id'] > 0 ) {
            $shopperiod['cardno'] = M('card')->where(array('id' => $shopperiod['card_id']))->field('no')->find()['no'];
        }

        $arr = array();
        $arr['shopperiod'] = $shopperiod;
        $arr['user'] = $user;
        $arr['shoporder'] = $shoporder;
        $arr['shop'] = $shop;

        return $arr;
    }
    /**
     * 订单详情 - new
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function orderinfo($param = array())
    {
        $shopperiod = M('shop_period')->where(array('id' => $param['pid']))->field('id,card_id,uid,sid,no,kaijang_num,order_status,contacts,phone,address,express_name,express_no,purchaseno,suppliername,purchasecash,purchaseorderstatus,kaijang_time,email,uid,exchange_type')->find();
        //$user = M('user')->where(array('id' => $shopperiod['uid']))->field('username,phone')->find();
        $shoporder = M('shop_order')->where(array('order_id' => $param['order_id']))->field('type,top_diamond,recharge_activity,order_id,create_time,exchange_transaction,exchange_type')->find();
        $shop = M('shop')->where(array('id' => $shopperiod['sid']))->field('name,price,full_price')->find();
        if ( $shopperiod['card_id'] > 0 ) {
            $card_info = M('card')->where(array('id' => $shopperiod['card_id']))->field('no,password')->find();
            $shopperiod['cardno'] = $card_info['no'];
            $length = mb_strlen($card_info['password'], 'utf8')-8;//密码长度
            $shopperiod['cardpassword']=substr_replace($card_info['password'], '********', 4, $length);
        }
        $user = M('user')->where(array('id' => $shopperiod['uid']))->field('nickname,phone,id')->find();
        $top_diamond = empty($shoporder['top_diamond']) ? 0 : $shoporder['top_diamond'];
        $recharge_activity = empty($shoporder['recharge_activity']) ? 0 : $shoporder['recharge_activity'];
        $arr = array();
        $arr['shopperiod'] = $shopperiod;
        $arr['user'] = $user;
        $arr['shoporder'] = $shoporder;
        $arr['shoporder']['top_diamond'] = $top_diamond;
        $arr['shoporder']['recharge_activity'] = $recharge_activity;
        $arr['shoporder']['total'] = $top_diamond+$recharge_activity;
        $arr['shop'] = $shop;

        return $arr;
    }
    /**
     * 提现单详情
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function orderinfonew($param = array())
    {
        $sql = "select c.*,(c.recharge_activity+c.top_diamond) as total,u.nickname,u.gold_balance,u.phone as tel,bc.channel_name,s.buy_price,s.name as shop_name from bo_user_cash c left join bo_user u on c.uid = u.id left join bo_channel bc on c.channel_id = bc.id left join bo_shop s on c.sid = s.id where 1=1";
        //用户ID/用户名
        if ( isset($param['id']) ) {
            $sql .= " and c.id = ".$param['id'];
        }
        $item = $this->query($sql, false);
        return $item[0];
    }
    

    public function purchaseorderadd($param = array())
    {
        $data['express_name'] = $param['express_name'];
        $data['express_no'] = $param['express_no'];
        $data['purchaseno'] = $param['purchaseno'];
        $data['suppliername'] = $param['suppliername'];
        $data['purchasecash'] = empty($param['purchasecash']) ? 0 : $param['purchasecash'];
        $data['purchaseorderstatus'] = $param['purchaseorderstatus'];

        $data['contacts'] = $param['contacts'];
        $data['phone'] = $param['phone'];
        $data['address'] = $param['contacts'];
        $data['email'] = $param['email'];
        //$count = D('Order')->purchaseorderadd($data);

        $period = M('shop_period')->where('id='.$param['pid'])->find();
        
        
        $flag=false;
        if($period['order_status']==$param['order_status']){
            $flag=true;
        }

        if($period['order_status']>100 && $param['order_status']==100){
            $flag=false;
        }
        else{
            //如果是已确认收货地址
            if($period['order_status']==100 && $param['order_status']==101)
            {
            $flag=true;
            }
            elseif ($period['order_status']==101 && $param['order_status'] == 102) {
                $flag=true;
            }elseif ($period['order_status']==101 && $param['order_status'] == 103) {
                $flag=true;
            }
        }
        
        if($flag == true){
            $order_time_array = !empty($period['order_status_time']) ? json_decode($period['order_status_time'], true) : array();
            $order_status = $param['order_status'];
            if ($order_status==100) {
                $order_time_array['receive_time'] = time();//领取时间
            } else if ($order_status==101) {
                $order_time_array['send_time'] = time();//发货时间
            } else if ($order_status==102) {
                $order_time_array['receipt_time'] = time();//收货时间
            }
            $data['order_status'] = $param['order_status'];
            $data['order_status_time'] = json_encode($order_time_array);
            $count = M('shop_period')->where(array('id' => $param['pid']))->save($data);
             if ($order_status == 101) {
                //是否是直充卡 1是0否
                $straight = 0;
                $shop_info = M('shop')->where('id='.$period['sid'])->field('name,category')->find();//获取商品明细and分类id
                if (!empty($shop_info)) {
                    $name = "直充卡";
                    $category_where = array();
                    $category_where['title'] = array('like', "%".$name."%");
                    $category_where['id'] = $shop_info['category'];
                    $category_count = M('category')->where($category_where)->count();
                    $straight = $category_count==0 ? 0 : $category_count;
                }
                $fictitious = M('shop')->where('id='.$period['sid'])->getField('fictitious');
                if ($straight!=0) {//直充卡
                    $phone = M('user')->where('id='.$period['uid'])->getField('phone');//手机号
                    if (!empty($phone) and !empty($shop_info['name'])) {
                        $n_result = D('api/Notification')->sendStraight($phone,$shop_info['name']);//发短信
                        $msg = "发送成功";
                        if ($n_result==101) {
                            $msg = "发送失败";
                        }
                        recordLog($msg,"发货发送短信:商品名称:".$period['id'].'手机号:'.$phone);
                    }
                } else {
                    if ($fictitious==2) {//虚拟商品
                        if (!empty($period['card_id'])) {
                            $phone = M('user')->where('id='.$period['uid'])->getField('phone');//手机号
                            $no = M('card')->where('id='.$period['card_id'].' and status=1 and issend=0')->getField('no');//卡号
                            if (!empty($phone) and !empty($no)) {
                                $n_result = D('api/Notification')->sendCardSnNew($param['pid'],$no,$phone);//发短信
                                $msg = "卡号跟卡密发送成功";
                                if ($n_result==101) {
                                    $msg = "卡号跟卡密发送失败";
                                } else if ($n_result==102) {
                                    $msg = "商品已经兑换过或者暂时不可用！";
                                } else if ($n_result==103) {
                                    $msg = "卡号不正确！";
                                }
                                recordLog($msg,"发货发送短信:开奖pid:".$period['id'].'手机号:'.$phone);
                                //已使用
                                $c_result = M('card')->where('id='.$period['card_id'])->save(array('issend'=>1,'send_time'=>time()));
                                if ($c_result != flase) {
                                    recordLog("卡号跟卡密使用成功","卡号:".$no);
                                } else {
                                    recordLog("卡号跟卡密使用失败","卡号:".$no);
                                }
                            }
                        }                  

                    }
                }
            }
            return $count;
        }
        else {
            return 0;
        }
    }

    /**
     * 活动汇总总数
     * 
     * @param  array  $param 条件
     * @author liuwei
     * @return [int] 总数
     */
    public function getActivityTotal($param = array())
    {
        $sql = " SELECT shop.id,shop.name,shop.buy_price,period.id pid,period.purchasecash FROM bo_shop shop INNER JOIN bo_shop_period period ON period.sid = shop.id WHERE 1=1  ";
        //商品名称搜索
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        //活动状态搜索
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        //开始时间搜索
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            $sql .= " and period.end_time>='" . $startTime . "000' ";
        }
        //结束时间搜索
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and period.end_time<='" . $endTime . "000' ";
        }
        //是否实物搜索
        if ( $param['fictitious'] ) {
            $sql .= " and fictitious=" . $param['fictitious'];
        }
        if ( isset($param['shopstatus'])) {
            $status = $param['shopstatus'];
            //品牌名称为金袋的品牌id
            $barnd_item = D('Brand')->where("title = '金袋'")->field('id')->find();
            if ($status==2) {
                $sql .= " and shop.fictitious=2";//1.非金袋2.金袋
            }
            if (!empty($barnd_item)) {
                if ($status==1) {
                    $sql .= " and shop.brand_id!=".$barnd_item['id'];
                } else {
                    $sql .= " and shop.brand_id=".$barnd_item['id'];  
                }
            }
        }
        $sql .= " order by period.sid desc";
        $list =  $this->query($sql, false);
        $data = array();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data[$value['id']][] = $value;
            }
        }
        return count($data);
    }

    /**
     * 活动汇总列表
     * 
     * @param  array  $param 条件
     * @author liuwei
     * @return [array] 列表
     */
    public function getActivity($param = array())
    {
        $sql = " SELECT shop.id,shop.name,shop.buy_price ,period.id pid,period.purchasecash FROM bo_shop shop INNER JOIN bo_shop_period period ON period.sid = shop.id WHERE 1 = 1  ";
        //商品名称搜索
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        //活动状态搜索
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        //开始时间搜索
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            $sql .= " and period.end_time>='" . $startTime . "000' ";
        }
        //结束时间搜索
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and period.end_time<='" . $endTime . "000' ";
        }
        //是否实物搜索
        if ( $param['fictitious'] ) {
            $sql .= " and fictitious=" . $param['fictitious'];
        }
        if ( isset($param['shopstatus'])) {
            $status = $param['shopstatus'];
            //品牌名称为金袋的品牌id
            $barnd_item = D('Brand')->where("title = '金袋'")->field('id')->find();
            if ($status==2) {
                $sql .= " and shop.fictitious=2";//1.非金袋2.金袋
            }
            if (!empty($barnd_item)) {
                if ($status==1) {
                    $sql .= " and shop.brand_id!=".$barnd_item['id'];
                } else {
                    $sql .= " and shop.brand_id=".$barnd_item['id'];  
                }
            }
        }
        $sql .= " order by period.sid desc";
        $list =  $this->query($sql, false);
        $data = array();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data[$value['id']][] = $value;
            }
        }
        $data_list = array();
        if (!empty($data)) {
            $i=0;
            foreach ($data as $key => $value) {
                $pid = array_column($value,'pid');//所有的期数id集合
                $where = array();
                $where['code'] = "OK";
                $where['pid'] = array('in', $pid);
                $order_list = D('shop_order')->where($where)->field('cash,gold')->select();//订单列表
                $buy_price = sprintf("%.2f", array_sum(array_column($value,'buy_price')));//预估成本总额
                $purchasecash = sprintf("%.2f", array_sum(array_column($value,'purchasecash')));//实际成本总额
                $cash = sprintf("%.2f", array_sum(array_column($order_list,'cash')));//现金总支付金额
                $gold = sprintf("%.2f", array_sum(array_column($order_list,'gold')));//金币总支付金额
                $name = array_column($value,'name');//金币总支付金额
                $data_list[$i]['shop_id'] = $key;
                $data_list[$i]['shop_name'] = $name[0];
                $data_list[$i]['record_number'] = count($value);
                $data_list[$i]['cash'] = $cash;
                $data_list[$i]['gold'] = $gold;
                $data_list[$i]['total'] = sprintf("%.2f", $gold+$cash);
                $data_list[$i]['buy_price'] = $buy_price;
                $data_list[$i]['purchasecash'] = $purchasecash;
                $i++;
            }
        }
        if (!empty($param['pageindex']) and !empty($param['pageindex'])) {
            return array_slice($data_list,$param['pageindex'],$param['pagesize']);
        } else {
            return $data_list;
        }

    }
    /**
     * 活动汇总总数 - new
     * 
     * @param  array  $param 条件
     * @author liuwei
     * @return [int] 总数
     */
    public function getNewActivityTotal($param = array())
    {
        $sql = " SELECT COUNT(*) count FROM bo_channel c INNER JOIN bo_shop shop WHERE 1=1";
        //结束时间搜索
        if ( !empty($param['channel'])) {
            $sql .= " and c.id = " . $param['channel'];
        }        
        $list =  $this->query($sql, false);
        return $list[0]['count'];
    }

    /**
     * 活动汇总列表
     * 
     * @param  array  $param 条件
     * @author liuwei
     * @return [array] 列表
     */
    public function getNewActivity($param = array())
    {
        //活动状态搜索
        $period_where = " 1=1";
        $order_where = " 1=1";
        $kaijang_where = " state = 2 ";
        $cash_where = " 1=1";
        if ( is_numeric($param['state']) ) {
            $period_where .= " and p.state=" . $param['state'];
            $order_where .= " and p.state=" . $param['state'];
            $kaijang_where .= " and state=" . $param['state'];
        }
        //开始时间搜索
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            $period_where .= " and p.end_time>='" . $startTime . "000' ";
            $order_where .= " and o.create_time>=" . $startTime;
            $kaijang_where .= " and create_time>='" . $startTime . "000' ";
            $cash_where .= " and create_time>=" . $startTime;
        }
        //结束时间搜索
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $period_where .= " and p.end_time<='" . $endTime . "000' ";
            $order_where .= " and o.create_time<=" . $endTime;
            $kaijang_where .= " and create_time<='" . $endTime . "000' ";
            $cash_where .= " and create_time<=" . $endTime;
        }
        $sql = " SELECT c.id as channel_id,c.channel_name,shop.id as sid,shop.name,(select count(*) from bo_shop_period p LEFT JOIN bo_user u ON u.id = p.uid where ".$period_where." and p.sid = shop.id and u.channelid = c.id) as total_count,(select count(*) from bo_shop_period p LEFT JOIN bo_user u ON u.id = p.uid where ".$kaijang_where." and p.sid = shop.id and u.channelid = c.id) as total_kaijiang_count,(SELECT (case when SUM(o.gold) is NUll then 0 else SUM(o.gold) end ) FROM bo_shop_order o LEFT JOIN bo_shop_period p ON o.pid = p.id LEFT JOIN bo_user user ON o.uid = user.id where ".$order_where." and p.sid = shop.id and user.channelid = c.id) as total_price,(SELECT (case when SUM(o.buy_gold) is NUll then 0 else SUM(o.buy_gold) end ) FROM bo_shop_order o LEFT JOIN bo_shop_period p ON o.pid = p.id where ".$order_where." and p.sid = shop.id and o.channel_id = c.id) as total_buy_gold,(SELECT (case when SUM(o.number) is NUll then 0 else SUM(o.number) end ) FROM bo_shop_order o LEFT JOIN bo_shop_period p ON o.pid = p.id where ".$order_where." and p.sid = shop.id and o.channel_id = c.id) as total_number,(SELECT (case when SUM(o.buy_gold) is NUll then 0 else SUM(o.buy_gold)*c.proportion end ) FROM bo_shop_order o LEFT JOIN bo_shop_period p ON o.pid = p.id where ".$order_where." and p.sid = shop.id and o.channel_id = c.id) as total_buy_price,(SELECT (case when SUM(other_expenses+purchasecash) is NUll then 0 else SUM(other_expenses+purchasecash) end ) FROM bo_user_cash where ".$cash_where." and channel_id = c.id) as total_actual_price FROM bo_channel c INNER JOIN bo_shop shop WHERE 1 = 1  ";
        //渠道
        if ( !empty($param['channel'] )) {
            $sql .= " and c.id =".$param['channel'];
        }
        
        $sql .= " order by c.id desc";
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $sql .= " limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $list =  $this->query($sql, false);
        return $list;
        

    }
    public function getAddAddress($map = array())
    {
        $order_item = D('Order')->orderinfo($map);
        if (empty($order_item['contacts']) and empty($order_item['phone'])) {
            $pid = empty($map['pid']) ? 0 : $map['pid'];
            $period_item = M('shop_period')->where('id='.$pid)->field('uid')->find();//开奖订单获取获奖者id
            if (!empty($period_item['uid'])) {
                $uid = $period_item['uid'];
                $list = M('shop_address')->where('uid='.$uid)->field('nickname,tel,address,email')->order('id desc')->find();//地址
                if (!empty($list)) {
                    $data = array();
                    $data['contacts'] = $list['nickname'];
                    $data['phone'] = $list['tel'];
                    $data['email'] = $list['email'];
                    $data['address'] = $list['address'];
                    M('shop_period')->where('id='.$pid)->save($data);
                }

            }
        }
        
    }
    public function getSendSms($pid)
    {
        $array = array();//data
        $code = 101;//code
        $msg = "该期不存在!";//code
        $period = M('shop_period')->where('id='.$pid)->field('uid,card_id,sid')->find();//获取uid和card_id
        if (!empty($period)) {
            $shop =  M('shop')
                ->alias('s')
                ->join('__CATEGORY__ c ON c.id = s.category')
                ->where('s.id='.$period['sid'])
                ->field('s.name,c.title')->find();
            $phone = M('user')->where('id='.$period['uid'])->getField('phone');//手机号
            $card = M('card')->where('id='.$period['card_id'])->field('no,password')->find();//卡号
            $name = "直充卡";//卡号
            if ($shop['title'] == $name) {
                if (!empty($phone) and !empty($shop['name'])) {
                    //向指定手机发卡密
                    $client = new Client;
                    $request = new SmsNumSend;
                    $smsParams = array(
                        'name' => $shop['name'],
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
                        $array = $request;
                        $code = 200;
                        $msg = "重发成功!";
                        recordLog($msg,"重发短信成功:pid为:".$pid.'手机号:'.$phone);
                    } else {
                        $array = $request;
                        $msg = $request["error_response"]["sub_msg"];//code
                        recordLog($msg,"重发短信失败:pid为:".$pid.'手机号:'.$phone);
                    }
                } else {
                    $msg = "手机号不存在/商品不存在";//code
                    recordLog($msg,"重发短信失败:pid为:".$pid.'手机号:'.$phone);
                }

            } else {
                if (!empty($phone) and !empty($card) and !empty($shop['name'])) {
                    //向指定手机发卡密
                    $client = new Client;
                    $request = new SmsNumSend;
                    $smsParams = array(
                        'name' => $shop['name'],
                        'card' => $card['no'],
                        'cardpassport' => $card['password'],
                    );
                    // 设置请求参数
                    $req = $request->setSmsTemplateCode('SMS_13880391')
                        ->setRecNum($phone)
                        ->setSmsParam(json_encode($smsParams))
                        ->setSmsFreeSignName("LIVE商城")
                        ->setSmsType('normal')
                        ->setExtend('card');
                    $request = $client->execute($req);
                    $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];
                    if($reqResult===true){
                        $array = $request;
                        $code = 200;
                        $msg = "重发成功!";
                        recordLog($msg,"重发短信成功:pid为:".$pid.'手机号:'.$phone);
                    } else {
                        $array = $request;
                        $msg = $request["error_response"]["sub_msg"];//code
                        recordLog($msg,"重发短信失败:pid为:".$pid.'手机号:'.$phone);
                    }
                } else {
                    $msg = "手机号不存在/卡号不存在/商品不存在";//code
                    recordLog($msg,"重发短信失败:pid为:".$pid.'手机号:'.$phone);
                }
            }
            


        }
        returnJson($array, $code, $msg);
        

    }
    /**
     * 提现单操作
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function cashadd($param = array())
    {
        $data['express_name'] = $param['express_name'];
        $data['express_no'] = $param['express_no'];
        $data['purchaseno'] = $param['purchaseno'];
        $data['suppliername'] = $param['suppliername'];
        $data['purchasecash'] = empty($param['purchasecash']) ? 0 : $param['purchasecash'];
        $data['purchaseorderstatus'] = $param['purchaseorderstatus'];

        $data['contacts'] = $param['contacts'];
        $data['phone'] = $param['phone'];
        $data['address'] = $param['address'];
        $data['email'] = $param['email'];
        $data['other_expenses'] = $param['other_expenses'];
        //$count = D('Order')->purchaseorderadd($data);
        $model = M('user_cash');
        $period = $model->where('id='.$param['oid'])->find();
        
        $flag=false;
        if($period['order_status']==$param['order_status']){
            $flag=true;
        }

        if($period['order_status']>100 && $param['order_status']==100){
            $flag=false;
        }
        else{
            //如果是已确认收货地址
            if($period['order_status']==100 && $param['order_status']==101)
            {
            $flag=true;
            }
            elseif ($period['order_status']==101 && $param['order_status'] == 102) {
                $flag=true;
            }elseif ($period['order_status']==101 && $param['order_status'] == 103) {
                $flag=true;
            }
        }
        
        if($flag == true){
            $order_time_array = !empty($period['order_status_time']) ? json_decode($period['order_status_time'], true) : array();
            $order_status = $param['order_status'];
            if ($order_status==100) {
                $order_time_array[100] = time();//领取时间
            } else if ($order_status==101) {
                $order_time_array[101] = time();//发货时间
            } else if ($order_status==102) {
                $order_time_array[102] = time();//收货时间
            }
            $data['order_status'] = $param['order_status'];
            $data['order_status_time'] = json_encode($order_time_array);
            $count = $model->where('id='.$param['oid'])->save($data);
            return $count;
        }
        else {
            return 0;
        }
    }
}