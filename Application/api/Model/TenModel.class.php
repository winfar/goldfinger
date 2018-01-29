<?php
namespace api\Model;
use Think\Model;

class TenModel extends Model{
	protected $connection = 'PASSPORT';
	
	
	public function ten1(){
		$list = $this->field(true)->order('sort')->select();
		var_dump($list);exit;
	
        return $list;
    }
}
