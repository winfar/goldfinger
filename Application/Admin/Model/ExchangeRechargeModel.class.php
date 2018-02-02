<?php
namespace Admin\Model;
use Think\Model;

class ExchangeRechargeModel extends Model {

    protected $_validate = array(
        // array('title', 'require', '名称不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
    	// array('meta_title', '1,50', '网页标题不能超过50个字符', self::VALUE_VALIDATE , 'length', self::MODEL_BOTH),
    	// array('keywords', '1,255', '网页关键字不能超过255个字符', self::VALUE_VALIDATE , 'length', self::MODEL_BOTH),
    	// array('description', '1,255', '网页描述不能超过255个字符', self::VALUE_VALIDATE , 'length', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
		array('status', '1', self::MODEL_INSERT),
    );

    public function info($id, $field = true){
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['title'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    /**
     * 更新
     * @return boolean 更新状态
     */
    public function update(){
        $data = $this->create();
        if(!$data){ //数据对象创建错误
            return false;
        }
        /* 添加或更新数据 */
        if(empty($data['id'])){
            $res = $this->add($data);
        }else{
            $res = $this->save($data);
        }
        return $res;
    }
}