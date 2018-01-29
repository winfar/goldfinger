<?php
/**
 * Author: zhangkang
 * Date: 2016/9/2416:28
 * Description:
 */

namespace Redirect\Controller;

use Think\Controller;

class InvitationController extends Controller
{
    public function invitationedit()
    {
        $this->display();
    }

    public function InvitationUpdate()
    {
        $code = I('code');
        $phone = I('num');

        $invitationid = M('user')->where("phone='" . $phone . "'")->getField('invitationid');
        if ( $invitationid ) {
            $re['result'] = 6;
            echo json_encode($re);
            exit;
        }
        $channelid = M('invitation')->where('id=' . $code)->getField('channelid');
        if ( $channelid ) {
            $user = M('user')->where("phone='" . $phone . "'")->find();
            if ( $user ) {
                $data['channelid'] = $channelid;
                $data['invitationid'] = $code;
                $count = M('user')->where("phone='" . $phone . "'")->setField($data);

                if ( $count ) {
                    $re['result'] = 3;
                } else {
                    if ( $channelid ) {
                        $re['result'] = 6;
                    } else {
                        $re['result'] = 2;
                    }
                }
            } else {
                $re['result'] = 1;
            }
        } else {
            $re['result'] = 2;
        }

        echo json_encode($re);
    }
}