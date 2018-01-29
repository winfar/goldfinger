<?php

namespace api\Controller;

use Think\Controller;

class NotificationController extends BaseController
{
    protected function _initialize()
    {
        vendor("JPush.JPush");
    }

    public function sendSmsWarning($mobile){

        $ip = getIP();

        if($ip == "120.92.44.69"){
            $request = D('Notification')->sendSmsWarning($mobile);
            $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];
            if($reqResult){
                returnJson($request, 200, 'success');
            }
            else{
                returnJson($request, 500, 'error');
            }
        }
    }

    /*
    * 商品下架返还金币通知
    */
    public function goldSendBack(){
        //title,content,passport_uid_list
        try{
            $result = @file_get_contents("php://input");
            $json = json_decode($result, true);

            if(empty($json['title']) || empty($json['content'])){
                returnJson('title,content',401,'参数错误');
            }

            if(count($json['passport_uid_list'])>0){
                $extras=array ("type"=>3,array ("pid"=>0));

                $uidStr = implode(',',$json['passport_uid_list']);
                $rs_ios     = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field('regid')->where("regid is not null and regid !='' and os is not null and os='iOS' and uid in(".$uidStr.")")->select();
                $rs_android = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field('regid')->where("regid is not null and regid !='' and os is not null and os='Android' and uid in(".$uidStr.")")->select();

                if(count($rs_ios['regid'])>0){
                    $result_ios = D("Notification")->pushNotification('ios',$rs_ios['regid'], $json['title'], $json['content'],true,$extras);
                }

                if(count($rs_android['regid'])>0){
                    $result_android = D("Notification")->pushNotification('android',$rs_android['regid'], $json['title'], $json['content'],true,$extras);
                }

                returnJson($result,200,'success');
            }
            else{
                returnJson("",401,'参数错误');
            }
        }catch(\Exception $e){
            returnJson($e->getMessage(),500,'errot');
        }
    }
    
    /**
     * @deprecated 中奖发送验证短信与邮件 id 中奖表的ID
     * */
    public function winningSendCode($id)
    {
        try{
            header('Content-Type:application/json; charset=utf-8');
            // $period = M('shop_period')->where("id=".$id)->field('sid,uid,no')->find();
            // $shopName=M('shop')->where("id=".$period['sid'])->getField('name');
            // $shopName='【（第'.$period['no'].'期）'.$shopName.'】';
            // $uid = M('user')->where("id=".$period['uid'])->getField('passport_uid');
            // D('Notification')->sendPhoneAndEmail($uid,$shopName);

            D('Notification')->sendNotificationByPid($id);
            exit;
        }catch(\Exception $e){
            
        }
    }

    /**
     *  新商品上架，消息事件通知
     */
    public function eventNotice4NewArrival(){
      // 消息推送
      // 是否有新上架的商品
        $count = D('Admin/shop')->hasNewArrival();
        if($count > 0){
            //有新品上架，需要发送通知
            $Notification = D('api/notification');
            $Notification->pushAllUser();
            echo 'notificat all user ...';
            return;
        }
        echo 'nothing to push ...';
    }
}