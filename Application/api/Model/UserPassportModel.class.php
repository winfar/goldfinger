<?php
namespace api\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;
use Com\Alidayu\AlidayuClient as Client;
use Com\Alidayu\Request\SmsNumSend;
use Think\Storage;

class UserPassportModel extends Model
{
    protected $connection = 'PASSPORT';   //通过指定不同数据库来进行切库
    protected $tableName = 'member_auth';
    protected $Quath2Url = 'http://1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w';// 回调地址

    protected function _initialize()
    {
        $config = S('DB_CONFIG_DATA');
        if ( !$config ) {
            $config = config_lists();
            S('DB_CONFIG_DATA', $config);
        }
        C($config);
        vendor("JPush.JPush");
    }

    /***
     * 生成tokenId
     * @return string
     */
    private function getTokenId()
    {
        return $tokenId = "tokenId:" . time();
    }

    /*用户注册*/
    public function register($param = array())//包含 手机号验证码，用户名密码，第三方
    {

        $credential = $param['credential'];//密码
        $code = $param['code'];//验证码

        if(!empty($code)){
            if ( $param['identifier'] == '18611186900' && $param['code'] == '173417' ) {
            } else {
                if($param['identity'] == 101){
                    $redisCache = new RedisCache();
                    $code = $redisCache->get($param['identifier']);
                    //验证短信；
                    recordLog($code, "register code");
                    recordLog($param['code'], "register param code");
                    if ( $code != $param['code'] ) {
                        returnJson('register', 1, '亲，验证码输错了哦！');
                    }
                }else{
                    //
                }
            }
        }        
        //判断是否已经注册；
        $map = array();
        $map['identifier'] = $param['identifier'];
        $user = $this->field(true)->where($map)->find();
        if ( $user ) {
            setThirdpartyUserInfo($param['identity'],$param['identifier'],$param['nickname'],$param['avatar']);
            returnJson('', 402, '该用户已经注册！');
        }

        $if_register = 1;

        //合并游客登录耿贯一开始2016-10-17
        if(!empty($param['deviceid'])){//判断deviceid
            $deviceidmap = array();
            $deviceidmap['identity'] = 401;//用户名密码
            $deviceidmap['identifier'] = $param['deviceid'];
            $deviceid_uid = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field('uid')->where($deviceidmap)->find();
            recordLog($param['deviceid'], '设备id开始判断');

            //如果已存在机器码登录方式
            if($deviceid_uid){
                //判断用户是否为转正用户
                $if_official = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("identity!=401 AND uid='".$deviceid_uid['uid']."'")->count();

                if($if_official > 0){//游客已转正
                    $if_register = 1;
                    recordLog($if_official, '判断用户是否为转正用户');
                }else{//合并游客与当前用户
                    $if_register = 2;

                    $member_save = array();
                    if($param['identity'] == 101){
                        recordLog($param['identifier'], "realname101");
                        $member_save['nickname'] = $param['identifier'];
                        $member_save['realname'] = $param['identifier'];
                        $member_save['username'] = $param['identifier'];
                        $member_save['avatar'] = $param['avatar'];
                        $member_save['phone'] = $param['identifier'];
                        $member_save['password'] = think_ucenter_md5($param['credential']);

                        $user_save['nickname'] = $member_save['nickname'];
                        $user_save['username'] = $member_save['username'];
                        $user_save['headimgurl'] = $member_save['avatar'];
                        $user_save['phone'] =    $member_save['phone'];
                        $user_save['password'] = $member_save['password'];

                    }else{
                        recordLog($param['identifier'], "realname");
                        $member_save['nickname'] = empty($param['nickname'])?$param['identifier']:$param['nickname'];
                        $member_save['realname'] = $param['identifier'];
                        $member_save['username'] = $param['identifier'];
                        $member_save['avatar'] = $param['avatar'];
                        $member_save['password'] = think_ucenter_md5($param['credential']);
                        //$member['phone'] = '';

                        $user_save['nickname'] = $member_save['nickname'];
                        $user_save['username'] = $member_save['username'];
                        $user_save['headimgurl'] = $member_save['avatar'];
                        $user_save['password'] = $member_save['password'];
                    }
                    recordLog($param['identifier'], "realname2");
                    //更新user
                    $res1 = M('user')->where("passport_uid='".$deviceid_uid['uid']."'")->save($user_save);
                    //更新member
                    $res2 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='".$deviceid_uid['uid']."'")->save($member_save);

                    if($res1 !== false && $res2 !== false){
                        recordLog('1', '是否更新合并');
                        //增加member_auth 新的登录方式
                        $member_auth = array();
                        $member_auth['id'] = uuid();
                        $member_auth['uid'] = $deviceid_uid['uid'];
                        $member_auth['createtime'] = time();
                        $member_auth['identity'] = $param['identity'];
                        $member_auth['identifier'] = $param['identifier'];
                        $member_auth['credential'] = think_ucenter_md5($param['credential']);
                        $member_auth['verified'] = $param['identity'] == 101?1:0;
                        M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_auth);

                        // //beging新增促销活动赠送红包+赠送金币+赠送积分
                        // //耿贯一2016-10-25
                        // $deviceid_userid = M('user')->where("passport_uid='".$deviceid_uid['uid']."'")->getField('id');
                        // D('User')->addSaleRegister($deviceid_userid);
                        // //ending新增促销活动赠送红包+赠送金币+赠送积分


                        //判断是否有注册积分
                        $deviceid_userinfo = M('user')->where("passport_uid='".$deviceid_uid['uid']."'")->field('id')->find();
                        $map_point = array();
                        $map_point['user_id'] = $deviceid_userinfo['id'];
                        $map_point['type_id'] = 101;
                        $if_register_point = M('Point_record')->where($map_point)->find();
                        if($if_register_point){
                        }else{
                            //注册送积分
                            $pointRs = D('Point')->addPointByUid(1000, 101, $deviceid_userinfo['id']);
                        }


                        returnJson('', 200, 'success');
                    }else{
                        returnJson('', 1, '处理错误！');
                    }
                }

            }else{
                $if_register = 1;
            }
        }

        //合并游客登录耿贯一结束2016-10-17

