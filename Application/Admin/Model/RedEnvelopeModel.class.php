<?php
namespace Admin\Model;

use Think\Model;
use Think\Storage;

class RedEnvelopeModel extends Model
{

    protected $_validate = array(
        array('name', 'require', '名称不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('min_amount', 'require', '金额下限不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('amount', 'require', '减少金额不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('total_amount', 'require', '资金池不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH)
    );

    protected $_auto = array(
        array('create_time', 'time', self::MODEL_INSERT, 'function'),
        array('begin_time', 'strtotime', self::MODEL_BOTH, 'function'),
        array('end_time', 'strtotime', self::MODEL_BOTH, 'function'),
    );
    	
    public function update()
    {
        $model = D('RedEnvelope');
        $data = $model->create();
        if (!empty($data['category_values'])) {
            $data['category_values'] = implode(',', $data['category_values']);
        }
        if ( !$data ) {
            exit($this->getError());
            //return false;
        }

        if ( empty($data['id']) ) {
            $res = $model->add($data);
        } else {
            $res = $model->save($data);
        }
        return $res;
    }

}
