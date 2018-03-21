<?php
/**
 * Created by PhpStorm.
 * User: zhangkang
 * Date: 2016/8/9
 * Time: 15:38
 */

namespace Admin\Model;

use Think\Model;

class FinanceModel extends Model
{
    public function lotterylist($param = array())
    {
        $sql = "select users.id userid,card.no cardno,period.purchaseorderstatus,period.suppliername,period.purchaseno,period.purchasecash,period.order_status,shop.`name`,shop.id shopid,shop.buy_price,period.id pid,shop.fictitious,period.`no`,users.username,users.phone,period.kaijang_num,period.kaijang_time,shoporder.cash,shoporder.gold,shoporder.type,shoporder.create_time,shoporder.order_id,period.iscommon,m.no as house_no from bo_shop_period period left JOIN bo_shop shop ON period.sid=shop.id LEFT JOIN bo_house_manage m ON period.house_id = m.id RIGHT JOIN bo_shop_order shoporder ON shoporder.pid = period.id left JOIN bo_user users on shoporder.uid = users.id  left join bo_card card on period.card_id=card.id where period.kaijang_num>0 and period.uid=users.id ";
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $sql .= " and period.kaijang_time>=" . $startTime;
        }

        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and  period.kaijang_time<=" . $endTime;
        }

        if ( $param['order_status'] ) {
            $sql .= " and period.order_status=" . $param['order_status'];
        }

        if ( $param['suppliersname'] ) {
            $sql .= " and period.suppliername='" . $param['suppliersname']."' ";
        }
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
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

        $sql .= " GROUP BY shoporder.pid  ORDER BY period.kaijang_time DESC  limit " . $param['pageindex'] . "," . $param['pagesize'];
        $users = $this->query($sql, false);

        // echo $this->getLastSql();exit();

        return $users;
    }

    public function lotterylisttotal($param = array())
    {
        $sql = "select count(*) count from ( select period.id  from bo_shop_period period left JOIN bo_shop shop ON period.sid=shop.id RIGHT JOIN bo_shop_order shoporder ON shoporder.pid = period.id LEFT JOIN bo_house_manage m ON period.house_id = m.id  left JOIN bo_user users on  shoporder.uid = users.id where period.kaijang_num>0 and period.uid=users.id ";

        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            $sql .= " and period.kaijang_time>=" . $startTime;
        }

        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and  period.kaijang_time<=" . $endTime;
        }

        if ( $param['order_status'] ) {
            $sql .= " and period.order_status=" . $param['order_status'];
        }
        if ( $param['suppliersname'] ) {
            $sql .= " and period.suppliername=" . $param['suppliersname'];
        }
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
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

    /*获取平台列表*/
    public function getsuppliersnamelist()
    {
        $sql = "select p.suppliername from bo_shop_period p where p.suppliername is not null and p.suppliername <> ''  GROUP BY p.suppliername";
        $suppliers = $this->query($sql, false);

        return $suppliers;
    }
    /**
     * 资金流水 总数
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function fundstotal($param = array())
    {
        $sql = " SELECT count(*) count FROM bo_capitalflow flow LEFT JOIN (SELECT so.id,period.`iscommon`,manage.no FROM bo_shop_order so LEFT JOIN bo_shop_period period ON so.pid = period.id LEFT JOIN bo_house_manage manage ON period.house_id = manage.id)  o ON flow.`order_id` = o.id  WHERE  1=1"; 
        //支付方式
        if (isset($param['pay_platform'])) {
            $sql .= " and flow.pay_platform=".$param['pay_platform']; 
        }
        //是否是实物
        if (isset($param['fictitious'])) {
            $sql .= " and flow.fictitious=".$param['fictitious']; 
        }
        //开始时间
        if ( !empty($param['starttime']) ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and flow.order_time >= " . $startTime;
        }
        //结束时间
        if ( !empty($param['endtime']) ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  flow.order_time <= " . $endTime;
        } 
        //用 户 名搜索
        if ( !empty($param['keyworduser']) ) {
            $keyworduser = $param['keyworduser'];
            $sql .= " and flow.username like  '%" . $keyworduser . "%' ";
        }
        //商品名称搜索
        if ( !empty($param['keywordshop']) ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and flow.shop_name like  '%" . $keywordshop . "%' ";
        }
        //渠道列表
        if ( !empty($param['channel_id'])) {
            $channelid = $param['channel_id'];
            $sql .= " and flow.channel_id in (" . $channelid .")";
        }
        //渠道用户限制
        if ( !empty($param['channel_ids'])) {
            $channel_ids = $param['channel_ids'];
            $sql .= " and flow.channel_id_profit in (" . $channel_ids .")";
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and o.iscommon =".$iscommon;
            } else {
                $sql .= " and (o.iscommon =".$iscommon." or o.iscommon is null )";
            }
            
        }
        //房间号
        if ( !empty($param['houseno'])) {
            $sql .= " and o.no = ".$param['houseno'];
        }
         //利润归属渠道名称
        if ( isset($param['profitchannelid'])   ) {
            $profitchannelid = $param['profitchannelid'];
            $sql .= " and flow.channel_id_profit in (".$profitchannelid.")";
        }
        //利润归属一级渠道
        if ( isset($param['profitid'])   ) {
            $profitid = $param['profitid'];
            $sql .= " and flow.channel_id_profit in (".$profitid.")";
        }
         //邀请码
        if ( isset($param['invitation'])   ) {
            $invitation = $param['invitation'];
            $sql .= " and o.invitation_id_profit like  '%" . $invitation . "%' ";
        }
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 资金流水 列表
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function funds($param = array())
    {
        $sql = " SELECT flow.*,o.iscommon,o.no AS house_no,channel.channel_name as profit_channel_name,channel.root_name as profit_root_name,o.invitation_id_profit as invitationid FROM bo_capitalflow  flow LEFT JOIN (SELECT so.id,period.`iscommon`,manage.no,so.invitation_id_profit FROM bo_shop_order so LEFT JOIN bo_shop_period period ON so.pid = period.id LEFT JOIN bo_house_manage manage ON period.house_id = manage.id)  o ON flow.`order_id` = o.id  LEFT JOIN bo_channel channel ON flow.channel_id_profit = channel.id  WHERE  1=1";
        //支付方式
        if (isset($param['pay_platform'])) {
            $sql .= " and flow.pay_platform=".$param['pay_platform']; 
        }
        //是否是实物
        if (isset($param['fictitious'])) {
            $sql .= " and flow.fictitious=".$param['fictitious']; 
        }
        //开始时间
        if ( !empty($param['starttime']) ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and flow.order_time >= " . $startTime;
        }
        //结束时间
        if ( !empty($param['endtime']) ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  flow.order_time <= " . $endTime;
        }
        //用 户 名搜索
        if ( !empty($param['keyworduser']) ) {
            $keyworduser = $param['keyworduser'];
            $sql .= " and flow.username like  '%" . $keyworduser . "%' ";
        }
        //渠道列表
        if ( !empty($param['channel_id'])) {
            $channelid = $param['channel_id'];
            $sql .= " and flow.channel_id in (" . $channelid .")";
        }
        //渠道用户限制
        if ( !empty($param['channel_ids'])) {
            $channel_ids = $param['channel_ids'];
            $sql .= " and flow.channel_id_profit in (" . $channel_ids .")";
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and o.iscommon =".$iscommon;
            } else {
                $sql .= " and (o.iscommon =".$iscommon." or o.iscommon is null )";
            }
            
        }
        //房间号
        if ( !empty($param['houseno'])) {
            $sql .= " and o.no = ".$param['houseno'];
        }
        //利润归属渠道名称
        if ( isset($param['profitchannelid'])   ) {
            $profitchannelid = $param['profitchannelid'];
            $sql .= " and flow.channel_id_profit in (".$profitchannelid.")";
        }
        //利润归属一级渠道
        if ( isset($param['profitid'])   ) {
            $profitid = $param['profitid'];
            $sql .= " and flow.channel_id_profit in (".$profitid.")";
        }
        //邀请码
        if ( isset($param['invitation'])   ) {
            $invitation = $param['invitation'];
            $sql .= " and o.invitation_id_profit like  '%" . $invitation . "%' ";
        }
        $sql .= " ORDER BY flow.order_time desc";
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $sql .= "  limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $list = $this->query($sql, false);
        return $list;
    }
    
    /**
     * 实时资金流水汇总列表
     *
     * @author liuwei
     * @param  array  $param 条件
     * @return array 
     */
    public function fundsflowsummary($param = array())
    {
        $sql = " select type,cash,gold,(cash + gold) as pay_total   from bo_shop_order  WHERE  1=1";
        //支付平台
        if ( $param['pay_platform'] ) {
            $pay_platform = $param['pay_platform'];
            $sql .= "  and type = " . $pay_platform ;
        }
        if ( $param['starttime'] ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and create_time >= " . $startTime;
        }

        if ( $param['endtime'] ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  create_time <= " . $endTime;
        } 
        $sql .= " ORDER BY type asc";       
        $users = $this->query($sql, false);
        $cash_total = 0.00;//现金总支付金额
        $gold_total = 0.00;//金币总支付
        $list = array();
        if (!empty($users)) {
            $cash_total = sprintf("%.2f",array_sum(array_column($users, 'cash')));//现金总支付金额
            $gold_total = sprintf("%.2f",array_sum(array_column($users, 'gold')));//金币总支付
            foreach ($users as $key => $value) {
                $list[$value['type']][] = $value;
            }
        }
        $data = array();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data[$key]['type_id'] = (string)$key;
                $data[$key]['cash'] = sprintf("%.2f", array_sum(array_column($value,'cash')));
                $data[$key]['gold'] = sprintf("%.2f",array_sum(array_column($value,'gold')));
            }
        }
        $result = array();
        $result['cash_total'] = $cash_total;
        $result['gold_total'] = $gold_total;
        $result['list'] = $data;
        return $result;
    }
    /**
     * 实时资金流水 - 列表
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function fundsflowlist($param = array())
    {
        $sql = " select  so.order_id as pay_order_id,so.create_time as order_time,so.type as pay_platform,so.cash,so.gold,(so.cash + so.gold) as pay_total ,(so.cash - so.after_rebates_cash) AS discount_cash,(so.gold - so.after_rebates_gold) AS discount_gold, so.id as order_id,so.code , c.channelid as channel_id,c.channel_name,c.channel_level , c.root_name ,c.rootId as channel_root_id ,c.uid as user_id ,c.username,c.nickname,so.invitation_id_profit as invitationid, b.`no` as action_no, b.sid as shop_id,b.`name` as shop_name, b.state , b.fictitious,b.periodnumber,b.kaijang_time,so.red_id,so.cash * 0.03  as  profit,red.name as red_name,red.amount as red_amount,so.pid,b.ten,b.iscommon,b.house_no,ten.unit,if(so.`code`='FAIL',so.gold+so.cash,ifnull((CONVERT(((so.gold+so.cash)/ten.unit-so.number)*ten.unit,SIGNED)),0)) fail_amount,so.recharge,channel.channel_name as profit_channel_name,channel.root_name as profit_root_name from hx_shop_order as so 
    LEFT JOIN (  SELECT sp.id AS spid ,sp.`no`,sp.kaijang_time, s.`name`,sp.sid,sp.state,s.fictitious,s.periodnumber,s.ten,sp.iscommon,m.no AS house_no FROM hx_shop_period sp LEFT JOIN hx_shop s ON sp.sid = s.id LEFT JOIN hx_house_manage m ON sp.house_id = m.id GROUP BY sp.id ) b ON so.pid = b.spid 
    LEFT JOIN hx_ten ten ON b.ten=ten.id
  
    LEFT JOIN (  select u.id as uid , u.username,u.nickname, u.channelid,u.invitationid ,c.channel_name,c.channel_level,c.rootId,c.root_name from hx_user u LEFT JOIN hx_channel c ON u.channelid = c.id ) c ON so.uid = c.uid
    LEFT JOIN hx_channel channel ON so.channel_id_profit = channel.id
    LEFT JOIN hx_red_envelope red ON so.red_id = red.id 
    WHERE  1=1    ";

        //渠道列表
        if ( isset($param['channel_id'])) {
            $channelid = $param['channel_id'];
            $sql .= " and c.channelid in (" . $channelid .")";
        }

        //实物
        if ( $param['fictitious']   ) {
            $fictitious = $param['fictitious'];
            if ($fictitious==3) {//充值
                $sql .= "  and so.pid = 0 " ;
            } elseif($fictitious==1) {//实物
              $sql .= "  and ( b.fictitious = " . $fictitious." or b.fictitious is null  and so.pid>0 )" ;    
            } else {//虚物
               $sql .= "  and b.fictitious = " . $fictitious." and so.pid>0" ;  
            }
        }

        //支付平台
        if ( $param['pay_platform'] ) {
            $pay_platform = $param['pay_platform'];
            $sql .= "  and so.type = " . $pay_platform ;
        }

        //活动状态
        if ( isset($param['state']) ) {
            $state = $param['state'];
            $sql .= "  and b.state = " . $state ;
        }

        //现金传入cash_nonzero 则查询金额非零的数据
        if ( isset($param['cash_nonzero']) && $param['cash_nonzero']   ) {
            $sql .= " and so.cash  <> 0 ";
        }

        if ( $param['starttime'] ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and so.create_time >= " . $startTime;
        }

        if ( $param['endtime'] ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  so.create_time <= " . $endTime;
        } 

        if ( $param['kstarttime'] ) {
            $kstartTime = strtotime($param['kstarttime']);
            $sql .= " and b.kaijang_time >=" . $kstartTime;
        }

        if ( $param['kendtime'] ) {
            $kendTime = strtotime($param['kendtime'] . " 23:59:59");
            $sql .= " and  b.kaijang_time <=" . $kendTime;
        }

        ///// like condition
        if ( $param['keyworduser'] ) {
            $keyworduser = $param['keyworduser'];
            $sql .= " and c.username like  '%" . $keyworduser . "%' ";
        }

        if ( $param['keywordshop'] ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and b.`name` like  '%" . $keywordshop . "%' ";
        }
        //用户的推荐码
        if ( $param['keyword_invitationid'] ) {
            $keyword_invitationid = $param['keyword_invitationid'];
            $sql .= " and so.invitation_id_profit like  '%" . $keyword_invitationid . "%' ";
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and b.iscommon =".$iscommon;
            } else {
                $sql .= " and (b.iscommon =".$iscommon." or b.iscommon is null )";
            }
            
        }
        //房间号
        if ( $param['houseno'] ) {
            $house_no = $param['houseno'];
            $sql .= " and b.house_no =".$house_no;
        }
        //渠道用户限制
        if ( !empty($param['channel_ids'])) {
            $channel_ids = $param['channel_ids'];
            $sql .= " and so.channel_id_profit in (" . $channel_ids .")";
        }
        //利润归属渠道名称
        if ( isset($param['profitchannelid'])   ) {
            $profitchannelid = $param['profitchannelid'];
            $sql .= " and so.channel_id_profit in (".$profitchannelid.")";
        }
        //利润归属一级渠道
        if ( isset($param['profitid'])   ) {
            $profitid = $param['profitid'];
            $sql .= " and so.channel_id_profit in (".$profitid.")";
        }
        /////////////////////////////////////////////
        if($param['kstarttime'] ||  $param['kendtime'] ){
            $sql .= " ORDER BY b.kaijang_time DESC  limit " . $param['pageindex'] . "," . $param['pagesize'];
        }else{
            $sql .= " ORDER BY so.create_time DESC  limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $users = $this->query($sql, false);
        $data = array();
        if (!empty($users)) {
            foreach ($users as $key => $value) {
                $data[] = $value;
                $fail_amount = sprintf("%.2f", $value['fail_amount']);
                $success_amount = sprintf("%.2f",$value['pay_total']-$fail_amount);
                if ($value['code']=="OK") {
                    $data[$key]['fail_amount'] = $fail_amount; 
                    $data[$key]['success_amount'] = $success_amount;
                } else {
                    $data[$key]['fail_amount'] = $value['pay_total']; 
                    $data[$key]['success_amount'] = 0.00;
                }
            }
        }
        return $data;
    }
    
    /**
     * 实时资金流水 - 总数
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function fundsflowtotal($param = array())
    {
        $sql = " select count(*) count from ( select  so.order_id  from hx_shop_order as so 
    LEFT JOIN (  select sp.id as spid , s.`name`,sp.sid,sp.state,sp.kaijang_time,s.fictitious,s.periodnumber,sp.iscommon,m.no  AS house_no from hx_shop_period sp LEFT JOIN hx_shop s ON sp.sid = s.id LEFT JOIN hx_house_manage m ON sp.id = m.periodid GROUP BY sp.id ) b ON so.pid = b.spid 
  
    LEFT JOIN (  select u.id as uid , u.username,u.nickname, u.channelid ,u.invitationid ,c.channel_name,c.channel_level,c.rootId,c.root_name from hx_user u LEFT JOIN hx_channel c ON u.channelid = c.id ) c ON so.uid = c.uid 
    WHERE  1=1   ";

        //渠道列表
        if ( isset($param['channel_id'])   ) {
            $channelid = $param['channel_id'];
            $sql .= " and c.channelid in ( " . $channelid .")";
        }

        //实物
        if ( $param['fictitious']   ) {
            $fictitious = $param['fictitious'];
            if ($fictitious==3) {//充值
                $sql .= "  and so.pid = 0 " ;
            } elseif($fictitious==1) {//实物
              $sql .= "  and ( b.fictitious = " . $fictitious." or b.fictitious is null  and so.pid>0 )" ;  
            } else {//虚物
               $sql .= "  and b.fictitious = " . $fictitious." and so.pid>0" ;  
            }
        }

        //支付平台
        if ( $param['pay_platform']   ) {
            $pay_platform = $param['pay_platform'];
            $sql .= "  and so.type = " . $pay_platform ;
        }

        //活动状态
        if ( isset($param['state'])    ) {
            $state = $param['state'];
            $sql .= "  and b.state = " . $state ;
        }

        //现金传入cash_nonzero 则查询金额非零的数据
        if ( isset($param['cash_nonzero']) && $param['cash_nonzero']   ) {
            $sql .= " and so.cash  <> 0 ";
        }

        if ( $param['order_time'] ) {
            if ( $param['starttime'] ) {
                $startTime = strtotime($param['starttime']);
                $sql .= " and so.order_time >= " . $startTime;
            }

            if ( $param['endtime'] ) {
                $endTime = strtotime($param['endtime'] . " 23:59:59");
                $sql .= " and  so.order_time <= " . $endTime;
            }
        }else{
            if ( $param['starttime'] ) {
                $startTime = strtotime($param['starttime']);
                $sql .= " and so.create_time >= " . $startTime;
            }

            if ( $param['endtime'] ) {
                $endTime = strtotime($param['endtime'] . " 23:59:59");
                $sql .= " and  so.create_time <= " . $endTime;
            }
        }
       

        if ( $param['kstarttime'] ) {
            $kstartTime = strtotime($param['kstarttime']);
            $sql .= " and b.kaijang_time  >= " . $kstartTime;
        }

        if ( $param['kendtime'] ) {
            $kendTime = strtotime($param['kendtime'] . " 23:59:59");
            $sql .= " and  b.kaijang_time  <= " . $kendTime;
        }

        ///// like condition
        if ( $param['keyworduser'] ) {
            $keyworduser = $param['keyworduser'];
            $sql .= " and c.username like  '%" . $keyworduser . "%' ";
        }

        if ( $param['keywordshop'] ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and b.`name` like  '%" . $keywordshop . "%' ";
        }

        //用户的推荐码
        if ( $param['keyword_invitationid'] ) {
            $keyword_invitationid = $param['keyword_invitationid'];
            $sql .= " and so.invitation_id_profit like  '%" . $keyword_invitationid . "%' ";
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and b.iscommon =".$iscommon;
            } else {
                $sql .= " and (b.iscommon =".$iscommon." or b.iscommon is null )";
            }
            
        }

        //房间号
        if ( $param['houseno'] ) {
            $house_no = $param['houseno'];
            $sql .= " and b.house_no =".$house_no;
        }
        //渠道用户限制
        if ( !empty($param['channel_ids'])) {
            $channel_ids = $param['channel_ids'];
            $sql .= " and so.channel_id_profit in (" . $channel_ids .")";
        }
        //利润归属渠道名称
        if ( isset($param['profitchannelid'])   ) {
            $profitchannelid = $param['profitchannelid'];
            $sql .= " and so.channel_id_profit in (".$profitchannelid.")";
        }
        //利润归属一级渠道
        if ( isset($param['profitid'])) {
            $profitid = $param['profitid'];
            $sql .= " and so.channel_id_profit in (".$profitid.")";
        }
        
        $sql .= " ) t "; 
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 实时资金流水 - new列表
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function newfundsflowlist($param = array())
    {
        $sql = " SELECT  so.buy_gold as number,so.exchange_transaction,so.order_id AS pay_order_id,so.create_time AS order_time,so.type AS pay_platform,so.top_diamond,so.recharge_activity,so.gold AS pay_total,so.id AS order_id,so.uid AS user_id ,user.nickname as username,b.sid AS shop_id,b.name AS shop_name, b.state , b.fictitious,b.periodnumber,b.kaijang_time,so.pid,b.ten as action_no,b.no as period_no,so.exchange_type,user.channel_id,user.channel_name,record.num as numbersinfo,sgr.gold_price FROM bo_shop_order AS so 
        LEFT JOIN (  SELECT sp.id AS spid ,sp.`no`,sp.kaijang_time, s.`name`,sp.sid,sp.state,s.fictitious,s.periodnumber,s.ten,sp.iscommon FROM bo_shop_period sp LEFT JOIN bo_shop s ON sp.sid = s.id GROUP BY sp.id) b ON so.pid = b.spid 
        LEFT JOIN (SELECT u.*,channel.id as channel_id,channel.channel_name,channel.proportion FROM bo_user u LEFT JOIN bo_channel channel ON channel.id = u.channelid) user ON so.uid=user.id 
        LEFT JOIN bo_shop_record record ON so.order_id = record.order_id
        LEFT JOIN bo_shop_gold_record sgr ON sgr.id=so.gr_id
        WHERE  1=1";
        //活动状态
        if ( isset($param['state']) ) {
            $state = $param['state'];
            $sql .= "  and b.state = " . $state ;
        }
        //开始时间
        if ( $param['starttime'] ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and so.create_time >= " . $startTime;
        }
        //结束时间
        if ( $param['endtime'] ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  so.create_time <= " . $endTime;
        } 
        //开奖开始时间
        if ( $param['kstarttime'] ) {
            $kstartTime = strtotime($param['kstarttime']);
            $sql .= " and b.kaijang_time >=" . $kstartTime;
        }
        //开奖结束时间
        if ( $param['kendtime'] ) {
            $kendTime = strtotime($param['kendtime'] . " 23:59:59");
            $sql .= " and  b.kaijang_time <=" . $kendTime;
        }
        //商品名称
        if ( $param['keywordshop'] ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and b.`name` like  '%" . $keywordshop . "%' ";
        }
        //所属渠道
        if ( !empty($param['channel'])   ) {
            $channel = $param['channel'];
            $sql .= " and user.`channelid` = ".$channel;
        }
        //支付流水号
        if ( $param['orderid'] ) {
            $orderid = $param['orderid'];
            $sql .= " and so.`order_id` like  '%" . $orderid . "%' ";
        }
        //用户id
        if ( $param['uid'] ) {
            $uid = $param['uid'];
            $sql .= " and so.`uid` like  '%" . $uid . "%' ";
        }
        if($param['kstarttime'] ||  $param['kendtime'] ){
            $sql .= " ORDER BY b.kaijang_time DESC ";
        }else{
            $sql .= " ORDER BY so.create_time DESC ";
        }
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $sql .= " limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $users = $this->query($sql, false);
        return $users;
    }
    
    /**
     * 实时资金流水 - new总数
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function newfundsflowtotal($param = array())
    {
        $sql = "select count(*) count from ( 
                    select  so.order_id  
                    from bo_shop_order as so 
                    LEFT JOIN (
                        select sp.id as spid , s.`name`,sp.sid,sp.state,sp.kaijang_time,s.fictitious,s.periodnumber,sp.iscommon 
                        from bo_shop_period sp 
                        LEFT JOIN bo_shop s ON sp.sid = s.id GROUP BY sp.id) b ON so.pid = b.spid  
                    LEFT JOIN bo_user user ON so.uid=user.id 
                    WHERE  1=1   ";
        //活动状态
        if ( isset($param['state']) ) {
            $state = $param['state'];
            $sql .= "  and b.state = " . $state ;
        }
        //支付时间开始时间
        if ( $param['starttime'] ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and so.create_time >= " . $startTime;
        }
        //支付时间结束时间
        if ( $param['endtime'] ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  so.create_time <= " . $endTime;
        }
        //开奖开始时间
        if ( $param['kstarttime'] ) {
            $kstartTime = strtotime($param['kstarttime']);
            $sql .= " and b.kaijang_time  >= " . $kstartTime;
        }
        //开奖结束时间
        if ( $param['kendtime'] ) {
            $kendTime = strtotime($param['kendtime'] . " 23:59:59");
            $sql .= " and  b.kaijang_time  <= " . $kendTime;
        }
        //商品名称
        if ( $param['keywordshop'] ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and b.`name` like  '%" . $keywordshop . "%' ";
        }
        //支付流水号
        if ( $param['orderid'] ) {
            $orderid = $param['orderid'];
            $sql .= " and so.`order_id` like  '%" . $orderid . "%' ";
        }
        //用户id
        if ( $param['uid'] ) {
            $uid = $param['uid'];
            $sql .= " and so.`uid` like  '%" . $uid . "%' ";
        }
        //所属渠道
        if ( !empty($param['channel'])   ) {
            $channel = $param['channel'];
            $sql .= " and user.`channelid` = ".$channel;
        }       
        $sql .= " ) t "; 
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 资金流水-支付方式
     * @author liuwei
     * @return array
     */
    public function get_platform_list()
    {
        $list = M('Capitalflow')->field('pay_platform')->select();//获取所有资金流水的type列表
        //type集合
        $ids = array();
        if (!empty($list)) {
            $id = array_column($list, 'pay_platform');
            $ids = array_unique($id);
        }
        //所有支付类型
        $type_data = get_payarr();
        //有单的支付类型
        if (!empty($type_data)) {
            foreach ($type_data as $key => $value) {
                if (in_array($value['type'], $ids)) {
                    $type_list[] = $value;
                }
            }
        } else {
           $type_list = $type_data; 
        }
        return $type_list;
    }

    /**
     * 实时资金流水-支付方式
     * @author liuwei
     * @return array
     */
    public function get_type_list()
    {
        $list = M('ShopOrder')->field('type')->select();//获取所有资金流水的type列表
        //type集合
        $ids = array();
        if (!empty($list)) {
            $id = array_column($list, 'type');
            $ids = array_unique($id);
        }
        //所有支付类型
        $type_data = get_payarr();
        //有单的支付类型
        if (!empty($type_data)) {
            foreach ($type_data as $key => $value) {
                if (in_array($value['type'], $ids)) {
                    $type_list[] = $value;
                }
            }
        } else {
           $type_list = $type_data; 
        }
        return $type_list;
    }

    /**
     * 测试资金流水-支付方式
     * @author liuwei
     * @return array
     */
    public function test_type_list()
    {
        $list = D('shop_test_order')->field('type')->select();//获取所有测试资金流水的type列表
        //type集合
        $ids = array();
        if (!empty($list)) {
            $id = array_column($list, 'type');
            $ids = array_unique($id);
        }
        //所有支付类型
        $type_data = get_payarr();
        //有单的支付类型
        if (!empty($type_data)) {
            foreach ($type_data as $key => $value) {
                if (in_array($value['type'], $ids)) {
                    $type_list[] = $value;
                }
            }
        } else {
           $type_list = $type_data; 
        }
        return $type_list;
    }
    public function callbacklisttotal($param = array())
    {
        $sql = "
            select count(*) count from ( select  DISTINCT(so.order_id) order_id  from bo_temporary_order as so
            LEFT JOIN (  select sp.id as spid , s.`name`,sp.sid,sp.state,sp.kaijang_time,s.fictitious,s.periodnumber from bo_shop_period sp LEFT JOIN bo_shop s ON sp.sid = s.id ) b ON so.pid = b.spid 
            LEFT JOIN (  select u.id as uid , u.username,u.nickname, u.channelid ,u.invitationid ,c.channel_name,c.channel_level,c.rootId,c.root_name from bo_user u LEFT JOIN bo_channel c ON u.channelid = c.id ) c ON so.uid = c.uid 
            LEFT JOIN bo_shop_order tem ON tem.`order_id`=so.`order_id`
            WHERE  1=1   ";
        //渠道列表
        if ( isset($param['channel_id'])   ) {
            $channelid = $param['channel_id'];
            $sql .= " and c.channelid in ( " . $channelid .")";
        }

        //实物
        if ( $param['fictitious']   ) {
            $fictitious = $param['fictitious'];
            $sql .= "  and b.fictitious = " . $fictitious ;
        }

        //支付平台
        if ( $param['pay_platform']   ) {
            $pay_platform = $param['pay_platform'];
            $sql .= "  and tem.type = " . $pay_platform ;
        }

        //活动状态
        if ( isset($param['state'])    ) {
            $state = $param['state'];
            $sql .= "  and b.state = " . $state ;
        }

        //现金传入cash_nonzero 则查询金额非零的数据
        $sql .= " and so.cash  <> 0 ";

        if ( $param['starttime'] ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and so.create_time >= " . $startTime;
        }

        if ( $param['endtime'] ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  so.create_time <= " . $endTime;
        } 
       

        if ( $param['kstarttime'] ) {
            $kstartTime = strtotime($param['kstarttime']);
            $sql .= " and b.kaijang_time  >= " . $kstartTime;
        }

        if ( $param['kendtime'] ) {
            $kendTime = strtotime($param['kendtime'] . " 23:59:59");
            $sql .= " and  b.kaijang_time  <= " . $kendTime;
        }

        ///// like condition
        if ( $param['keyworduser'] ) {
            $keyworduser = $param['keyworduser'];
            $sql .= " and c.username like  '%" . $keyworduser . "%' ";
        }

        if ( $param['keywordshop'] ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and b.`name` like  '%" . $keywordshop . "%' ";
        }

        //用户的推荐码
        if ( $param['keyword_invitationid'] ) {
            $keyword_invitationid = $param['keyword_invitationid'];
            $sql .= " and c.invitationid like  '%" . $keyword_invitationid . "%' ";
        }
        if ( isset($param['callback'])    ) {
            $callback = $param['callback'];
            $sql .= "  and so.is_callback = " . $callback ;
        }
        $sql .= " ) t "; 
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    public function callbacklist($param = array())
    {
        $sql = " select tem.order_id as pay_order_id,tem.create_time as order_time,so.type as pay_platform,tem.cash,tem.gold,(tem.cash + tem.gold) as pay_total , tem.id as order_id,so.code , c.channelid as channel_id,c.channel_name,c.channel_level , c.root_name ,c.rootId as channel_root_id ,c.uid as user_id ,c.username,c.nickname,c.invitationid , b.`no` as action_no, b.sid as shop_id,b.`name` as shop_name, b.state , b.fictitious,b.periodnumber,b.kaijang_time,so.cash * 0.03  as  profit,tem.is_callback from bo_temporary_order as tem 
    LEFT JOIN (  select sp.id as spid ,sp.`no`,sp.kaijang_time, s.`name`,sp.sid,sp.state,s.fictitious,s.periodnumber from bo_shop_period sp LEFT JOIN bo_shop s ON sp.sid = s.id ) b ON tem.pid = b.spid 
  
    LEFT JOIN (  select u.id as uid , u.username,u.nickname, u.channelid,u.invitationid ,c.channel_name,c.channel_level,c.rootId,c.root_name from bo_user u LEFT JOIN bo_channel c ON u.channelid = c.id ) c ON tem.uid = c.uid

    LEFT JOIN bo_shop_order so ON tem.`order_id`=so.`order_id`
    WHERE  1=1    and  tem.id IN (SELECT MAX(id) FROM  bo_temporary_order GROUP BY order_id)";

        //渠道列表
        if ( isset($param['channel_id'])   ) {
            $channelid = $param['channel_id'];
            $sql .= " and c.channelid in (" . $channelid .")";
        }

        //实物
        if ( $param['fictitious']   ) {
            $fictitious = $param['fictitious'];
            $sql .= "  and b.fictitious = " . $fictitious ;
        }

        //支付平台
        if ( $param['pay_platform']   ) {
            $pay_platform = $param['pay_platform'];
            $sql .= "  and so.type = " . $pay_platform ;
        }

        //活动状态
        if ( isset($param['state'])   ) {
            $state = $param['state'];
            $sql .= "  and b.state = " . $state ;
        }

        //现金传入cash_nonzero 则查询金额非零的数据
        $sql .= " and tem.cash  <> 0 ";

        if ( $param['starttime'] ) {
            $startTime = strtotime($param['starttime']);
            $sql .= " and tem.create_time >= " . $startTime;
        }

        if ( $param['endtime'] ) {
            $endTime = strtotime($param['endtime'] . " 23:59:59");
            $sql .= " and  tem.create_time <= " . $endTime;
        } 

        if ( $param['kstarttime'] ) {
            $kstartTime = strtotime($param['kstarttime']);
            $sql .= " and b.kaijang_time >=" . $kstartTime;
        }

        if ( $param['kendtime'] ) {
            $kendTime = strtotime($param['kendtime'] . " 23:59:59");
            $sql .= " and  b.kaijang_time <=" . $kendTime;
        }

        ///// like condition
        if ( $param['keyworduser'] ) {
            $keyworduser = $param['keyworduser'];
            $sql .= " and c.username like  '%" . $keyworduser . "%' ";
        }

        if ( $param['keywordshop'] ) {
            $keywordshop = $param['keywordshop'];
            $sql .= " and b.`name` like  '%" . $keywordshop . "%' ";
        }
        //用户的推荐码
        if ( $param['keyword_invitationid'] ) {
            $keyword_invitationid = $param['keyword_invitationid'];
            $sql .= " and c.invitationid like  '%" . $keyword_invitationid . "%' ";
        }
        if ( isset($param['callback'])    ) {
            $callback = $param['callback'];
            $sql .= "  and tem.is_callback = " . $callback;
        }
        /////////////////////////////////////////////
        if($param['kstarttime'] ||  $param['kendtime'] ){
            $sql .= " ORDER BY b.kaijang_time DESC  limit " . $param['pageindex'] . "," . $param['pagesize'];
        }else{
            $sql .= " ORDER BY tem.create_time DESC  limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 房间利润汇总 - 总数
     * 
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function housetotal($param= array())
    {
        $sql = "SELECT COUNT(*) as count FROM bo_house_manage m LEFT JOIN (  SELECT u.id AS uid , u.channelid,u.invitationid ,c.channel_name,c.root_name,c.rate FROM bo_user u LEFT JOIN bo_channel c ON u.channelid = c.id ) c ON m.uid = c.uid  WHERE ispublic = 1";
        //渠道搜索
        if (isset($param['channel_id'])) {
            $sql .= " and c.channelid in (".$param['channel_id'].")";
        }
        //渠道搜索
        if (isset($param['channelid'])) {
            $sql .= " and c.channelid=".$param['channelid'];
        }
        //邀请码搜索
        if (!empty($param['invitationid'])) {
            $sql .= " and m.invitecode like  '%" . $param['invitationid'] . "%' ";
        }
        //房间号搜索
        if (!empty($param['no'])) {
            $sql .= " and m.no like  '%" . $param['no'] . "%' ";
        }
        //开始时间
        if ( !empty($param['starttime']) ) {
            $start_time = strtotime($param['starttime']);
            $sql .= " and m.create_time >= ".$start_time;
        }
        //结束时间
        if ( !empty($param['endtime']) ) {
            $end_time = strtotime($param['endtime']. " 23:59:59");
            $sql .= " and m.create_time <= ".$end_time;
        }
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 房间利润汇总 - 列表
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function houselist($param= array())
    {
        $sql = "SELECT m.id,c.username,m.no,m.uid,m.invitecode,c.channel_name,c.root_name,c.rate,c.channelid,m.create_time FROM bo_house_manage m LEFT JOIN (  SELECT u.id AS uid ,u.username,u.channelid,u.invitationid ,c.channel_name,c.root_name,c.rate FROM bo_user u LEFT JOIN bo_channel c ON u.channelid = c.id ) c ON m.uid = c.uid  WHERE ispublic = 1";
        //渠道搜索
        if (isset($param['channel_id'])) {
            $sql .= " and c.channelid in (".$param['channel_id'].")";
        }
        //渠道搜索
        if (isset($param['channelid'])) {
            $sql .= " and c.channelid=".$param['channelid'];
        }
        //邀请码搜索
        if (!empty($param['invitationid'])) {
            $sql .= " and m.invitecode like  '%" . $param['invitationid'] . "%' ";
        }
        //房间号搜索
        if (!empty($param['no'])) {
            $sql .= " and m.no like  '%" . $param['no'] . "%' ";
        }
        //开始时间
        if ( !empty($param['starttime']) ) {
            $start_time = strtotime($param['starttime']);
            $sql .= " and m.create_time >= ".$start_time;
        }
        //结束时间
        if ( !empty($param['endtime']) ) {
            $end_time = strtotime($param['endtime']. " 23:59:59");
            $sql .= " and m.create_time <= ".$end_time;
        }
        $users = $this->query($sql, false);
        $sql .= " ORDER BY m.create_time";
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $sql .= "  limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $data = $this->query($sql, false);
        $list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $list[] = $value;
                //用户开房间总金额
                $pk_list=M()->table('bo_shop_order o')
                        ->join('LEFT JOIN bo_shop_period period ON o.pid=period.id')
                        ->where('period.house_id = '.$value['id'])
                        ->field('sum(o.cash) as cash')
                        ->select();
                $pk_cash = empty($pk_list[0]['cash']) ? 0.00 : $pk_list[0]['cash'];//房间总金额
                $rate = empty($value['rate']) ? '0.03' : $value['rate']/100;//利率
                $rate_money = $pk_cash*$rate;//利润
                $list[$key]['cash'] = sprintf("%.2f", $pk_cash);
                $list[$key]['rate_money'] = sprintf("%.2f", $rate_money);
                $list[$key]['rate'] = empty($value['rate']) ? '3%' :$value['rate'].'%';
            }
        }
        return $list;
    }
    /**
     * 提金流水 - 总数
     *
     * @author liwuei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getFetchGoldTotal($param= array())
    {
        $sql = "select count(*) count from bo_user_cash c left join bo_user u on c.uid = u.id where 1=1";
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',u.id,u.nickname) like '%".$param['name']."%' )";
        }
        //开始时间
        if(!empty($param['starttime'])){
            $sql .= " and c.create_time >= ".strtotime($param['starttime']);
        }
        //结束时间
        if(!empty($param['endtime'])){
            $endtime = strtotime($param['endtime'])+86400;
            $sql .= " and c.create_time < ".$endtime;
        }
        //渠道id
        if ( !empty($param['channel']) ) {
            $sql .= " and c.passport_uid = ".$param['channel'];
        }
        //物流状态
        if ( isset($param['status']) ) {
            $sql .= " and c.order_status = ".$param['status'];
        }
        //订单号
        if ( isset($param['keywordorder']) ) {
            $sql .= " and c.order_id like  '%" . $param['keywordorder'] . "%' ";
        }
        $count = $this->query($sql, false);
        return $count[0]['count'];
    }
    /**
     * 提金流水 - 列表
     *
     * @author liwuei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getFetchGoldList($param= array())
    {
        $sql = "select c.*,(c.other_expenses+c.purchasecash) as actual_price,(c.recharge_activity+c.top_diamond) as total,u.nickname,u.gold_balance,bc.channel_name,(c.number*c.gold_price) as buy_price from bo_user_cash c left join bo_user u on c.uid = u.id left join bo_channel bc on c.channel_id = bc.id left join bo_shop s on c.sid = s.id where 1=1";
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',u.id,u.nickname) like '%".$param['name']."%' )";
        }
        //开始时间
        if(!empty($param['starttime'])){
            $sql .= " and c.create_time >= ".strtotime($param['starttime']);
        }
        //结束时间
        if(!empty($param['endtime'])){
            $endtime = strtotime($param['endtime'])+86400;
            $sql .= " and c.create_time < ".$endtime;
        }
        //渠道id
        if ( !empty($param['channel']) ) {
            $sql .= " and c.passport_uid = ".$param['channel'];
        }
        //物流状态
        if ( isset($param['status']) ) {
            $sql .= " and c.order_status = ".$param['status'];
        }
        //订单号
        if ( isset($param['keywordorder']) ) {
            $sql .= " and c.order_id like  '%" . $param['keywordorder'] . "%' ";
        }
        $sql .= " order by c.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $list = $this->query($sql, false);
        return $list;
    }
    /**
     * 提金流水 - 总数
     *
     * @author liwuei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getFetchCashTotal($param= array())
    {
        $sql = "select count(*) count from bo_user_extract c left join bo_user u on c.uid = u.id where 1=1";
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',u.id,u.nickname) like '%".$param['name']."%' )";
        }
        //开始时间
        if(!empty($param['starttime'])){
            $sql .= " and c.create_time >= ".strtotime($param['starttime']);
        }
        //结束时间
        if(!empty($param['endtime'])){
            $endtime = strtotime($param['endtime'])+86400;
            $sql .= " and c.create_time < ".$endtime;
        }
        //审核状态
        if ( isset($param['status']) ) {
            $sql .= " and c.state = ".$param['status'];
        }
        $count = $this->query($sql, false);
        return $count[0]['count'];
    }
    /**
     * 提金流水 - 列表
     *
     * @author liwuei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getFetchCashList($param= array())
    {
        $sql = "select c.*,u.nickname,bc.channel_name,bc.extract_money from bo_user_extract c left join bo_user u on c.uid = u.id left join bo_channel bc on c.channel_id = bc.id left join bo_shop s on c.sid = s.id where 1=1";
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',u.id,u.nickname) like '%".$param['name']."%' )";
        }
        //开始时间
        if(!empty($param['starttime'])){
            $sql .= " and c.create_time >= ".strtotime($param['starttime']);
        }
        //结束时间
        if(!empty($param['endtime'])){
            $endtime = strtotime($param['endtime'])+86400;
            $sql .= " and c.create_time < ".$endtime;
        }
        //审核状态
        if ( isset($param['status']) ) {
            $sql .= " and c.state = ".$param['status'];
        }
        $sql .= " order by c.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $list = $this->query($sql, false);
        return $list;
    }
    /**
     * 更新分类信息
     * @return boolean 更新状态
     */
    public function examine(){
        $model = D('user_extract');
        $data = $model->create();
        if(!$data){ //数据对象创建错误
            return false;
        }
        /* 添加或更新数据 */
        if(!empty($data['id'])){
            if ($data['state']==1) {//审核通过
                $res = $model->save($data);
            } else {
                $info = $model->where('id='.$data['id'])->field('uid,number')->find();
                //减去黄金余量
                $rs_u = M('user')->where('id='.$info['uid'])->setInc('gold_balance',$info['number']);
                if ($rs_u != false) {
                    $res = $model->save($data);
                } else{
                    $res = false;
                }
            }
            
        }else{
            $res = false;
        }
        return $res;
    }
}