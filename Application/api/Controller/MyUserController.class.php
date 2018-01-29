<?php
namespace api\Controller;

use Think\Controller;

class MyUserController extends BaseController
{
     
     
    /**
     * @deprecated 我的晒单
     * @author zhangkang
     * @date 2016-7-6
     **/
    public function getShared()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'getShared');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $data = D('User')->getShared($json['tokenid'], $json['pageindex'], $json['pagesize']);

        returnJson($data, 200, 'success');
    }

    /**
     * @deprecated 我的晒单合并pk商品
     * @author gengguanyi
     * @date 2016-7-6
     **/
    public function getSharedNew()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'getShared');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $data = D('User')->getSharedNew($json['tokenid'], $json['pageindex'], $json['pagesize']);

        returnJson($data, 200, 'success');
    }


    /**
     * @deprecated 晒单列表
     * @author zhangkang
     * @date 2016-7-6
     **/
//    public function getShareList()
//    {
//        $result = file_get_contents('php://input');
//        recordLog($result, 'getShared');
//        $json = json_decode($result, true);
//
//        $data = D('User')->getShareList($json['pageindex'], $json['pagesize'], $json['$tokenid']);
//
//        returnJson($data, 200, 'success');
//    }

    /**
     * @deprecated 我的幸运记录
     * @author zhangkang
     * @date 2016-7-6
     **/
    public function getLottery()
    {
        try{
            $result = file_get_contents('php://input');
            recordLog($result, 'getLottery');
            $json = json_decode($result, true);

            if ( isEmpty($json['tokenid']) ) {
                returnJson('', 1, '您还未登录！');
            }

            $data = D('User')->lottery($json['tokenid'], $json['pageindex'], $json['pagesize']);

            returnJson($data, 200, 'success');
        }catch(\Exception $e){
            returnJson($e->getMessage(), 500, 'error');
        }
    }

    /**
     * @deprecated 晒单详情页
     * @author zhangkang
     * @date 2016-7-6
     **/
    public function getShareDetail($id)
    {
        $data = D('User')->displays_more($id);

        returnJson($data, 200, 'success');
    }


    /**
     * @deprecated 晒单详情页合并pk
     * @author genggaunyi
     * @date 2016-10-17
     **/
    public function getShareDetailNew($id)
    {
        $data = D('User')->displays_more_new($id);

        returnJson($data, 200, 'success');
    }



    /**
     * @deprecated 我的夺宝记录接口
     * @author zhangran
     * @date 2016-07-06
     **/
    public function getRecords()
    {
        //测试
//        $request_s = '{"tokenid":"1","pageindex":"1","state":"1"}';    //state(1-即将揭晓、0-进行中、2已揭晓)
        $request_s = file_get_contents('php://input');
        //记录LOG
        //	recordLog($request_s, "我的夺宝记录request");
        $request = json_decode($request_s, true);

        $records = D('User')->records($request['tokenid'], $request['pageindex'], $request['pagesize'], $request['state']);
        returnJson($records, 200, 'success');
    }


    

    /**
     * @deprecated 用户地址添加，修改
     * @author zhangkang
     * @date 2016-07-06
     **/
    public function addressEdit()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'addressEdit');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        if ( isEmpty($json['nickname']) ) {
            return returnJson('', 1, '联系人不能为空！');
        }
        if ( isEmpty($json['tel']) ) {
            return returnJson('', 1, '联系电话不能为空！');
        }
        if ( isEmpty($json['province']) ) {
            return returnJson('', 1, '请选择所在城市！');
        }
        if ( isEmpty($json['city']) ) {
            return returnJson('', 1, '请选择所在城市！');
        }
        if ( isEmpty($json['address']) ) {
            return returnJson('', 1, '请填写收货地址！');
        }

        $records = D('User')->address_update($json);
        returnJson('', 200, 'success');
    }

    /**
     * @deprecated 设置默认地址
     * @author zhangkang
     * @date 2016-07-06
     **/
    public function addressDefault()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'addressDefault');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $records = D('User')->addressdefault($json['tokenid'], $json['id']);
        returnJson('', 200, 'success');
    }

    /**
     * @deprecated 删除地址
     * @author zhangkang
     * @date 2016-07-06
     **/
    public function addressDel()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'addressDel');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        D('User')->addressdel($json['tokenid'], $json['id']);
    }

    /**
     * @deprecated 获取用户地址列表
     * @author zhangkang
     * @date 2016-7-7
     **/
    public function addressList()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'addressDel');
        $json = json_decode($result, true);
        
        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }
        $rs = D('User')->addressList($json['tokenid']);
        returnJson($rs, 200, 'success');
    }
    
    /**
     * @deprecated 用户重置密码
     * @author zhangkang
     * @date  2016-7-7
     **/
    public function resetPwd()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'resetPwd');
        $json = json_decode($result, true);

        $rs = D('UserPassport')->resetPwd($json);
    }

    public  function checkUserPhone(){
        $result = file_get_contents('php://input');
        recordLog($result, 'checkUserPhone');
        $json = json_decode($result, true);
        $param = array();
        $param['phone'] = $json['phone'];
        $rs = D('UserPassport')->checkUserPhone($param);
    }

    /**
     * @deprecated 验证码验证
     * @author zhangkang
     * @date  2016-7-7
     **/
    public function checkPhoneCode()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'checkPhoneCode');
        $json = json_decode($result, true);
        $param = array();
        $param['phone'] = $json['phone'];
        $param['code'] = $json['code'];

        $rs = D('UserPassport')->checkPhoneCode($param);
    }

    /**
     * @deprecated 用户修改密码
     * @author zhangkang
     * @date  2016-7-7
     **/
    public function updatePwd()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'updatePwd');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $rs = D('UserPassport')->updatePwd($json['tokenid'], $json['pwd']);
    }

    /**
     * @deprecated 发送手机验证码接口
     * @author zhangkang
     * @date
     **/
    public function sendcode($mobile)
    {
        $rs = D('UserPassport')->cellcode($mobile);
        returnJson($rs, 200, 'success');
    }

    /**
     * @deprecated 修改昵称
     * @author zhangkang
     * @date 2016-7-7
     **/
    public function updateUserName()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'updateUserName');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $rs = D('UserPassport')->updateUserName($json['tokenid'], $json['nickname']);
    }

    /**
     * @deprecated 修改签名
     * @author zhangkang
     * @date 2016-7-7
     **/
    public function updateSignature()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'updateSignature');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        $rs = D('UserPassport')->updateSignature($json['tokenid'], $json['signature']);
    }

    /**
     * @deprecated 绑定邮箱
     * @author zhangkang
     * @date 2016-7-7
     **/
    public function bindEmail()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'updateSignature');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            returnJson('', 1, '您还未登录！');
        }

        if ( isEmpty($json['email']) ) {
            returnJson('', 1, '邮箱不能为空！');
        }

        $rs = D('UserPassport')->bindEmail($json);
    }

    /**
     * @deprecated 激活邮箱
     * @author zhangkang
     * @date 2016-7-7
     **/
    public function emailValidate($validateLink)
    {
        header("content-type:text/html; charset=utf-8");
        if ( isEmpty($validateLink) ) {
            echo "<H1 style='text-align:center;padding-top:50px;'>您的邮箱激活失败！</H1>";
            exit;
        }

        $rs = D('UserPassport')->emailValidate($validateLink);
        echo "<H1 style='text-align:center;padding-top:50px;'>" . $rs . "</H1>";
        exit;
    }

    /**
     * @deprecated 用户上传头像
     * @author zhangkang
     * @date 2016-7-11
     **/
    public function userUploadPicture()
    {
        $result = @file_get_contents("php://input");
        //   recordLog($result, 'userUploadPicture');
        $json = json_decode($result, true);
        if ( $json == null ) {
            returnJson('', 1, '请选择上传头像！');
        }
        $rs = D('UserPassport')->userUploadPicture($json);
    }

    /**
     * 发送卡密服务
     * @param $no  卡号
     * @param $mobile 手机号
     * @param $sid  商品ID
     * @return mixed
     */
    public function sendCardSN(){
        if(IS_POST){
            $result = @file_get_contents("php://input");
            $json = json_decode($result, true);

            if ( isEmpty($json['tokenid']) ) {
                returnJson('', 1, '您还未登录！');
            }
            if ( isEmpty($json['no']) ) {
                returnJson('', 2, '充值卡号不能为空！');
            }
            $tokenid = $json['tokenid'];
            $no = $json['no'];
            return D('Notification')->sendCardSN($tokenid,$no);    
        }else{
            $no = I('no');
            $mobile = I('mobile');
            return D('Notification')->sendCardSN('',$no,$mobile);
        }
        
        
    }
}