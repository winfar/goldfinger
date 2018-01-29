<?php

namespace api\Controller;

use Think\Controller;

class GoldController extends BaseController
{
    /**
     * 获取商品兑换的详细规则
     */
    public function detail($tokenid)
    {
        try {
            $param['tokenid'] = $tokenid;
            $result = D('GoldRecord')->getGoldByUid($param);
            returnJson($result, 200, 'success');
        } catch ( \Exception $e ) {
            returnJson($e->getMessage(), 500, 'error');
        }
    }

    public  function addGoldInfo(){
        try {
            $result = @file_get_contents("php://input");
            $json = json_decode($result, true);
            if ( isEmpty($json['tokenid']) ) {
                returnJson('', 1, '您还未登录！');
            }

            //兑换
            $exchangeConfigStatus = M('exchange_config')->where("name='hx_exchange_virtual'")->getField('status');

            if ( $exchangeConfigStatus <= 0 ) { 
                returnJson('', 2, '您暂时无法进行兑换！请稍后再试');
            } 

            // 检查虚拟卡商品是否已经发送过短信
            $ShopPeriod = D('ShopPeriod');
            $isSendSN = $ShopPeriod->isSendSN($json['pid']);
            if( (is_null($isSendSN) || $isSendSN['issend'] > 0) && $isSendSN['card_id'] != 0  ){
                returnJson('', 3, '不能兑换金币，商品已经发送过卡密！');
            }
            
            $param['tokenid'] = $json['tokenid'];
            $param['gold']=$json['gold'];
            $param['typeid']=$json['typeid'];
            $param['remark']=$json['remark'];
            $param['pid']=$json['pid'];
            

            $result = D('GoldRecord')->addGoldByUid($param);
            returnJson('', 200, 'success');
        } catch ( \Exception $e ) {
            returnJson($e->getMessage(), 500, 'error');
        }
    }

    /**
     * 获取金币兑换的比率规则
     */
    public function detailMock()
    {
//    {
//            "time": "string",
//            "type": "string",
//            "content": "string",
//            "gold": "string",
//            "tradetype": "string"
//    }

        $data = array(
            array('time' => '1472473735', 'type' => '1', 'content' => '购买失败退还', 'gold' => '10', 'tradetype' => '1'),
            array('time' => '1472473635', 'type' => '2', 'content' => '期数取消退还', 'gold' => '5', 'tradetype' => '1'),
            array('time' => '1472472735', 'type' => '3', 'content' => '商品下架退还', 'gold' => '66', 'tradetype' => '1'),
            array('time' => '1472463735', 'type' => '4', 'content' => '积分兑换', 'gold' => '50', 'tradetype' => '1'),
            array('time' => '1472373735', 'type' => '5', 'content' => '支付', 'gold' => '20', 'tradetype' => '1'),
            array('time' => '1472273735', 'type' => '8', 'content' => '系统修改', 'gold' => '10', 'tradetype' => '1'),
            array('time' => '1472173735', 'type' => '9', 'content' => '充值', 'gold' => '11', 'tradetype' => '1'),
            array('time' => '1472153735', 'type' => '8', 'content' => '系统修改', 'gold' => '-5', 'tradetype' => '2'),

        );
        returnJson($data, '200', 'success');
    }
}