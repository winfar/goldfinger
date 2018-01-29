<?php
/**
 * Author: wenyuan
 * Date: 2016-09-18
 * Description:
 */

namespace Admin\Model;

use Think\Model;

class MessageModel extends Model
{
    public function addMsg($data)
    {
        $data['create_time'] = time();
        $data['status'] = 1;
        $data['type'] = 101;
        $message = M('Message');
        $count = $message->data($data)->add();
        return $count;
    }

    /**
     * @deprecated 新增用户消息
     * @param $uid 用户id
     * @param $messageid 消息id
     * */
    public function addUserMessage($uid,$type,$itemid)
    {
        if(!empty($uid) && !empty($type)){
            $data['uid']=$uid;
            $data['typeid'] = $type;
            $data['itemid'] = $itemid;
            $data['messageid']= M('message')->where('type='.$type)->getField('id');
            $data['create_time']=time();
            
            $rs = M('message_user')->add($data);

            return $rs;
        }
        else {
            return null;
        }
    }
    /**
     * @deprecated 获取用户消息
     * @param $uid 用户id
     * */
    public function getListByUserId($uid){
        $rs = array();
        if($uid>0){
            $msg = M("message m")->field('IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) msg_time,IFNULL(mu.isread,1) isread,mu.itemid,IFNULL(m.link,mu.itemid) link')
            ->join('LEFT JOIN __MESSAGE_USER__ mu ON m.id=mu.messageid')
            ->where('m.status=1 AND mu.status=1 and mu.uid='.$uid.' OR type=0 OR type=101 and m.`status`=1')
            ->order('msg_time DESC')
            ->select();
            foreach ($msg as $key => $value) {
                switch ($value['type']) {
                    case 102:
                        # 恭喜您成功购得 【第${no}期】【${name}】~
                        $message = M('message m')->field('IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) create_time,IFNULL(mu.isread,1) isread,mu.itemid,s.`name`,p.id pid,p.`no`')
                        ->join('LEFT JOIN __MESSAGE_USER__ mu ON m.id=mu.messageid')
                        ->join('LEFT JOIN __SHOP_PERIOD__ p ON mu.itemid=p.id and mu.uid=p.uid')
                        ->join('LEFT JOIN __SHOP__ s ON s.id=p.sid')
                        ->where('m.`status`=1 AND mu.`status`=1 and mu.uid=' . $uid .' and p.id=' . $value['itemid'])
                        ->order('m.sort, mu.create_time DESC')
                        ->find();
                        if($message){
                            $value['title'] = str_replace('${no}',$message['no'],$value['title']);
                            $value['title'] = str_replace('${name}',$message['name'],$value['title']);
                            $value['content'] = str_replace('${no}',$message['no'],$value['title']);
                            $value['content'] = str_replace('${name}',$message['name'],$value['title']);
                        }
                        break;
                    case 104:
                        # 恭喜您成功购得 【第${no}期】【${name}】~
                        $message = M('message m')->field('IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) create_time,IFNULL(mu.isread,1) isread,mu.itemid,s.`name`,p.id pid,p.`no`')
                        ->join('LEFT JOIN __MESSAGE_USER__ mu ON m.id=mu.messageid')
                        ->join('LEFT JOIN __SHOP_PERIOD__ p ON mu.itemid=p.id')
                        ->join('LEFT JOIN __SHOP__ s ON s.id=p.sid')
                        ->where('m.`status`=1 AND mu.`status`=1 and mu.uid=' . $uid .' and p.id=' . $value['itemid'])
                        ->order('m.sort, mu.create_time DESC')
                        ->find();
                        if($message){
                            $value['title'] = str_replace('${no}',$message['no'],$value['title']);
                            $value['title'] = str_replace('${name}',$message['name'],$value['title']);
                            $value['content'] = str_replace('${no}',$message['no'],$value['title']);
                            $value['content'] = str_replace('${name}',$message['name'],$value['title']);
                        }
                        break;
                    case 103:
                        # 您在 第${no}期${name}商品中有${count}人次参与失败，已退款${gold}金币给您~
                        $order = M('shop_order o')
                        ->table('__SHOP_ORDER__ o, __SHOP_PERIOD__ p, __SHOP__ s,__TEN__ ten')
                        ->field("p.id,o.uid,o.create_time,FROM_UNIXTIME(o.create_time) order_create_time,o.number,o.gold,o.cash,CONVERT((gold+cash)/unit,SIGNED) cnt, if(o.`code`='FAIL',o.number,(CONVERT(((gold+cash)/unit-o.number),SIGNED))) as fail_cnt,if(o.`code`='FAIL',o.cash,(CONVERT(((gold+cash)/unit-o.number)*unit,SIGNED))) as backgold,s.`name`,p.`no` ,o.`code`")
                        ->where('o.pid=p.id and s.id=p.sid and s.ten=ten.id and o.pid=' . $value['itemid'] . ' and o.uid='.$uid)
                        ->having('backgold>0')
                        ->order('o.create_time DESC')
                        ->find();
                        $value['title'] = str_replace('${no}',$order['no'],$value['title']);
                        $value['title'] = str_replace('${name}',$order['name'],$value['title']);
                        $value['title'] = str_replace('${count}',$order['fail_cnt'],$value['title']);
                        $value['title'] = str_replace('${gold}',$order['backgold'],$value['title']);
                        $value['content'] = str_replace('${no}',$order['no'],$value['title']);
                        $value['content'] = str_replace('${count}',$order['fail_cnt'],$value['title']);
                        $value['content'] = str_replace('${no}',$order['no'],$value['title']);
                        $value['content'] = str_replace('${gold}',$order['backgold'],$value['title']);
                        break;
                    default:
                        # code...
                        break;
                }
                $rs[$key] = $value;
            }

        }else {
            $rs = M("message")->field('id,type,title,content,create_time msg_time,1 isread,link')
            ->where('status=1 AND type<=101')
            ->order('msg_time DESC')
            ->select();
        }

        return $rs;
    }
}