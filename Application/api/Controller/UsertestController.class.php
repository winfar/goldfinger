<?php
namespace api\Controller;

use Think\Controller;

class UsertestController extends BaseController
{
    protected function _initialize()
    {
        parent::_initialize();
        vendor("weixin.class_weixin_adv");


    }


    /**
     * @deprecated 机器码登录
     * @author gengguanyi
     * @date  2016-9-8
     **/
    public function login()
    {

        $result = file_get_contents('php://input');
        //file_get_contetns  1:读取POST数据 2：不能用于multipart/form-data类型  3

        recordLog($result, 'login');//记录
        $json = json_decode($result, true);


        //登录密码
        $json['credential'] = '123456';
        $param = array();

        $param['identity'] = $json['identity'];//身份  (username,email,phone,wechat,qq,weibo...);
        $param['identifier'] = $json['identifier'];//标识  (username,email,phone,thirdpatyopenid...)
        $param['credential'] = $json['credential'];//凭证(password,token)
        $param['deviceid'] = $json['deviceid'];//设备唯一id
        $param['regid'] = $json['regid'];//第三方服务商(jpush)提供的唯一id ,
        $param['imei'] = $json['imei'];//手机串号 
        $param['os'] = $json['os'];//os (string, optional): 操作系统
        $param['osversion'] = $json['osversion'];//系统版本(9.0)
        $param['brand'] = $json['brand'];//厂商型号(iPhone 6,iPhone 6s,iPad mini...)
        $param['channel'] = $json['channel'];//注册渠道


        /*


        //test
         $param = array();

        $param['identity'] = 401;//身份  (username,email,phone,wechat,qq,weibo...);
        $param['identifier'] = '47dbcd965c66642ab1694921f0bc9ac1';//标识  (username,email,phone,thirdpatyopenid...)
        $param['credential'] = '123456';//凭证(password,token)
        $param['deviceid'] = '47dbcd965c66642ab1694921f0bc9ac1';//设备唯一id   47dbcd965c66642ab1694921f0bc9ac1
        $param['regid'] = '18171adc030bb7e15d1';//第三方服务商(jpush)提供的唯一id ,
        $param['imei'] = 'imei';//手机串号 
        $param['os'] = 'iOS';//os (string, optional): 操作系统
        $param['osversion '] = '9.0';//系统版本(9.0)
        $param['brand'] = 'iPhone 6';//厂商型号(iPhone 6,iPhone 6s,iPad mini...)
        $param['channel'] = 'guanfang';//注册渠道


        
        //test
        */

        //判断用户是否已经注册     判断设备
        $map['deviceid'] = $json['deviceid'];

        //$map['deviceid'] = $param['deviceid'];


        $user = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($map)->find();
        if ( $user ) {//已经注册

            D('UserPassport')->newlogin($param);
        } else {//未注册

            $this->register($param);
        }

    }

    //用户注册
    public function register($param = array())
    {

        if ( isEmpty($param['identifier']) ) {
            returnJson('', 1, '标识不能为空1！');
        }
        if ( isEmpty($param['identity']) ) {
            returnJson('', 1, '身份不能为空！');
        }

        if ( isEmpty($param['credential']) ) {
            returnJson('', 1, '凭证不能为空！');
        }

        if ( isEmpty($param['channel']) ) {
            returnJson('', 1, '注册渠道不能为空！');
        }

        if ( isEmpty($param['deviceid']) ) {
            returnJson('', 1, '设备id不能为空！');
        }
        /*
        if ( isEmpty($json['phone']) ) {
            returnJson('', 1, '电话不能为空！');
        }*/
        //$param['nickname'] = $json['nickname'];
        //$param['realname'] = $json['realname'];
        //$param['username'] = $json['username'];
        //$param['password'] = $json['password'];
        //$param['gender'] = $json['gender'];
        //$param['birthday'] = $json['birthday'];
        //$param['province'] = $json['province'];
        //$param['city'] = $json['city'];
        //$param['county'] = $json['county'];
        //$param['avatar'] = '';
        //$param['channel'] = $json['channel'];
        D('UserPassport')->newregister($param);
    }


    //绑定手机号
    public function bindingPhone()
    {
        $request_s = file_get_contents("php://input");
        recordLog($request_s, '用户签到request');
        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        if ( isEmpty($request['code']) ) {
            returnJson('', 1, '验证码不能为空！');
        }
        if ( isEmpty($request['phone']) ) {
            returnJson('', 1, '电话不能为空！');
        }

        $param['tokenid'] = $request['tokenid'];
        $param['code'] = $request['code'];
        $param['phone'] = $request['phone'];
        D('UserPassport')->bindingPhone($param);
    }

