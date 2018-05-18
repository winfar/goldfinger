<?php
namespace Admin\Model;
use Think\Model;

/**
 * 虚拟币记录明细
 * Class GcouponRecordModel
 * @package Admin\Model
 */
class GcouponRecordModel extends Model {

    /**
     * 新增虚拟币记录
     *
     * @param [type] $uid
     * @param [type] $activity_type 0:充值,1:大转盘,2:牛气冲天,3:系统赠送,10:注册,11:大转盘消费
     * @param [type] $num 虚拟币数量
     * @param integer $d_recharge
     * @param integer $d_active
     * @param integer $sn
     * @return void
     */
    public function addRecord($uid,$activity_type,$num,$d_recharge=0,$d_active=0,$sn=null){
        $map['uid'] = $uid;
        $map['activity_type'] = $activity_type;
        $map['num'] = $num;
        $map['d_recharge'] = $d_recharge;
        $map['d_active'] = $d_active;
        $map['create_time'] = NOW_TIME;
        $map['sn'] = $sn;

        $this->startTrans();

        if(abs($num)>0){
            $data =  $this->add($map);
            $sql = $this->getLastSql();
            $rsGoldCoupon = M('User')->where('id=' . $uid)->setInc('gold_coupon', $num);
            recordLog($data,'添加虚拟币明细结果');
        }

        if($data && $rsGoldCoupon){
            $this->commit();
            return $data;
        }
        else{
            $this->rollback();
            return false;
        }
    }
}
