<?php
namespace api\Model;

use Think\Model;

class PeriodModel extends Model {

	/**
	 * 新增一期商品活动在period表
	 * 包含普通及PK专场（公开，私有房间）
	 */
	public function addShopAction($PERIOD,$max_pNumber ,$shopstock,$ispublic = 1,$curr_pid='' ,$uid=''){ //,$area_type ,$ispublic,$pid,$sid,$pkcid,$uid){

		//普通摸金  $area_type =  1 => 普通   2 => PK专区
//		if($info['status'] == 0){
//			//检查商品状态 ，商品是否有效状态 status=>0 下架状态 status=>1 上架状态
//			return ;
//		}

		$area_type = $PERIOD['iscommon'];

		$sid = $PERIOD['sid'];
		$house_id = $PERIOD['house_id'];
		/***************************************
		 * 开新一期
		 ****************************************/
		if(empty($area_type) || $area_type == 1){
				//判断夺宝期数是否达到或者是否有库存
				if(intval($PERIOD['no'])<=(intval($max_pNumber) && $shopstock>0 )){
					//确认当前商品没有进行中的任务，没有新期数的才开新期
					$rs_count = M('shop_period')->where('state = 0 and iscommon = 1 and sid='.$sid)->count();
					if($rs_count == 0){
//						$PERIOD['iscommon'] = 1;
						//存在进行中的任务，不添加新任务
						$rs_period = M('shop_period')->data($PERIOD)->add();
						
						//库存-1
						$rs_shop = M('Shop')->where('id='.$sid)->setDec('shopstock',1);

//						if($rs_period && $rs_shop){
//							//更新pk房间为最新期号
//							D('HouseManage')->where(array('id'=>$house_id))->setField('periodid',$rs_period);
//						}
					}
				}
				else{
					M('Shop')->where('id=' . $sid)->setField('status', 0);
				}
			}

//		}
		/***************************************
		 * PK专场 公共房间
		 ****************************************/
		elseif ($area_type == 2 && $ispublic == 1){
			//判断该期夺宝是否已筹满  并且有充足的库存
			//判断夺宝期数是否达到或者是否有库存
				if(intval($PERIOD['no'])<=(intval($max_pNumber) && $shopstock>0 )){
					//确认当前商品没有进行中的任务，没有新期数的才开新期
				$rs_public = $this->getShopAct($sid,'',2);
				if($rs_public == 0){
//					$PERIOD['iscommon'] = 2; // 2 = pk房间
					$rs_period = M('shop_period')->data($PERIOD)->add();
					//库存-1
					$rs_shop = M('Shop')->where('id='.$sid)->setDec('shopstock',1);
					if($rs_period && $rs_shop){
						//更新pk房间为最新期号
						D('HouseManage')->where(array('id'=>$house_id))->setField('periodid',$rs_period);
					}
				}
			}
//			else{
//				M('Pkconfig')->where('id=' . $sid)->setField('status', 0);
//			}
		}
		/***************************************
		 * PK专场 私有房间
		 ****************************************/
		elseif ($area_type == 2 && $ispublic == 0 ){
			//判断夺宝期数是否达到或者是否有库存
			if(intval($PERIOD['no'])<=(intval($max_pNumber) && $shopstock>0 )){

				//确认当前商品没有进行中的任务，没有新期数的才开新期
				$rs_private = $this->getShopAct($sid,$curr_pid,2,0,$uid);
				if($rs_private == 0){
//					$PERIOD['iscommon'] = 2; // 2 = pk房间
					$rs_period = M('shop_period')->data($PERIOD)->add();
					//库存-1
					$pkcid = M('HouseManage')->where(array('id'=>$house_id))->getField('pksetid');
					$rs_pkconfig = M('Pkconfig')->where('id='.$pkcid)->setDec('inventory',1);
					if($rs_period && $rs_pkconfig){
						//更新pk房间为最新期号
						D('HouseManage')->where(array('id'=>$house_id))->setField('periodid',$rs_period);
					}
				}
			}
//			else{
//				M('Pkconfig')->where('id=' . $sid)->setField('status', 0);
//			}
		}

	}