    //获取用户登录方式
    public function loginWay($uid)
    {
        $identity = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where('uid=' . $uid)->order('createtime DESC')->getField("identity");
        return returnJson($identity, 200, 'success');
    }


    //微信登录回调
    function oauth2()
    {
        $weixin = new \class_weixin_adv();
        if ( isset($_GET['code']) ) {

            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx0c1d682ba7418c7c&secret=c0899d4509f5e2c1d3920da57b638ceb&code=" . $_GET['code'] . "&grant_type=authorization_code";
            $res = $weixin->https_request($url);
            $res = (json_decode($res, true));


//            if($res['openid']){
//                //判断微信用户是否已经注册
//                $map = array();
//                $map['identifier'] = $res['openid'];
//                $if_have = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where($map)->find();
//                $param = array();
//				if($if_have){
//                    $param['uid'] = $if_have['uid'];
//                    $param['id'] = $if_have['id'];
//                    $param['openid'] = $res['openid'];
//                    D('UserPassport')->weChatLogin($param);
//				}else{
//                    $param['openid'] = $res['openid'];
//                    $param['nickname'] = '';
//                    D('UserPassport')->weChatRegister($param);
//				}
//            }else{
//                $url = "http://onlinetest.1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w?tokenid=&code=1&skin=#!main";
//                $result_url = $url;
//                header('Location: '.$result_url);
//            }

            //验证access_token是否过期
            $url2 = "https://api.weixin.qq.com/sns/auth?access_token=" . $res['access_token'] . "&openid=" . $res['openid'];
            $res2 = $weixin->https_request($url2);
            $res2 = (json_decode($res2, true));
            if ( $res2['errcode'] == 0 ) {//access_token没过期

            } else {
                $url3 = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=wx0c1d682ba7418c7c&grant_type=refresh_token&refresh_token=" . $res['refresh_token'];
                $res3 = $weixin->https_request($url3);
                $res3 = (json_decode($res3, true));
                if ( empty($res3['errcode']) ) {
                    $res['access_token'] = $res3['access_token'];
                } else {
                    returnJson('', 1, '授权出错,请重新授权!');
                }
            }
            $row = $weixin->get_user_info($res['openid'], $res['access_token']);

            if ( $row['openid'] ) {
                //判断微信用户是否已经注册
                $map = array();
                $map['identifier'] = $row['openid'];
                $map['identity'] = 201;
                $if_have = M('member_auth', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where($map)->find();
                $param = array();
                if ( $if_have ) {
                    $param['uid'] = $if_have['uid'];
                    $param['id'] = $if_have['id'];
                    $param['openid'] = $row['openid'];
                    D('UserPassport')->weChatLogin($param);
                } else {
                    $param['openid'] = $row['openid'];
                    $param['nickname'] = $row['nickname'];
                    $param['headimgurl'] = $row['headimgurl'];
                    D('UserPassport')->weChatRegister($param);
                }
            } else {
                $url = "http://onlinetest.1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w?tokenid=&code=1&skin=#!main";
                $result_url = $url;
                header('Location: ' . $result_url);
            }


        } else {
            $url = "http://onlinetest.1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w?tokenid=&code=1&skin=#!main";
            $result_url = $url;
            header('Location: ' . $result_url);
//              $url = "http://onlinetest.1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/user.w";
//              $result_url = $url . '?tokenid=&code=1';
//              header('Location: '.$result_url);
        }

    }


    /**
     * @deprecated pk首页列表，首页推荐列表
     * @author guanyi
     * @date 2016-09-29
     * @param $pageindex 页码(默认第1页)
     * @param $pagesize 每页记录数(默认20)
     * @param $phone 手机号搜索 默认空
     * @param $room 房间号搜索 默认空
     * @param $number 场次 默认空
     **/
    public function pk($pageindex = 1, $pagesize = 20, $room = '', $number = 0)
    {
        $shop = D('Shop')->pk($pageindex, $pagesize, $room, $number);
        returnJson($shop, 200, 'success');
    }

    /**
     * 获取pk场次
     * @param int $filtrate 进行筛选，筛选后的结果只显示开房间的场次（0 = 全部 （默认），1 = 商品选择的场次列表 ，2=pk列表的场次列表）
     * @param int $tokenid
     */
    public function numberList($filtrate = 1,$tokenid ='')
    {
//        $user = isLogin($tokenid);
//        //查询出所有有效场次
////        $rs_all = M()->table('__PKCONFIG__ p , __HOUSE_MANAGE__ m')
////            ->field('peoplenum')
////            ->where( array('m.isresolving'=> 0,'m.ispublic'=>1 ))
////            ->where('p.id = m.pksetid')
////            ->group('peoplenum')
////            ->select();
//
//        $rs_all = M()->table('__PKCONFIG__ p , __SHOP__ s')
//            ->where(array('s.status'=> 1))
//            ->where('p.inventory > 0 and p.shopid = s.id ')
//            ->group('p.peoplenum')
//            ->field('p.peoplenum')
//            ->select();
//
//        switch ($filtrate){
//            case 0 :
//                returnJson($rs_all, 200, 'success');
//                break;
//            case 1 :
//                if(empty($user['uid'])){
//                    returnJson($rs_all, 200, 'success');
//                }
//                $map['m.uid'] = $user['uid'];
//                $map['m.ispublic'] = 1 ;
//                $map['m.isresolving'] = 0 ;
//                $data = M()->table('__HOUSE_MANAGE__ m ')->join('LEFT JOIN __PKCONFIG__ p ON m.pksetid = p.id ')
//                    ->where($map)
//                    ->field('peoplenum')
//                    ->group('p.peoplenum')
//                    ->select();
////                SELECT p.peoplenum from hx_house_manage m LEFT JOIN hx_pkconfig p on m.pksetid = p.id where   m.uid = '102417' and m.isresolving = 0 and m.ispublic = 1 GROUP BY p.peoplenum ;
//                $count = 0;
//                foreach ($rs_all as $k) {
//                        foreach ($data as $k2 ){
//                            if($k['peoplenum'] == $k2['peoplenum']){
//                             array_splice(   $rs_all,1,1);
//                            }
//                        }
//                    $count++;
//                }
//                returnJson($rs_all, 200, 'success');
//                break;
//            case 2 :
//                $list = M('shop_period')
//                    ->table('__SHOP__ shop,__SHOP_PERIOD__ period,__HOUSE_MANAGE__ housemanage,__PKCONFIG__ pkconfig')
//                    ->field('pkconfig.peoplenum')
//                    ->where('shop.id=period.sid and shop.status=1 and shop.display=1 and period.state=0 AND period.iscommon=2 AND period.id=housemanage.periodid AND housemanage.isresolving=0 AND housemanage.pksetid=pkconfig.id')
//                    ->group('pkconfig.peoplenum')
//                    ->select();
//                break;
//        }
        $list = D('Shop')->numberList($filtrate,$tokenid);
        returnJson($list, 200, 'success');
    }

    /*    
    //进入公开pk房间
    public function pkinfo($tokenid,$houseid){
        
        // if(isEmpty($tokenid) ) {
        //     returnJson('', 401, '您还未登录！');
        // }
        if(isEmpty($houseid)){
            returnJson('',401,'房间id不能为空');
        }
        $rs = D('Shop')->pkinfo($tokenid,$houseid);
        returnJson($rs, 200, 'success');
    }

    //进入私密pk房间
    public function privacyPkInfo($tokenid,$houseid,$invitecode){
        if(isEmpty($tokenid) ) {
            returnJson('', 401, '您还未登录！');
        }
        if(isEmpty($houseid)){
            returnJson('',401,'房间id不能为空');
        }

        $rs = D('Shop')->privacyPkInfo($tokenid,$houseid,$invitecode);
        returnJson($rs, 200, 'success');
    }
    */

    //进入pk房间详情页面
    public function pkinfo($tokenid = '', $houseid = '',$pid='')
    {

        if ( isEmpty($tokenid) ) {
            returnJson('', 401, '您还未登录！');
        }
        if ( isEmpty($houseid) ) {
            returnJson('', 401, '房间id不能为空');
        }
        // if($ispublic==""){
        //     returnJson('', 401, '请确认公开私密房间');
        // }
        // if ($ispublic==1&& isEmpty($invitecode) ) {
        //     returnJson('', 401, '邀请码不能为空');
        // }

        $rs = D('Shop')->newPkInfo($tokenid, $houseid,$pid);
        returnJson($rs, 200, 'success');
    }

    //pk房间详情
    public function pkhouseinfo($houseid = '')
    {
        if ( isEmpty($houseid) ) {
            returnJson('', 401, '房间id不能为空');
        }

        $rs = D('Shop')->getHouseByHouseId($houseid);
        returnJson($rs, 200, 'success');
    }



    //选择pk商品
    public function selectPk($pageindex = 1, $pagesize = 20, $number = 0,$tokenid = '')
    {
        if(isEmpty($tokenid)){
             returnJson('', 100, '请登录！');
        }
        $res = D('Shop')->selectPk($pageindex, $pagesize, $number,$tokenid);
        returnJson($res, 200, 'success');
    }

    //创建私人房间
    public function createPrivacyPk()
    {
        $request_s = file_get_contents("php://input");
        $request = json_decode($request_s, true);
        if ( isEmpty($request['tokenid']) ) {
            returnJson('', 401, '您还未登录！');
        }
        if ( isEmpty($request['pkid']) ) {
            returnJson('', 401, '请选择pk商品id！');
        }
        if ( isEmpty($request['invitecode']) ) {
            returnJson('', 401, '邀请码不能为空！');
        }

        $param['tokenid'] = $request['tokenid'];
        $param['pkid'] = $request['pkid'];
        $param['invitecode'] = $request['invitecode'];
        D('Shop')->createPrivacyPk($param);
    }

    //我的pk夺宝记录接口
    public function pkGetRecords()
    {
        //测试
//        $request_s = '{"tokenid":"1","pageindex":"1","state":"1"}';    //state(1-即将揭晓、0-进行中、2已揭晓)
        $request_s = file_get_contents('php://input');
        //记录LOG
        //	recordLog($request_s, "我的pk记录request");
        $request = json_decode($request_s, true);

        $records = D('User')->pkrecords($request['tokenid'], $request['pageindex'], $request['pagesize'], $request['state']);
        returnJson($records, 200, 'success');
    }

    //我的pk幸运记录
    public function pkGetLottery()
    {
        try {
            $result = file_get_contents('php://input');
            recordLog($result, 'getLottery');
            $json = json_decode($result, true);

            if ( isEmpty($json['tokenid']) ) {
                returnJson('', 401, '您还未登录！');
            }
            $data = D('User')->pkLottery($json['tokenid'], $json['pageindex'], $json['pagesize']);
            returnJson($data, 200, 'success');
        } catch ( \Exception $e ) {
            returnJson($e->getMessage(), 500, 'error');
        }
    }


    //摸金记录列表合并pk
    public function getRecordsNew()
    {
        //测试
//        $request_s = '{"tokenid":"1","pageindex":"1","state":"1"}';    //state(1-即将揭晓、0-进行中、2已揭晓)
        $request_s = file_get_contents('php://input');
        //记录LOG
        //	recordLog($request_s, "我的pk记录request");
        $request = json_decode($request_s, true);

        $records = D('User')->recordsNew($request['tokenid'], $request['pageindex'], $request['pagesize'], $request['state']);
        returnJson($records, 200, 'success');
    }

    //幸运记录列表合并pk
    public function getLotteryNew()
    {
        try {
            $result = file_get_contents('php://input');
            recordLog($result, 'getLottery');
            $json = json_decode($result, true);

            if ( isEmpty($json['tokenid']) ) {
                returnJson('', 1, '您还未登录！');
            }
            $data = D('User')->lotteryNew($json['tokenid'], $json['pageindex'], $json['pagesize']);
            returnJson($data, 200, 'success');
        } catch ( \Exception $e ) {
            returnJson($e->getMessage(), 500, 'error');
        }
    }


    //晒单列表合并pk
    public function sharedlistNew($sid = 0, $pageindex = 1, $pagesize = 20, $tokenid = 0)
    {

        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的晒单记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }
        try {
            $shared_list = D('User')->shopdisplaysnew($pageindex, $map['uid'], $pagesize, $sid);
            if ( !$shared_list ) {
                $shared_list = array();
            }
            returnJson($shared_list, 200, 'success');

        } catch ( \Exception $e ) {
            returnJson('', 500, $e->getMessage());
        }

    }


