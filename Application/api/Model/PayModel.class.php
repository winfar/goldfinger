<?php
namespace api\Model;

use Think\Model;

class PayModel extends Model
{

	protected $md5_key = "busonline";
	protected $appid = "busonline";
//	protected	$url = 'http://139.129.21.58:8088/wapapi.php?s='; // 自定义钻石服务地址 http://139.129.21.58:8088/wapapi.php?s=/api/getcoins

	protected $onlinetest_url = 'http://apigametest.busonline.cn:16789';
	protected $test_url = 'http://120.92.76.202:16790';
	protected $prod_url = 'http://apigame.busonline.com';
	/**
	 * 钻石支付处理
	 * @param $pid
	 * @param $sid
	 * @param $price 购买次数
	 * @param $uid 用户id
	 * @param $type 0 = 夺宝购买 ，1= 商品兑换
	 * @param int $gold 消费钻石数
	 * @param int $md5_gp 锁定的金价订单数据MD5
	 */
	public function pay($pid, $sid = 0, $price, $uid, $type, $gold_do,$md5_gp = '')
	{

		/*********************************************************
		 * 1.相关参数数据验证
		 * 2.生成购买订单
		 * 3.调用支付接口进行支付
		 * 4.完成支付处理流程
		 *
		 **********************************************************/
		//调用支付接口进行支付
		try {

			//检查商品是否已经下架
			 $rs_status = M('shop')->where('id='.$sid)->getField('status');
			if($rs_status == '0'){
				returnJson('', 410, '该商品已下架');
			}
			//检查该商品是否已经结束
			$rs_state = M('shop_period')->where('id='.$pid)->getField('state');
			if(intval($rs_state) > 0){
				returnJson('', 420, '本期已结束，请参与最新一期');
			}

			//金价MD5校验，时间校验
			$map_gold_verify['uid'] = $uid;
			$map_gold_verify['md5'] = $md5_gp;

			$rs_gold_verify = M('shop_gold_record')->where($map_gold_verify)->find();
			$gr_id = 0;
			if($rs_gold_verify){
				$gold_price = $rs_gold_verify['gold_price']; //获取锁定时的金价
				$gr_id = $rs_gold_verify['id'];
				$create_time = $rs_gold_verify['create_time'];
				if($create_time <  ( NOW_TIME - 60 )*1000 ){
					returnJson('', 402, '金价数据已超时');
				}
			}else{
				returnJson('', 401, '金价数据校验失败');
			}

			$rs_channel = M('channel')
				->table('__CHANNEL__ channel,__USER__ user')
				->field('channel.starting_number,channel.premium')
				->where('channel.id=user.channelid and user.id = '.$uid)
				->find();
			if($rs_channel){
				$_starting_number = $rs_channel['starting_number']; //购买的黄金毫克数
				$_premium = $rs_channel['premium'];
			}

			//购买的总金额校验
			// 实时金价*参与黄金毫克数*人民币和金券的兑换比例*（1+溢价百分比）
			$_amount_gold =ceil ($gold_price / 10000 * $_starting_number * (100+$_premium) * $price)  ;
			if($gold_do != $_amount_gold){
				returnJson('', 403, '金价'.C("WEB_CURRENCY").'金额校验失败');
			}


			//TODO init param
			$shopname = '';
			$mch_tradeno = 'shopno'.think_md5(microtime(), $uid);


			//TODO 写入临时表记录 orderno、pid、uid、shopname、price、gold、time 增加钻石的A，B信息
			$dataArr = array(
				'order_id' => $mch_tradeno,
				'pid' => $pid,
				'uid' => $uid,
				'shopname' => urldecode($shopname),
				'price' => $price,
				'gold' => $gold_do,
				'create_time' => NOW_TIME,
				'gr_id' => $gr_id
			);

			$res = M('temporary_order')->add($dataArr);

			$orderno = $mch_tradeno;            //订单号

			$shop_order = M('shop_order')->where('order_id=' . $orderno)->find();

			if ($shop_order) {
				returnJson('', 264, '该订单已生成，请在我的摸金记录确认购买状态，如有问题请联系我们的客服人员进行核对!');
			}

			//金币支付
			if ($gold_do > 0) {
				//$this->rechargeDeal($orderno, $orderno, true, $channel);
				//全价兑换
				if ($type == 1) {

				} else {
					//夺宝购买
					//限制条件
//					$unit = getUnit($pid);
					$result = $this->payadd($pid, $orderno,$price, $uid, $type,  $gold_do ,$gr_id);
				}
				if ($result) {
					returnJson('', 200, 'success');
				} else {
					returnJson('success', 411, C("WEB_CURRENCY").'不足！！！');
				}
			}
		} catch (\Exception $e) {
			returnJson($e->getMessage(), 500, 'error');
		}

	}
	/**
	 * 兑换经验
	 *
	 * @author liuwei
	 * @param  [type] $uid    [description]
	 * @param  [type] $sid    [description]
	 * @param  [type] $amount [description]
	 * @return [type]         [description]
	 */
	public function experience($uid, $sid, $amount)
	{
		try {
			$describe = array();
			$describe['用户id'] = $uid;
			//用户是否存在
			$user_count = M('user')->where('id='.$uid)->count();
			if($user_count == '0'){
				returnJson('', 101, '亲，用户信息丢失啦');
			}
			//商品是否存在
			$shop_item = M('shop')->where('id='.$sid)->field('status,fictitious,prop_type,is_full')->find();
			if (empty($shop_item)) {
				returnJson('', 102, '商品不存在');
			}
			if ($shop_item['status'] == '0') {
				returnJson('', 102, '商品已下架');
			}
			if ($shop_item['fictitious'] == '2' and $shop_item['prop_type'] == '1' and $shop_item['is_full'] == '1') {
				//用户剩余钻石
				$pay_json = $this->getCoins($uid);//钻石
		        $pay = json_decode($pay_json, true);
		        $gold = empty($pay['data']['amount_coin']) ? 0 : $pay['data']['amount_coin'];
		        if ($amount > $gold) {
		        	returnJson('', 101, C("WEB_CURRENCY").'余额充足');
		        }
		        $describe['用户兑换经验之前'.C("WEB_CURRENCY").'数'] = $gold;
		        //获取我的经验
		        $experience_info = array();
		        $experience_result = D('api/PlatformApi')->getExpInfo($uid, $gold);
		        if ($experience_result['code']==1000) {
		            $experience_info = $experience_result['result'];
		        }
		        $exchange = $experience_info['exchangeNum'];//兑换比例
		        $describe['用户兑换经验之前等级'] = $experience_info['currentLevel'];
		        $describe[C("WEB_CURRENCY").',经验兑换比例'] = "1:".$exchange;
		        $describe['花费'.C("WEB_CURRENCY").'数'] = $amount;
		        $describe['兑换经验值'] = $amount*$exchange; 
		        $pay_result = D('api/PlatformApi')->exchangeExp($uid, $amount);
		        recordLog(json_encode($pay_result), "经验购买");
		        if (!empty($pay_result) and $pay_result['code']==1000) {
		        	//开启事务
					M()->startTrans();
					try {
						//shop_period增加记录
			        	$d_period = array();
						$d_period['sid'] = $sid;
						$d_period['create_time'] = time();
						$d_period['state'] = 2; // 2= 已开奖
						$d_period['order_status'] = '102'; // 102= 已收货
						$order_time_array = array();
						$order_time_array['receive_time'] = time();//领取时间
	                	$order_time_array['receipt_time'] = time();//收货时间
	                	$d_period['order_status_time'] = json_encode($order_time_array);
						$d_period['number'] = 1;//购买的数量
						$d_period['uid'] = $uid;
						$d_period['exchange_type'] = 1; //兑换类型 1=兑换 ；0=夺宝；
						$d_period['kaijang_time'] = time(); //开奖时间为当前时间
						$d_period['end_time'] = $this->getMillisecond();//结束时间
						$rs_period = M('shop_period')->add($d_period);
						recordLog(json_encode($rs_period), "经验购买p-".$rs_period);
						//shop_order 增加记录
						$data = array();
						$pid = $rs_period;
						$create_time = time();
						$msg = '兑换经验成功,已经升到LV'.$pay_result['result']['currentLevel'];
						$order_id = 'shopno'.think_md5(microtime(), $uid);
						$code = '0';
						$exchange_type = 1;
						$data['uid'] = $uid;
						$data['pid'] = $rs_period; //添加 刚刚新增的pid
						$data['create_time'] = $create_time;
						$data['msg'] = $msg;
						$data['number'] = 1;
						$data['order_id'] = $order_id;
						$data['type'] = 1;//支付类型金钻
						$data['gold'] = $pay_result['result']['cost'];
						$data['code'] = 0;
						$data['exchange_transaction'] = $pay_result['result']['orderNo']; //兑换流水号
						$data['top_diamond'] = $pay_result['result']['costA'] >0 ? '-'.$pay_result['result']['costA'] : $pay_result['result']['costA']; //钻石-充值
						$data['recharge_activity'] = $pay_result['result']['costB'] > 0 ? '-'.$pay_result['result']['costA'] : $pay_result['result']['costB']; //钻石-活动
						$data['exchange_type'] = $exchange_type;
						$rs_order = M('shop_order')->add($data);
						recordLog(json_encode($rs_order), "经验购买o-".$rs_order);
						//订单记录
						$describe['用户兑换经验之后'.C("WEB_CURRENCY").'数'] = $pay_result['result']['balance'];
						$describe['用户兑换经验之后等级'] = $pay_result['result']['currentLevel'];
						$log_data['oid'] = $rs_order;
						$log_data['type'] = 0;
						$log_data['describe'] = json_encode($describe);
						$log_data['time'] = time(); 
						$rs_log = M('order_log')->add($log_data);
						recordLog(json_encode($rs_log), "经验购买l-".$rs_log);
						//库存减1
						D('shop')->where(array('id' => $sid))->setDec('shopstock');
						M()->commit();//事务提交
						returnJson('', 200, '成功');
						recordLog("", "经验购买成功");
					} 
					catch (\Exception $e) {
						M()->rollback();//回滚
						returnJson('', 101, $e->getMessage());
						recordLog(json_encode($e->getMessage()), "经验购买失败");
					}
		        	
					
		        } else {
		        	$result_msg = $pay_result['msg'];
		        	$code = $pay_result['code'];//错误码
		        	if ($code == 4004) {
		        		$result_msg = "系统错误";
		        	}
		        	returnJson('', $code, $result_msg);
		        	recordLog($result_msg, '接口状态码:'.$code);
		        }
			} else {
				returnJson('', 102, '商品无法兑换经验');
			}
		} catch (\Exception $e) {
			returnJson('', 101, $e->getMessage());
		}
	}
	public function payadd($id, $sn, $price, $uid, $type, $gold_do = 0,$gr_id = 0)
	{

		$data['uid'] = $uid;
		$data['pid'] = $id;
		$data['create_time'] = NOW_TIME;
		//购买次数
		$data['number'] = $price;
		$data['gold'] = $gold_do;
		$data['order_id'] = $sn;
		$data['type'] = $type;
		recordLog(json_encode($data), "pay flow");

		//开启事务
		M()->startTrans();//开启事务 
		$starttime = time();

		recordLog('starttime = ' . $starttime, '[' . $sn . "] pay flow time start");
		$rs = false;
		try {
			$rs = $this->_payadd($id, $sn, $price, $uid, $type, $gold_do, $gr_id );
		} //这里的\Exception不加斜杠的话回使用think的Exception类
		catch (\Exception $e) {
			M()->rollback();//回滚
			return $rs;
		}

		M()->commit();//事务提交
		$endtime = time();
		$usetime = $endtime - $starttime;
		recordLog('endtime = ' . $endtime . ' => usetime = ' . $usetime, '[' . $sn . "] pay flow time end ");
		return $rs;

	}

