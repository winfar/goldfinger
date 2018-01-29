<?php
namespace api\Model;
use Think\Model;

/**
 * 消息模型
 */
class ShopAddressModel extends Model{

    public function addAddress($tokenid,$username,$tel,$address,$email){
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $data = array();
        $data['contacts'] = $username;
        $data['phone'] = $tel;
        $data['address'] = $address;
        $data['email'] = $email;
        $data['uid'] = $user['uid'];

        $result = $this->add($data);
        if ( $result >= 0 ) {
            returnJson('', '200', 'success');
        } else {
            returnJson('', '1', '确认地址失败');
        }
    }
}