    //私密房间72小时解除接口
    public function pkDissolve($houseid=0)
    {
        G('begin');
        \Think\Log::record('================ START 私密房间定时解除接口 '.time_format(time()).'===================','INFO');
        if($houseid == 0){
            $end_time = time()- (empty(C('ROOM_OPEN_TIME'))? 86400 * getPKValid():C('ROOM_OPEN_TIME'));

            //获取私密房间正在进行期
            $pklist = M('shop_period')
                ->table('__SHOP_PERIOD__ period,__HOUSE_MANAGE__ house')
                ->field('period.id as pid,period.no as pno,period.create_time,house.pksetid as pkid,house.id as houseid,house.id as houseno')
                ->where('period.state = 0 AND period.iscommon = 2 AND period.id = house.periodid AND house.ispublic=1 AND period.create_time <= '.($end_time))  // 259200 =  3 * 24 * 60 * 60
                ->order('period.create_time')
                ->select();
            \Think\Log::record('获取私密房间，创建时间<='.date('Y-m-d H:i:s',$end_time).'查询结果数=>'.count($pklist),'INFO');
        }else{

            //获取私密房间正在进行期
//            $pklist = M('shop_period')
//                ->table('__SHOP_PERIOD__ period,__HOUSE_MANAGE__ house')
//                ->field('period.id as pid,period.no as pno,period.create_time,house.pksetid as pkid,house.id as houseid,house.id as houseno')
//                ->where('period.iscommon = 2 AND period.id = house.periodid AND house.ispublic=1 AND house.id='.$houseid)  //
//                ->order('period.create_time')
//                ->select();
            $pklist = M('house_manage')->where('id=' . $houseid)->setField(array('isresolving'=>1,'end_time'=>time()));
            \Think\Log::record('获取指定私密房间houseid=>'.$houseid.' 查询结果数=>'.count($pklist),'INFO');
        }

        if ( $pklist ) {
            foreach ( $pklist as $k => $v ) {
                \Think\Log::record('房间ID['.$v['houseid'].']解散开始','INFO');
                    $update['state'] = 3;
                    $result = M('ShopPeriod')->where('id=' . $v['pid'])->save($update);
                    if ( $result ) {
                        //pk商品库存增加1
                        M('pkconfig')->where('id=' . $v['pkid'])->setInc('inventory');

                        //修改这个房间isresolving状态为1 
                        M('house_manage')->where('id=' . $v['houseid'])->setField(array('isresolving'=>1,'end_time'=>time()));

                        $recordlist = M('shop_record')->field('order_id')->where('pid=' . $v['pid'])->select();
                        if ( $recordlist ) {
                            foreach ( $recordlist as $k1 => $v1 ) {
                                $res = M()->table('__SHOP_ORDER__ s ,__USER__ u')->field('s.uid,u.username,s.gold,s.cash,u.phone')->where(' s.uid = u.id')->where(array('s.order_id'=>$v1['order_id']))->find();
                                if ( $res ) {
                                    //退还金币
                                    //更新新用户金币
                                    $add_black = $res['gold'] + $res['cash'];
                                    $rs_black = M('user')->where('id=' . $res['uid'])->setInc('black', $add_black);
                                    //增加返还金币记录
                                    $data['uid'] = $res['uid'];
                                    $data['typeid'] = 16;
                                    $data['gold'] = $add_black;
                                    $data['create_time'] = time();
//                                    $data['remark'] =   '{"所属房间house_manage-id":"' . $v['houseid'] . '","商品pkid":"' . $v['pkid'] . '","用户id":"' . $res['uid'] . '","用户名":"' . $res['username'] . '","电话":'. $res['phone']. ' }';
                                    $remark['所属房间house_manage-id'] = $v['houseid'];
                                    $remark['商品pkid'] = $v['pkid'];
                                    $remark['用户id'] = $res['uid'] ;
                                    $remark['用户名'] = $res['username'] ;
                                    $remark['电话'] =  $res['phone'];
                                    $data['remark'] = json_encode($remark) ;
                                    $data['pid'] = $v['pid'];
                                    $rs_gold = M('gold_record')->add($data);

                                    \Think\Log::record('房间ID['.$v['houseid'].']解散,信息['.json_encode($data).'],退换金币处理返回值 增加金币result=>'.$rs_black.' 保存金币明细result=>'.$rs_gold.';','INFO');

                                    //解散房间消息推送
                                    D('Notification')->push4DissolveRoom($res['uid'],$v['pid'],$v['houseno'], $v['pno']);
                                }
                            }
                        }
                    }
                \Think\Log::record('房间ID['.$v['houseid'].']解散结束','INFO');
                \Think\Log::save();
            }
        }
        G('end');
        \Think\Log::record('================ END 私密房间定时解除接口 '.'time=>'.time_format(time()).'=================== 耗时'.G('begin','end').'s','INFO');

    }

    /**
     * 进入Pk房间
     * @param $tokenid
     * @param $pkid
     */
    public function entryPKRoom($tokenid = '', $roomid = '', $invitecode = ''){
        if ( isEmpty($tokenid) ) {
            returnJson('', 401, '您还未登录！');
        }
        if ( isEmpty($roomid) ) {
            returnJson('', 401, '房间id不能为空');
        }

        $rs = D('Shop')->entryPKRoom($tokenid, $roomid, $invitecode);
        returnJson(is_null($rs)? '': $rs, 200, 'success');
    }

    public function getConfig($key){
        $_key = explode(',',$key);
        foreach ($_key as $k =>$v  ) {
            echo  'key'.$k.'=>'.$v.' value=>'.(string)C($v).' <= <br/>';

        }
    }
    
}