<?php
namespace Admin\Model;

use Think\Model;
use Think\Storage;

class ShopTestOrderModel extends Model
{
    public function update()
    {
        $data = $this->create();
        $info = $this->where(['order_no'=>$data['order_no']])->field('id')->find();//order_no是否存在
        if (empty($info)) {
            if (!empty($data['pay_time'])) {
                $data['pay_time'] = strtotime($data['pay_time']);
            }

            $data['create_time'] = time();
            if ( empty($data['id']) ) {
                $res = $this->add($data);
            } else {
                $res = $this->save($data);
            }
        } else {
            $res = false;
        }
        return $res;
    }

}
