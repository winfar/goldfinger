<?php
namespace api\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;

class TemporaryOrderModel extends Model
{

    //获取详情
    public function info($id, $field = true)
    {
        $map = array();
        if ( is_numeric($id) ) {
            $map['id'] = $id;
        }
        $info = $this->field($field)->where($map)->find();
        return $info;
    }

    /**
     * 设置支付平台回调状态
     * @param $order_id
     * @param int $status
     * @return bool
     */
    public function updateCallbackStatus($order_id , $status = 1){
        $map['order_id'] = $order_id;
        $rd = $this->where($map)->save(array('is_callback'=>$status,'callback_time'=>time()));
        return $rd;
    }
}
