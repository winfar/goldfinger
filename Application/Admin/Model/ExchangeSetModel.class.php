<?php
namespace Admin\Model;
use Think\Model;

/**
 * 钻石兑换金币
 * @author:liuwei
 */
class ExchangeSetModel extends Model {
	/**
	 * 详情
	 * @param  [type] $type 类型
	 * @return array
	 */
	public function info($type)
	{
		$info = $this->where(['type'=>$type])->find();
		return $info;
	}

	/**
	 * 编辑
	 * @param  [type] $type 类型
	 * @return  false 或者 array
	 */
	public function update($type){
		//新增数据集合
		$list = $_POST;
		$list['type'] = $type;
		//记录是否存在
        $num = $this->where(['type'=>$type])->count();
        if ($num==0) {
        	$list['create_time'] = time();
        	$list['edit_time'] = time();
        } else {
        	$list['edit_time'] = time();
        }
        $data = $this->create($list);
        if(empty($data)){
            return false;
        }
        if ($num==0) {//新增
            $id = $this->add();
            if(!$id){
                $this->error = '兑换设置出错！';
                return false;
            }
        } else {//编辑
            $status = $this->save();
            if(false === $status){
                $this->error = '兑换设置出错！';
                return false;
            }
        }
        return $data;
    }

}