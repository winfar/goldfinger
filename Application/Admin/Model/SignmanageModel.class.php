<?php
/**
 * Author: zhangkang
 * Date: 2016/9/819:22
 * Description:
 */

namespace Admin\Model;

use Think\Model;

class SignmanageModel extends  Model
{
    /**
     * 获取配置的状态（启用，禁用）
     * @param string $name
     * @return mixed
     */
    public function getStatus($name = ''){
        $status =  M('SignConfig')->where(array('name'=>$name))->field('status,begintime,endtime')->find();
        if($status){
            return $status;
        }
    }
}