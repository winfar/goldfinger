<?php
namespace api\Model;

use Think\Cache\Driver\RedisCache;
use Think\Model;

class GoldRecordModel extends Model
{
    //注册
    public function register($uid,$createTime,$giftGold){

        $typeid = 11;

        // $user = M('user u')->table('__CHANNEL__ c')->field('u.*,c.channel_name')->where('u.channelid=c.id and id='.$uid)->find();

        if($uid && $giftGold){
            $remarkArr=array();
            $remarkArr["注册时间"]=date("Y-m-d H:i:s",$createTime);
            $remarkArr["赠送虚拟币数量"]=$giftGold;

            $rs = $this->addGoldRecord($uid,$typeid,$giftGold,$remarkArr,0);
            return $rs;
        }
    }

    /**
     * 金币消费商品明细记录（金币消费，金币退还）
     * @param $uid
     * @param $pid
     * @param $typeid
     * @return bool
     */
    public function shopGoldRecord($uid,$pid){

        $order = M('shop_order')
        ->table('__SHOP_ORDER__ o,__SHOP_PERIOD__ p, __SHOP__ s, __TEN__ ten')
        ->field('o.uid,p.id pid,s.`name`,p.`no`,o.number,o.`code`,gold,cash,CONVERT((gold+cash)/unit,SIGNED) cnt,(CONVERT(((gold+cash)/unit-o.number),SIGNED)) fail_cnt,(CONVERT(((gold+cash)/unit-o.number)*unit,SIGNED)) backgold')
        ->where('o.pid=p.id and s.id=p.sid and s.ten=ten.id and o.pid=' . $pid . ' and o.uid=' . $uid)
        ->order('o.create_time DESC')
        ->find();

        //echo M()->getLastSql();exit();

        // select o.uid,p.id pid,s.`name`,p.`no`,o.number,o.`code`,gold,cash,CONVERT((gold+cash)/unit,SIGNED) cnt,(CONVERT(((gold+cash)/unit-o.number),SIGNED)) fail_cnt,(CONVERT(((gold+cash)/unit-o.number)*unit,SIGNED)) backgold
        // from bo_shop_order o,bo_shop_period p,bo_shop s,bo_ten ten
        // where o.pid=p.id and s.id=p.sid and s.ten=ten.id
        // //购买失败条件
        // and (CONVERT(((gold+cash)/unit-o.number),SIGNED))>0 and (CONVERT(((gold+cash)/unit-o.number)*unit,SIGNED))>0
        // and o.pid=998 AND o.uid=101903
        // order by o.create_time DESC

        if($order){
            $remarkArr=array();
            $remarkArr["商品名称"]=$order["name"];
            $remarkArr["商品期号"]=$order["no"];
            $remarkArr["金币支付金额"]=$order["gold"];

            if($order['code']=='OK'){
                //购买成功或者成功一半
                // $remarkArr["现金支付金额"]=$order["cash"]-$order["backgold"];
                // $remarkArr["购买成功金额"]=$order["cash"];
                // $remarkArr["购买失败金额"]=$order["backgold"];

                $remarkArr["现金支付金额"]=$order["cash"];
                $remarkArr["购买成功金额"]=$order["cash"];
                $remarkArr["购买失败金额"]=0;

                // if($order["backgold"]>0){
                //     $rs_inc = $this->addGoldRecord($uid,1,$order["backgold"],$remarkArr,$pid);
                // }

                if($order["gold"]>0){
                    $rs_dec = $this->addGoldRecord($uid,5,0-$order["gold"],$remarkArr,$pid);
                }

                return $rs_inc || $rs_dec;
            }
            else {
                //购买完全失败
                $remarkArr["现金支付金额"]=$order["cash"];
                $remarkArr["购买成功金额"]=0;
                $remarkArr["购买失败金额"]=$order["cash"];

                if($order["cash"]>0){
                    $rs_inc = $this->addGoldRecord($uid,1,$order["cash"],$remarkArr,$pid);
                }

                return $rs_inc;
            }
        }
        else{
            return false;
        }
    }

    //充值
    public function recharge($uid,$amount,$totalGold,$giftGold){

        $typeid = 9;

        $remarkArr=array();
        $remarkArr["充值时间"]=date("Y-m-d H:i:s",time());
        $remarkArr["充值金额"]=$amount;
        $remarkArr["赠送金币数量"]=$giftGold;

        $rs = $this->addGoldRecord($uid,$typeid,$totalGold,$remarkArr,0);
        return $rs;
    }

