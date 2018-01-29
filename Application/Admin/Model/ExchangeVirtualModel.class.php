<?php
namespace Admin\Model;
use Think\Model;

class ExchangeVirtualModel extends Model {
    protected $_validate = array(
        array('bid', '', '商品品牌已经存在', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),
    );
}