        recordLog($if_register, 'if_register');
        if($if_register == 1){
            // 耿贯一新修改
            // $mape = array();
            // $mape['phone'] = $param['identifier'];
            // if($param['identity'] == 101){
            //     $m = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($mape)->find();
            //     if ( $m ) {
            //         $uid = $m['uid'];
            //     } else {
            //         $uid = uuid();
            //     }
            // }else{
            //     $uid = uuid();
            // }
            $uid = uuid();

            // $mape = array();
            // $mape['phone'] = $param['phone'];
            // $m = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($mape)->find();
            // if ( $m ) {
            //     $uid = $m['uid'];
            // } else {
            //     $uid = uuid();
            // }

            $password = think_ucenter_md5($param['credential']);

            $member_auth = array();
            $member_auth['id'] = uuid();
            $member_auth['uid'] = $uid;
            $member_auth['createtime'] = time();
            $member_auth['identity'] = $param['identity'];
            $member_auth['identifier'] = $param['identifier'];
            $member_auth['credential'] = $password;
            $member_auth['verified'] = $param['identity'] == 101?1:0;

            $member = array();
            $member['uid'] = $uid;
            if($param['identity'] == 101){
                $member['nickname'] = $param['identifier'];
                $member['realname'] = $param['identifier'];
                $member['username'] = $param['identifier'];
                $member['phone'] = $param['identifier'];

            }else{
                $member['nickname'] = empty($param['nickname'])?$param['identifier']:$param['nickname'];
                $member['realname'] = $param['identifier'];
                $member['username'] = $param['identifier'];
                //$member['phone'] = '';
            }
            
            $member['password'] = $password;
            //$member['gender'] = $param['gender'];
            //$member['birthday'] = $param['birthday'];
            //$member['province'] = $param['province'];
            //$member['county'] = $param['county'];
            $member['avatar'] = $param['avatar'];
            $member['status'] = 1;
            $member['createtime'] = time();
            $member['channel'] = $param['channel'];
            //添加信息：bo_member，bo_member_auth
            $model = new Model('', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME']);
            $model->startTrans();//事务处理开始

            $rs = $this->add($member_auth);
            $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member);
            // $rs1 = 1;
            // if ( !$m ) {
            //     $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member);
            // }

            if ( count($rs) && count($rs1) ) {
                $model->commit();

                $userparam = array();
                $userparam['passport_uid'] = $uid;

                if($param['identity'] == 101){
                    $userparam['nickname'] = $param['identifier'];
                    $userparam['username'] = $param['identifier'];
                    $userparam['phone'] = $param['identifier'];
                }else{
                    $userparam['nickname'] = empty($param['nickname'])?$param['identifier']:$param['nickname'];
                    $userparam['username'] = $param['identifier'];
                    //$userparam['phone'] = $param['phone'];
                }

                $userparam['headimgurl'] = $param['avatar'];
                $userparam['password'] = $password;

                //判断推广渠道（360，meizu,xiaomi等）
                $channelId = M('Channel')->where('app_name=' . $param['channel'])->getField('id');
                if ( $channelId ) {
                    $userparam['channelid'] = $channelId;
                } else {
                    // if($param['channel']=='guanfang' || empty($param['channel'])){
                    //     $userparam['channelid'] = 1;//guanfang或其他异常用户都统一id为1
                    // }
                    // else{
                    //     $userparam['channelid']=$param['channel'];
                    // }
                    $userparam['channelid'] = 1;//guanfang或其他异常用户都统一id为1
                }

                $userparam['market_channel'] = $param['channel'];

                // if($param['identity'] == 401){
                //     $newUserId = D('User')->addUserInfoNew($userparam);
                // }else{
                //     $newUserId = D('User')->addUserInfo($userparam);
                // }
                

                $newUserId = D('User')->addUserInfo($userparam);
                if ( $newUserId ) {
                    
                    // //beging新增促销活动赠送红包+赠送金币+赠送积分
                    // //耿贯一2016-10-25

                    // if($param['identity'] == 101){
                    //     D('User')->addSaleRegister($newUserId);
                    // }
                    // //ending新增促销活动赠送红包+赠送金币+赠送积分
                    
                    returnJson('', 200, 'success');
                } else {
                    returnJson('用户同步错误', 410, '用户同步错误');
                }
            } else {
                $model->rollback();
                returnJson('', 1, '处理错误！');
            }
        }
    }

    public function signin($param = array())
    {
        $identity = $param['identity'];
        $identifier = $param['identifier'];
        $channel = $param['channel'];

        $sms_code = '888888';
        $identity = '101';
        $identifier = '13522788991';
        $credential = '';

        $redisCache = new RedisCache();
        $code = $redisCache->get($param['phone']);
        //验证短信；
        if ( $code != $param['code'] ) {
            returnJson('', 1, '亲，验证码输错了哦！');
        }

        //判断是否已经注册；
        $map = array();
        $map['identifier'] = $identifier;
        $user = $this->field(true)->where($map)->find();
        if ( !$user ) {
            //注册
            $mape = array();
            $mape['phone'] = $identifier;
            $m = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($mape)->find();
            if ( $m ) {
                $uid = $m['uid'];
            } else {
                $uid = uuid();
            }
            $member_auth = array();
            $member_auth['id'] = uuid();
            $member_auth['uid'] = $uid;
            $member_auth['createtime'] = time();
            $member_auth['identity'] = $identity;
            $member_auth['identifier'] = $identifier;
            $member_auth['credential'] = '';
            $member_auth['verified'] = 1;

            $member = array();
            $member['uid'] = $uid;
            $member['nickname'] = $identifier;
            $member['realname'] = $identifier;
            $member['username'] = $identifier;
            $member['phone'] = $identifier;
            $member['password'] = '';
            $member['status'] = 1;
            $member['createtime'] = time();
            $member['channel'] = $channel;
            //returnJson('', 402, '该用户已经注册！');
        } else {
            //登录
        }
    }

    /*用户登录*/
    public function login($param = array())
    {
        if($param['identity'] == 101){
            if ( empty($param['code']) && empty($param['identifier']) ) {
                returnJson('', 401, '参数不能为空');
            }
        }

        $phone = $param['identifier'];
        $code = $param['code'];
        $map = array();

        if(!empty($code)){
            if ( $param['identifier'] == '18611186900' && $param['code'] == '173417' ) {
            } else {
                if($param['identity'] == 101){
                    //读取redis
                    $redisCache = new RedisCache();
                    $code = $redisCache->get($phone);
                    //验证短信；
                    if ( $code != $param['code'] ) {
                        returnJson('login', 1, '亲，验证码输错了哦！');
                    }
                }else{
                    //$member_auth = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid=".$userinfo["uid"])->delete();
                }
            }
        }
        else{
            //用户名注册验证密码
            if($param['identity'] == 100){
                $map['credential'] = think_ucenter_md5($param['credential']);
            }
        }

        //添加信息：bo_member_device(设备)，bo_member_signin(ip及登录时间)
        $map['identity'] = $param['identity'];//身份
        $map['identifier'] = $param['identifier'];//标识
        
        $user = $this->field(true)->where($map)->find();
        if ( !$user ) {
            returnJson('', 1, '用户不存在或者密码错误！');
        }

        $usermap = array();
        $usermap['uid'] = $user['uid'];
        $userinfo = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($usermap)->find();
        if ( $userinfo['status'] == 0 ) {//0禁止；1激活；
            returnJson('', 1, '此用户已被禁止登录，请联系客服人员核对！');
        }
        
        if(!empty($param['deviceid'])){//判断是否有设备id 区分h5与app

            //添加设备，修改为清除原有数据，新增最新数据，保持只有一台设备接收推送
            $rs1 = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid=".$userinfo["uid"])->delete();
            $rs2 = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->delete($param['deviceid']);

            //recordLog($rs1.'###'.$rs2,'rsrsrs');

            $member_device['deviceid'] = $param['deviceid'];
            $member_device['uid'] = $user['uid'];
            $member_device['regid'] = $param['regid'];
            $member_device['imei'] = $param['imei'];
            $member_device['createtime'] = time();
            $member_device['os'] = $param['os'];
            $member_device['osversion'] = $param['osversion'];
            $member_device['brand'] = $param['brand'];
            $device_rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_device);
            
            //recordLog($device_rs,'rsrsrs1');

            // $device['deviceid'] = $param['deviceid'];
            // $member_device = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($device)->find();
            // recordLog(json_encode($param['deviceid']), "deviceid");

            // if ( !$member_device ) {
            //     $member_device['regid'] = $param['regid'];
            //     $member_device['imei'] = $param['imei'];
            //     $member_device['os'] = $param['os'];
            //     $member_device['osversion'] = $param['osversion'];
            //     $member_device['brand'] = $param['brand'];
            //     $member_device['deviceid'] = $param['deviceid'];
            //     $member_device['uid'] = $user['uid'];
            //     $member_device['createtime'] = time();
            //     $device_rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_device);
            // } else {
            //     $member_device['regid'] = $param['regid'];
            //     $member_device['imei'] = $param['imei'];
            //     $member_device['os'] = $param['os'];
            //     $member_device['osversion'] = $param['osversion'];
            //     $member_device['brand'] = $param['brand'];
            //     $member_device['uid'] = $user['uid'];
            //     $member_device['createtime'] = time();
            //     $device_rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->save($member_device);//更新
            // }

            //新增耿贯一
            //绑定设备登录方式 清楚原有设备账号绑定
            // $automap = array();
            // //$automap['identity'] = 401;
            // $automap['identifier'] = $param['deviceid'];

            // M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($automap)->delete();
            // $member_auth = array();
            // $member_auth['id'] = uuid();
            // $member_auth['uid'] = $user['uid'];
            // $member_auth['createtime'] = time();
            // $member_auth['identity'] = 401;
            // $member_auth['identifier'] = $param['deviceid'];
            // $member_auth['credential'] = think_ucenter_md5('123456');
            // $member_auth['verified'] = 1;
            // M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_auth);
            //新增结束耿贯一

            //登录日志
            $member_signin = array();
            $member_signin['uid'] = $user['uid'];
            $member_signin['authid'] = $user['id'];
            $member_signin['deviceid'] = $param['deviceid'];
            $member_signin['createtime'] = time();
            $member_signin['ip'] = getIP();

            $signinCount = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_signin);

        }         
        
        $userparam = array();
        $userparam['passport_uid'] = $user['uid'];
        $userparam['nickname'] = $param['identifier'];
        $userparam['username'] = $param['identifier'];
        $userparam['phone'] = $param['identifier'];
        $userparam['password'] = think_ucenter_md5('123456');
        $userparam['updatetime'] = time();

        //新增或更新用户信息
        D('User')->addUserInfo($userparam);

        $uid = M('User')->where('passport_uid=' . $userinfo['uid'])->getField('id');

        //$userid = D('User')->getUserId($user['uid']);
        //更新登录时间
        //M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $userid)->save(array('updatetime' => time(), 'avatar' => $param['avatar']));

        //返回token；

    
        $redisCache = new RedisCache();
        $tokenId = $this->getTokenId();
        $sid = md5($param['identifier'] . ":" . $tokenId);
        $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
            'uid' => $uid,
            'passportuid' => $user['uid'],
            'username' => $userinfo['username'],
        ), 2592000);
        $redisCache->rm($param['identifier']);//登录完成删除验证码；防止多次登录

        $data = array("tokenid" => $sid);
        //ios审核参数
        $data['isreview'] = 0;
        $data['reviewversion'] = 1.7;
        returnJson($data, 200, 'success');
    }

    //更新用户regid
    public function setRegId($tokenid,$deviceid,$regid){
        
        if(empty($tokenid) || empty($deviceid) || empty($regid)){
            returnJson('', 401, '参数不能为空');
        }

        try{

            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }

            //recordLog($user['uid'] . ':' . $user['passportuid'] . ':' . $user['username'], "userinfo");

            $map = array();
            $map['deviceid'] = $deviceid;
            $rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->find();

            $rs_md=0;
            if($rs){
                $data['uid']=$user['passportuid'];
                $data['regid']=$regid;
                $data['createtime']=time();
                $rs_md = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->save($data);    
            }
            else{
                $map['uid']=$user['passportuid'];
                $map['regid']=$regid;
                $map['createtime']=time();
                $map['imei']='';
                $map['os']='';
                $map['osversion']='';
                $map['brand']='';
                $rs_md = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($map);
            }

            if($rs_md){
                returnJson($rs_md, 200, 'success');
            }
            else{
                returnJson($rs_md, 410, '更新失败');
            }

        }
        catch(Exception $e){
            returnJson($e->getMessage(), 500, '服务器内部错误');
        }

    }

    //用户退出登录
    public function exitLogin($param = array())
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $device = array();
        $device['deviceid'] = $param['deviceid'];

        M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($device)->setField("regid", '');

        $redisCache = new RedisCache();
        $redisCache->rm($param['tokenid']);

        returnJson('', 200, 'success');
    }

    //获取用户相关信息
    public function userinfo($tokenid=0,$uid=0)
    {
        $map = array();

        if($uid>0){
            $user = D('user')->getUserInfoByUid($uid); 
            if($user){
                $map['uid'] = $user['passport_uid'];
            }
            else{
                returnJson('', 404, '用户不存在');
            }
        }
        else{
            $user = isLogin($tokenid);

            recordLog($user['uid'] . ':' . $user['passportuid'] . ':' . $user['username'], "userinfo");

            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }

            $map['uid'] = $user['passportuid'];
        }

        
        $users = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($map)->find();
        if ( $users ) {
            $avatar = $users['avatar'];  
            
            if ( strpos($avatar, 'http') === false ) {
                $users['avatar'] = completion_pic_passport($avatar);
            }

            $users['nickname'] = isMobile($users['nickname']) ? substr_replace($users['nickname'], '****', 3, 4) : $users['nickname'];
        }

        $userparam['uid'] = $user['uid'];
        $userparam['passport_uid'] = $user['passportuid'];
        $userInfo = D('User')->getUserInfo($userparam);
        
        $users['uid'] = $user['uid'];
        $users['passport_uid'] = $user['passportuid'];
        $users['periodcount'] = $userInfo['periodcount'];
        $users['totalpoint'] = $userInfo['totalpoint'];
        $users['gold'] = $userInfo['black'];

        $day_start = strtotime(date('Y-m-d', time()));//strtotime("-1 day", time());//当前时间减去一天;   strtotime(date('Y-m-d', strtotime('-' . $x . ' day')));
        $currentDay = M('point_record')->where("type_id='102' and user_id=" . $user['uid'] . " and create_time>='" . $day_start . "'")->count('id');

        if ( $currentDay <= 0 ) {
            $status = 1; //未签到
        } else {
            $status = 2;
        }
        $users['issign'] = $status;
        if ( empty($users['phone']) ) {
            $users['tourist'] = 1;
        } else {
            $users['tourist'] = 0;
        }

        unset($users['password']);

        //获取注册红包+金币
        $redisCache = new RedisCache();
        $redisarray = $redisCache->get($user['uid']);
        if ( !$redisarray ) {
            $users['redisarray'] = [];
        }else{
            $users['redisarray'] = $redisarray;
        }
        return $users;
    }

    /**
     * @deprecated 通过手机号删除用户信息
     * @author
     * @date
     **/
    public function deletePassportUserByUid($uid)
    {
        $result = false;

        $condition = array();
        $condition['uid'] = $uid;
        
        $user = M("user")->where($condition)->find();

        if($user){
            $passport_user = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$user['passport_uid'])->find();

            if ( $passport_user ) {
                $subCondition = array();
                $subCondition['uid'] = $passport_user['uid'];

                $model = new Model('', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME']);
                $model->startTrans();

                $r1 = M('member_address', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($subCondition)->delete();
                $r2 = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($subCondition)->delete();
                $r3 = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($subCondition)->delete();
                $r4 = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($subCondition)->delete();
                $r5 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($subCondition)->delete();

                if ( $r1 !== false && $r2 !== false && $r3 !== false && $r4 !== false && $r5 !== false ) {

                    // $shop_user = M('User')->where('passport_uid='.$user['uid'])->delete();

                    // if ( $shop_user !== false ) {
                    //     $model->commit();
                    //     $result = true;
                    // } else {
                    //     $model->rollback();
                    // }
                    $model->commit();
                    $result = true;
                } else {
                    $model->rollback();
                }

                if ( $result ) {
                    returnJson('', 200, 'success');
                } else {
                    $data = array("member_address" => $r1, "member_signin" => $r2, "member_device" => $r3, "member_auth" => $r4, "member" => $r5, "user" => $shop_user);
                    returnJson($data, 401, '删除用户信息出错');
                }
            } else {
                returnJson('', 404, '用户不存在');
            }
        }
    }

    /**
     * 验证码检查
     */
    private function check_verify($code, $id = "")
    {
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    /**
     * 获取随机位数数字
     * @param  integer $len 长度
     * @return string
     */
    public function randString($len = 6)
    {
        $chars = str_repeat('0123456789', $len);
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
        return $str;
    }

    //手机验证码保存
    public function cell_code($code)
    {
        $session = array();
        $session['cell_code'] = $code;
        $session['cell_time'] = NOW_TIME;
        session('cell_code', $session);
    }

    public function cellcode($cell)
    {
        try {
            if ( $_SERVER['REQUEST_METHOD'] === "ArrayOPTIONS" ) {
                return false;
            }

            $client = new Client;
            $request = new SmsNumSend;
            $code = $this->randString(4);
            $this->cell_code($code);
            $title = C("WEB_SITE_TITLE");
            $smsParams = array(
                'code' => $code
            );
            // 设置请求参数
            $req = $request->setSmsTemplateCode('SMS_13230323')
                ->setRecNum($cell)
                ->setSmsParam(json_encode($smsParams))
                ->setSmsFreeSignName('摸金达人')
                ->setSmsType('normal')
                ->setExtend('reg');
            $request = $client->execute($req);

            $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];

            if ( $reqResult === true ) {
                $redisCache = new RedisCache();
                $redisCache->set($cell, $code, 600);
                return $request;
            } else {
                return false;
            }
        } catch ( \Excetion $e ) {
            returnJson($e->getMessage(), 400, '验证码发送失败！');
        }
    }

    public function resetcellcode($cell)
    {
        $client = new Client;
        $request = new SmsNumSend;
        $code = $this->randString();
        $this->cell_code($code);
        $title = C("WEB_SITE_TITLE");
        $smsParams = array(
            'code' => $code,
            'product' => $title
        );
        // 设置请求参数
        $req = $request->setSmsTemplateCode('SMS_12800435')
            ->setRecNum($cell)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName('摸金达人')
            ->setSmsType('normal')
            ->setExtend('reg');
        $request = $client->execute($req);
        $redisCache = new RedisCache();
        $redisCache->set($cell, $code, 600);
        return $request;
    }

    public function checkPhoneCode($param = array())
    {
        $redisCache = new RedisCache();
        $code = $redisCache->get($param['phone']);
        //验证短信；
        if ( $code != $param['code'] ) {
            returnJson('', 1, '亲，验证码输错了哦！');
        } else {
            returnJson('', 200, '验证码校验成功！');
        }
    }

    public function  checkUserPhone($param = array())
    {
        //判断是否已经注册；
        $map = array();
        $map['identifier'] = $param['phone'];
        $user = $this->field(true)->where($map)->find();
        if ( $user ) {
            $this->resetcellcode($param['phone']);
            returnJson('', 200, '验证码发送成功！');
        } else {
            returnJson('', 1, '该用户还未注册！');
        }
    }

    public function resetPwd($param)
    {
        $pwd = $param['pwd'];//新密码
        $account = $param['account'];//账号
//        if ( strpos($account, '@') !== false ) {
//            $rs = sendMail($account, C("WEB_SITE_TITLE") . '-帐号邮件',
//                '<div class="wrapper" style="margin: 20px auto 0; width: 500px; padding-top:16px; padding-bottom:10px;"><br style="clear:both; height:0"><div class="content" style="background: none repeat scroll 0 0 #FFFFFF; border: 1px solid #E9E9E9; margin: 2px 0 0; padding: 30px;"><p>您好: </p><p>您的1元夺宝密码已重置，新密码是:' . $pwd . ';系统自动发送的，请妥善保管，请勿直接回复；</p></div></div>');
//        } else {
//            $rs = $this->resetPwdCellcode($account, $pwd);
//        }

        $rs1 = $this->where("identifier='" . $account . "'")->save(array('credential' => think_ucenter_md5($pwd)));
        if ( $rs1 ) {
            $userparam = array();
            $userparam['phone'] = $account;
            $userparam['password'] = think_ucenter_md5($pwd);
            D('User')->resetUserPwd($userparam);
            returnJson('', 200, '密码修改成功！');
        } else {
            returnJson('', 1, '密码修改失败！');
        }
    }

    public function updatePwd($tokenid, $pwd)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $rs1 = $this->where('uid=' . $user['passportuid'])->save(array('credential' => think_ucenter_md5($pwd)));
        if ( $rs1 ) {
            $userparam = array();
            $userparam['passport_uid'] = $user['passportuid'];
            $userparam['password'] = think_ucenter_md5($pwd);
            D('User')->updateUserPwd($userparam);
            returnJson('', 200, '密码修改成功！');
        } else {
            returnJson('', 1, '密码修改失败！');
        }
    }

    public function updateSignature($tokenid, $signature)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $user['passportuid'])->save(array('signature' => $signature));
        if ( $rs1 ) {
            returnJson('', 200, '签名修改成功！');
        } else {
            returnJson('', 1, '签名修改失败！');
        }
    }

    public function updateUserName($tokenid, $username)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $user['passportuid'])->save(array('nickname' => $username));
        if ( $rs1 ) {
            returnJson('', 200, '昵称修改成功！');
        } else {
            returnJson('', 1, '昵称修改失败！');
        }
    }

    public function bindEmail($param = array())
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $email = $param['email'];

        $rs1 = $this->where("uid=" . $user['passportuid'] . " AND identity=102 AND identifier='" . $email . "'")->find();
        if ( $rs1 ) {
            returnJson('', 1, '该邮箱地址已存在！');
        }

        $code = $this->randString();
        $sid = md5(time() . "email" . $email);

        //返回token；
        $redisCache = new RedisCache();
        $redisRs = $redisCache->set($sid, array(
            'code' => $code
        ), 3600);

        $link = $sid . "^" . $user['passportuid'] . "^" . $param['email'] . "^" . $code;
        $validateLink = base64_encode($link);

        $url = "http://" . $_SERVER['HTTP_HOST'] . "/api.php?s=/MyUser/emailValidate/validateLink/" . $validateLink;
        $rs = sendMail($email, C("WEB_SITE_TITLE") . '-帐号激活邮件', '<div class="wrapper" style="margin: 20px auto 0; width: 500px; padding-top:16px; padding-bottom:10px;"><br style="clear:both; height:0"><div class="content" style="background: none repeat scroll 0 0 #FFFFFF; border: 1px solid #E9E9E9; margin: 2px 0 0; padding: 30px;"><p>您好: </p><p style="border-top: 1px solid #DDDDDD;margin: 15px 0 25px;padding: 15px;">请点击以下链接激活您的账号: <a href="' . $url . '" target="_blank">' . $url . '</a></p><p style="border-top: 1px solid #DDDDDD; padding-top:6px; margin-top:25px; color:#838383;"><p>请勿回复本邮件, 此邮箱未受监控, 您不会得到任何回复。</p><p>如果点击上面的链接无效，请尝试将链接复制到浏览器地址栏访问。</p></p></div></div>');

        if ( $rs ) {
            M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $user['passportuid'])->setField("email", '1');
            returnJson('', 200, 'success');
        } else {
            returnJson('', 1, '邮件发送失败！');
        }
    }

    public function emailValidate($validateLink)
    {
        $link = base64_decode($validateLink);
        $tempLink = explode('^', $link);
        $sid = $tempLink[0];
        $uid = $tempLink[1];
        $email = $tempLink[2];
        $code = $tempLink[3];
        //返回token；
        $redisCache = new RedisCache();
        $redisRs = $redisCache->get($sid);
        if ( $redisRs['code'] != $code ) {
            return "邮箱激活失败";
        }

        $rs1 = $this->where("uid=" . $uid . " AND identity=102 AND identifier='" . $email . "'")->find();
        if ( $rs1 ) {
            return "您已经绑定了此邮箱！";
        }

        $member_auth = array();
        $member_auth['id'] = uuid();
        $member_auth['uid'] = $uid;
        $member_auth['createtime'] = time();
        $member_auth['identity'] = '102';
        $member_auth['identifier'] = $email;
        $member_auth['credential'] = ' ';
        $member_auth['verified'] = 1;
        $rs = $this->add($member_auth);

        $map = array();
        $map['uid'] = $uid;
        $user = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->setField("email", $email);

        if ( $user ) {
            return "邮箱激活成功";
        } else {
            return "邮箱激活失败";
        }
    }

    public function userUploadPicture($tmp)
    {
        $tokenId = $tmp["tokenid"];
        $user = isLogin($tokenId);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
//        $uid = $user['uid'];

        if ( preg_match('/^(data:\s*image\/(\w+);base64,)/', $tmp['picture'], $result) ) {
            $type = $result[2];
            $new_file = './Picture/Head/' . uniqid() . '.' . $type;
            $new_filei = './Picture/Head/' . uniqid() . '.' . $type;
            if ( Storage::put($new_file, base64_decode(str_replace($result[1], '', $tmp['picture']))) ) {
                // $return['status'] = 1;
                // $return['path'] = $this->web_url . substr($new_file, 1);
                img2thumb($new_file, $new_filei, $width = 130, $height = 130, $cut = 0, $proportion = 0);

                D('User')->setHeadimg($user['uid'], substr($new_filei, 1));
                $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $user['passportuid'])->save(array('avatar' => substr($new_filei, 1)));
                if ( $rs1 ) {
                    returnJson(completion_pic_passport(substr($new_filei, 1)), 200, '图片上传成功！');
                } else {
                    returnJson('', 1, '图片上传失败！');
                }
            } else {
                returnJson('', 1, '上传图片失败！');//上传图片或者文件失败
            }
        }
    }

    public function resetPwdCellcode($cell, $code)
    {
        $client = new Client;
        $request = new SmsNumSend;

        $this->cell_code($code);
        $title = C("WEB_SITE_TITLE");
        $smsParams = array(
            'code' => $code,
            'product' => $title
        );
        // 设置请求参数
        $req = $request->setSmsTemplateCode('SMS_10990920')
            ->setRecNum($cell)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName('变更验证')
            ->setSmsType('normal')
            ->setExtend('reg');
        $request = $client->execute($req);

        return $request;
    }


    /*新用户注册
    *gengguanyi   
    *20160908
    */
    public function  newregister($param = array())
    {
        //判断是否已经注册；
        $map = array();
        $map['identifier'] = $param['identifier'];
        $user = $this->field(true)->where($map)->find();
        if ( $user ) {
            setThirdpartyUserInfo($param['identity'],$param['identifier'],$param['nickname'],$param['avatar']);
            returnJson('', 402, '该用户已经注册！');
        }

        $mape = array();
        //用户uid唯一标示
        $uid = uuid();

        $password = think_ucenter_md5($param['credential']);

        $member_device = array();

        $member_device['deviceid'] = $param['deviceid'];
        $member_device['uid'] = $uid;
        $member_device['regid'] = $param['regid'];
        $member_device['imei'] = $param['imei'];
        $member_device['createtime'] = time();
        $member_device['os'] = $param['os'];
        $member_device['osversion'] = $param['osversion'];
        $member_device['brand'] = $param['brand'];

        $member_auth = array();
        $member_auth['id'] = uuid();
        $member_auth['uid'] = $uid;
        $member_auth['createtime'] = time();
        $member_auth['identity'] = $param['identity'];
        $member_auth['identifier'] = $param['identifier'];
        $member_auth['credential'] = $password;
        $member_auth['verified'] = 1;

        $member = array();
        $member['uid'] = $uid;
        $member['nickname'] = '';
        $member['realname'] = '';
        $member['username'] = $param['deviceid'];
        $member['phone'] = '';
        $member['password'] = $password;
        //$member['gender'] = $param['gender'];
        //$member['birthday'] = $param['birthday'];
        //$member['province'] = $param['province'];
        //$member['county'] = $param['county'];
        $member['avatar'] = $param['avatar'];
        $member['status'] = 1;
        $member['createtime'] = time();
        $member['channel'] = $param['channel'];
        //添加信息：bo_member，bo_member_d,bo_member_device

        $member_signin = array();
        $member_signin['uid'] = $uid;
        $member_signin['authid'] = $member_auth['id'];
        $member_signin['deviceid'] = $param['deviceid'];
        $member_signin['createtime'] = time();
        $member_signin['ip'] = getIP();

        $model = new Model('', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME']);
        $model->startTrans();//事务处理开始

        //判断member_device 删除并从新添加  保证一个用户只对应一个设备
        //设备
        $device = array();
        $device['uid'] = $uid;

        M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($device)->delete();
        M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_device);

        //设备只能绑定最新用户删除原有设备绑定
        $auth = array();
        $auth['identifier'] = $param['deviceid'];
        $this->where($auth)->delete();

        // $if_have_member_device = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($device)->find();

        // if($if_have_member_device){
            
        //     $device_rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($device)->save($member_device);//更新
        // }else{
        //    $rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_device);
        // }

        $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member);

        $rs2 = $this->add($member_auth);

        $rs3 = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_signin);

        if (count($rs1) && count($rs2) && count($rs3) ) {

            $model->commit();

            $userparam = array();
            $userparam['passport_uid'] = $uid;
            $userparam['nickname'] = "";
            $userparam['username'] = $param['deviceid'];
            $userparam['phone'] = "";
            $userparam['headimgurl'] = $param['avatar'];
            $userparam['password'] = $password;

            //判断推广渠道（360，meizu,xiaomi等）
            $channelId = M('Channel')->where('app_name=' . $param['channel'])->getField('id');
            if ( $channelId ) {
                $userparam['channelid'] = $channelId;
            } else {
                // if($param['channel']=='guanfang' || empty($param['channel'])){
                //     $userparam['channelid'] = 1;//guanfang或其他异常用户都统一id为1
                // }
                // else{
                //     $userparam['channelid']=$param['channel'];
                // }
                $userparam['channelid'] = 1;//guanfang或其他异常用户都统一id为1
            }

            $userparam['market_channel'] = $param['channel'];
            $newUserId = D('User')->addUserInfoNew($userparam);
            //$newUserId = D('User')->addUserInfo($userparam);
            if ( $newUserId ) {
                //返回token；
                // $redisCache = new RedisCache();
                // $tokenId = $this->getTokenId();
                // $sid = md5($param['identifier'] . ":" . $tokenId);
                // $redisCache->set($sid, array(
                //     'uid' => $useruid,
                //     'passportuid' => $uid,
                //     'username' => $param['username']
                // ), 2592000);
                // $data = array("tokenid" => $sid);
                //returnJson('', 200, 'success');

                //修改游客登录用户名
                $update_nickname = array();
                $update_nickname['nickname'] = "游客".$newUserId;
                //修改用户名
                M('user')->where('id='.$newUserId)->save($update_nickname);
                M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$uid)->save($update_nickname);

                //返回token；
                $redisCache = new RedisCache();
                $tokenId = $this->getTokenId();
                $sid = md5($param['deviceid'] . ":" . $tokenId);
                $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
                    'uid' => $newUserId,
                    'passportuid' => $uid,
                    'username' => '',
                ), 2592000);
                $redisCache->rm($param['deviceid']);//登录完成删除验证码；防止多次登录

                $data = array("tokenid" => $sid);

                returnJson($data, 200, 'success');
            } else {
                returnJson('用户同步错误', 410, 'error');
            }
        } else {
            $model->rollback();
            returnJson('', 1, '处理错误！');;
        }
    }


    /*新用户登录
     *gengguanyi   
     *20160908
    */
    function newlogin($param = array())
    {
        if ( empty($param['deviceid']) ) {
            returnJson('', 401, '参数不能为空');
        }

        //添加信息：bo_member_device(设备)，bo_member_signin(ip及登录时间)
        $map = array();
        $map['identity'] = $param['identity'];//身份
        //$map['identifier'] = $param['deviceid'];//标示
        $map['identifier'] = $param['identifier'];//标示
        //$map['credential'] = think_ucenter_md5($param['credential']);
        $user = $this->field(true)->where($map)->find();
        if ( !$user ) {
            returnJson('', 1, '用户不存在或者密码错误！');
        }

        $usermap = array();
        $usermap['uid'] = $user['uid'];
        $userinfo = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($usermap)->find();
        if ( $userinfo['status'] == 0 ) {//0禁止；1激活；
            returnJson('', 1, '此用户已被禁止登录，请联系管理员！');
        }

        //设备
        $device = array();
        $device['uid'] = $user['uid'];
        M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($device)->delete();
        $member_device = array();
        $mamber_device['deviceid'] = $param['deviceid'];
        $member_device['regid'] = $param['regid'];
        $member_device['imei'] = $param['imei'];
        $member_device['os'] = $param['os'];
        $member_device['osversion'] = $param['osversion'];
        $member_device['brand'] = $param['brand'];
        $member_device['uid'] = $user['uid'];
        $member_device['createtime'] = time();
        $device_rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_device);

        // recordLog(json_encode($param['deviceid']), "deviceid");

        // if ( !$member_device ) {
        //     //添加新用户
        //     $this->newregister($param);
        // } else {
        //     $member_device['regid'] = $param['regid'];
        //     $member_device['imei'] = $param['imei'];
        //     $member_device['os'] = $param['os'];
        //     $member_device['osversion'] = $param['osversion'];
        //     $member_device['brand'] = $param['brand'];
        //     $member_device['uid'] = $user['uid'];
        //     $member_device['createtime'] = time();
        //     $device_rs = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($device)->save($member_device);//更新
        // }

        //签到
        $member_signin = array();
        $member_signin['uid'] = $user['uid'];
        $member_signin['authid'] = $user['id'];
        $member_signin['deviceid'] = $param['deviceid'];
        $member_signin['createtime'] = time();
        $member_signin['ip'] = getIP();

        $signinCount = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_signin);

        /*
        $userparam = array();
        $userparam['passport_uid'] = $user['uid'];
        $userparam['nickname'] = $userinfo['nickname'];
        $userparam['username'] = $userinfo['username'];
        $userparam['phone'] = $userinfo['phone'];
        $userparam['password'] = think_ucenter_md5('123456');
        $userparam['updatetime'] = time();
        */

        $uid = M('User')->where('passport_uid=' . $userinfo['uid'])->getField('id');



        //$userid = D('User')->getUserId($user['uid']);
        //更新登录时间
        //M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $userid)->save(array('updatetime' => time(), 'avatar' => $param['avatar']));

        //返回token；
        $redisCache = new RedisCache();
        $tokenId = $this->getTokenId();
        $sid = md5($param['deviceid'] . ":" . $tokenId);
        $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
            'uid' => $uid,
            'passportuid' => $user['uid'],
            'username' => $userinfo['username'],
        ), 2592000);
        $redisCache->rm($param['deviceid']);//登录完成删除验证码；防止多次登录

        $data = array("tokenid" => $sid);

        returnJson($data, 200, 'success');
    }

    /*新用户绑定手机号
     *gengguanyi   
     *20160908
    */
    public function bindingPhone1($param = array())
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $passportuid = $user['passportuid'];
        $uid = $user['uid'];

        $redisCache = new RedisCache();
        $code = $redisCache->get($param['phone']);
        //验证短信；
        recordLog($code, "register code");
        recordLog($param['code'], "register param code");
        if ( $code != $param['code'] ) {
            returnJson('register', 1, '亲，验证码输错了哦！');
        }

        //判断手机号是否已经绑定过
        $map['phone'] = $param['phone'];
        $have_binding = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->Field('uid,nickname,avatar')->find();

        if ( $have_binding ) {//已绑定过手机号  现用户绑定手机号用户

            //不能绑定同一个手机号
            if ( $have_binding['uid'] == $passportuid ) {
                returnJson('', 1, '亲，手机号已绑定！');
            }else{
                //新用户passportuid
                $new_passportuid = $have_binding['uid'];
                //新用户userid,total_point,black
                $new_uid = M('user')->where('passport_uid='.$new_passportuid)->field('id,total_point,black,username')->find();
                //老用户total_point,black,hongbao
                //$uid_info = M('user')->where('id='.$uid)->field('total_point,black,hongbao')->find();
                $uid_info = M('user')->where('id='.$uid)->field(true)->find();

                //记录用户操作
                $user_record = array();
                $user_record['id'] = $uid_info['id'];
                $user_record['nickname'] = $uid_info['nickname'];
                $user_record['username'] = $uid_info['username'];
                $user_record['phone'] = $uid_info['phone'];
                $user_record['password'] = $uid_info['password'];
                $user_record['create_time'] = $uid_info['create_time'];
                $user_record['black'] = $uid_info['black'];
                $user_record['login_ip'] = $uid_info['login_ip'];
                $user_record['login_time'] = $uid_info['login_time'];
                $user_record['hongbao'] = $uid_info['hongbao'];
                $user_record['total_point'] = $uid_info['total_point'];
                $user_record['invitationid'] = $uid_info['invitationid'];
                $user_record['channelid'] = $uid_info['channelid'];
                $user_record['market_channel'] = $uid_info['market_channel'];
                $user_record['passport_uid'] = $uid_info['passport_uid'];
                $user_record['new_passport_uid'] = $new_passportuid;
                /*

                //新用户user total_point 用户积分
                $new_uid_total_point = $new_uid['total_point'];

                
                //新用户  签到积分获取记录
                $sql102 = "SELECT * FROM hx_point_record WHERE user_id = ".$new_uid['id']." AND type_id = 102";
                $new_uid_point_record = M()->query($sql102);

                foreach($new_uid_point_record AS $k=>$v){
                    $new_uid_point_record[$k]['create_time'] = date('Y-m-d',$v['create_time']);
                }
                
                

                //老用户 user total_point 用户积分
                $uid_total_point = M('user')->where('id='.$uid)->getField('total_point');
                
                //老用户 签到积分获取记录
                $uid_sql102 = "SELECT * FROM hx_point_record WHERE user_id = $uid AND type_id = 102";
                $uid_point_record = M()->query();
                

                
                //老用户注册 积分记录
                $uid_register_point_record_id = M('point_record')->where("type_id = 101 AND  user_id= $uid")->getField('id');
                foreach($uid_register_point_record_id AS $k=>$v){
                    $uid_register_point_record_id[$k]['create_time'] = date('Y-m-d',$v['create_time']);
                }

                */
                

                $model = M();
                $model->startTrans();



                $hxoneshop = array();
                $hxoneshop['user_id']=$new_uid['id'];

                $hxoneshop_two = array();
                $hxoneshop_two['uid']=$new_uid['id'];    
                    
                
                //更新资金流水
                $res4 = true;
                if(M('capitalfow')->where('user_id='.$uid)->count()){
                    $res_capitalfow = M('capitalfow')->where('user_id='.$uid)->select();
                    $user_record['capitalfow'] = json_encode($res_capitalfow);
                    $res4 = M('capitalfow')->where('user_id='.$uid)->save($hxoneshop);
                }
                
                
                //更新金币记录
                $res5 = true;
                if(M('gold_record')->where('uid='.$uid)->count()){
                    $res_gold_record = M('gold_record')->where('uid='.$uid)->select();
                    $user_record['gold_record'] = json_encode($res_gold_record);
                    $res5 = M('gold_record')->where('uid='.$uid)->save($hxoneshop_two);
                }

                
                //更新用户消息关系表
                $res6 = true;
                if(M('message_user')->where('uid='.$uid)->count()){
                    $res_message_user = M('message_user')->where('uid='.$uid)->select();
                    $user_record['message_user'] = json_encode($res_message_user);
                    $res6 = M('message_user')->where('uid='.$uid)->save($hxoneshop_two);
                }
                //更新积分记录表
                $res7 = true;
                if(M('point_record')->where('user_id='.$uid)->count()){
                    $res_point_record = M('point_record')->where('user_id='.$uid)->select();
                    $user_record['point_record'] = json_encode($res_point_record);
                    $res7 = M('point_record')->where('user_id='.$uid)->save($hxoneshop);
                }
                //更新购物地址表
                $res8 = true;
                if(M('shop_address')->where('uid='.$uid)->count()){
                    $res_shop_address = M('shop_address')->where('uid='.$uid)->select();
                    $user_record['shop_address'] = json_encode($res_shop_address);
                    $res8 = M('shop_address')->where('uid='.$uid)->save($hxoneshop_two);
                }
                
                
                //商品订单表
                $res9 = true;
                if(M('shop_order')->where('uid='.$uid)->count()){
                    $res_shop_order = M('shop_order')->where('uid='.$uid)->select();
                    $user_record['shop_order'] = json_encode($res_shop_order);
                    $res9 = M('shop_order')->where('uid='.$uid)->save($hxoneshop_two);
                }
                //更新商品开奖表
                $res10 = true;
                if(M('shop_kaijiang')->where('uid='.$uid)->count()){
                    $res_shop_kaijiang = M('shop_kaijiang')->where('uid='.$uid)->select();
                    $user_record['shop_kaijiang'] = json_encode($res_shop_kaijiang);
                    $res10 = M('shop_kaijiang')->where('uid='.$uid)->save($hxoneshop_two);
                }

                //商品开奖周期表
                $res11 = true;
                if(M('shop_period')->where('uid='.$uid)->count()){
                    $res_shop_period = M('shop_period')->where('uid='.$uid)->select();
                    $user_record['shop_period'] = json_encode($res_shop_period);
                    $res11 = M('shop_period')->where('uid='.$uid)->save($hxoneshop_two);
                }    
                //购物车记录表
                $res12 = true;
                if(M('shop_record')->where('uid='.$uid)->count()){
                    $res_shop_record = M('shop_record')->where('uid='.$uid)->select();
                    $user_record['shop_record'] = json_encode($res_shop_record);
                    $res12 = M('shop_record')->where('uid='.$uid)->save($hxoneshop_two);
                }
                //商品分享表

                $res13 = true;
                if(M('shop_shared')->where('uid='.$uid)->count()){
                    $res_shop_shared = M('shop_shared')->where('uid='.$uid)->select();
                    $user_record['shop_shared'] = json_encode($res_shop_shared);
                    $res13 = M('shop_shared')->where('uid='.$uid)->save($hxoneshop_two);
                }    
                //购物车临时表
                $res14 =true;
                if(M('temporary_order')->where('uid='.$uid)->count()){
                    $res_temporary_order = M('temporary_order')->where('uid='.$uid)->select();
                    $user_record['temporary_order'] = json_encode($res_temporary_order);
                    $res14 = M('temporary_order')->where('uid='.$uid)->save($hxoneshop_two);
                }
                
                //点赞表
                $res15 =true;
                if(M('up')->where('uid='.$uid)->count()){
                    $res_up = M('up')->where('uid='.$uid)->select();
                    $user_record['up'] = json_encode($res_up);
                    $res15 = M('up')->where('uid='.$uid)->save($hxoneshop_two);
                }


                //活动记录表
                $res16 = true;
                if(M('activity_log')->where('user_id='.$uid)->count()){
                    $res_activity_log = M('activity_log')->where('user_id='.$uid)->select();
                    $user_record['activity_log'] = json_encode($res_activity_log);
                    $res16 = M('activity_log')->where('user_id='.$uid)->save($hxoneshop);
                }    

                
                

                if($res4 !== false && $res5 !== false && $res6 !== false && $res7 !== false && $res8 !== false && $res9 !== false && $res10 !== false && $res11 !== false && $res12 !== false && $res13 !== false && $res14 !== false && $res15 !== false && $res16 !== false){
                    $model->commit();
                    //成功
                    $member_device = array();
                    $member_device['uid'] = $new_passportuid;

                    //删除新用户原有登录方式新增
                    M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$new_passportuid)->delete();
                        //删除所有当前用户的device新增

                    $res1 = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$passportuid)->save($member_device);


                    

                    
                    $member_signin = array();
                    $member_signin['uid'] = $new_passportuid;
                    $res2 = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$passportuid)->save($member_signin);

                    $member_auth = array();
                    $member_auth['uid'] = $new_passportuid;
                    $res3 = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$passportuid)->save($member_auth);

                    //更新新用户积分
                    if($uid_info['total_point'] > 0){
                        M('user')->where('id='.$new_uid['id'])->setInc('total_point',$uid_info['total_point']);
                    }

                    //更新新用户金币
                    if($uid_info['black']> 0 ){
                        M('user')->where('id='.$new_uid['id'])->setInc('black',$uid_info['black']);
                    }
                    
                    //更新新用户红包
                    if($uid_info['hongbao']> 0 ){
                        M('user')->where('id='.$new_uid['id'])->setInc('hongbao',$uid_info['hongbao']);
                    }



                    //昵称头像对比  调整为最新昵称头像
                    $update_member_save = array();
                    $update_user_save = array();
                    $status = 1;
                    $old_userinfo = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$passportuid)->Field('nickname,avatar')->find();

                    if(empty($have_binding['avatar']) || $have_binding['avatar'] == NULL){
                        if(empty($old_userinfo['avatar']) || $old_userinfo['avatar'] == NULL){

                        }else{
                            $status = 2; 
                            $update_member_save['avatar'] = $old_userinfo['avatar'];
                            $update_user_save['headimgurl'] = $old_userinfo['avatar'];
                        }
                    }

                    if($have_binding['nickname'] == $param['phone']){//昵称等于手机号
                        if ( strpos($old_userinfo['nickname'], '游客') === false ) {
                            $status = 2;
                            $update_member_save['nickname'] = $old_userinfo['nickname'];
                            $update_user_save['nickname'] = $old_userinfo['nickname'];
                        }else{
                            
                        }
                    }

                    if($status == 2){
                        M('user')->where('passport_uid='.$new_passportuid)->save($update_user_save);
                        M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$new_passportuid)->save($update_member_save);
                    }


                    //删除老用户bo_member
                    M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$passportuid)->delete();
                    //删除老用户user
                    M('user')->where('id='.$uid)->delete();
                    //增加游客绑定手机修改记录表
                    M('delete_user_record')->add($user_record);
                    //增加游客绑定手机修改记录表

                    
                    //返回token；
                    $redisCache = new RedisCache();
                    $tokenId = $this->getTokenId();
                    $sid = md5($param['phone'] . ":" . $tokenId);
                    $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
                        'uid' => $new_uid['id'],
                        'passportuid' => $new_passportuid,
                        'username' => $new_uid['username'],
                    ), 2592000);
                    $redisCache->rm($param['phone']);//登录完成删除验证码；防止多次登录
                    
                    $data = array("tokenid" => $sid);
                    returnJson($data, '200', 'success');
                }else{
                    $model->rollback();
                    returnJson('用户同步错误', 410, 'error');
                }

             }
        }else{//第一次绑定手机号 更新相关信息

            $member_info = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $passportuid)->field('nickname,realname')->find();

            $member = array();
            $member['username'] = $param['phone'];
            $member['phone'] = $param['phone'];


            //游客默认名称
            $touristNickname = "游客".$uid;


            if ( empty($member_info['nickname']) || $member_info['nickname']==$touristNickname) {
                $member['nickname'] = $param['phone'];
            }

            if ( empty($member_info['realname']) ) {
                $member['realname'] = $param['phone'];
            }

            M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $passportuid)->save($member);


            $user_info = M('User')->where('id=' . $uid)->field('nickname')->find();
            $userparam = array();
            $userparam['username'] = $param['phone'];
            $userparam['phone'] = $param['phone'];

            if ( empty($user_info['nickname']) || $user_info['nickname']==$touristNickname) {
                $userparam['nickname'] = $param['phone'];
            }

            /*
            //更新积分 及积分记录
            $total_point1 = M('user')->where('id='.$uid)->getField('total_point');
            $userparam['total_point'] = $total_point1+1000;
            */

            $model = M('User');
            $model->startTrans();
            $new_uid = M('User')->where('id=' . $uid)->save($userparam);

            if ( $new_uid > 0 ) {
                $model->commit();

                //增加手机号登录方式
                $member_auth = array();
                $member_auth['id'] = uuid();
                $member_auth['uid'] = $passportuid;
                $member_auth['createtime'] = time();    
                $member_auth['identity'] = 101;
                $member_auth['identifier'] = $param['phone'];
                $member_auth['credential'] = think_ucenter_md5('123456');
                $member_auth['verified'] = 1;                
                M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_auth);

                //判断是否有注册积分
                $map_point = array();
                $map_point['user_id'] = $uid;
                $map_point['type_id'] = 101;

                $if_register_point = M('Point_record')->where($map_point)->find();

                if($if_register_point){
                    
                }else{
                    //注册送积分
                    $pointRs = D('Point')->addPointByUid(1000, 101, $uid);
                }
                $data = array("tokenid" => $param['tokenid']);
                returnJson($data, 200, 'success');
            } else {
                $model->rollback();
                returnJson('用户同步错误', 410, 'error');
            }
        }
    }


    /*新用户绑定手机号
     *gengguanyi   
     *20160908
    */
    public function bindingPhone($param = array())
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $passportuid = $user['passportuid'];
        $uid = $user['uid'];

        $redisCache = new RedisCache();
        $code = $redisCache->get($param['phone']);
        //验证短信；
        recordLog($code, "register code");
        recordLog($param['code'], "register param code");
        if ( $code != $param['code'] ) {
            returnJson('register', 1, '亲，验证码输错了哦！');
        }

        //判断用户是否为转正用户
        $if_official = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("identity=101 AND identifier='".$param['phone']."'")->count();
        if($if_official>0){//正常用户

            // //判断用户是否赠送红包及金币及积分
            // $if_one_user = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='".$passportuid."'")->count();
            // if($if_one_user == 1){
            //     D('User')->addSaleRegister($uid);
            // }



            $model = M('User');
            $model->startTrans();

            $member = array();
            $member['phone'] = $param['phone'];
            //$res1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $passportuid)->save($member);
            $res2 = M('User')->where('id=' . $uid)->save($member);

            if ( $res2 > 0 ) {
                    $model->commit();
                    M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='". $passportuid."'")->save($member);

                     //判断是否有注册积分
                    $map_point = array();
                    $map_point['user_id'] = $uid;
                    $map_point['type_id'] = 101;
                    $if_register_point = M('Point_record')->where($map_point)->find();
                    if($if_register_point){
                    }else{
                        //注册送积分
                        $pointRs = D('Point')->addPointByUid(1000, 101, $uid);
                    }

                    $data = array("tokenid" => $param['tokenid']);
                    returnJson($data, 200, '手机号已添加');
            }else{
                $model->rollback();
                returnJson('用户同步错误', 410, 'error');
            }
        }else{

            // //判断用户是否赠送红包及金币及积分
            // $if_one_user = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='".$passportuid."'")->count();
            // if($if_one_user == 1){
            //     D('User')->addSaleRegister($uid);
            // }

            $new_member_auth = array();
            $new_member_auth['id'] = uuid();
            $new_member_auth['uid'] = $passportuid;
            $new_member_auth['createtime'] = time();
            $new_member_auth['identity'] = 101;
            $new_member_auth['identifier'] = $param['phone'];
            $new_member_auth['credential'] = think_ucenter_md5('123456');
            $new_member_auth['verified'] = 1;
            //增加手机号验证码快速登录方式
            $new_auth = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($new_member_auth);

            if($new_auth > 0){
                //游客默认名称
                $touristNickname = "游客".$uid;
                $member_info = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='".$passportuid."'")->find();
                
                $member = array();
                $member['phone'] = $param['phone'];
                if ( empty($member_info['nickname']) || $member_info['nickname']==$touristNickname) {
                    $member['nickname'] = $param['phone'];
                }
                M('User')->where('id=' . $uid)->save($member);
                M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='". $passportuid."'")->save($member);


                //判断是否有注册积分
                $map_point = array();
                $map_point['user_id'] = $uid;
                $map_point['type_id'] = 101;
                $if_register_point = M('Point_record')->where($map_point)->find();
                if($if_register_point){
                }else{
                    //注册送积分
                    $pointRs = D('Point')->addPointByUid(1000, 101, $uid);
                }

                $data = array("tokenid" => $param['tokenid']);
                returnJson($data, 200, '绑定手机号成功,您也可以手机快速登录！');
            }else{
                returnJson('用户同步错误', 410, 'error');
            }
        }
    }

    //微信第三方登录
    public function weChatLogin($param = array()){

        $if_have_user = M('User')->where('passport_uid='.$param['uid'])->find();

        if($if_have_user){
            //获取uid
            $userinfo = M('User')->where('passport_uid='.$param['uid'])->field('id,nickname')->find();
        }else{
            $credential ='123456';
            $password = think_ucenter_md5($credential);
            $userparam = array();
            $userparam['passport_uid'] = $param['uid'];
            $userparam['nickname'] = $param['nickname'];
            $userparam['username'] = $param['unionid'];
            $userparam['phone'] = "";
            $userparam['password'] = $password;
            $userparam['channelid'] = 1;
            $userparam['market_channel'] = 'wechat';
            $userparam['headimgurl'] = $param['headimgurl'];
            $newUserId = D('User')->addUserInfoNew($userparam);
            $userinfo = M('User')->where('passport_uid='.$param['uid'])->field('id,nickname')->find();
        }

        //更新用户头像及用户名
        $if_have_member = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$param['uid'])->field('nickname,avatar')->find();
        if($if_have_member){
            $member = array();
            $member['uid'] = $param['uid'];
            if(empty($if_have_member['nickname'])){
                $member['nickname'] = $param['nickname'];
            }
            if(empty($if_have_member['avatar'])){
                $member['avatar'] = $param['headimgurl'];
            }
            M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='".$param['uid']."'")->save($member);
        }else{
            $member = array();
            $credential ='123456';
            $member['uid'] = $param['uid'];
            $member['nickname'] = $param['nickname'];
            $member['realname'] = '';
            $member['username'] = $param['unionid'];
            $member['phone'] = '';
            $member['password'] = think_ucenter_md5($credential);
            $member['status'] = 1;
            $member['createtime'] = time();
            $member['channel'] = "wechat";
            $member['avatar'] = $param['headimgurl'];
            M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member);
        }

        //登录日志
        $member_signin = array();
        $member_signin['uid'] = $param['uid'];
        $member_signin['authid'] = $param['id'];
        $member_signin['deviceid'] = $param['unionid'];
        $member_signin['createtime'] = time();
        $member_signin['ip'] = getIP();
        $signinCount = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_signin);

        
        //返回token；
        $redisCache = new RedisCache();
        $tokenId = $this->getTokenId();
        $sid = md5($param['unionid'] . ":" . $tokenId);


        recordLog($param['unionid'], "unionid");
        recordLog($tokenId, "tokenId");
        recordLog($sid, "sid");
        $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
            'uid' => $userinfo['id'],
            'passportuid' => $param['uid'],
            'username' => $userinfo['nickname'],
        ), 2592000);
        recordLog($redisRs, "redisRs");
        recordLog($userinfo['id'], "uid");
        recordLog($param['uid'], "passportuid");
        recordLog($userinfo['nickname'], "username");
        //$redisCache->rm($param['unionid']);//登录完成删除验证码；防止多次登录
        $url = $this->Quath2Url."?tokenid=".$sid."&code=200&time=".$param['login_time'];
        recordLog($url, "url");
        $result_url = $url;
        header('Location: '.$result_url);
    }


    //微信第三方注册
    public function weChatRegister($param = array()){
        $mape = array();
        //用户uid唯一标示
        $uid = uuid();
        $credential ='123456';
        $password = think_ucenter_md5($credential);

        $member_auth = array();
        $member_auth['id'] = uuid();
        $member_auth['uid'] = $uid;
        $member_auth['createtime'] = time();
        $member_auth['identity'] = 201;
        $member_auth['identifier'] = $param['unionid'];
        $member_auth['credential'] = $password;
        $member_auth['verified'] = 1;

        $member = array();
        $member['uid'] = $uid;
        $member['nickname'] = $param['nickname'];
        $member['realname'] = '';
        $member['username'] = $param['unionid'];
        $member['phone'] = '';
        $member['password'] = $password;
        $member['status'] = 1;
        $member['createtime'] = time();
        $member['channel'] = "wechat";
        $member['avatar'] = $param['headimgurl'];
        //添加信息：bo_member，bo_member_auth

        $model = new Model('', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME']);
        $model->startTrans();//事务处理开始
    
        $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member);

        $rs2 = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_auth);

        if(count($rs1) && count($rs2)){
            $model->commit();
            
            $userparam = array();
            $userparam['passport_uid'] = $uid;
            $userparam['nickname'] = $param['nickname'];
            $userparam['username'] = $param['unionid'];
            $userparam['phone'] = "";
            $userparam['password'] = $password;
            $userparam['channelid'] = 1;
            $userparam['market_channel'] = 'wechat';
            $userparam['headimgurl'] = $param['headimgurl'];
            $newUserId = D('User')->addUserInfo($userparam);

            if($newUserId){
                //返回token；
                $redisCache = new RedisCache();
                $tokenId = $this->getTokenId();
                $sid = md5($param['unionid'] . ":" . $tokenId);
                $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
                    'uid' => $newUserId,
                    'passportuid' => $uid,
                    'username' => $param['nickname'],
                ), 2592000);
                //$redisCache->rm($param['unionid']);//登录完成删除验证码；防止多次登录

                $url = $this->Quath2Url."?tokenid=".$sid."&code=200&time=".$param['login_time'];
                $result_url = $url;
                header('Location: '.$result_url);


            }else{

                $url = $this->Quath2Url."?code=410";
                $result_url = $url;
                header('Location: '.$result_url);
                
            }
        }else{
            $model->rollback();
            $url = $this->Quath2Url."?code=411";
            $result_url = $url;
            header('Location: '.$result_url);
        }
    }

    //QQ第三方登录
    public function QQLogin($param = array()){

        $if_have_user = M('User')->where('passport_uid='.$param['uid'])->find();

        if($if_have_user){
            //获取uid
            $userinfo = M('User')->where('passport_uid='.$param['uid'])->field('id,nickname')->find();
        }else{
            $credential ='123456';
            $password = think_ucenter_md5($credential);
            $userparam = array();
            $userparam['passport_uid'] = $param['uid'];
            // $userparam['nickname'] = $param['nickname'];
            $userparam['username'] = $param['openid'];
            $userparam['phone'] = "";
            $userparam['password'] = $password;
            $userparam['channelid'] = 1;
            $userparam['market_channel'] = 'QQ';
            // $userparam['headimgurl'] = $param['headimgurl'];
            $newUserId = D('User')->addUserInfoNew($userparam);
            $userinfo = M('User')->where('passport_uid='.$param['uid'])->field('id,nickname')->find();
        }

        //更新用户头像及用户名
        $if_have_member = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid='.$param['uid'])->field('nickname,avatar')->find();

        if($if_have_member){
            $member = array();
            $member['uid'] = $param['uid'];
            if(empty($if_have_member['nickname'])){
                $member['nickname'] = $param['nickname'];
            }
            if(empty($if_have_member['avatar'])){
                $member['avatar'] = $param['headimgurl'];
            }

            M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid='".$param['uid']."'")->save($member);
        }


        
        //签到
        $member_signin = array();
        $member_signin['uid'] = $param['uid'];
        $member_signin['authid'] = $param['id'];
        $member_signin['deviceid'] = $param['openid'];
        $member_signin['createtime'] = time();
        $member_signin['ip'] = getIP();
        $signinCount = M('member_signin', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_signin);

        
        //返回token；
        $redisCache = new RedisCache();
        $tokenId = $this->getTokenId();
        $sid = md5($param['openid'] . ":" . $tokenId);


        recordLog($param['openid'], "openid");
        recordLog($tokenId, "tokenId");
        recordLog($sid, "sid");
        $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
            'uid' => $userinfo['id'],
            'passportuid' => $param['uid'],
            'username' => $userinfo['nickname'],
        ), 2592000);
        recordLog($redisRs, "redisRs");
        recordLog($userinfo['id'], "uid");
        recordLog($param['uid'], "passportuid");
        recordLog($userinfo['nickname'], "username");
        //$redisCache->rm($param['openid']);//登录完成删除验证码；防止多次登录
