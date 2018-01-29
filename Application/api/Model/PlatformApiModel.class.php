<?php
namespace api\Model;

use Think\Model;

/**
 * 
 */

class PlatformApiModel extends Model{
	
    const LIVE_API_HOST = 'http://app.wopaitv.com';
    const LIVE_API_HOST_TEST = 'http://test-app.wopaitv.com';
    const LIVE_API_PLATFORMID = '2';
    const LIVE_API_KEY = '23049SDKFJ98FDF';
    const LIVE_API_USERINFO = '/api/game/userInfo';
    const LIVE_API_SENDMSG = '/api/push/sendThirdMsg';
    const LIVE_API_EXINFO = '/api/common/thirdExpInfo';
    const LIVE_API_EXCHANGEEXP = '/api/pay/thirdExchangeExp';

    private function getUrl(){
        return isHostProduct() ? self::LIVE_API_HOST : self::LIVE_API_HOST_TEST;
    }

    //查询用户信息
    public function getUserInfo($uid){
        $apiurl = $this->getUrl() . self::LIVE_API_USERINFO;

        $param['platformId'] = self::LIVE_API_PLATFORMID;
        $param['nonce'] = strval(time())."000";
        $param['uid'] = $uid;
        $param['signature'] = urlencode(param_signature("POST", $param, self::LIVE_API_KEY));

        $result = post_str($apiurl, $param);

        return json_decode($result['data'],true);
    }

    /*
     * 向客户端推送消息
     */
    public function sendMsg($uids,$title,$description,$passThrough=0){
        $apiurl = $this->getUrl() . self::LIVE_API_SENDMSG;

        $param['platformId'] = self::LIVE_API_PLATFORMID;
        $param['nonce'] = strval(time())."000";
        $param['msgType'] = 1;//推送类型：1-商城消息
        $param['uids'] = $uids;//推送人ID，可传多个，用英文逗号分隔（限制50个ID）
        $param['title'] = $title;//推送标题，长度小于16个字符（汉字、字母均算作一个字符）
        $param['description'] = $description;//推送文本，长度小于128个字符（汉字、字母均算作一个字符）
        $param['passThrough'] = $passThrough;//是否透传消息，1表示透传消息，0表示通知栏消息
        //$param['delayed'] = $uid;//延时推送，单位秒（如需实时推送不传此参数）
        $param['signature'] = urlencode(param_signature("POST", $param, self::LIVE_API_KEY));

        $result = post_str($apiurl, $param);

        return json_decode($result['data'],true);
    }

    //获取用户货币兑换经验信息
    public function getExpInfo($uid,$amount){
        $apiurl = $this->getUrl() . self::LIVE_API_EXINFO;

        $param['platformId'] = self::LIVE_API_PLATFORMID;
        $param['nonce'] = strval(time())."000";
        $param['uid'] = $uid;
        $param['type'] = 2;//货币类型，1-金豆，2-钻石
        $param['amount'] = $amount;//用户货币余额
        $param['thirdType'] = 1;//	第三方类型。1-商城
        $param['signature'] = urlencode(param_signature("POST", $param, self::LIVE_API_KEY));

        $result = post_str($apiurl, $param);

        return json_decode($result['data'],true);
    }

    //货币兑换经验
    public function exchangeExp($uid,$amount){
        $apiurl = $this->getUrl() . self::LIVE_API_EXCHANGEEXP;

        $param['platformId'] = self::LIVE_API_PLATFORMID;
        $param['nonce'] = strval(time())."000";
        $param['uid'] = $uid;
        $param['type'] = 2;//货币类型，1-金豆，2-钻石
        $param['amount'] = $amount;//兑换额度
        $param['thirdType'] = 1;//	第三方类型。1-商城
        $param['signature'] = urlencode(param_signature("POST", $param, self::LIVE_API_KEY));

        $result = post_str($apiurl, $param);

        return json_decode($result['data'],true);
    }
	
}