    //签到
    public function sign($uid,$gold,$point){

        $typeid = 15;

        $remarkArr=array();
        $remarkArr["活动名称"]='签到';
        $remarkArr["金币"]=$gold;
        $remarkArr["积分"]=$point;

        $rs = $this->addGoldRecord($uid,$typeid,$gold,$remarkArr,0);
        return $rs;
    }

    protected function addGoldRecord($uid,$typeid,$gold,$remarkArr,$pid=0){

        $data["uid"]=$uid;
        $data["typeid"]=$typeid;
        $data["gold"]=$gold;
        $data["create_time"]=time();
        $data["remark"]=json_encode($remarkArr,JSON_UNESCAPED_UNICODE);
        $data["pid"]=$pid;

        // $remarkArr=array();

        // switch ($typeoid) {
        //     case 1:
        //         //购买失败返还
        //         $remarkArr["商品名称"]="移动20元充值卡";
        //         $remarkArr["商品期号"]="100002";
        //         $remarkArr["商品金额"]=123.00;
        //         $remarkArr["购买成功金额"]=23.00;
        //         $remarkArr["购买失败金额"]=100;
        //         $remark = json_encode($remarkArr);
        //         break;
        //     case 2:
        //         //期数取消退还
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 3:
        //         //商品下架退还
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 4:
        //         //积分兑换
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 5:
        //         //支付
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 6:
        //         //虚拟商品兑换
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 7:
        //         //活动获取
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 8:
        //         //系统修改
        //         $remark = '{"兑换积分数量": "1000"}';
        //         break;
        //     case 9:
        //         //充值
        //         $remark='{"充值时间": "2016/08/17 15:00:00","充值金额": "99","赠送金币数量": "1"}';
        //         break;
        //     case 10:
        //         //邀请好友
        //         $remark='{"充值时间": "2016/08/17 15:00:00","充值金额": "99","赠送金币数量": "1"}';
        //         break;
        //     case 11:
        //         //注册
        //         $remark='{"注册时间": "2016/08/17 15:00:00","所属渠道": "1"}';
        //         break;
        //     case 12:
        //         //晒单
        //         $remark='{"充值时间": "2016/08/17 15:00:00","充值金额": "99","赠送金币数量": "1"}';
        //         break;
        //     default:
        //         # code...
        //         break;
        // }

        if(!$this->checkGoldcoupon($gold,$uid)){
            returnJson($gold,401,'虚拟币不够');
        }

        $model = M('gold_record');
        $model->startTrans();

        if(abs($gold)>0){
            $rsGold_record = $model->add($data);
            $rsGold = M('User')->where('id=' . $uid)->setInc('gold_coupon', $gold);
        }

        if($rsGold>0 && $rsGold_record>0){
            $model->commit();
            return true;
        }
        else{
            $model->rollback();
            return false;
        }
    }

    /**
	 * 检查虚拟币是否足够
	 * @param $amount
	 * @param $uid
	 * @return bool
	 */
	protected function checkGoldcoupon($amount, $uid)
	{
		$black = M('User')->where('id=' . $uid)->getField('gold_coupon');
		if ($black >= $amount) {
			return true;
		} else {
			return false;
		}
	}

