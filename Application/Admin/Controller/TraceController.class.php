<?php

/** * Created by PhpStorm. * User: wenyuan * Date: 2016/9/28 * Time: 15:51 */

namespace Admin\Controller;

class TraceController extends WebController {
    /**
     * 用户轨迹
     * @author liuwei
     */
    public function user(){
        //条件
        $where = array();
        $search = array();
        //uid搜索
        if (isset($_GET['uid'])) {
            $uid = intval($_GET['uid']);
            $where['id'] = $uid;
            $search['uid'] = $uid;
        }
        //手机号搜索
        if (!empty($_GET['phone'])) {
            $phone = trim($_GET['phone']);
            $where['phone'] = $phone;
            $search['phone'] = $phone;
        }
        //用户详情
        $item = D('user')->user_info($search,'id,nickname,phone,black,total_point,create_time,province,city');
        $uid = empty($item) ? 0 : intval($item['id']);//用户id
        //金币收入明细
        $gold_list = D('user')->user_gold_info($uid);
        //用户金币总和
        $total_gold = 0;
        if (!empty($gold_list)) {
            $gold = array_column($gold_list, 'gold');
            $total_gold = array_sum($gold);
        }
        //用户积分明细
        $point_list = D('user')->user_point_list($uid);
        //用户积分总和
        $total_point = 0;
        if (!empty($point_list)) {
            $point = array_column($point_list, 'point');
            $total_point = array_sum($point);
        }
        //用户购买记录
        $record_list = D('user')->user_record_list($uid);
        //用户获奖记录
        $period_list = D('order')->lottery_order(array('uid'=>$uid));
        //用户分享记录
        $shared_list = D('user')->user_shared_list($uid);
        //用户红包记录
        $envelope_list = D('user')->user_envelope_list($uid);
        //用户临时订单记录
        $temporary_list = D('user')->user_temporary_list($uid);
        //用户消息记录
        $message_list = D('message')->getListByUserId($uid);
        //用户签到记录
        $checkin_list = $list = M('checkin_record')->where('uid='.$uid)->order('create_time desc')->select();

        $this->assign('_total_gold',$total_gold);
        $this->assign('_total_point',$total_point);
        $this->assign('_shared_list',$shared_list);
        $this->assign('_period_list',$period_list);
        $this->assign('_record_list',$record_list);
        $this->assign('_point_list',$point_list);
        $this->assign('_envelope_list',$envelope_list);
        $this->assign('_temporary_list',$temporary_list);
        $this->assign('_checkin_list',$checkin_list);
        $this->assign('_gold',$gold_list);
        $this->assign('_message_list',$message_list);
        $this->assign('_user', $item);
        $this->display();
    }
    
    public function period(){
        //$this->meta_title = '红包列表';

        $model = M('shop_period');

        $map=array();
        if(I('keyword')!==''){
            $map['pid']= I('keyword');
            $period = $model->field('s.name,s.price,ten.title,p.*')->table('__SHOP_PERIOD__ p, __SHOP__ s,__TEN__ ten')->where('s.id=p.sid and s.ten=ten.id and p.id='.$map['pid'])->find();
            if($period){
                $this->assign('_period', $period);

                $orderlist = M('shop_order')->where($map)->order('create_time,id')->select();
                $this->assign('_orderlist', $orderlist);

                $recordlist = M('shop_record')->where($map)->order('create_time,id')->select();
                $this->assign('_recordlist', $recordlist);
            }
        }

        // if(I('category')!==''){
        //     $map['category']= I('category');
        // }
        // if(!empty(I('keyword'))){
        //     $map['name']=array('like','%'.I('keyword').'%');
        // }
        // if(!empty(I('starttime'))){
        //     $map['begin_time']= array('egt',strtotime(I('starttime')));
        // }
        // if(!empty(I('endtime'))){
        //     $map['end_time']=  array('lt',strtotime(I('endtime')));
        // }

        //$list = $this->lists('RedEnvelope', $map,'create_time desc');

        $this->display();
    }

