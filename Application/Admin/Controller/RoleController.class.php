<?php
namespace Admin\Controller;

use Think\Storage;

class RoleController extends WebController
{

    /**
     * 角色列表
     */
    public function index()
    {
        $map = array();
        if ( isset($_GET['keyword']) ) {
            $map['rolename'] = array('like', '%' . I('keyword') . '%');
        }
        $list = $this->lists('Role', $map);
        $this->assign('_list', $list);
        $this->assign('keyword', I('keyword'));
        $this->meta_title = '角色列表';
        $this->display();
    }

    public function add()
    {
        if ( IS_POST ) {
            $roleName = I('rolename');
            if ( $roleName == "" ) {
                $this->error('角色名称不能为空');
            }
            if ( M('Role')->where(array('rolename' => $roleName))->count('id') > 1 ) {
                $this->error('角色名称已被占用！');
            }
            $id = I('hidId');

            $data['rolename'] = $roleName;
            $data['status'] = I('status');
            $data['note'] = I('note');
            if ( $id != "" ) {
                $data['id'] = $id;
                $count = D('Role')->edit($data);
            } else {
                $count = D('Role')->addRole($data);
            }

            if ( is_numeric($count) ) {
                $this->success('角色编辑成功！', U('Role/index'));
            } else {
                $this->error(D('Role')->getError());
            }
        } else {
            $id = I('id');
            if ( $id ) {
                $map['id'] = $id;
                $list = $this->lists('Role', $map);
                $this->assign('info', $list[0]);
            }

            $this->display();
        }
    }

    public function del()
    {
        $id = array_unique((array)I('id', 0));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $mrs = M('member_role')->where('roleID=' . $id[0])->count('id');
        if ( $mrs ) {
            $this->error('有用户在用此角色，不能删除！');
        }
        $res = D('Role')->remove($id);
        if ( $res !== false ) {
            $this->success('删除角色成功！');
        } else {
            $this->error('删除角色失败！');
        }
    }
}