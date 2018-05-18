<?php
namespace api\Model;
use Think\Model;

class EventsModel extends Model {


    /**
     * 触发事件，例如：用户注册成功，购买成功等
     *
     * @param [type] $eventType 事件类型:register,...
     * @param [type] $userId    用户id
     * @return void
     */
    public function activate($eventType,$userId){
        switch ($eventType) {
            case 'register':
                # 注册送虚拟币，需后台配置时间与额度
                if($userId){
                    $sp = M('sales_promotion')->where(['status'=>1,'type'=>3])->order('create_time desc')->find();
                    $now = time();
                    if($sp &&  $now >= $sp['begin_time'] && $now <= $sp['end_time']){

                        $map['activity_type'] = 10;
                        $map['create_time'] = array('between',array(intval($sp['begin_time']),$sp['end_time']+1));
                        $total = M('gcoupon_record')->where($map)->sum('num');
                        // $sql = M('gcoupon_record')->getLastSql();
                        if($total==null) $total=0;

                        $items = json_decode($sp['remark'],true);
                        $gold = $items['rules'][0]['gold'];

                        if(($total+$gold) <= $items['total_gold']){
                            if($gold >0){

                                // $goldRecord_rs = D('api/GoldRecord')->register($userId,$createTime,$gold );

                                $rs_gcoupon_record = D('Admin/GcouponRecord')->addRecord($userId,$activity_type=10,$gold);

                                A('api/Wechat')->sendTplMsgRigister($userId,$gold);

                                return $rs_gcoupon_record;
                            }
                        }
                    }
                }
                break;
            
            default:
                # code...
                break;
        }
        return false;
    }
}