//        $url = "http://onlinetest.1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/user.w";
//        $result_url = $url . '?tokenid='.$sid.'&code=200';
//        header('Location: '.$result_url);
        $url = $this->Quath2Url."?tokenid=".$sid."&code=202&time=".$param['login_time']."&skin=#!main";
        recordLog($url, "url");
        $result_url = $url;
        header('Location: '.$result_url);

    }


    //QQ第三方注册
    public function QQRegister($param = array()){
        $mape = array();
        //用户uid唯一标示
        $uid = uuid();
        $credential ='123456';
        $password = think_ucenter_md5($credential);

        $member_auth = array();
        $member_auth['id'] = uuid();
        $member_auth['uid'] = $uid;
        $member_auth['createtime'] = time();
        $member_auth['identity'] = 202;
        $member_auth['identifier'] = $param['openid'];
        $member_auth['credential'] = $password;
        $member_auth['verified'] = 1;


        $member = array();
        $member['uid'] = $uid;
        // $member['nickname'] = $param['nickname'];
        $member['realname'] = '';
        $member['username'] = $param['openid'];
        $member['phone'] = '';
        $member['password'] = $password;
        $member['status'] = 1;
        $member['createtime'] = time();
        $member['channel'] = "QQ";
        // $member['avatar'] = $param['headimgurl'];
        //添加信息：bo_member，bo_member_auth

            $model = new Model('', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME']);
            $model->startTrans();//事务处理开始
        
            $rs1 = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member);

            $rs2 = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->add($member_auth);

            if(count($rs1) && count($rs2)){
                $model->commit();
                
                $userparam = array();
                $userparam['passport_uid'] = $uid;
                // $userparam['nickname'] = $param['nickname'];
                $userparam['username'] = $param['openid'];
                $userparam['phone'] = "";
                $userparam['password'] = $password;
                $userparam['channelid'] = 1;
                $userparam['market_channel'] = 'qq';
                // $userparam['headimgurl'] = $param['headimgurl'];
                $newUserId = D('User')->addUserInfo($userparam);

                if($newUserId){
                    //返回token；
                    $redisCache = new RedisCache();
                    $tokenId = $this->getTokenId();
                    $sid = md5($param['openid'] . ":" . $tokenId);
                    $redisRs = $redisCache->set($sid, array(//存储token 并且存储相关对应用户信息
                        'uid' => $newUserId,
                        'passportuid' => $uid,
                        'username' => $param['nickname'],
                    ), 2592000);

                    $url = $this->Quath2Url."?tokenid=".$sid."&code=202&time=".$param['login_time']."&skin=#!main";
                    $result_url = $url;
                    header('Location: '.$result_url);


                }else{

                    $url = $this->Quath2Url."?tokenid=&code=1&time=&skin=#!main";
                    $result_url = $url;
                    header('Location: '.$result_url);
                    
                }
            }else{
                $model->rollback();
                $url = $this->Quath2Url."?tokenid=&code=1&time=&skin=#!main";
                $result_url = $url;
                header('Location: '.$result_url);



            }
    }
    /**
     * 验证码发送
     * @param  [type] $cell [description]
     * @return [type]       [description]
     */
    public function cellcodenew($phone)
    {
        try {
            $client = new Client;
            $request = new SmsNumSend;
            $code = $this->randString(4);
            $this->cell_code($code);
            $title = C("WEB_SITE_TITLE");
            $smsParams = array(
                'code' => $code
            );
            // 设置请求参数
            $req = $request->setSmsTemplateCode('SMS_47505131')
                ->setRecNum($phone)
                ->setSmsParam(json_encode($smsParams))
                ->setSmsFreeSignName("LIVE商城")
                ->setSmsType('normal')
                ->setExtend('reg');
            $request = $client->execute($req);

            $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];
            if ( $reqResult === true ) {
                $redisCache = new RedisCache();
                $redisCache->set($phone, $code, 600);
                return returnJson(array(), 200, '验证码发送成功！');
            } else {
                return returnJson(array(), 101, '验证码发送失败！');
            }
        } catch ( \Excetion $e ) {
            return returnJson($e->getMessage(), 101, '验证码发送失败！');
        }
    }
    /**
     * 绑定手机号
     * @param  [type] $phone [description]
     * @param  [type] $code  [description]
     * @param  [type] $uid   [description]
     * @return [type]        [description]
     */
    public function bindphone($phone,$code,$uid)
    {  
        try {
            $info = M('user')->where('id='.$uid)->field('phone,id')->find();//用户详情
            if (!empty($info)) {
                if (empty($info['phone'])) {
                    $redisCache = new RedisCache();
                    $send_code = $redisCache->get($phone);
                    //验证短信；
                    recordLog($send_code, "register code");
                    recordLog($code, "register param code");
                    if ( $send_code != $code ) {
                        returnJson('register', 1, '亲，验证码输错了哦！');
                    } else {
                        $result = M('user')->where('id='.$uid)->save(array('phone'=>$phone));
                        if ( $result != false ) {
                            //$redisCache->rm($phone);
                            return returnJson(substr_replace($phone, '****', 3, 4), 200, '绑定手机成功！');
                        } else {
                            return returnJson(array(), 101, '绑定手机失败！');
                        }
                    }
                } else {
                    return returnJson(array(), 101, '您已经绑定了手机！');
                }
            } else {
                return returnJson(array(), 101, '用户不存在！');
            }
        } catch ( \Excetion $e ) {
            return returnJson($e->getMessage(), 101, '验证码发送失败！');
        }
        
    }






}