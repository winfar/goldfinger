<?php
namespace api\Model;

use Think\Model;

class ShopPeriodModel extends Model
{

    /**
     * 是否已经兑换金币
     * @param $pid
     * @return mixed
     */
    public function isExchangeGold($pid){
        if(is_null($pid)){
           return null;
        }
        //兑换状态
        $map['id'] = $pid;
        $rs =  $this->where($map)->getField('status_gold');
        return $rs;
    }

    /**
     * 是否已经发送卡密
     * @param $pid
     * @return null  0 = 未发送 ； 1 = 已发送
     */
    public function isSendSN($pid){
        if(is_null($pid)){
            return null;
        }

        //兑换状态
        $Model = D();
        $map['sp.id'] = $pid;
        $rs = $Model->table('hx_shop_period sp ')
            ->field('c.issend,sp.card_id')
            ->join('LEFT JOIN hx_card c ON sp.card_id = c.id ')
            ->where($map) 
            ->find();
        return $rs;
    }
}
