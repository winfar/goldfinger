<?php
namespace api\Model;

use Think\Model;

class ShopOrderModel extends Model
{

    /**
     * 新增充值订单
     *
     * @param [type] $uid
     * @param [type] $trade_no
     * @param [type] $recharge
     * @return void
     */
	public function addRechargeOrder($uid,$trade_no,$recharge)
	{

        if($recharge && $recharge['payout']>0){

            $data = array();
            $data['uid'] = $uid;
            //$data['username'] = $username;
            $data['pid'] = 0; //充值pid为0
            $data['create_time'] = time();
            $data['msg'] = '准备充值';
            $data['number'] = 1;
            $data['order_id'] = $trade_no;
            $data['type'] = 0;//支付类型：现金
            $data['gold'] = $recharge['income'] + $recharge['extra'];//虚拟币
            $data['cash'] = $recharge['payout'];//现金：元
            $data['code'] = '0';//默认0
            $data['recharge'] = 1;//0:购买;1:充值
            $data['status'] = 1;
            $data['order_status'] = 0;//订单状态(0:默认,1:充值成功,2:充值失败,100:已确认收货地址,101:已发货,102:已收货,103:已晒单)
            $data['order_status_time'] = $data['create_time'];
            // $data['exchange_transaction'] = $billid; //兑换流水号
            // $data['top_diamond'] = ''; //钻石-充值
            // $data['recharge_activity'] = ''; //钻石-活动
            // $data['exchange_type'] = 0;
            //$data['billid'] = $billid;    

            $result = $this->add($data);

            if ( $result ) {
                return true;
            } else {
                return false;
            }
        }
        else {
            return false;
        }
    }

}