	public function _payadd($id, $sn, $price, $uid, $type, $gold_do = 0,$gr_id = 0)
	{

		$data['uid'] = $uid;
		$data['pid'] = $id;
		$data['create_time'] = NOW_TIME;
		//购买次数
		$data['number'] = $price;
		$data['gold'] = $gold_do;

		$data['order_id'] = $sn;
		$data['type'] = $type;

		//总价
		$amount = $gold_do;

		//普通摸金区域，正价商品
		$info = M('shop_period')->
		table('__SHOP__ shop,__SHOP_PERIOD__ period')
			->lock(true)
			->field('shop.id as sid,shop.name,shop.price,shop.buy_price,shop.status,shop.edit_price,shop.ten,shop.periodnumber,shop.shopstock,period.no,period.number,period.state,period.jiang_num,period.kaijang_time,period.iscommon,shop.fictitious')
			->where('shop.id=period.sid and period.id=' . $id)
			->find();


		//检查商品是否已经下架
		$rs_status = M('shop')->lock(true)->where('id='.$info['sid'])->getField('status');
		if($rs_status == '0'){
			returnJson('', 410, '该商品已下架');
		}
		//检查该商品是否已经结束
		$rs_state = M('shop_period')->lock(true)->where('id='.$id)->getField('state');
		if(intval($rs_state) > 0){
			returnJson('', 420, '本期已结束，请参与最新一期');
		}

		//如果已达到条件，并且已支付，提示停止开奖，并将金额充入金币余额
		if ($info['state'] > 0) {
			$data['number'] = 0;
			$data['code'] = 'FAIL';
			$data['msg'] = '您手慢了,该期已经准备开奖停止购买了!系统已将购买金额自动充入余额。';
			return false;
		}

		//TODO 检查是否已经超过该期的开奖时间
		if($info['kaijang_time'] < time() && $info['state'] == 0){
			$rs_state = M('shop_period')->where('id='.$id)->setField('state',1);
			//检查是否有最新一期，并生成新的一期
			$pid_progress = M('shop_period')->where(array('state'=> 0))->getField('id');
			if(empty($pid_progress)){
				$arr_ssc = getNextSsc();
//				$kj_data = D('api/Period')->getKaijiang();
//				$jdata = json_decode($kj_data,true);
//				$openTime = $jdata['openTime'];
				
//				$kaijang_ssc   = $jdata[0]['WinNumber'];
//				$kaijang_issue = $jdata[0]['Issue'];

				$period['sid'] = $info['sid'];
				$period['create_time'] = NOW_TIME;
				$period['kaijang_time'] = $arr_ssc['lottery_time']; //设置开奖时间
				$period['kaijiang_issue'] = intval($arr_ssc['lottery_issue']); //设置当前时时彩期数

				$period['state'] = 0;
				//$period['no']=$info['no']+1;
				$max_no = M('shop_period')->where('sid=' . $info['sid'])->max('no');
				$period['no'] = $max_no + 1;

				D('api/Period')->addShopAction($period, (intval($info['periodnumber']) + 100000), $info['shopstock'], 1, $id);
			}

			M()->commit();//事务提交
			returnJson('', 430, '本期正在揭晓，请参与最新一期');
		}

		//TODO 调用支付接口扣减钻石
		$billid = 'zssc-' . think_md5(microtime(), $uid);
								//		$uid, $billid,$order_no,$coin_a,$coin_b,
		$rs_discount = $this->discountGcoupon( $uid, $billid,$sn ,$amount);
		$j_discount = json_decode($rs_discount);
		if (!$rs_discount) return false;
		    $point_do_active 	= $j_discount->data->expend_recharge_coin; 		//钻石-充值数量
			$point_do_exchange 	= $j_discount->data->expend_gift_coin; 	//钻石-活动数量

		$data['exchange_transaction'] = $billid;
		$data['top_diamond'] = $point_do_active;
		$data['recharge_activity'] = $point_do_exchange;
		$data['gold'] = $amount;

		//通过uid 获取渠道对应的 单位配置
		$rs_channel = M('channel')
			->table('__CHANNEL__ channel,__USER__ user')
			->field('channel.starting_number,channel.premium')
			->where('channel.id=user.channelid and user.id = '.$uid)
			->find();
		if($rs_channel){
			$data['buy_gold'] = $price * $rs_channel['starting_number']; //购买的黄金毫克数
		}
		//TODO 分配号码 按序进行分配
		$pre_number = $info['number'] ;
		$pre_number = $pre_number + 100001;
		$max_number = $pre_number + $price;
		$jiang_num = range($pre_number,$max_number-1);
		$dataList = array('uid' => $uid, 'pid' => $id, 'sid'=>$info['sid'], 'create_time' => $this->getMillisecond(), 'number' =>$price, 'order_id' => $sn, 'num' => implode(',', $jiang_num));
		M('shop_record')->add($dataList);

		M('shop_period')->where('id=' . $id)->save(array('number' => $info['number']+$price, 'jiang_num' => implode(',', $jiang_num)));

		$wid = M('user')->where('id=' . $uid)->getField('wid');
		$data['code'] = 'OK';
		$data['msg'] = '购买成功';
		$data['wid'] = $wid;
		$data['gr_id'] = $gr_id;
//				$data['recharge']=1;
		$this->shop_order($data);
		return $sn;
	}

