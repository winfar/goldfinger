<?php
namespace Admin\Model;

use Think\Model;

class MemberModel extends Model
{
    protected $_validate = array(
        array('username', '5,30', '用户名长度必须在5-30个字符之间！', self::EXISTS_VALIDATE, 'length', self::MODEL_INSERT), //用户名长度不合法
//        array('oldpassword', 'require', '请输入原密码！', self::EXISTS_VALIDATE, 'regex', self::MODEL_UPDATE),
        array('password', '6,30', '密码长度必须在6-30个字符之间！', self::EXISTS_VALIDATE, 'length'),
        array('fullname', 'require', '请输入真实姓名！', self::EXISTS_VALIDATE, 'regex', self::MODEL_UPDATE),
        array('phone', 'require', '请输入手机号！', self::EXISTS_VALIDATE, 'regex', self::MODEL_UPDATE),
    );

    protected $_auto = array(
        array('password', 'think_admin_md5', self::MODEL_BOTH, 'function'),
        array('status', 'getStatus', self::MODEL_INSERT, 'callback')
    );

    //获取用户和角色信息
    public function getMembers($param = array())
    {
        $sql = "select member.username,member.id,fullname,phone,member.status,role.rolename,role.id as roleid,
		member.last_login_time,channel.channel_name from bo_member as member,bo_member_role as memberrole,
		bo_role as role,bo_channel as channel where member.id=memberrole.userID and memberrole.roleID=role.id and member.channel_id = channel.id
		 and (member.username like  '%" . $param['username'] . "%' or role.rolename like '%" . $param['rolename'] . "%') ";
        if (!empty($param['channel_id'])) {
            $sql .= " and member.channel_id = ".$param['channel_id'];
        } 
        $sql .= " order by member.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $users = $this->query($sql, false);
        return $users;
    }

    public function getMembersTotal($param = array())
    {
        $sql = "select count(member.id) as count from bo_member as member,bo_member_role as memberrole,
		bo_role as role where member.id=memberrole.userID and memberrole.roleID=role.id
		 and (member.username like  '%" . $param['username'] . "%' or role.rolename like '%" . $param['rolename'] . "%')";
        if (!empty($param['channel_id'])) {
            $sql .= " and member.channel_id = ".$param['channel_id'];
        }
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }

    public function info($uid)
    {
        $map['id'] = $uid;
        $map['status'] = 1;
        $info = $this->where($map)->field('id,username,last_login_time,fullname,phone')->find();
        return $info;
    }

    public function reg($param = array())
    {
        if ( $data = $this->create() ) {
            $data['last_login_time'] = time();
//            $data['password']=think_admin_md5($data['password']);
            $userid = $this->add($data);

            $memberRole = M('MemberRole');
            $roles = array();
            $roles['userID'] = $userid;
            $roles['roleID'] = $param['roleid'];
            $memberRole->add($roles);
            return $userid;
        } else {
            return $this->getError();
        }
    }

    public function password($param)
    {
        if ( !$data = $this->create() ) {
            return false;
        }
//        if ( I('post.password') !== I('post.repassword') ) {
//            $this->error = '您输入的新密码与确认密码不一致！';
//            return false;
//        }
//        if ( !$this->verifyUser($param['id'], I('post.oldpassword')) ) {
//            $this->error = '验证出错：密码不正确！';
//            return false;
//        }
        $this->id = $param['id'];
//        $this->password=think_admin_md5(I('post.password'));
        $res = $this->save();

        $mr = M('MemberRole')->where('userID=' . $param['id'])->setField('roleID', $param['roleid']);
        return $res;
    }

    protected function verifyUser($uid, $password_in)
    {
        $password = $this->getFieldById($uid, 'password');
        if ( think_ucenter_md5($password_in) === $password ) {
            return true;
        }
        return false;
    }

    protected function getBirthdayTime()
    {
        $birthday_time = I('post.birthday');
        return $birthday_time ? strtotime($birthday_time) : NOW_TIME;
    }

    protected function getStatus()
    {
        return true;
    }
}