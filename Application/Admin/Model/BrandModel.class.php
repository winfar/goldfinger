<?php
namespace Admin\Model;
use Think\Model;

class BrandModel extends Model{

    protected $_validate = array(
        array('title', 'require', '名称不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
        array('title', '', '渠道名称已经存在', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),
        array('sort', 'number', '单位为数字', self::VALUE_VALIDATE , 'regex', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
		array('status', '1', self::MODEL_BOTH),
    );


   public function info($id, $field = true){
        $map = array();
        if(is_numeric($id)){
            $map['id'] = $id;
        } else {
            $map['title'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    public function update(){
        $data = $this->create();
        if(!$data){
            return false;
        }
        if(empty($data['id'])){
            $res = $this->add($data);
        }else{
            $res = $this->save($data);
        }
        return $res;
    }

    /**
     * 提供获取有效状态的品牌列表
     */
    public function getBrand( $status = 1 ){
        return $this->where('status >= '.$status)->field('id,title')->select();
    }
}
