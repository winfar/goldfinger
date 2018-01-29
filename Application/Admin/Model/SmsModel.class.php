<?php

namespace Admin\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;
use Com\Alidayu\AlidayuClient as Client;
use Com\Alidayu\Request\SmsNumSend;

class SmsModel extends Model
{
    public function sendSms($mobile,$smsTemplateCode,$smsParams,$smsFreeSignName='摸金达人'){
        
        //如果是非正式站，则不发送短信
        if(!isHostProduct()){
            return true;
        }

        $client = new Client;
        $request = new SmsNumSend;
        // $smsParams = array(
        //     'name' => $name,
        //     'card' => $no,
        //     'cardpassport' => $password,
        // );

        // 设置请求参数
        $req = $request->setSmsTemplateCode($smsTemplateCode)
            ->setRecNum($mobile)
            ->setSmsParam(json_encode($smsParams))
            ->setSmsFreeSignName($smsFreeSignName)
            ->setSmsType('normal')
            ->setExtend('');
        $request = $client->execute($req);
        $reqResult = $request["alibaba_aliqin_fc_sms_num_send_response"]["result"]["success"];

        if($reqResult===true){
            //设置发送状态为已发送
            return true;
        }
        else{
            return false;
        }
    }
}