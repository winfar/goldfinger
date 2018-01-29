<?php
namespace Admin\Controller;

use Think\Storage;

class MemberController extends WebController
{
    /**
     * 管理员管理首页
     */
    public function index()
    {
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        $map = array();
        $conditionarr = array();
        //管理员名称/角色名称
        if ( isset($_GET['keyword']) ) {
            $map['username'] = I('keyword');
            $map['rolename'] = I('keyword');
            $conditionarr['keyword'] = I('keyword');
        }
        $map['channel_id'] = $channelId;
        //渠道
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel_id'] = I('channel');
            $conditionarr['channel'] = I('channel');
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $total = D('Member')->getMembersTotal($map);;
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Member')->getMembers($map);
        $this->assign('_list', $list);
        $this->assign('channelId', $channelId);
        $this->assign('category', D('Channel')->getTree($channelId));
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '管理员列表';
        $this->display();
    }

    /**
     * 修改密码
     */
    public function password()
    {
        if ( IS_POST ) { //提交表单
            if($_POST['channel_id'] and $_POST['channel_id'] < 0){
                $this->error('请选择渠道名称');
            }
            if(!$this->check_name(I('phone'))){
                $this->error('请正确输入注册邮箱或手机号');
            }
            //获取参数
            $param['id'] = I('post.userid');
            $param['roleid'] = I('post.roleid');
            $res = D('Member')->password($param);
            if ( $res !== false ) {
                $this->success('修改成功！', U('index'));
            } else {
                $this->error(D('Member')->getError());
            }
        } else {
            $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
            $channelId = D('Channel')->ischannelid($channel_id);
            $userid = I('get.id');
            $users = M('Member')->where('id=' . $userid)->find();
            $this->assign('users', $users);

            $roleid = I('get.roleid');
            $roles = D('Role')->getRoles('');
            $this->assign('roles', $roles);
            $this->assign('roleidtag', $roleid);
            $this->assign('category', D('Channel')->getTree($channelId));
            $this->meta_title = '修改密码';
            $this->display();
        }
    }

    public function add()
    {
        if ( IS_POST ) {
            if($_POST['channel_id'] and $_POST['channel_id'] < 0){
                $this->error('请选择渠道名称');
            }
            if(!$this->check_name(I('phone'))){
                $this->error('请正确输入联系电话');
            }
//            if ( I("password") != I("repassword") ) {
//                $this->error('密码和重复密码不一致！');
//            }
            if ( M('Member')->where(array('username' => I("username")))->getField('id') ) {
                $this->error('用户名已被占用！');
            }
            $param = array();
            $param['roleid'] = I('post.roleid');

            $uid = D('Member')->reg($param);
            if ( is_numeric($uid) ) {
                $this->success('用户添加成功！', U('Member/index'));
            } else {
                $this->error(D('Member')->getError());
            }
        } else {
            $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
            $channelId = D('Channel')->ischannelid($channel_id);
            $id = I('roleid');
            $roles = D('Role')->getRoles($id);
            $this->assign('roles', $roles);
            $this->assign('category', D('Channel')->getTree($channelId));
            $this->display();
        }
    }

    /**
     * 删除管理员
     */
    public function del()
    {
        $id = array_unique((array)I('id', 0));

        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map = array('id' => array('in', $id));
        $rolemap = array('userID' => array('in', $id));
        M('MemberRole')->where($rolemap)->delete();

        if ( M('Member')->where($map)->delete() ) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    protected function check_name($username){
        if(is_numeric($username)){
            return (ereg("^(0|86|17951)?(13[0-9]|15[012356789]|18[0-9]|14[57])[0-9]{8}$",$username));
        }else{
            return (ereg("^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+",$username));
        }
    }
}