	/**
	 * 全价兑换
	 * @param $uid
	 * @param $sid
	 * @param string $username
	 * @param $sn
	 * @param $sid
	 * @param $gold_do
	 * @param $order_id
	 * @param $prepay_id
	 * @param $exchange_transaction
	 * @param $top_diamond
	 * @return mixed
	 */
	public function payFullPrice($uid, $sid, $number, $coin_amount, $order_id, $exchange_type)
	{

		//检查商品库存情况（商品库存数 > shop_period表中state = 0 进行中的商品）
		$rs_count = D('api/shop')->getInventory4do($sid);
		if (intval($rs_count) <= 0) {
			//商品库存不够
			return false;
		}

		//TODO 调用钻石接口扣减相应钻石数量
		$billid = 'zssc-'.think_md5(microtime(), $uid);
		$rs_discount = $this->discountGcoupon( $uid, $billid,$order_id ,$coin_amount);
		if (!$rs_discount) return false;
//		$j_discount = json_decode($rs_discount);
//		$point_do_exchange 	= $j_discount->data->expend_recharge_coin; 		//钻石-充值数量
//		$point_do_active	= $j_discount->data->expend_gift_coin; 	//钻石-活动数量
//		$point_do_active = 100;        //钻石-充值数量
//		$point_do_exchange = 10;    //钻石-活动数量

		//根据扣减情况进行处理
		//1.shop_period 增加一条记录
		//2.shop_order 增加一条记录
		//3.库存减1

		$d_period = array();
		$d_period['sid'] = $sid;
		$d_period['create_time'] = time();
		$d_period['state'] = 2; // 2= 已开奖
		$d_period['number'] = $number;//购买的数量
		$d_period['uid'] = $uid;
//		$d_period['username'] = $username;
		$d_period['exchange_type'] = 1; //兑换类型 1=兑换 ；0=夺宝；
		$d_period['kaijang_time'] = time(); //开奖时间为当前时间
		$d_period['end_time'] = $this->getMillisecond();//结束时间
		//虚拟商品 - 选取 - liuwei
		$fictitious = M('shop')->where(array('id' => $sid))->getField('fictitious');
		if ($fictitious==2) {
			//是否是直充卡
			$category = M('shop')->where('id='.$sid)->getField('category');//获取商品分类id
            $name = "直充卡";
            $category_where = array();
            $category_where['title'] = array('like', "%".$name."%");
            $category_where['id'] = $category;
            $category_count = M('category')->where($category_where)->count();
            $straight = $category_count==0 ? 0 : $category_count;
            if ($straight ==0) {
            	$card_id = M('card')->where('status=0 and issend=0 and type=' . $sid)->getField('id');//获取
				if (!empty($card_id)) {
					$d_period['card_id'] = $card_id;
					//虚拟卡状态变成 已经使用
					M('card')->where('id='.$card_id)->save(array('status'=>1));
				}
            }
		}
		$rs_period = M('shop_period')->add($d_period);

		//shop_order 增加记录
		$data = array();

//		$username = '';
		$pid = $rs_period;
		$create_time = time();
		$msg = '购买成功';
		$order_id = $order_id;
		$code = '0';
  
		$exchange_type = 1;
		$data['uid'] = $uid;
		$data['pid'] = $rs_period; //添加 刚刚新增的pid
		$data['create_time'] = $create_time;
		$data['msg'] = $msg;
		$data['number'] = $number;
		$data['order_id'] = $order_id;
		$data['type'] = 1;//支付类型金钻
		$data['gold'] = $coin_amount;
		$data['code'] = 0;
		$data['exchange_transaction'] = $billid; //兑换流水号
		$data['top_diamond'] = ''; //钻石-充值
		$data['recharge_activity'] = ''; //钻石-活动
		$data['exchange_type'] = $exchange_type;
//		$data['billid'] = $billid;

		$rs = M('shop_order')->add($data);
		//shop_period增加记录
		//库存减1
		D('shop')->where(array('id' => $sid))->setDec('shopstock');

		return $billid;
	}

