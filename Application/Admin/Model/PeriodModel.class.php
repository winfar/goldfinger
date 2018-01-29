<?php
namespace Admin\Model;

use Think\Model;

class PeriodModel extends Model
{

    public function express($id)
    {
        $info = M('shop_period')->field('express_name,express_no,uid')->where('id=' . $id)->find();
        $info['address'] = M('shop_address')->where('uid=' . $info['uid'])->order('`default` desc')->select();
        return $info;
    }

    public function update()
    {
        $rules = array(
            array('express_name', 'require', '快递公司不能为空'),
            array('express_no', 'require', '快递单号不能为空'),
        );
        if ( $data = M('shop_period')->validate($rules)->create() ) {
            
            //更改发货状态，101:已发货
            $data['order_status'] = 101;
            $order_status_time = M('shop_period')->where('id='.$data['id'])->getField('order_status_time');
            $data['order_status_time'] = $order_status_time . ',' . time();

            return M('shop_period')->save($data);
        } else {
            return $this->getError(); //错误详情见自动验证注释
        }
    }

    /* 更新用户晒单 */
    public function shared($pid)
    {
        $rules = array(
            array('content', 'require', '晒单内容不能为空！')
        );
        if ( !$data = M('shop_shared')->validate($rules)->create() ) {
            $this->error = M('shop_shared')->getError();
            return false;
        }
        $pic = array_unique((array)$data['pic']);
        if ( empty($pic) ) {
            $this->error = '至少要有一张晒单图片!';
            return false;
        }
        $uid = M('shop_period')->where('id=' . $pid)->getField('uid');
        $data['pid'] = $pid;
        $data['uid'] = $uid;
        $data['pic'] = implode(',', $data['pic']);
        $data['thumbpic'] = implode(',', $data['thumbpic']);
        $data['create_time'] = NOW_TIME;
        $res = M('shop_shared')->add($data);
        M('shop_period')->where('id=' . $pid . " and uid=" . $uid)->setField("shared", 0);
        return $res;
    }

    public function getPeriodNoBySid($sid, $type)
    {
        if ( $type == "no" ) {
            $no = M('shop_period')->where(array('sid' => $sid))->max('no');//当前期数
            $result = $no;
        } else {
            $no = M('shop_period')->where(array('sid' => $sid))->count('id');//当前期数
            $result = $no;
        }

        return $result;
    }

    public function getPeriodInfo($sid){
     $result=   M('shop_period')->where(array('sid' => $sid))->field('create_time,end_time,kaijang_num,state')->order('id desc')->find();//当前期数
        return $result;
    }

    public function user_num($uid, $pid)
    {
        $variable = M('shop_record')->field('group_concat(num) as num')->where("uid=" . $uid . " and pid=" . $pid)->group("uid,pid")->find();
        if ( $variable ) {
            return explode(',', $variable['num']);
        }
    }

    /**
     * 创建新的商品开奖周期
     * @param intger $shopId 商品id
     * @param intger $price  价格
     * @param intger $specialId 专区id(十元)
     * @param intger $houseid PK房间id，0为普通摸金
     */
    public function createPeriod($shopId,$price,$specialId,$houseid=0){

        if($shopId<=0){
            return false;
        }
        //升级经验价格可以为0
        // $prop_type = M('shop')->where('id='.$shopId)->getField('prop_type');
        // $prop = empty($prop_type) ? 0 : $prop_type;
        // if($price<=0 and $prop==0){
        //     return false;
        // }

        if($specialId<=0){
            return false;
        }

        $map =  array();
        $map['sid'] = $shopId;
        $map['state'] = 0;//进行中

        if($houseid==0){
            //普通摸金
            $map['iscommon'] = 1;   

            $period_rs = M('shop_period')->where($map)->select();
        }
        else{
            //pk
            $map['iscommon'] = 2;

            $period_rs == null;//pk无法确定当前周期是几人场，后续需要判断场次是否存在，再添加周期

            // SELECT p.id,sid,p.create_time,state,number,p.no,jiang_num,iscommon,house_id,h.id,h.no,h.ispublic,h.isresolving,h.shopid,h.pksetid,h.periodid,c.id,c.peoplenum,c.amount,c.inventory,c.shopid 
            // from hx_shop_period p,hx_house_manage h,hx_pkconfig c
            // where p.hosue_id=h.id and h.pksetid=c.id and p.sid=510
            // order by create_time desc;
        }

        if($period_rs === false){
            return false;
        }
        elseif($period_rs === null){
            $period = array();
            $period['iscommon'] = $map['iscommon'];
            $period['house_id'] = $houseid;

            $period['sid'] = $shopId;
            $period['create_time'] = time();
            $period['state'] = 0;

            //N元专区
            $unit = M('ten')->where(array('id' => $specialId, 'status' => 1))->getField('unit');
            // $period['jiang_num'] = $unit ? jiang_num($price / $unit - 1) : jiang_num($price - 1) ;
            $period['jiang_num'] = '';

            //最大期号
            $maxno = M('shop_period')->where('sid=' . $shopId)->max('no');
            $period['no'] = $maxno ? $maxno + 1 : 100001;

            $period_id = M('shop_period')->add($period);

            $map_shop['id']=$shopId;
            M('shop')->where($map_shop)->setDec('shopstock',1);

            return $period_id;
        }
        else {
            return $period_rs['id'];
        }
    }
}