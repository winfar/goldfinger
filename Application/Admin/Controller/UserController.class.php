<?php
namespace Admin\Controller;

class UserController extends WebController
{
    /**
     * 用户列表
     * 
     * @return [type] [description]
     */
    public function index()
    {

        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId);        

        $map = array();
        $conditionarr = array();
        //用户ID/用户名
        if ( isset($_GET['keyword']) ) {
            //$map['_string'] = "CONCAT_WS('-',id,nickname) like '%".I('keyword')."%'";
            $keyword = I('keyword');
            $map['name'] = $keyword;
            $conditionarr['keyword'] = $keyword;
        }
        //开始时间
        if(!empty(I('starttime'))){
            //$map['create_time']= array('egt',strtotime(I('starttime')));
            $starttime = I('starttime');
            $map['starttime'] = $starttime;
            $conditionarr['starttime'] = $starttime;
        }
        //结束时间
        if(!empty(I('endtime'))){
            //$map['create_time']= array('elt',strtotime(I('endtime'))+86400);
            $endtime = I('endtime');
            $map['endtime'] = $endtime;
            $conditionarr['endtime'] = $endtime;
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            //$map['_string'] = "CONCAT_WS('-',id,nickname) like '%".I('keyword')."%'";
            $channel = I('channel');
            $map['channel'] = $channel;
            $conditionarr['channel'] = $channel;
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $model = D('user');
        $total = $model->getUsersTotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        $list = $model->getUsersList($map);
        $this->assign('channelId', $channelId);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->assign('_channelList', $channelList);
        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        $this->display();
    }
    /**
     * 详情
     * @return [type] [description]
     */
    public function item()
    {
        //用户id
        $uid = empty($_GET['uid']) ? 0 : I('uid');
        if ($uid==0) {
            $this->error('非法请求', U('User/index'));
        }
        //类型(1参与详情2中奖纪录3.用户详情)
        $tid = 1;
        if (!empty($_GET['tid'])) {
            $tid = I('tid');
        }
        $map = array();
        $map['uid'] = $uid;
        $model = D('user');
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        if ($tid == 1) {//参与详情
            $result = $model->participation($map);
            $total = $result['count'];
        } elseif ($tid == 2) {//中奖纪录
            $result = $model->winlist($map);
            $total = $result['count'];
        } elseif ($tid == 3) {//提现纪录
            $result = $model->cashlist($map);
            $total = $result['count'];
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        $total = 0;
        $list = array();
        if ($tid == 1) {//参与详情
            $result = $model->participation($map);
            $list = $result['list'];
        } elseif ($tid == 2) {//中奖纪录
            $result = $model->winlist($map);
            $list = $result['list'];
        } elseif ($tid == 3) {//提现纪录
            $result = $model->cashlist($map);
            $list = $result['list'];
        }
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $this->assign('_list', $list);
        $this->assign('uid', $uid);
        $this->assign('tid', $tid);
        $this->meta_title = '用户管理 > 用户详情';
        $this->display();
    }

    public function password($id)
    {
        if ( IS_POST ) {
            $res = D('User')->password();
            if ( $res !== false ) {
                $this->success('修改密码成功！', U('index'));
            } else {
                $this->error(D('users')->getError());
            }
        } else {
            $nickname = M('User')->where("id=" . $id)->getField('nickname');
            $this->assign('nickname', $nickname);
            $this->meta_title = '修改密码';
            $this->display();
        }
    }

    /**
     * 用户编辑
     * 金币明细通过事务控制
     * @param $id
     */
    public function edit($id)
    {
        if ( IS_POST ) {
            //后台用户、所属角色、姓名、电话
            $uid = is_login();
            //保存金币明细
            $record = D('GoldRecord');
            $record->startTrans();

            $rs = $record->addAdminEdit($id,$uid);
            $uid = D('User')->edit();
            if ( is_numeric($uid) && $rs) {
                $record->commit();//成功则提交
                $this->success('用户修改资料成功！', U('index'));
            } else {
                $record->rollback();//不成功，则回滚
                $this->error(D('User')->getError());
            }
        } else {
            $info = D('User')->info($id);
            $this->assign('info', $info);
            $this->meta_title = '修改用户';
            $this->display();
        }
    }

    public function del()
    {
        $id = array_unique((array)I('id', 0));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }

        $point_map['user_id'] = $id;
        $rs_p = M('point_record')->where($point_map)->delete();

        $map = array('id' => array('in', $id));
        $rs_u = M('User')->where($map)->delete();


        if ( $rs_p && $rs_u ) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    public function record($id)
    {
        $map['uid'] = $id;
        $list = $this->lists('shop_record', $map, $order = '', $rows = 0, $base = '', $field = true);
        foreach ( $list as $k => $v ) {
            $period = M('shop_period')->where('id=' . $v['pid'])->field('sid,no,state,kaijang_time,kaijang_num')->find();
            $shop = M('shop')->where('id=' . $period['sid'])->field('name,price')->find();
            $list[$k]['no'] = $period['no'];
            $list[$k]['state'] = $period['state'];
            $list[$k]['kaijang_time'] = $period['kaijang_time'];
            $list[$k]['kaijang_num'] = $period['kaijang_num'];
            $list[$k]['name'] = $shop['name'];
            $list[$k]['price'] = $shop['price'];
        }
        $this->assign('_list', $list);
        $this->display();
    }

    public function period($id)
    {
        $map['uid'] = $id;
        $list = $this->lists('shop_period', $map, $order = '', $rows = 0, $base = '', $field = true);
        foreach ( $list as $k => $v ) {
            $shop = M('shop')->where('id=' . $v['sid'])->field('name,price')->find();
            $list[$k]['no'] = $v['no'];
            $list[$k]['kaijang_time'] = $v['kaijang_time'];
            $list[$k]['kaijang_num'] = $v['kaijang_num'];
            $list[$k]['name'] = $shop['name'];
            $list[$k]['price'] = $shop['price'];
            $list[$k]['number'] = M('shop_record')->where('uid=' . $id . ' and pid=' . $v['id'])->sum('number');
        }
        $this->assign('_list', $list);
        $this->display();
    }

    public function pay($id)
    {
        $map['uid'] = $id;
        $map['type'] = array('gt', 1);
        $list = $this->lists('shop_order', $map, $order = '', $rows = 0, $base = '', $field = true);
        $this->assign('_list', $list);
        $this->display();
    }

    public function activity($id)
    {
        $map['user_id'] = $id;
        $list = $this->lists('activity_log', $map, $order = '', $rows = 0, $base = '', $field = true);
        $this->assign('_list', $list);
        $this->display();
    }

    public function userpoint($id)
    { 
        $map['user_id'] = $id;         
        $list = $this->lists('point_record', $map, $order = '', $rows = 0, $base = '', $field = true);
        $this->assign('_list', $list);
        $this->display();
    }
}