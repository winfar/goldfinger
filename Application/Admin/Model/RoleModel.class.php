<?php
namespace Admin\Model;

use Think\Model;

// use Think\Storage;

class RoleModel extends Model
{
    public function addRole($data)
    {
        $role = M('Role');
        $count = $role->data($data)->add();
        return $count;
    }

    public function remove($id = null)
    {
        $map = array('id' => array('in', $id));
        return $this->where($map)->delete();
    }

    /**
     * 修改角色信息
     */
    public function edit($data)
    {
        $role = M('Role');
        $count = $role->save($data);
        return $count;
    }


    public function getRoles($id)
    {
        if ( $id == '' ) {
            $roles = M('Role')->field('rolename,id')->where(array('status' => 1))->select();
        } else {
            $roles = M('Role')->field('rolename,id')->where(array('status' => 1, 'id' => $id))->select();
        }

        return $roles;
    }
}