<?php
namespace api\Model;

use Think\Model;

class HouseManageModel extends Model{

	/**
	 * 是否为有效房间
	 * @param $houseid
	 * @return bool
	 */
	public function isValidRoom($houseid){
		$map['id']  = $houseid;
		$rs = $this->field('id,shopid,isresolving')->where($map)->find();
		if($rs ){
			if($rs['isresolving'] == 1){
				return false;
			}
			return D('Shop')->isShopValid($rs['shopid'] );
		}
	}

	/**
	 * 指定商品能够创建房间
	 * @param $uid
	 * @param $shopid
	 * @return mixed
	 */
	public function canCreateRoom($uid,$shopid,$pksetid,$private=1){
		$map['uid'] = $uid;
		$map['shopid'] = $shopid;
		$map['isresolving'] = '0';
		$map['pksetid'] = $pksetid;
		
		$count = $this->where($map)->count();
		if($count){
			return false;
		}
		return true;
	}


//* @param int  $room_type PK专区 是否为公开房间 0 => 所有房间 1 =>公共房间 （默认） 2=>私有房间
//$valid_status=0  0=> 不区分房间商品及期号的状态类型 1=> 有效房间 2=> 无效房间
	public function getRoomList($number,$room_type =  0){
		$map['m.isresolving']	= 0;
		if($number > 0){
			$map['c.peoplenum']		= $number;
		}

		if($number){
			$data = $this->alias('m')->join('LEFT JOIN __PKCONFIG__ c ON m.pksetid=c.id')->where($map)->field('m.id as houseid,m.ispublic,m.shopid')->select();
		}

		if($room_type == 2 ){
			//获取所有私有有效商品id
			$private_shops = D('shop')->getShopList(2,2,null,$number);
			//获取有效的pkconfig的id
			foreach ($data as $k => $v){
				if( $v['ispublic'] == 1){
					if( !in_array($v['shopid'], $private_shops) ) {
						//私密房间
						unset($data[$k]);
					}
				} else{
					unset($data[$k]);
				}
			}
		}

		if($room_type == 1 ){
			//获取所有公共有效商品id
			$public_shops = D('shop')->getShopList(2,1,null,$number);
			//获取有效的pkconfig的id
			foreach ($data as $k => $v){
				if( $v['ispublic'] == 0 ){
					if(!in_array($v['shopid'], $public_shops)){
						//公共房间
						unset($data[$k]);
					}
				}else{
					unset($data[$k]);
				}
			}
		}
		$data =		array_column($data,'houseid');
//		$arr = array_values($data); //重建索引
		return $data ;
	}

	public function getPKRoomList($number){
		$private_room = $this->getRoomList($number,2);
		$public_room = $this->getRoomList($number,1);

		$data = array_merge($private_room,$public_room);
		return $data;
	}

	public function getRoomByNo($room_no=0,$room_type){
		$map['isresolving']= 0;
  		$map['no'] = $room_no ;
		$this->where($map)->field('id')->select();
	}
}