    /** 商品兑换单独使用
     * @param $uid 用户id
     * @param int $gold 金币
     * @param $typeid 类型id
     * @param $remark 描述
     * @return bool
     */
    public function addGoldByUid($param)
    {
        $tokenid = $param['tokenid'];
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
//        $gold = $param['gold'];
//        if ( $gold <= 0 ) {
//            return false;
//        }
        $uid = $user['uid'];
        $pid = $param['pid'];         

        //获取brandid sql
        $sql = "SELECT 	a.title,a.buy_price,a.brand_id FROM	hx_shop_period sp LEFT JOIN ( SELECT s.id,s.buy_price,s.brand_id, b.title FROM hx_shop s LEFT JOIN hx_brand b ON b.id = s.brand_id ) a ON a.id = sp.sid WHERE sp.id = ".$pid;
        $result = $this->query($sql, false);

        $bid = $result[0]['brand_id'] ;

        $rate = M('exchange_virtual')->where('bid='.$bid)->getField('rate');

        if( is_null($bid ) || is_null($rate)){
            returnJson('', 400, '该商品不允许兑换！');
        }

        if($result[0]['title'] === '金袋'){
            $tradetypeid = M('trade_type')->where('code=1013')->getField('id');
        }else{
            $tradetypeid = M('trade_type')->where('code=1006')->getField('id');
        }

        $gold = floor($result[0]['buy_price']*($rate/100)) ;

        //商品已经兑换过
        $statusGold = M('shop_period')->where('id=' . $param['pid'] )->getField('statusGold');
        if ( $statusGold > 0 ) {
            returnJson('', 400, '此商品已经兑换过！');
        }

        $data['uid'] = $uid;
        $data['typeid'] = $tradetypeid;
        $data['gold'] = $gold;
        $data['create_time'] = NOW_TIME;
        $data['pid'] = $param['pid'];

        $this->startTrans();

        $rs_user = M('User')->where('id=' . $uid)->setInc('black', $gold);

        $shopperiod = M('shop_period')->where('id=' . $param['pid'])->field('sid,no,card_id')->find();
        $shopid = $shopperiod['sid'];
        $shopinfo = M('shop')->where('id=' . $shopid)->find();
        $shopinfo['shopstock'] = intval($shopinfo['shopstock']) + 1;//修改库存
        $rs_shop = M('shop')->save($shopinfo);
        $rs_period = M('shop_period')->where('id=' . $param['pid'] )->setField('statusGold','1');
        //修改虚拟卡状态，将激活的卡密改回可获得的状态
        $rs_card = M('card')->where('id=' . $shopperiod['card_id'])->setField('status',0);

        //构造remark
        //虚拟卡
        //商品名称，商品ID，卡号，商品金额，商品期号
        $remarkArr=array();
        $remarkArr["商品名称"]=$shopinfo['name'];
        $remarkArr["商品ID"]=$shopinfo['id'];
        if($shopperiod['card_id'] > 0 ){
            $cardno = M('card')->where('id=' . $shopperiod['card_id'])->field('no')->find();
            $remarkArr["卡号"]=$cardno['no']; //设置虚拟卡的卡号
        }
        $remarkArr["进货价"]= $shopinfo['buy_price'];
        $remarkArr["商品期号"]= $shopperiod['no'];

        $data['remark'] = json_encode($remarkArr,JSON_UNESCAPED_UNICODE);
        $rsGold_record = $this->add($data);

        $param['order_status'] = '102'; //设置为已收货
        $rs_order = D("Shop")->updateOrderStatus($user["uid"],$param["pid"],$param['order_status']);

        if ( count($rs_user) && count($rs_shop) && count($rs_period) && count($rs_card) && count($rsGold_record) && count($rs_order) ) {
            $this->commit();
            return true;
        } else {
            recordLog('rs_user:'.$rs_user.' rs_shop:'.$rs_shop.' rs_period:'.$rs_period.' rs_card:'.$rs_card.' rsGold_record:'.$rsGold_record.' rs_order:'.$rs_order,'addGoldByUid');
            $this->rollback();
            return false;
        }
    }

    public function getGoldByUid($param)
    {
        $tokenid = $param['tokenid'];
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $sql = "SELECT gold.create_time time, tradetype.`name` content,tradetype.id type,gold.gold FROM hxGold_record gold INNER JOIN hx_trade_type tradetype ON gold.typeid=tradetype.id where gold.uid=" . $user['uid']." order by gold.id desc";
        $goldInfo = $this->query($sql, false);
        if ( $goldInfo ) {
            foreach ( $goldInfo as $key => $item ) {
                if ( $item['gold'] > 0 ) {
                    $goldInfo[$key]['tradetype'] = 1;
                } else {
                    $goldInfo[$key]['tradetype'] = 2;
                }
            }
        }

        return $goldInfo;
    }
 
    // /**
    //  * @deprecated 用户积分详情API
    //  * @author zhangkang
    //  * @date 2016-07-05
    //  **/
    // public function getPoints($tokenid)
    // {
    //     $user = isLogin($tokenid);
    //     if ( !$user ) {
    //          returnJson('', 100, '请登录！');
    //     }

    //     $points = M('point_record')->where("user_id=" . $user['uid'])->order('create_time desc')->select();
    //     $userpoint = M('User')->where('id=' . $user['uid'])->field('total_point')->find();

    //     $data = array('totalPoint' => $userpoint['total_point'], 'points' => $points);

    //      returnJson($data, 200, 'success');
    // }
}
