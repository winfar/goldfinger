<?php
namespace api\Model;
use Think\Model;

class ExchangeConfigModel extends Model {


    /**
     * 获取配置的状态（启用，禁用）
     * @param string $name
     * @return mixed
     */
    public function getStatus($name = ''){
        $status =  $this->where(array('name'=>$name))->field('status')->find();
        if($status){
            return $status['status'];
        }
    }
}