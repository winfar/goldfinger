<?php
namespace Admin\Model;
use Think\Model;

/**
 * 金券记录明细
 * Class GcouponRecordModel
 * @package Admin\Model
 */
class GcouponRecordModel extends Model {

    /**
     *
     * @param $uid
     * @param $activity_type
     * @param $num
     * @param $d_recharge
     * @param $d_active
     */
    public function addRecord($uid,$activity_type,$num,$d_recharge,$d_active,$sn){
        $map['uid'] = $uid;
        $map['activity_type'] = $activity_type;
        $map['num'] = $num;
        $map['d_recharge'] = $d_recharge;
        $map['d_active'] = $d_active;
        $map['create_time'] = NOW_TIME;
        $map['sn'] = $sn;

        $data =  $this->data($map)->add();
        return $data;
    }
}
