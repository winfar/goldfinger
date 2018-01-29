<?php
namespace api\Controller;
use Think\Controller;
class AddressController extends BaseController
{
    /**
     * 增加用户收货地址
     * @param $username
     * @param $tel
     * @param $address
     * @param string $email
     */
    public function addAddress(){

        $result = file_get_contents('php://input');
        recordLog($result, 'addAddress');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            return returnJson('', 1, '您还未登录！');
        }
        if ( isEmpty($json['username']) ) {
            return returnJson('', 1, '联系人不能为空！');
        }
        if ( isEmpty($json['tel']) ) {
            return returnJson('', 1, '联系电话不能为空！');
        }
        if ( isEmpty($json['address']) ) {
            return returnJson('', 1, '详细地址不能为空！');
        }

        if ( isEmpty($json['email']) ) {
            return returnJson('', 1, 'email不能为空！');
        }

        $userAddress = D('shop_address')->addAddress($json['tokenid'],$json['username'],$json['tel'],$json['address'],$json['email']);

    }
}