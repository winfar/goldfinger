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
                    if($sp &&  $now >= $sp['begin_time'] && $now < $sp['end_time']){
                        $items = json_decode($sp['remark'],true);
                        if($items[0]['gold']>0){

                            // $goldRecord_rs = D('api/GoldRecord')->register($userId,$createTime,$items[0]['gold']);

                            $rs_gcoupon_record = D('Admin/GcouponRecord')->addRecord($userId,$activity_type=10,$items[0]['gold']);

                            A('api/Wechat')->sendTplMsgRigister($userId,$items[0]['gold']);

                            return $goldRecord_rs;
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