	/**
	 * 获取当前商品所在区域的有效活动数
	 * @param $shopid
	 * @param $area  区域类型  默认为普通摸金 1=>普通摸金  2=>pk专区
	 * @param $ispublic 1=>公开 0=>不公开
	 * @return  pid 列表
	 */
	public function getShopAct($shopid,$pid='',$area=1,$ispublic=1,$uid=''){
		if($area == 1 ){
			//普通摸金
			//确认当前商品没有进行中的任务，没有新期数的才开新期
			$rs_count = M('shop_period')->where('state = 0 and sid='.$shopid)->count();
			return $rs_count;
		}elseif ($area == 2 ){
			//获取场次信息
//			$pkconfig_ids = M('')->table('__SHOP_PERIOD__ p , __HOUSE_MANAGE__ m , __PKCONFIG__ c')->where(array('p.id'=>$pid))->where('p.house_id = m.id AND m.pksetid = c.id ')->getField('c.id',true);
			$peop_total = M()->table(' __SHOP_PERIOD__ p , __HOUSE_MANAGE__ m , __PKCONFIG__ c')->where(array('p.id'=>$pid))->where('p.house_id = m.id AND m.pksetid = c.id ')->getField('c.peoplenum');
			$pkconfig_ids = M('Pkconfig')->where(array('peoplenum'=>$peop_total))->getField('id',true);

			if($ispublic == 1){
//				PK专区公开
				$count_public = M()->table('__SHOP_PERIOD__ p , __HOUSE_MANAGE__ m')->where( array('p.state'=>0,'p.sid'=> $shopid ,'p.iscommon'=>2, 'm.ispublic'=> 0,'m.pksetid'=>array('in',$pkconfig_ids),'m.isresolving'=>0))->where('p.house_id = m.id')->count();
				return $count_public;
//				select p.id,p.state,p.iscommon,m.ispublic,p.house_id,m.no,m.periodid,p.sid from hx_shop_period p , hx_house_manage m where p.house_id = m.id and p.state = 0 and p.sid='';
			}else{
				//PK专区私密
				$count_private = M()->table('__SHOP_PERIOD__ p , __HOUSE_MANAGE__ m')->where( array('p.state'=>0,'p.sid'=> $shopid ,'p.iscommon'=>2, 'm.ispublic'=> 1,'m.pksetid'=>array('in',$pkconfig_ids),'m.isresolving'=>0,'m.uid'=>$uid))->where('p.house_id = m.id')->count();
				return $count_private;
			}
		}
	}
	/**
	 * 最新一期详情
	 * @param  array  $param [description]
	 * @return [type]        [description]
	 */
	public function periodInfo($param = array())
	{
		$map = array();
		$sql = "select period.state,period.id as pid,period.kaijang_num,period.kaijiang_ssc,period.kaijiang_issue,period.no,period.kaijang_time,period.number as total_number,(select (case when sum(buy_gold) is NUll then 0 else ABS(sum(buy_gold)) end ) from bo_shop_order where pid = period.id) as total_buy_gold from bo_shop_period period  where 1 = 1";
		//商品
		if ( isset($param['sid'])) {
			$sql .=" and period.sid=".$param['sid'];
		}
		//商品
		if ( isset($param['pid'])) {
			$sql .=" and period.id=".$param['pid'];
		}
		//状态
		if ( isset($param['state'])) {
			$sql .=" and period.state=".$param['state'];
		}
		$sql .= " order by period.no desc";
		$users = $this->query($sql, false);
		
		$list = array();

		
		if (!empty($users)) {
			$list['user_number'] = 0;
			$time = C('KAIJANG_TIME')*60;
			$list = $users[0];
			recordLog($list['pid'].'@@@'.$list['kaijang_time'],'获取周期');
			// if ($list['total_buy_gold']>1000) {
			// 	$list['total_buy_gold'] = ($list['total_buy_gold']/1000).'克';
			// } else {
			// 	$list['total_buy_gold'] = $list['total_buy_gold'].'毫克';
			// }
			// 开奖时间-一分钟>当前时间时 为 揭晓中
			if ($list['kaijang_time']<=time()-$time and $list['state']==1) {
				$list['state'] = 5;
			}
			if ($list['kaijang_time']<=time() and $list['state']==0) {
				$list['state'] = 1;
			}
			$list['total_buy_gold'] = ($list['total_buy_gold']/1000).'克';
			$list['kaijang_date'] = $list['kaijang_time'].'000';
			$list['down_date'] = date("Y-m-d H:i:s", $list['kaijang_time']-$time);
			$list['ssc_date'] = $list['kaijang_time']+$time.'000';
			$list['down_time'] = $list['kaijang_time']-time();//倒计时结束时间
			$list['ssc_time'] = $list['kaijang_time']+$time-time();//倒计时结束时间
			$list['now_time'] = date("Y-m-d H:i:s", NOW_TIME); 
			$list['last_no'] = M('shop_period')->where('state=0')->order('id desc')->getField('id');
			if (isset($param['uid'])) {
				$uid = $param['uid'];
				$user_number = M('shop_record')->where('uid='.$uid.' and pid='.$list['pid'])->sum('number'); 
				$list['user_number'] =  empty($user_number) ? 0 : $user_number;
			}
		}
		return $list;
	}
	/**
	 * 参与详情
	 *
	 * @author liuwei
	 * @param  array  $param [description]
	 * @return [type]        [description]
	 */
	public function periodInfoList($param = array())
	{
		$data = array();
		if (isset($param['uid']) and isset($param['pid']) and isset($param['sid'])) {
			$uid = $param['uid'];//用户id
			$pid = $param['pid'];//期号
			$sid = $param['sid'];//商品名称
			$where = array();
			$where['uid'] = $uid;
			$where['pid'] = $pid;
			$count = M('shop_order')->where($where)->count();//是否参与这一期
			$win_id = M('shop_period')->where("id=".$pid." and sid=".$sid)->getField('uid');//获奖者id
			$data['user'] = array();
			$data['win'] = array();
			//用户详情
			if ($count > 0 and $win_id != $uid) {//已经参与并且中奖者不是该用户
				$data['user']['uid'] = $uid;
				$data['user']['nickname'] = get_user_name($uid);
	            $data['user']["img"] = get_user_pic_passport($uid);
	            $total_number = M('shop_record')->where('pid='.$pid)->sum('number');//总参与人数
                $user_total_number = M('shop_record')->where("uid=" . $uid . " and pid=" . $pid)->sum('number');//我参与的次数
                $data['user']['rate'] = number_format(($user_total_number/$total_number)*100, 2, '.', '');//中奖率
                $data['user']['number'] = empty($user_total_number) ? 0 : $user_total_number;//参与次数
			}
			//中奖者详情
			if ($win_id > 0 ){
				$data['win']['uid'] = $win_id;
				$data['win']['nickname'] = get_user_name($win_id);
	            $data['win']["img"] = get_user_pic_passport($win_id);
                $user_total_number = M('shop_record')->where("uid=" . $win_id . " and pid=" . $pid)->sum('number');//我参与的次数
                $data['win']['number'] = empty($user_total_number) ? 0 : $user_total_number;//参与次数.
            }
			$neq_uid = $win_id != $uid ? $uid : $win_id;
			$order_where = array();
			$order_where['pid'] = $pid;
			$order_where['uid'] = array("neq", $neq_uid);
			$order_data = array();
			$order_list = M('shop_order')->where($order_where)->field('id,uid,create_time,number,order_id')->select();
			if (!empty($order_list)) {
				foreach ($order_list as $key => $value) {
					$order_data[] = $value;
					$order_data[$key]['nickname'] = get_user_name($value['uid']);
	            	$order_data[$key]["img"] = get_user_pic_passport($value['uid']);
	            	$order_data[$key]['create_date'] = date('m.d H:i:s', $value['create_time']);
				}
			}
			$data['list'] = $order_data;
		}
		return $data;
	}
	/**
	 * 开奖处理
	 */
	public function execLottery(){
		//获取所有超过开奖时间的 pid ;

		//开奖时间
		$now = time() ;// 开奖前提前一分钟结束购买
		$rs_data = M('shop_period')
			->table('__SHOP__ shop,__SHOP_PERIOD__ period')
			// ->lock(true)
			->field('shop.id as sid,shop.periodnumber,shop.shopstock,period.id as pid ,period.number,period.kaijang_num, period.state,period.kj_count')
			->where('shop.id=period.sid and period.state < 2  and kaijang_time <= '.$now)
			->order('period.state')
			->find();


		//如果没有待开奖的任务则不做处理
		if(empty($rs_data)){
			echo '没有待开奖的任务！';
			return false;

		}

		recordLog(json_encode($rs_data),'execLottery');

		// 将指定期置为待开奖
		if($rs_data['state'] == '0'){
			M('shop_period')->where(array('id'=>$rs_data['pid']))->setField('state','1');
		}

		// 如果无人购买则流拍
		if($rs_data['number'] == '0' ){
			M('shop_period')->where(array('id' => $rs_data['pid']))->setField('state','4');
		}

		$arr_ssc = getNextSsc();

		//检查是否有最新一期，并生成新的一期
		$pid_progress = M('shop_period')->where(array('state'=> 0))->getField('id');

		if(empty($pid_progress)){

			$period['sid'] = $rs_data['sid'];
			$period['create_time'] = NOW_TIME;
			$period['kaijang_time'] = $arr_ssc['lottery_time']; //设置开奖时间
			$period['kaijiang_issue'] = intval($arr_ssc['lottery_issue']); //设置当前时时彩期数

			$period['state'] = 0;
			//$period['no']=$info['no']+1;
			$max_no = M('shop_period')->where('sid=' . $rs_data['sid'])->max('no');
			$period['no'] = $max_no + 1;

//			if ($info['status'] > 0 && $info['shopstock'] > 0 && $info['periodnumber'] > ($period['no'] - 100000)) {
			$this->addShopAction($period, (intval($rs_data['periodnumber']) + 100000), $rs_data['shopstock'], 1, $rs_data['pid']);
//			}
		}

		$kj_data = $this->getKaijiang();
		$jdata = json_decode($kj_data,true);
		$openTime = $jdata['openTime'];
		$winNumber = $jdata['0']['WinNumber'];
		$kaijang_issue = $jdata[0]['Issue'];
		//如果是获取到开奖信息，则更新p表的对应值
		if($arr_ssc['lottery_issue'] == $kaijang_issue+1 ){
			$rs_pssc = M('shop_period')->where(array('kaijiang_issue'=> $kaijang_issue))->getField('kaijiang_ssc');
			if(empty($rs_pssc['kaijiang_ssc'])){
				$rs_period_data = array('kaijiang_ssc' => $winNumber );
				M('shop_period')->where(array('kaijiang_issue'=> $kaijang_issue))->save($rs_period_data);
			}
		}else{
			$winNumber = 0; 
		}

		M('shop_period')->where(array('id' => $rs_data['pid']))->setInc('kj_count');
		if($rs_data['kj_count'] > 3 && empty($rs_data['kaijang_num']) ){
			//开奖次数超过3次以上仍然失败
			// 系统自动开奖
			$winNumber = mt_rand(11111, 99999);
		}
		echo '<br>[开奖号='.$winNumber.' 开奖次数='.$rs_data['number'] .' 	期号='.$arr_ssc['lottery_issue'].'] 正在开奖...<br>';
		if($winNumber > 0 && $rs_data['number'] > 0 ){
			//进行计算 并开奖 (调用实时彩服务3次) ，随后再进行本地自由开奖
			$result = $this->lottery($rs_data['pid'],$winNumber);
			if(!$result){
				return false;
			}
		}
		return true;
	}

