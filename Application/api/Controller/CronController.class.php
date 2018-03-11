<?php
namespace api\Controller;
use Com\Alidayu\AlidayuClient as Client;
use Com\Alidayu\Request\SmsNumSend;
use Think\Controller;
class CronController extends BaseController
{
    //给手机发送中奖通知 $recNum手机号，$shopName商品标题
    public function sendSms(){
        $phone = I('phone');
        $productName = I('name');
        // session_write_close();
        ignore_user_abort();
        // set_time_limit(30);
        recordLog(time(),'before');

        //延迟
        sleep(30);
        
        $client = new Client();
        $request = new SmsNumSend;
        $title = "LIVE商城";
        $smsParams = array(
            //'name' => $title,
            'product' => $productName
        );
        // 设置请求参数
        $req = $request->setSmsTemplateCode('SMS_47475157')
            ->setRecNum($phone)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName($title)
            ->setSmsType('normal')
            ->setExtend('ext');
        $request = $client->execute($req);

        recordLog(json_encode($request),'sms');
        recordLog(time(),'after');
    }

    //时时彩开奖处理
    //按照开奖时间表进行开奖处理
    public function execLottery (){
        //将p表的数据，进行开奖处理
        $data = D('period')->execLottery();
        if($data){
            echo 'execLottery success ';
        }else{
            echo 'execLottery next time ...';
        }
    }


    /**
     * 虚拟币按月统计
     * 默认统计上个月的数据
     * @param string $month 格式例如  2017-05
     * @param int $force
     */
    public function execGcouponM($month = '',$force = 0){
        //每月初进行统计上月的数据
        // 统计 虚拟币收入 （收入存数为正数 ） 虚拟币消耗（消耗存数为负）

        if(empty($month)){
            $curr_month   = date('Y-m',strtotime(date('Y',NOW_TIME).'-'.(date('m',NOW_TIME)-1).'-01'));
            $pre_month = date('Y-m',strtotime($curr_month.' -1 month'));
        }else{
            $pre_month =  date('Y-m',strtotime('-1 month',strtotime($month)));
            $curr_month = $month ;
        }
        
        $month_start_f = date("Y-m-01",strtotime($pre_month));
        $month_start = strtotime($month_start_f);
//        $month_end = strtotime(date("Y-m-d",strtotime("$month_start_f +1 month -1 day")));
        $month_end = strtotime(date("Y-m-01",strtotime($curr_month)));

        //虚拟币收入
        $item['income_gcoupon'] = M('gcoupon_record')->where(      array('num'=>array('gt',0),'create_time'=> array(array('gt',$month_start),array('lt',$month_end  ) )))->sum('num');

        //虚拟币消耗
        $item['expend_gcoupon'] = M('gcoupon_record')->where(      array('num'=>array('lt',0),'create_time'=> array(array('gt',$month_start),array('lt',$month_end  ) )))->sum('num');

        $item['curr_surplus']  = M('user')->sum('gold_coupon');
        $item['pre_surplus']  = M('statistics_gc_m')->where(array('month'=> str_replace("-","",$pre_month)))->getField('curr_surplus');
        $item['create_time']  = NOW_TIME;

        $item['month']  = str_replace("-","",$curr_month) ;

        $rs = M('statistics_gc_m')->add($item);
        if($rs){
            echo 'execGcouponM success result = '.$rs;
        }else{
            echo 'execGcouponM failed reuslt = '.$rs;
        }
    }
}