    public function errororder(){
        $flag_error = false;
        if(I('keyword')!==''){
            $orderid_list = explode(',',I('keyword'));

            $c_order['order_id'] = array ('in',$orderid_list);
            $orders = M('shop_order')->where($c_order)->select();

            foreach ($orders as $key => $value) {
                //$map['order_id'] = $value['order_id'];

                $c_shop_record = M('shop_record')->where('order_id='.$value['order_id'])->find();
                if($c_shop_record){
                    $period = M('shop_period')->where('id='.$value['pid'])->find();
                    if($period){
                        //充值暂不处理
                        if($value['pid'] == 0){
                            continue;
                        }
                        //有金币支付暂不处理
                        if($value['gold'] > 0){
                            continue;
                        }
                        //购买失败暂不处理
                        if($value['code'] != 'OK'){
                            continue;
                        }
                        //非盛付通支付暂不处理
                        if($value['type'] != 4){
                            continue;
                        }
                        //已下架暂不处理
                        if($period['state'] == 3){
                            continue;
                        }
                        //已开奖暂不处理
                        if($period['state'] == 2){
                            continue;
                        }
                        $shop = M('shop')->where('id='.$period['sid'])->find();

                        if($shop){
                            $unit = M('ten')->where('id='.$shop['ten'])->getField('unit');
                            $shop_count = $period['number'];
                            $record_number = $c_shop_record['number'];
                            $record_num = $c_shop_record['num'];

                            $model = M();
                            $model->startTrans();

                            $map['uid']=$value['uid'];
                            $map['pid']=$value['pid'];
                            $c_gold_record = M('gold_record')->where($map)->delete();

                            $map1['uid']=$value['uid'];
                            $map1['itemid']=$value['pid'];
                            $c_message_record = M('message_user')->where($map1)->delete();

                            //state=0,number=1594,kaijang_time=NULL, uid=0,kaijang_num=0,kaijiang_count=0,kaijiang_ssc=0,kaijiang_issue=0,
                            //jiang_num,end_time=0,order_status_time=null,contacts=NULL,phone=NULL,address=NULL where id=7651
                            $update_data['state']=0;
                            $update_data['number']=$shop_count-$record_number;
                            $update_data['kaijang_time']=NULL;
                            $update_data['uid']=0;
                            $update_data['kaijang_num']=0;
                            $update_data['kaijiang_count']=0;
                            $update_data['kaijiang_ssc']=0;
                            $update_data['kaijiang_issue']=0;

                            if($period['jiang_num']==''){
                                $update_data['jiang_num']=$record_num;
                            }
                            else{
                                $update_data['jiang_num']=$record_num . ',' . $period['jiang_num'];
                            }

                            $update_data['end_time']=0;
                            $update_data['order_status_time']=NULL;
                            $update_data['contacts']=NULL;
                            $update_data['phone']=NULL;
                            $update_data['address']=NULL;

                            $c_period_rs = M('shop_period')->where('id='.$value['pid'])->save($update_data);

                            $c_record_rs = M('shop_record')->where('order_id='.$value['order_id'])->delete();

                            $c_order_rs = M('shop_order')->where('order_id='.$value['order_id'])->delete();

                            if($c_period_rs!==false && $c_record_rs!==false && $c_order_rs!==false){
                                $model->commit();
                                //$this->success('处理成功');
                            }
                            else{
                                $model->rollback();
                                $flag_error = true;
                                //$this->error('处理失败');
                                $_result = 'order_id:'.$orders['order_id'] . '\n gold_record:'.$c_gold_record.'\n message_record:'.$c_message_record.'\n period_rs:'.$c_period_rs.'\n record_rs:'.$c_record_rs.'\n order_rs:'.$c_order_rs;
                                recordLog($_result,"订单处理失败");
                            }
                        }
                    }
                }
            }

            if($flag_error){
                $this->error('部分订单处理失败');
            }
            else{
                $this->success('处理成功');
            }
        }

        $this->display();
    }
}