	/**
	 *  对指定的期 进行开奖操作
	 * @param $pid
	 * @param $winNumber
	 */
	public function lottery($pid,$winNumber){
//		$rs_data = $this->where(array('id'=>$pid,'state'=>array('lt','2')))->getField('kaijang_time,number'); //获取待开奖的期
		$Period = M('shop_period');
		$rs_data = $Period->where(array('id'=>$pid))->field('kaijang_time,number')->find(); //获取待开奖的期

		//检查开奖时间小于当前时间
		$kaijang_time = $rs_data['kaijang_time'];
		if($kaijang_time > time()){
			return false;
		}else{
			M('shop_period')->where(array('id'=>$pid))->setField('state','1');  //将指定期置为待开奖
		}

		$number = $rs_data['number'];

		//开奖计算中奖用户
		$_winNumber = $winNumber % $number ;

		$_winNumber += 100001 ;
		$winner = M('shop_record')->where('pid=' . $pid . ' and FIND_IN_SET("' . $_winNumber . '",num)')->getField('uid');

		$gold_price = getGoldprice();
		
		try{
			M()->startTrans();

			//保存用户中的黄金数  通过期号获取相应的购买记录总数 
			$amount_gold = M('shop_order')->where(array('pid'=>$pid))->sum('buy_gold');
			$inc_gold = $amount_gold ;
			$rs_user = M('user')->where('id=' . $winner)->setInc('gold_balance',$inc_gold);

			//保存中奖用户信息
			$rs_period_data = array('state' => '2' ,'kaijang_num' => $_winNumber, 'uid' => $winner, 'end_time' => $this->getMillisecond(),'gold_price'=> $gold_price);
			$rs_period = M('shop_period')->where('id=' . $pid)->save($rs_period_data);
			//中奖消息
			$rs_msg = D('api/Message')->addUserMessage($winner,101,$pid);
		
			if($rs_user && $rs_period && $rs_msg){
				M()->commit();
				echo '开奖成功！';
			}
			else{
				M()->rollback();
				echo '开奖失败[rs_user='.$rs_user.' rs_period='.$rs_period.' rs_msg='.$rs_msg.']';
				return false;
			}
		}
		catch(Excetion $e){
			M()->rollback();
			recordLog(json_encode($e),'中奖异常');
			echo '中奖异常['.json_encode($e).']';
			return false;
		}
	}

	public function getMillisecond()
	{
		list($t1, $t2) = explode(' ', microtime());
		return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
	}

	public function getKaijiang(){
		$data = get ("http://chart.cp.360.cn/zst/qkj/?lotId=255401");
//		$jdata = json_decode($data);
//		$openTime = $jdata->openTime;
//		var_dump($data);
		return $data;
	}
}