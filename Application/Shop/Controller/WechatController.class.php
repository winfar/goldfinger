<?php

namespace Shop\Controller;

use Think\Controller;

class WechatController extends Controller
{   

    // protected function _initialize()
    // {
    //     parent::_initialize();
    // }

    //微信jsapi支付回调
    public function wxcallbacknotify(){
        
        recordLog('','微信支付回调开始');
        A('api/Wechat')->wxcallbacknotify();
        // echo 'shop result:'.$result;
    }
}	