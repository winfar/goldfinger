<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 用户首页控制器
 */
class PublicController extends Controller {

	 protected function _initialize(){
        $config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =  config_lists();
            S('DB_CONFIG_DATA',$config);
        }
        C($config);
        if(!C('WEB_SITE_CLOSE')){
            $this->error('站点已经关闭，请稍后访问~');
        }
		$this->webpath=__ROOT__."/";
		$this->webtitle=C("WEB_SITE_TITLE");
		$this->weblogo=C("WEB_LOGO");
		$this->keywords=C("WEB_SITE_KEYWORD");
		$this->description=C("WEB_SITE_DESCRIPTION");
		$this->icp=C("WEB_SITE_ICP");
		$this->weburl=C("WEB_URL");
		$this->webname=C("WEB_NAME");
	}
    /**
     * 验证码检查
     */
  	private  function check_verify($code, $id = ""){
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }
	
	public function login($username = null, $password = null){
        if(IS_POST){
            // 检查验证码
            $verify = I('param.verify','');
            if(!$this->check_verify($verify) && isHostProduct()){
                $this->error("亲，验证码输错了哦！",$this->site_url,3);
            }
            $uid = D('Public')->login($username, $password);
//            select * from hx_role r, hx_member_role  mr where  r.id = mr.roleID and mr.userID = '';
            $Role = D();
            $role_rs = $Role->table('bo_role r, bo_member_role  mr')->where(' r.id = mr.roleID and mr.userID ='.$uid)->field('mr.roleID,r.rolename')->find();

            $channel = M();
            $c_rs = $channel->field('c.id,c.channel_level,c.channel_name,c.pid,c.root_name')->table(array('bo_member'=>'u','bo_channel'=>'c'))->where('u.channel_id = c.id and u.id=%s ',$uid)->find();

            if(0 < $uid){
                cookie('roleId',$role_rs['roleID']);
                cookie('rolename',$role_rs['rolename']);
                cookie('channel_id',$c_rs['id']);
                cookie('channel_name',$c_rs['channel_name']);
                cookie('channel_level',$c_rs['channel_level']);
                cookie('channel_root',$c_rs['root_name']);

                if(cookie('rolename') === '渠道'){
                    //【渠道】角色用户，默认打开渠道列表页面
                    $this->success('登录成功！', U('Channel/index'));
                }else{
                    //系统用户默认打开欢迎页面
                    $this->success('登录成功！', U('/Wx/welcome'));
                }

            } else { //登录失败
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    default: $error = '未知错误！'; break;
                }
                $this->error($error);
            }
        } else {
            if(is_login()){
                $this->redirect('/Wx/welcome');
            }else{

                $this->display();
            }
        }
    }

    public function verify_c(){
        $Verify = new \Think\Verify();
        $Verify->fontSize = 20;
        $Verify->length   = 4;
        $Verify->expire = 600;
        $Verify->entry();
    }

    /* 退出登录 */
    public function logout(){
        if(is_login()){
            D('Public')->logout();
            session('[destroy]');
            cookie(null);
            $this->success('退出成功！', U('login'));
        } else {
            $this->redirect('login');
        }
    }
	
    public function getpass(){
        if(IS_POST){
            D('Public')->logout();
            $this->success('退出成功！', U('login'));
        } else {
            $this->display();
        }
    }


    /**
     * 重新加载用户session
     * @param $names array 数组session对象名
     */
    public function reloadUserSession($names=null){
        $uid = is_login();
        if($uid > 0 ){
            
            $auth = cookie('user_auth');
            $data_auth_sign = cookie('user_auth_sign');
            cookie(null);

            cookie('user_auth', $auth);
            cookie('user_auth_sign', $data_auth_sign);

            $Role = D();
            $role_rs = $Role->table('bo_role r, bo_member_role  mr')->where(' r.id = mr.roleID and mr.userID ='.$uid)->field('mr.roleID,r.rolename')->find();

            $channel = M();
            $c_rs = $channel->field('c.id,c.channel_level,c.channel_name,c.pid,c.root_name')->table(array('bo_member'=>'u','bo_channel'=>'c'))->where('u.channel_id = c.id and u.id=%s ',$uid)->find();

            cookie('roleId', $role_rs['roleID']);
            cookie('rolename', $role_rs['rolename']);
            cookie('channel_id', $c_rs['id']);
            cookie('channel_name', $c_rs['channel_name']);
            cookie('channel_level', $c_rs['channel_level']);
            cookie('channel_root', $c_rs['root_name']);
//            $this->redirect(U('edit'),2,'Session数据重新加载，页面跳转中。。。');
        }
    }
}
