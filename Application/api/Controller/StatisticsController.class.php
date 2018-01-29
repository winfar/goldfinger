<?php
namespace api\Controller;
use Think\Controller;

/**
 * 数据统计控制器
 * Class StatisticsController
 * @package api\Controller
 */
class StatisticsController {

    /**
     * 每日统计任务
     * @param $startDate 2017-02-02
     * @param string $endDate 2017-02-17
     */
    public function daySecdule($startDate=''){
        if(empty($startDate)){
            $startDate = date('Ymd',strtotime('-1 day'));
        }
        $starttime = strtotime($startDate);
        $endtime = strtotime($startDate)+86400;

        echo '<br>date => '.$startDate;
        echo '<br>starttime=>'.$starttime.' => endtime=>'.$endtime;
        $Statistics = M ( 'statistics_day') ;
        $rs_count = $Statistics->where(array('date'=>$startDate))->count();
        if($rs_count > 0 ){
            echo '<br>starttime=>'.$startDate.' exsits!';
            return ;
        }
        //获取每个参数
        // 按日统计
        $Model = M();

//        $sql = "select count(1) as v from (
//select * from bo_user_access_log t GROUP BY t.uid HAVING( count(1)= 1 and t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."')) tmp ;";
        //  激活用户数
        $rs_active = $Model->query("select count(1) as v from (
select * from bo_user_access_log t where t.create_time <  '".$endtime."' GROUP BY t.uid HAVING( count(1)= 1 and t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."')) tmp ;");
//        echo '<br>rs_active sql =>'.$sql;
        echo '<br>rs_active=>'.intval($rs_active[0]['v']);
        //  活跃(UV)
        $rs_UV = $Model->query("select count(1) as v from (
select * from bo_user_access_log t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' GROUP BY t.uid ) tmp;");
        echo '<br>rs_UV=>'.intval($rs_UV[0]['v']);
        //  次留
        $rs_day1retention = $Model->query("select count(1) as v from ( select * from  bo_user_access_log t where t.create_time <   '".$endtime."' and t.create_time >= '".($starttime-86400)."' GROUP BY t.uid HAVING( count(1)>1 )) tmp ;");
        echo '<br>rs_day1retention=>'.intval($rs_day1retention[0]['v']);
        //次日留存率  (次日留存/次日活跃UV)
        $rs_day1UV = $Model->query("select count(1) as v from (
select * from bo_user_access_log t where t.create_time <  '".($endtime-86400)."' and t.create_time >= '".($starttime-86400)."' GROUP BY t.uid ) tmp;");

        $rs_day1retentionrate = $rs_day1retention[0]['v'] / $rs_day1UV[0]['v'];

        echo '<br>rs_day1retention=>'.($rs_day1retentionrate);
        //  抽奖金额
        $rs_draw_amount = $Model->query(" select abs(sum(t.top_diamond+t.recharge_activity)) as v from bo_shop_order t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type = 0 ; ");
        echo '<br>rs_draw_amount=>'.intval($rs_draw_amount[0]['v']);
        //  全价兑换金额
        $rs_exchange_amount = $Model->query("select abs(sum(t.top_diamond+t.recharge_activity)) as v from bo_shop_order  t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type = 1 ;");
        echo '<br>rs_exchange_amount=>'.intval($rs_exchange_amount[0]['v']);
        //  总消费金额
        $rs_full_amount = $rs_draw_amount[0]['v'] + $rs_exchange_amount[0]['v'];
        echo '<br>rs_full_amount=>'.$rs_full_amount;
        //  充值钻石消耗
        $rs_rechage_amount = $Model->query("select abs(sum(t.top_diamond)) as v from bo_shop_order  t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' ;");
        echo '<br>rs_rechage_amount=>'.intval($rs_rechage_amount[0]['v']);
        //  活动钻石消耗
        $rs_active_amount = $Model->query("select abs(sum(t.recharge_activity)) as v from bo_shop_order  t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' ;");
        echo '<br>rs_active_amount=>'.intval($rs_active_amount[0]['v']);

        //  新用户消费人数
        $rs_newer_consume_total = $Model->query("select count(1) as v from (select * from bo_shop_order s where s.create_time <  '".$endtime."' and s.create_time >= '".$starttime."') a inner join (select * from bo_user_access_log t   where t.create_time <  '".$endtime."'  GROUP BY t.uid HAVING( count(1)= 1 and t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."')) b on a.uid =b.uid group by a.uid;");
        echo '<br>rs_newer_consume_total=>'.intval($rs_newer_consume_total[0]['v']);

        //主播 消费夺宝钻石数 A
        $rs_consume_exchange_A_total = $Model->query("select sum(t.top_diamond) as v from bo_shop_order t where  t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type =0 and t.spread = 1;");
        //        select ABS(sum(t.top_diamond)) as total1 from bo_shop_order t where  t.create_time <  '1487606400' and t.create_time >= '1484928000' and t.exchange_type =0;

        //主播 消费夺宝钻石数 B
        $rs_consume_exchange_B_total = $Model->query("select sum(t.recharge_activity) as v from bo_shop_order t where  t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type =0 and t.spread = 1;");
//        select ABS(sum(t.recharge_activity)) as total2 from bo_shop_order t where  t.create_time <  '1487606400' and t.create_time >= '1484928000' and t.exchange_type =0;

//       #主播 消费全价钻石数 A
        $rs_consume_full_A_total = $Model->query("select sum(t.top_diamond) as v from bo_shop_order t where  t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type =1 and t.spread = 1;");
//        select ABS(sum(t.top_diamond)) as total3 from bo_shop_order t where  t.create_time <  '1487606400' and t.create_time >= '1484928000' and t.exchange_type =1;

//        #主播 消费全价钻石数 B
        $rs_consume_full_B_total = $Model->query("select sum(t.recharge_activity) as v from bo_shop_order t where  t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type =1 and t.spread = 1 ;");
//        select ABS(sum(t.recharge_activity)) as total4 from bo_shop_order t where  t.create_time <  '1487606400' and t.create_time >= '1484928000' and t.exchange_type =1;

        $Statistics = M ( 'statistics_day') ;
        $data['date'] = $startDate;
        $data['create_time'] = time();
        $data['active_total'] = $rs_active[0]['v'];
        $data['uv'] = $rs_UV[0]['v'];
//        $data['day1retention'] = $rs_day1retention[0]['v'];
        $data['day1retention'] = $rs_day1retentionrate;
        $data['draw_amount'] = $rs_draw_amount[0]['v'];
        if(empty($rs_exchange_amount[0]['v'])){ $data['exchange_amount'] = 0;}else{
            $data['exchange_amount'] = $rs_exchange_amount[0]['v'];
        }
        $data['full_amount'] = $rs_full_amount;
        $data['rechage_amount'] = $rs_rechage_amount[0]['v'];
        $data['active_amount'] = $rs_active_amount[0]['v'];
        $data['newer_consume_total'] = $rs_newer_consume_total[0]['v'];

        $data['anchor_consume_exchange_A']  = empty($rs_consume_exchange_A_total[0]['v']) ? 0 : $rs_consume_exchange_A_total[0]['v'];
        $data['anchor_consume_exchange_B']  = empty($rs_consume_exchange_B_total[0]['v']) ? 0 : $rs_consume_exchange_B_total[0]['v'];
        $data['anchor_consume_full_A']      = empty($rs_consume_full_A_total[0]['v']) ? 0 : $rs_consume_full_A_total[0]['v'];
        $data['anchor_consume_full_B']      = empty($rs_consume_full_B_total[0]['v']) ? 0 : $rs_consume_full_B_total[0]['v'];

        $Statistics->add($data);
    }

    /**
     * 每日统计任务 （逗号分隔多个日期）例如： 20170102,20170103,20170104,20170105,20170121
     * @param $startDates
     */
    public function daysSecdule($startDates){
        $arr_date = explode(',',$startDates);
        foreach ($arr_date as $v){
            $this->daySecdule($v);
            echo  '<br>----------------------------------------------------<br>';
        }
    }

    /**
     * 获取指定日期范围的日报表
     * @param $startDate
     * @param $endDate
     */
    protected function getDaliyReport($startDate,$endDate){
        $Statistics = M ( 'statistics_day') ;
        $rs =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->order('date')
            ->field(array('date'=>"'日期'",'create_time'=>"'创建时间'",'active_total'=>"'激活用户数'",'uv'=>"'访问人数（UV）'",'ev'=>"'兑换人数（EV exchange value）'",'edv'=>"'兑换钻石数（exchange dimand value）'",'day1retention'=>"'次日留存率'",'draw_amount'=>"'抽奖金额'",'exchange_amount'=>"'全价兑换金额'",'full_amount'=>"'总消费金额'",'rechage_amount'=>"'充值钻石消耗'",'active_amount'=>"'活动钻石消耗'",'newer_consume_total'=>"'新用户消费人数'"))
            ->select();
        var_dump($rs);
    }
    /**
     * 运营概况
     * @param $startDate 示例 20170102
     * @param $endDate  示例 20170113
     */
    public function operatingSituation($startDate,$endDate){
        $starttime = strtotime($startDate);
        $endtime = strtotime($endDate)+86400;

        $Statistics = M ( 'statistics_day') ;

        $arr_statistics = array();
        $Model = M();
        // 激活用户数
        $rs_sum_new =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('active_total'); //

        $User = D('api/user') ;
        // 用户注册数
        $rs_sum_register =  $User->getUserCount($starttime,$endtime);

        // 平均次日留存
        $rs_avg_day1retention =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate) ))))->avg('day1retention'); //

        // 老用户活跃
        $rs_old_active = $Model->query("select count(1) as v from (
            select * from bo_user_access_log t where t.create_time < '".$endtime."'  GROUP BY t.uid HAVING( count(1)> 1 and t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."')) tmp ;");
//        select count(1) from (
//            select * from bo_user_access_log t GROUP BY t.uid HAVING( count(1)> 1 and t.create_time <  '1487154298' and t.create_time >= '1455531834')) tmp ;
        // 总活跃
        $rs_sum_active = $Model->query("select count(1) as v from (
            select * from bo_user_access_log t where t.create_time < '".$endtime."' GROUP BY t.uid HAVING(t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."')) tmp ;");


        // 老用户活跃占比 = 老用户活跃/总活跃（区间内活跃人数）

//        $rs_sum_active =  $Statistics->where(array(array('egt',$starttime),array('lt',$endtime)))->sum('uv'); //
        $rs_old_active_rate = $rs_old_active[0]['v'] / $rs_sum_active[0]['v'];
//        echo '<br>rs_old_active_rate=>'.intval($rs_old_active_rate);

        // 抽奖人数
        $rs_draw_total = $Model->query("select count(1) as v from (
            SELECT * from bo_shop_order t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type = 0 GROUP BY t.uid ) tmp;");

        // 抽奖金额
        $rs_sum_draw_amount =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('draw_amount');

        //全价兑换金额
        $rs_sum_exchange_amount =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('exchange_amount');

        //总消费金额
        $rs_sum_full_amount = $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('full_amount');

        //充值钻石消耗
        $rs_sum_rechage_amount = $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('rechage_amount');

        //活动钻石消耗
        $rs_sum_active_amount = $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('active_amount');

        // 全价兑换人数
        $rs_fullprice_total = $Model->query("select count(1) as v from (
            SELECT * from bo_shop_order t where t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."' and t.exchange_type = 1 GROUP BY t.uid ) tmp;");

//        select count(1) from (
//            SELECT * from bo_shop_order t where t.create_time <  '1487154298' and t.create_time >= '1455531834' and t.exchange_type = 1 GROUP BY t.uid ) tmp;

        //  新用户消费人数
        $rs_sum_newer_consume_total =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('newer_consume_total');

        //老用户消费人数
        $rs_old_consume_total = $Model->query("select count(1) as v from ( select * from bo_shop_order s where s.create_time <  '".$endtime."' and s.create_time >= '".$starttime."' GROUP BY s.uid ) a
	inner join ( select * from bo_user_access_log t  where t.create_time < '".$endtime."' GROUP BY t.uid HAVING(  count(1)>1 and t.create_time <  '".$endtime."' ) ) b ON a.uid = b.uid  ;");

//        select count(*) from ( select * from bo_shop_order s where s.create_time <  '1487154298' and s.create_time >= '1455531834') a
//	inner join ( select * from bo_user_access_log t GROUP BY t.uid HAVING( t.create_time <  '1487154298' ) ) b ON a.uid = b.uid  ;

        //消费人数
        $rs_consume_total = $Model->query("select count(1) as v from (
    select * from bo_shop_order s where s.create_time <  '".$endtime."'  and s.create_time >= '".$starttime."' group by s.uid ) tmp;");
//        select count(1) from (
//            select * from bo_shop_order s where s.create_time <  '1487154298' and s.create_time >= '1455531834' group by s.uid
//) tmp;

//        消费率 (消费人数/活跃)
        $rs_consumption_rate =  $rs_consume_total[0]['v'] / $rs_sum_active[0]['v'];
//        ARPU（兑换钻石数/访问人数）

        //访问人数

//        select count(1) from (
//            select DISTINCT t.uid from bo_user_access_log t where    t.create_time <  '1487154298' and t.create_time >= '1455531834')  o ;
//
//
        $rs_sum_consume =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('full_amount');
        $ARPU = $rs_sum_consume / $rs_sum_active[0]['v'];

        //ARPPU（兑换钻石数/消费人数)
        $ARPPU = $rs_sum_consume / $rs_consume_total[0]['v'];

        /***********************************************************
         * "钻石兑换金额(金币兑换钻石）"
         * "兑换人数(金币兑换钻石）"
         * 新用户兑换人数
         * 老用户兑换人数
         * 钻石平均兑换量（钻石兑换金额/兑换人数）
         **********************************************************/
        //调用游戏后台统计服务接口
        //        http://onlinetest.service.busonline.cn/backend/web/index.php?r=running/statisticsuser&beginTime=2017-02-11&endTime=2017-02-15
        $json_data   = getStatisticOperatingData($startDate,$endDate);
        $third_statistic   =  json_decode($json_data);

//        echo '<br>------------- 运营概况 ---------<br> ';
//        echo '<br> 激活             ->'.$rs_sum_new;
//        echo '<br> 平均次日留存     ->'.$rs_avg_day1retention;
//        echo '<br> 活跃             ->'.$rs_sum_active[0]['v'];
//        echo '<br> 老用户活跃       ->'.$rs_old_active[0]['v'];
//        echo '<br> 老用户活跃占比   ->'.$rs_old_active_rate;
//        echo '<br> 抽奖金额         ->'.$rs_sum_draw_amount;
//        echo '<br> 全价兑换金额     ->'.$rs_sum_exchange_amount;
//        echo '<br> 总消费金额       ->'.$rs_sum_full_amount;
//        echo '<br> 充值钻石消耗     ->'.$rs_sum_rechage_amount;
//        echo '<br> 活动钻石消耗     ->'.$rs_sum_active_amount;
//        echo '<br> 抽奖人数         ->'.$rs_draw_total[0]['v'];
//        echo '<br> 全价兑换人数     ->'.$rs_fullprice_total[0]['v'];
//        echo '<br> 新用户消费人数   ->'.$rs_sum_newer_consume_total;
//        echo '<br> 老用户消费人数   ->'.$rs_old_consume_total[0]['v'];
//        echo '<br> 消费人数         ->'.$rs_consume_total[0]['v'];
//        echo '<br> 消费率           ->'.$rs_consumption_rate;
//        echo '<br> ARPU             ->'.$ARPU;
//        echo '<br> ARPPU            ->'.$ARPPU;
//
//
//        echo '<br>------------- 游戏钻石服务接口数据 ---------<br> ';
//        echo '<br> 钻石兑换金额(金币兑换钻石）->'.$chargeGameGold;
//        echo '<br> 兑换人数(金币兑换钻石）->'.$rateUserCount;
//        echo '<br> 新用户兑换人数'.$newUserCount;
//        echo '<br> 老用户兑换人数'.$oldUserCount;
//        echo '<br> 钻石平均兑换量（钻石兑换金额/兑换人数）'.$averageRate;


        $arr_statistics[] = array('sum_new'=>$rs_sum_register,
            'avg_day1retention'=>$rs_avg_day1retention,
            'sum_active'=>$rs_sum_active[0]['v'],
            'old_active'=>$rs_old_active[0]['v'],
            'old_active_rate'=>$rs_old_active_rate,
            'sum_draw_amount'=>$rs_sum_draw_amount,
            'sum_exchange_amount'=>$rs_sum_exchange_amount,
            'sum_full_amount'=>$rs_sum_full_amount,
            'sum_rechage_amount'=>$rs_sum_rechage_amount,
            'sum_active_amount'=>$rs_sum_active_amount,
            'draw_total'=>$rs_draw_total[0]['v'],
            'fullprice_total'=>$rs_fullprice_total[0]['v'],
            'sum_newer_consume_total'=>$rs_sum_newer_consume_total,
            'old_consume_total'=>$rs_old_consume_total[0]['v'],
            'consume_total'=>$rs_consume_total[0]['v'],
            'consumption_rate'=>$rs_consumption_rate,
            'ARPU'=>$ARPU,
            'ARPPU'=>$ARPPU   );
        return $arr_statistics;
    }

    public function anchorBehavior($startDate,$endDate){
        $starttime = strtotime($startDate);
        $endtime = strtotime($endDate)+86400;

        $Statistics = M ( 'statistics_day') ;

        $arr_statistics = array();
        $Model = M();

        // 主播访问人数
        $uv =  $Model->query(" select count(*) as v from (select  u.id from  bo_user u where u.spread = 1 ) a INNER JOIN
                (select t.uid   from bo_user_access_log t where  t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."'  GROUP BY t.uid ) b ON a.id = b.uid ;");

        //
        //# 主播消费人数
        $ucv =  $Model->query("select count(*) as v from (select  u.id from  bo_user u where u.spread = 1 ) a INNER JOIN
                (select t.uid,COUNT(t.id) as count from bo_shop_order t where  t.create_time <  '".$endtime."' and t.create_time >= '".$starttime."'  GROUP BY t.uid ) b ON
           a.id = b.uid ;");

        //消费钻石数
        $consume_exchange_total =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('anchor_consume_exchange_A+anchor_consume_exchange_B');

        $consume_full_total =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('lt',getNextDate($endDate)  ))))->sum('anchor_consume_full_A+anchor_consume_full_B');

        $consume_total = $consume_exchange_total + $consume_full_total;

        //
        //# ARPU （消费钻石数/主播访问人数）
        //
        $ARPU = $consume_total / $uv[0]['v'] ;
        //# ARPPU（消费钻石数/主播消费人数)
        //(total1+total2+total3+total4) / 主播消费人数
        $ARPPU = $consume_total / $ucv[0]['v'] ;

        $arr_statistics[] = array('daterange'=>$startDate.'-'.$endDate,
            'uv'=>$uv[0]['v'],
            'ucv'=>$ucv[0]['v'],
            'consume_total'=>$consume_total,
            'consume_exchange_total'=>$consume_exchange_total,
            'consume_full_total'=>$consume_full_total,
            'ARPU'=>$ARPU,
            'ARPPU'=>$ARPPU);
        
        return $arr_statistics;
    }
}