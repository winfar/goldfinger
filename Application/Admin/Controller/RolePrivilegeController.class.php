<?php
namespace Admin\Controller;
use Think\Db;
use Vendor\Database;

/**
 * 角色权限控制器
 * 角色直接关联菜单
 */
class RolePrivilegeController extends WebController{


    public function index(){

    }

    public function edit($roleId = 0 ){
        //优先使用传入的roleId，未传入roleId时从session中获取roleId
        if($roleId == 0){
          $roleId = cookie('roleId') ;
        }

        $RolePrivilege = D('RolePrivilege');
        if(IS_POST){ //提交表单
            $checkbox = $_POST['tables'];
            $this->del($roleId);
            /*如果要获取全部数值*/
            for($i=0;$i<=count($checkbox);$i++)
            {
                if(!is_null($checkbox[$i]))
                {  //保存插入数据库
                    $RolePrivilege = M("RolePrivilege"); // 实例化角色权限关系对象
                    $data['roleID'] = $roleId;
                    $data['privilegeID'] = $checkbox[$i];
                    $RolePrivilege->add($data);
                }
            } 
            $result = R('Public/reloadUserSession');
            $this->success('保存成功！');
        } else { 
            if($roleId){
                /* 获取角色权限（菜单）信息 */
               $tMenu =  $this->getTree($roleId);
            }
            /* 获取栏目信息 */
            $this->assign('_list',   $tMenu);
            $this->assign('roleId',$roleId);
            $this->meta_title = '权限管理——角色列表—权限设置';
            $this->display();
        }
    }

    public function del($roleId = 0){
        if ( empty($roleId) ) {
            $this->error('请选择要操作的角色!');
        }
        $res =  M('RolePrivilege')->where('roleId='.$roleId)->find();
        if(!$res)
            return;
        $res = M('RolePrivilege')->where('roleId='.$roleId)->delete();
        if($res == false){
            $this->error('删除角色权限关系失败！');
        }
    }

}
