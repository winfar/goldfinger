<?php

namespace api\Controller;

use Think\Controller;
use Think\Cache\Driver\RedisCache;

class MessageController extends BaseController
{
    
    /**
     * @deprecated 获取用户消息
     * @param $messageid 消息id
     * @param $uid 用户id
     * */
    public function getList($pageindex=1,$pagesize=20,$tokenid=0)
    {
        $pIndex = I('get.pageIndex');
        if(!empty($pIndex))
        {
            $pageindex=$pIndex;
        }
        // if(empty($tokenid)){
        //     returnJson('', 401, '参数不能为空');
        // }
        try{
            $uid=0;
            if(!empty($tokenid) || $tokenid>0){
                $user = isLogin($tokenid);
                if ( $user ) {
                    $uid=$user['uid'];
                }
            }

            $rs = D('message')->getListByUserId($pageindex,$pagesize,$uid);
            returnJson($rs, 200, 'success');
        }catch(\Exception $e){
            returnJson('error', 500, $e->getMessage());
        }
    }
    
    /**
     * @deprecated 删除用户消息
     * @param $id 消息id
     * */
    public function deleteUserMessage($tokenid, $id)
    {
        if(empty($tokenid) || empty($id)){
            returnJson('', 401, '参数不能为空');
        }

        try{
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }

            $msg = D('message')->getUserMessageDetails($user['uid'],$id);
            if($msg){
                $rs = D('message')->deleteUserMessage($id);
                if($rs){
                    returnJson($rs, 200, 'success');
                }
                else{
                    returnJson($rs, 410, '删除失败');
                }
            }
            else{
                returnJson($id, 404, '内容不存在');
            }
        }catch(\Exception $e){
            returnJson('error', 500, $e->getMessage());
        }
    }

    /**
     * @deprecated 设置消息已读
     * @param $messageid 消息id
     * @param $uid 用户id
     * */
    public function read($tokenid,$messageid)
    {
        if(empty($tokenid) || empty($messageid)){
            returnJson('参数不能为空', 401, 'error');
        }
        try{
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }

            $msg = D('message')->getUserMessageDetails($user['uid'],$messageid);
            if($msg){
                $map_user=array('uid'=>$user['uid'],'id'=>$messageid);
                $rs = M('message_user')->where($map_user)->setField('isread',1);
                if($rs>0){
                    returnJson($rs, 200, 'success');
                }
                else {
                    returnJson('', 404, '记录不存在');
                }
            }
            else{
                returnJson($messageid, 404, '内容不存在');
            }
        }catch(\Exception $e){
            returnJson('error', 500, $e->getMessage());
        }
    }
}