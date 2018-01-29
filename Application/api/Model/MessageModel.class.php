<?php
namespace api\Model;
use Think\Model;

/**
 * 消息模型
 */
class MessageModel extends Model{

    /**
     * @deprecated 新增消息
     * @param $type 消息类型
     * @param $title 消息标题
     * @param $content 消息内容
     * */
    public function addMessage($type,$title,$content)
    {
        if(!empty($type) && !empty($title)){
            $data['type']=$type;
            $data['title']=$title;
            $data['content']=$content;
            $data['create_time']=time();
            $data['status']=1;
            
            $rs = M('message')->add($data);

            return $rs;
        }
        else {
            return null;
        }
    }

    /**
     * @deprecated 删除消息
     * @param $messageid 消息id
     * */
    public function deleteMessage($messageid)
    {
        $rs = M('message')->delete($messageid);
        return $rs;
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
            $data['messageid']= M('message')->where('type='.$type)->getField('id');
            $data['type'] = $type;
            $data['itemid'] = $itemid;
            $data['create_time']=time();
            
            $rs = M('message_user')->add($data);

            return $rs;
        }
        else {
            return null;
        }
    }

    /**
     * @deprecated 新增用户列表消息
     * @param $uid 用户id
     * @param $messageid 消息id
     * */
    public function addAllUserMessage($type,$pid,$uidArr)
    {
        $messageid = M('message')->where('type='.$type)->getField('id');

        if($messageid){
            foreach ($uidArr as $key => $value) {
                $dataList[$key] = array('uid'=>$value['uid'],'type'=>$type,'itemid'=>$pid,'messageid'=>$messageid,'create_time'=>time());
            }
            // 批量添加数据
            $rs = M('message_user')->addAll($dataList);
            return $rs;
        }
        else{
            return null;
        }
    }

    /**
     * @deprecated 删除用户消息
     * @param $messageid 消息id
     * */
    public function deleteUserMessage($id)
    {
        $rs = D('message_user')->delete($id);
        return $rs;
    }

    /**
     * @deprecated 获取用户消息详情
     * @param $uid 用户id
     * @param $id 消息id
     * */
    public function getUserMessageDetails($uid,$id)
    {
        if(!empty($id) && !empty($uid)){
            $map_user=array('uid'=>$uid,'id'=>$id);
            $rs = M('message_user')->field(true)->where($map_user)->find();
            return $rs;
        }else {
            return null;
        }
    }

    /**
     * @deprecated 获取用户消息
     * @param $uid 用户id
     * */
    public function getListByUid($pageindex=1,$pagesize=20,$uid){
        $rs = array();
        if($uid>0){
            $msg = M("message m")->field('IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) msg_time,IFNULL(mu.isread,1) isread,mu.itemid,IFNULL(m.link,mu.itemid) link')
            ->join('LEFT JOIN __MESSAGE_USER__ mu ON m.id=mu.messageid')
            ->where('m.status=1 AND mu.status=1 and mu.uid='.$uid.' OR m.type=0 and m.`status`=1')
            ->order('msg_time DESC')
            ->page($pageindex,$pagesize)
            ->select();
            // SELECT IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) msg_time,IFNULL(mu.isread,1) isread,mu.itemid,IFNULL(m.link,mu.itemid) link 
            // from hx_message m
            // LEFT JOIN hx_message_user mu ON m.id=mu.messageid 
            // WHERE m.`status`=1 AND mu.`status`=1 and mu.uid='101900' OR type=0 and m.`status`=1
            // order by msg_time DESC

            foreach ($msg as $key => $value) {
                switch ($value['type']) {
                    case 101:
                        # 恭喜您获得{$product}，请在 “参与记录”领取！
                        $map_cnt['sr.uid'] = $uid;
                        $map_cnt['sr.pid'] = $value['itemid'];
                        $cnt = M('shop_record sr')
                        ->field('c.channel_name,c.proportion, u.nickname,p.`no`, sum(sr.number) cnt,(c.proportion*sum(sr.number)/1000) gold')
                        ->join('LEFT JOIN __SHOP_PERIOD__ p ON p.id = sr.pid')
                        ->join('LEFT JOIN __USER__ u ON u.id=sr.uid')
                        ->join('LEFT JOIN __CHANNEL__ c ON c.id=u.channelid')
                        ->where($map_cnt)->find();

                        // select c.channel_name,c.proportion, u.nickname,p.`no`, sum(sr.number) cnt,(c.proportion*sum(sr.number)/1000) gold
                        // from bo_shop_record sr
                        // LEFT JOIN bo_shop_period p ON p.id = sr.pid
                        // LEFT JOIN bo_user u ON u.id = sr.uid
                        // LEFT JOIN bo_channel c on c.id=u.channelid
                        // WHERE sr.uid = 1587
                        // and sr.pid = 4

                        // $sql = M()->getLastSql();
                        if($cnt){
                            $cnt_str = $cnt['proportion']*$cnt['cnt']/1000 . '克黄金（'.$cnt['no'].'期）';
                            $value['title'] = str_replace('${product}',$cnt_str,$value['title']);
                            $value['content'] = str_replace('${product}',$cnt_str,$value['content']);

                        }
                        else{
                            continue;
                        }
                        break;
                    case 102:
                        # 尊敬的用户，你发起的提金业务已受理，流水号{$order_id}。
                        $map_uc['id'] = $value['itemid'];
                        $usercash = M('user_cash')->where($map_uc)->find();
                        
                        if($usercash){
                            $value['title'] = str_replace('${order_id}',$usercash['order_id'],$value['title']);
                            $value['content'] = str_replace('${order_id}',$usercash['order_id'],$value['content']);
                        }
                        else{
                            continue;
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

                        // echo M()->getLastSql();exit();

                        // select p.id,o.uid,o.create_time,FROM_UNIXTIME(o.create_time) order_create_time,o.number,o.gold,o.cash,CONVERT((gold+cash)/unit,SIGNED) cnt, if(o.`code`='FAIL',o.number,(CONVERT(((gold+cash)/unit-o.number),SIGNED))) as fail_cnt,if(o.`code`='FAIL',o.cash,(CONVERT(((gold+cash)/unit-o.number)*unit,SIGNED))) as backgold,s.`name`,p.`no` ,o.`code`
                        // from hx_shop_order o,hx_shop_period p,hx_shop s,hx_ten ten
                        // where o.pid=p.id and s.id=p.sid and s.ten=ten.id and o.pid=1079 AND o.uid=101902
                        // HAVING backgold>0 
                        // order by o.create_time DESC

                        if($order){
                            $value['title'] = str_replace('${no}',$order['no'],$value['title']);
                            $value['title'] = str_replace('${name}',$order['name'],$value['title']);
                            $value['title'] = str_replace('${count}',$order['fail_cnt'],$value['title']);
                            $value['title'] = str_replace('${gold}',$order['backgold'],$value['title']);
                            $value['content'] = str_replace('${no}',$order['no'],$value['title']);
                            $value['content'] = str_replace('${name}',$order['name'],$value['title']);
                            $value['content'] = str_replace('${count}',$order['fail_cnt'],$value['title']);
                            $value['content'] = str_replace('${gold}',$order['backgold'],$value['title']);
                        }
                        else{
                            continue;
                        }
                        break;
                    case 104:
                        # 您参与的${name}（第${no}期） 即将揭晓！
                        $message = M('message m')
                        ->field('IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) create_time,IFNULL(mu.isread,1) isread,mu.itemid,s.`name`,p.id pid,p.`no`,p.iscommon,p.house_id')
                        ->join('LEFT JOIN __MESSAGE_USER__ mu ON m.id=mu.messageid')
                        ->join('LEFT JOIN __SHOP_PERIOD__ p ON mu.itemid=p.id')
                        ->join('LEFT JOIN __SHOP__ s ON s.id=p.sid')
                        ->where('m.`status`=1 AND mu.`status`=1 and mu.uid=' . $uid .' and p.id=' . $value['itemid'])
                        ->order('m.sort, mu.create_time DESC')
                        ->find();

                        // SELECT IFNULL(mu.id,0) id,m.type,m.title,m.content,IFNULL(mu.create_time,m.create_time) create_time,IFNULL(mu.isread,1) isread,mu.itemid,s.`name`,p.id pid,p.`no`
                        // from hx_message m
                        // LEFT JOIN hx_message_user mu ON m.id=mu.messageid 
                        // LEFT JOIN hx_shop_period p ON mu.itemid=p.id
                        // LEFT JOIN hx_shop s ON s.id=p.sid
                        // WHERE m.`status`=1 AND mu.`status`=1 and mu.uid=101911
                        // order by m.sort, mu.create_time DESC

                        //cho M()->getLastSql();exit();
                        if($message){
                            $value['title'] = str_replace('${no}',$message['no'],$value['title']);
                            $value['title'] = str_replace('${name}',$message['name'],$value['title']);
                            $value['content'] = str_replace('${no}',$message['no'],$value['title']);
                            $value['content'] = str_replace('${name}',$message['name'],$value['title']);

                            //pk
                            if($message['iscommon']==2){
                                $value['ispk'] = true;
                                $value['houseid'] = $message['house_id'];
                            }else{
                                $value['ispk'] = false;
                            }
                        }
                        else{
                            continue;
                        }
                        break;
                    case 105:
                        # 亲，您参与的${name}商品，已下架，支付的金额将以金币方式返还给您，请在“我的金币”中查看，感谢您的参与！
                        $period = M('shop_period o')
                        ->table('__SHOP_PERIOD__ p, __SHOP__ s')
                        ->field('s.name')
                        ->where('p.sid=s.id and p.id=' . $value['itemid'])
                        ->find();

                        if($period){
                            $value['title'] = str_replace('${name}',$period['name'],$value['title']);
                            $value['content'] = str_replace('${name}',$period['name'],$value['title']);
                        }
                        else{
                            continue;
                        }
                        break;
                    case 106:
                        # 你创建的“${name}”PK房间发布成功！房间号${houseno}，邀请码${invitecode}。
                        $period = M('shop_period o')
                        ->table('__SHOP_PERIOD__ p, __SHOP__ s,__HOUSE_MANAGE__ h')
                        ->field('s.name,p.iscommon,p.house_id,p.id as pid,h.no,h.invitecode')
                        ->where('p.sid=s.id and p.house_id=h.id and p.id=' . $value['itemid'])
                        ->find();

                        if($period){
                            $value['title'] = str_replace('${name}',$period['name'],$value['title']);
                            $value['title'] = str_replace('${houseno}',$period['no'],$value['title']);
                            $value['title'] = str_replace('${invitecode}',$period['invitecode'],$value['title']);
                            $value['content'] = str_replace('${name}',$period['name'],$value['title']);
                            $value['content'] = str_replace('${houseno}',$period['no'],$value['title']);
                            $value['content'] = str_replace('${invitecode}',$period['invitecode'],$value['title']);

                            $value['pid'] = $period['pid'];
                            //pk
                            if($period['iscommon']==2){
                                $value['ispk'] = true;
                                $value['houseid'] = $period['house_id'];
                            }else{
                                $value['ispk'] = false;
                            }

                        }
                        else{
                            continue;
                        }
                        break; 
                    case 107:
                        # 由于72小时内${houseno}房间第${houseissue}期参与人数未满已解散，您参与的金额已退还到您金币账户!
                        $period = M('shop_period o')
                        ->table('__SHOP_PERIOD__ p, __SHOP__ s,__HOUSE_MANAGE__ h')
                        ->field('h.no houseno,p.no')
                        ->where('p.sid=s.id and p.house_id=h.id and p.id=' . $value['itemid'])
                        ->find();

                        if($period){
                            $value['title'] = str_replace('${houseno}',$period['houseno'],$value['title']);
                            $value['title'] = str_replace('${houseissue}',$period['no'],$value['title']);
                            $value['content'] = str_replace('${houseno}',$period['houseno'],$value['title']);
                            $value['content'] = str_replace('${houseissue}',$period['no'],$value['title']);
                        }
                        else{
                            continue;
                        }
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
            ->page($pageindex,$pagesize)
            ->select();
        }

        //用户消息全部更新为已读
        $map_mu['uid']=$uid;
        M('message_user')->where($map_mu)->setField('isread',1);

        return $rs;
    }
    /**
     * 中奖列表
     *
     * @param  integer $number [description]
     * @return [type]          [description]
     */
    public function msglist($number = 50)
    {
        $sql = "select period.id,period.uid,shop.name,user.nickname,period.no,(select sum(number) from bo_shop_order where pid = period.id and uid = period.uid) as total_number,(select sum(buy_gold) from bo_shop_order where pid = period.id) as total_buy_gold from bo_shop_period period INNER join bo_shop shop on period.sid = shop.id INNER join bo_user user on period.uid = user.id where period.state=2 and period.exchange_type=0 and period.kaijang_time < ".time()."  order by period.kaijang_time desc,period.id desc limit 0,".$number;
        $list = $this->query($sql, false);
        $data = array();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data[] = $value;
                $data[$key]['total_buy_gold'] = $value['total_buy_gold']/1000;
            }
        }
        return $data;
    }
}