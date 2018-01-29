<?php
namespace Common\Model;

use Think\Model;

class RedEnvelopeModel extends Model
{
	public function addRedEnvelopeByUid($redEnvelopeId, $uid)
	{
		if ( empty($redEnvelopeId) || empty($uid) ) {
			return false;
		}
		
		$map['id'] = $redEnvelopeId;
		$redEnvelope = M('RedEnvelope')->where($map)->find();
		
		if ( $redEnvelopeId === false ) {
			return false;
		}

		$count = getRedEnvelopeCountById($redEnvelopeId);

		if($count>=$redEnvelope['quantity']){
			return false;
		}

		$map['id'] = $uid;
		$user = M('User')->where($map)->find();
		
		if ( $user === false) {
			return false;
		}
		
		$data['red_envelope_id'] = $redEnvelopeId;
		$data['uid'] = $uid;
		$data['status']=0;
		$data['create_time'] = time();
		
		$model = M('red_envelope_record');
		
		//$model->startTrans();
		
		$rs_rer = $model->add($data);

		if (count($rs_rer)) {
			return true;
			// $rs_re = M('red_envelope')->where(array('id',$redEnvelopeId))->setDec('quantity');
			// if (count($rs_re)) {
			// 	$model->commit();
			// 	return true;
			// }
			// else {
			// 	$model->rollback();
			// 	return false;
			// }
		}
		else {
			//$model->rollback();
			return false;
		}
	}

    public function getRedEnvelopeByUid($uid,$isuse=0)//默认未使用
	{
		if ( empty($uid) ) {
			return false;
		}

        if($isuse>0){
            $sql = 'isuse>0';    
        }
        else{
		    $sql = 'isuse=0';
        }

		$redEnvelopeList = M('RedEnvelope')
        ->table('__RED_ENVELOPE__ re,__RED_ENVELOPE_RECORD__ rer')
        ->field('re.id,re.`name`,re.category,re.category_values,re.remark as range_desc,re.specialarea,re.total_amount,re.min_amount,re.amount,re.begin_time,re.end_time,rer.uid,IF(UNIX_TIMESTAMP(NOW())>re.end_time,2,rer.`status`) as isuse,rer.create_time')
        ->where('re.id=rer.red_envelope_id AND re.`status`>0 AND re.quantity>0 and rer.uid='.$uid)
        ->having($sql)
        ->order('rer.create_time DESC')
        ->select();

		foreach ($redEnvelopeList as &$v){
			$v['id'] = intval($v['id']);
			$v['category'] = intval($v['category']);
//			$v['total_amount'] =  number_format($v['total_amount'],2,'.','') ;
//			$v['min_amount'] = number_format($v['min_amount'],2,'.','') ;
//			$v['amount'] = number_format($v['amount'],2,'.','') ;
			$v['specialarea'] = intval($v['specialarea']);
			$v['begin_time'] = intval($v['begin_time']);
			$v['end_time'] = intval($v['end_time ']);
			$v['uid'] = intval($v['uid']);
			$v['isuse'] = intval($v['isuse']);
			$v['create_time'] = intval($v['create_time']);
		}
		
		return $redEnvelopeList;
	}

	/**
	 * 红包有效性检查
	 * 现阶段仅支持单个红包
	 * @param $red_packets
	 * @return bool
	 */
	public function checkRedEnvelope($uid,$red_packets)
	{
		$flag = false;
		//逗号分隔红包id
		//TODO 仅支持单个红包检查红包有效性 
		if (!is_numeric($red_packets) ) {
			//单个id
			return $flag;
		}


		//判断分隔数 ,逗号砍头去尾 
//		$_count  = substr_count($red_packets,',');
//		$_count++;
//
//		//逗号分隔红包id
		$map['uid'] = $uid;
		$map['id'] = array('in',$red_packets);
		$Model = D('RedEnvelopeRecord');
		$count = $Model->where($map)->count();

//		if( $count  == $_count ){
		if( $count  == 1 ){
			$flag = true;
		}
		return $flag;
	}

	/**
	 * 查询指定红包扣减的值
	 * @param $uid
	 * @param $red_packets 红包ID
	 */
	public function getReduceByPacketID($uid,$red_packetID){
		$redEnvelopeList = $this->getRedEnvelopeByUid($uid);
		foreach ($redEnvelopeList as $k => $v ){
			if($v['id'] == $red_packetID){
				return $v['amount'] ;
			}
		}
		return 0;
	}
}