	public function checkPrice($price, $uid)
	{
		$black = M('User')->where('id=' . $uid)->getField('black');
		if ($black >= $price) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 检查金券是否足够
	 * @param $amount
	 * @param $uid
	 * @return bool
	 */
	protected function checkPrice4GC($amount, $uid)
	{
		$black = M('User')->where('id=' . $uid)->getField('gold_coupon');
		if ($black >= $amount) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 调用钻石模块查询
	 * @param $price
	 * @param $uid
	 * @return bool
	 */
	public function checkPrice4do($amount, $uid)
	{

		$rs_amount = array();
		$data = $this->getCoins($uid);
		$jdata = json_decode($data);

		//钻石余额充足
		if(($jdata->data->amount_coin) >= $amount){
//			$tmp = $data->recharge_coin - $amount ;
//
//			if($tmp < 0){
//				$amount['recharge_coin'] = 0;
//				$amount['gift_coin'] = $data->gift_coin + $tmp;
//			}else{
//				$amount['recharge_coin'] = $tmp;
//			}
			$rs_amount['coin_a'] = $jdata->data->recharge_coin;
			$rs_amount['coin_b'] =  $jdata->data->gift_coin;
			return $rs_amount;
		}else{
			return false;
		}
	}

	/**
	 * 获取用户钻余额
	 * @param $uid
	 * @return mixed
	 * @throws \Exception
	 */
	public function getCoins($uid){
//		$url = 'http://local.passport.busonline.com/wapapi.php?s=/api/getcoins';
//		$url = 'http://139.129.21.58:8088/wapapi.php?s=/api/getcoins'; // 自定义钻石服务地址
		$url = $this->getUrl(). '/api/getcoins';  //公司内部钻石地址


		$param['appid'] = $this->appid;
		if(empty($uid))$uid  = 101898;//TODO 固定的用户ID
		$param['uid'] =  $uid ;
		$param['time'] = time().'000';
		$sign = param_signature('post',$param);
		$param['sign'] = $sign;

		$result = post_str($url ,$param,3); // 通过字符拼装方式提交post,超时时间3s

		return $result['data'];
	}
	

	public function getMillisecond()
	{
		list($t1, $t2) = explode(' ', microtime());
		return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
	}

	public function kaijiangtime($id,$sid)
	{
		$kjtime_sum = 0;
		$subQuery = M('shop_record')->order('create_time desc')->buildSql();
		$kaijiang_time = M('shop_record')->field('uid,create_time,pid as shopid')->table($subQuery . ' a')->group('uid')->order('create_time desc')->limit(35)->select();
		$count = 0;
		//全场最后35条参与时间的前30条+该商品的最后一条参数与时间
		foreach ($kaijiang_time as $key => $value) {
			if ($count++ < 5) {
				continue;
			}
			$kjtime[$key] = $value;
			$kjtime[$key]['pid'] = $id;
			$kjtime_sum +=  (float)$value["create_time"];
		}

		$rs_last = M('shop_record')->where('sid=' . $sid)->order('create_time desc')->getField('create_time');
		$kjtime_sum +=  (float)$rs_last;

		M('shop_kaijiang')->addAll($kjtime);
		return $kjtime_sum;
	}

	public function pay_result($sn, $pcode = null)
	{
		$map['order_id'] = $sn;
		$return['order'] = M('shop_order')->where($map)->field('order_id,uid,pid,code,msg,number,type,gold,cash,create_time')->find();
		$num = M('shop_record')->where($map)->getField('num');

		if (empty($num)) {
			$return['record'] = array();
		} else {
			$return['record'] = explode(',', $num);
		}

		//增加私有商品处理
		if (isPCodeProc($pcode)) {
			$shop = D('Shop')->detail($return['order']['pid'], $pcode);
		} else {
			$shop = D('Shop')->detail($return['order']['pid']);
		}

		if ($shop) {
			$return['shop'] = $shop;
			if (isEmpty($return['order'])) {
				//是否是pk商品
				$period_item = M()->table('hx_shop_period p')
					->join('LEFT JOIN hx_house_manage m ON p.house_id=m.id')
					->field('p.iscommon,m.id as houseid,m.no as room_no,m.ispublic')
					->where('p.id=' . $return['order']['pid'])
					->find();
				//echo M()->getLastSql();exit;
				$return['shop']['iscommon'] = empty($period_item['iscommon']) ? 1 : $period_item['iscommon'];
				if (!empty($period_item['iscommon']) and $period_item['iscommon'] != 1) {//pk商品
					$return['shop']['houseid'] = $period_item['houseid'];
					$return['shop']['room_no'] = $period_item['room_no'];
					$return['shop']['ispublic'] = $period_item['ispublic'];
				}
			}

		} else {
			$return['shop'] = null;
		}
		return $return;
	}

	public function kjtime()
	{
		$kj_time = time() + 30;//开奖时间+30秒
		return $kj_time;
	}

	public function shop_order($data)
	{
//		if(empty($data['channel_id_profit'])){
//			$rs_channel  = $this->getProfit4Channel($data['uid'],$data['pid']);
//			if($rs_channel){
//				$data['channel_id_profit'] = $rs_channel['channelid'];
//				$data['invitation_id_profit'] = $rs_channel['invitationid'];
//			}
//		}

		$rs = $this->shop_order_add($data);
		if ($rs) {
			//购买成功或失败记录金币明细
			$rs_gold_record = D('api/GoldRecord')->shopGoldRecord($data['uid'], $data['pid']);
			recordLog($rs_gold_record, "rs_gold_record");
		}
		return $rs;
	}

	public function shop_order_add($data)
	{
		$data['status'] = 1;
		if ($id = M('shop_order')->where(array('order_id' => $data['order_id']))->getField('id')) {
			$data['id'] = $id;
			$rs = M('shop_order')->save($data);
		} else {
			$rs = M('shop_order')->add($data);
		}
		return $rs;
	}

	/**
	 * 同步用户分配号码
	 * 解决多个用户同一时间并发购买，造成分配相同号码
	 * @param $uid
	 * @param $pid
	 * @param $order_id
	 * @param $data ['number']
	 */
	public function assignedNumberSync($uid, $pid, $order_id, $purchase_quantity)
	{
		M()->startTrans();//开启事务
		$map['id'] = $pid;//查询条件
		$data = M('ShopPeriod')
			->field('number,jiang_num')
			->lock(true)->where($map)->find();//行加锁查询,其它行记录正常查询，使用非for update 的语句不受影响；
		if ($data) {
			if (empty($data['jiang_num'])) {
				M()->rollback();//回滚
				return false;
			}
			$jiang_num = explode(',', $data['jiang_num']);
			$dataList = array('uid' => $uid, 'pid' => $pid,  'create_time' => $this->getMillisecond(), 'number' => $data['number'], 'order_id' => $order_id, 'num' => implode(',', array_slice($jiang_num, 0, $purchase_quantity)));
			$rs1 = M('shop_record')->add($dataList);
			array_splice($jiang_num, 0, $data['number']);
			//统一进行奖号分配处理
			$rs2 = M('shop_period')->where('id=' . $pid)->save(array('number' => $data['number'] + $purchase_quantity, 'jiang_num' => implode(',', $jiang_num)));

			//执行你想进行的操作, 最后返回操作结果 result
			if (!($rs1 && $rs2)) {
				M()->rollback();//回滚
				return false;
			}
		}
		M()->commit();//事务提交
		return true;
	}

	public function orderOver($data, $uid)
	{
		//如果已达到条件，并且已支付，提示停止开奖，并将金额充入金币余额
		$data['number'] = 0;
		$data['code'] = 'FAIL';
		$data['msg'] = '您手慢了,该期已经准备开奖停止购买了!系统已将购买金额自动充入余额。';
		$data['recharge'] = 1;
		$rs_shop_order = $this->shop_order($data);
		//添加消息
		if ($data['cash'] > 0) {
			D('Message')->addUserMessage($uid, 103, $data['pid']);
		}
		if ($rs_shop_order) {
			return $data['order_id'];
		} else {
			return false;
		}
	}

	/**
	 * 获取对应计算利润的渠道
	 * @param $uid
	 * @param int $pid
	 * @return mixed
	 */
	public function getProfitChannel($uid, $pid = 0)
	{
		/****************************************
		 * 按顺序执行查询
		 * 1.取当期所属房主的渠道ID
		 * 2.地推邀请归属渠道+绑定用户归属的渠道
		 ***************************************/

		//获取房主的归属渠道 //判断该期商品是否为私有PK专场
		$rs_house = M()->table('__SHOP_PERIOD__ p,__HOUSE_MANAGE__ m,__USER__ u ')
			->field('u.channelid,u.invitationid')
			->where(array('m.ispublic' => 1, 'p.id' => $pid))
			->where('m.id = p.house_id and m.uid = u.id')->find();
		if ($rs_house) {
			return $rs_house['channelid'];
		}
		// 获取地推邀请归属渠道  //获取用户的归属渠道
		$rs_user = M('user')->where(array('id' => $uid))->getField('channelid');
		if ($rs_user) {
			return $rs_user;
		}
	}

	public function getProfit4Channel($uid,$pid=0){
		/****************************************
		 * 按顺序执行查询
		 * 1.取当期所属房主的渠道ID
		 * 2.地推邀请归属渠道+绑定用户归属的渠道
		 ***************************************/

		//获取房主的归属渠道 //判断该期商品是否为私有PK专场
		$rs_house = M()->table('__SHOP_PERIOD__ p,__HOUSE_MANAGE__ m,__USER__ u ')
			->field('u.channelid,u.invitationid')
			->where(array('m.ispublic'=>1,'p.id'=>$pid))
			->where('m.id = p.house_id and m.uid = u.id')->find();
		if($rs_house){
			return $rs_house;
		}
		// 获取地推邀请归属渠道  //获取用户的归属渠道
		$rs_user = M('user')->where(array('id'=>$uid))->field('channelid,invitationid')->find();
		if($rs_user){
			return $rs_user;
		}
	}

	/**
	 * 消费金钻
	 * @param $uid
	 * @param $billid
	 * @param $order_no
	 * @param $amount
	 * @param string $item_name
	 * @return bool
	 */
	public function discountDo( $uid, $billid,$order_no,$coin_count,$item_name = '商城消耗'){

		//appid ,billid,uid,platform_recharge_coin,platform_gift_coin,order_no,time,sign

		//需要参数传递的
		// $billid,uid,

		//调用接口检查金钻的余额
		$rs_amount = $this->checkPrice4do($coin_count, $uid);

		if(!$rs_amount){
			return false;
		}
		//计算coin_a ,coin_b 应该的扣减数
		$coin_a = 0;
		$coin_b = 0;
		if($coin_count >  $rs_amount['coin_a']){
			$coin_a = $rs_amount['coin_a'];
			$coin_b = $coin_count - $rs_amount['coin_a'];
		}else{
			$coin_a = $coin_count;
		}

		if($rs_amount['coin_a'] >= $coin_a && $rs_amount['coin_b'] >= $coin_b ){

			//扣减金钻
			//		appid,billid,uid,type=102,order_no,item_id,item_name,item_price,remark,receive_uid,room_id,item_count,promo_code,off_price,time,sign
			//需要外部传递参数
			//billid,uid,order_no,item_name,item_price
//			$url = 'http://local.passport.busonline.com/wapapi.php?s=/api/expend';
//			$url = 'http://139.129.21.58:8088/wapapi.php?s=/api/expend';  // 自定义钻石服务地址
			$url = $this->getUrl().'/api/expend';  //公司内部钻石地址
			$data = array();
			$data['appid'] = $this->appid;
			$data['billid'] = $billid;
			$data['uid'] = $uid;
			$data['type'] = 107;
			$data['expend_recharge_coin'] = -$coin_a;
			$data['expend_gift_coin'] = -$coin_b;
			$data['order_no'] = $order_no;
			$data['item_name'] = $item_name;
			$data['remark'] = $item_name;

			$data['time'] = time().'000';
			$sign = param_signature('post',$data);
			$data['sign'] = $sign;

			$result = post_str($url ,$data); //TODO 通过字符拼装方式提交post

			if($result['code'] == '200'){
				return $result['data'];
			}
		}
		return false;
	}

	public function discountGcoupon( $uid, $billid,$order_no,$coin_count,$item_name = '商城消耗'){

		//appid ,billid,uid,platform_recharge_coin,platform_gift_coin,order_no,time,sign

		//需要参数传递的
		// $billid,uid,

		//调用接口检查金钻的余额
		$rs_amount = $this->checkPrice4GC($coin_count, $uid);

		if(!$rs_amount){
			return false;
		}
		 //直接扣减用户的金券数量
		$rs  = D('api/user')->discountGCoupon($uid,$coin_count);
		if($rs){
			return true;
		}else{
			return false;
		}
	}


	public function expend($uid, $billid,$order_no,$coin_a,$coin_b,$item_name = '商城消耗'){
		$url = $this->getUrl().'/api/expend';  //公司内部钻石地址
		$data = array();
		$data['appid'] = $this->appid;
		$data['billid'] = $billid;
		$data['uid'] = $uid;
		$data['type'] = 107;
		$data['expend_recharge_coin'] = $coin_a;
		$data['expend_gift_coin'] = $coin_b;
		$data['order_no'] = $order_no;
		$data['item_name'] = $item_name;
		$data['remark'] = $item_name;
		$data['time'] = time().'000';
		$sign = param_signature('post',$data);
		$data['sign'] = $sign;

		$result = post_str($url ,$data); //TODO 通过字符拼装方式提交post

		if($result['code'] == '200'){
			return $result['data'];
		}
	}

	//虚拟币消耗，覆盖expend方法，$type:107(商城消耗),207(提金消耗),item_count 提金数量(g)
	public function cost($type, $uid, $billid, $order_no, $cost_coin, $item_name = '商城消耗', $item_count = 0){
		$url = $this->getUrl().'/api/expend'; 
		$data = array();
		$data['appid'] = $this->appid;
		$data['billid'] = $billid;
		$data['uid'] = $uid;
		$data['type'] = $type;
		$data['expend_recharge_coin'] = $cost_coin>0?0-$cost_coin:$cost_coin;
		$data['expend_gift_coin'] = 0;
		// $data['expend_operating_coin'] = 0;//游戏接口支持后放开注释
		$data['order_no'] = $order_no;
		$data['item_name'] = $item_name;
		$data['item_count'] = $item_count;
		$data['remark'] = $item_name;
		$data['time'] = time().'000';
		$sign = param_signature('post',$data);
		$data['sign'] = $sign;

		$result = post_str($url ,$data); //TODO 通过字符拼装方式提交post

		if($result['code'] == '200'){
			return $result['data'];
		}
		else{
			return false;
		}
	}

	public function getUrl(){
		//正式环境正式钻石服务地址
		//测试环境使用测试服务地址

		if(isHostProduct()){
			return $this->prod_url;
		}
		else if(isHostOnlineTest()){
			return $this->onlinetest_url;
		}
		else{
			return $this->test_url;
		}
	}

	/**
	 * 立即下单
	 * 保存当前的金价，时间信息
	 */
	public function preOrder($uid='4308197'){
		$gold_price = getGoldprice();
		$curr_time = $this->getMillisecond(); //毫秒时间

		$str = $curr_time.$gold_price.$uid;
		$md5 = think_md5($str,'gold');

		$rs = M('shop_gold_record')->add(    array('gold_price'=>$gold_price,'uid'=>$uid,'md5'=>$md5,'create_time'=>$curr_time));

		$arr_gold = array('gold_price'=>$gold_price,'md5'=>$md5);
		if($rs ){
			return $arr_gold;
		}else{
			return '';
		}
	}
	
}