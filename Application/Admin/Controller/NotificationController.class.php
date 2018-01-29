<?php
/**
 * Created by PhpStorm.
 * User: ppa
 * Date: 2016/6/28
 * Time: 14:52
 */

namespace Admin\Controller;

class NotificationController extends WebController
{
    /**
     * 消息模板列表
     * 
     * @return [type] [description]
     */
    public function index(){
        $map = array();
        //$map['type'] = array("elt",101);
        $order="create_time desc";
        $list   =   $this->lists('Message', $map ,$order);
        $this->assign('_list', $list);
        $this->meta_title = '消息模板管理';
        $this->display();
    }
    public function edit()
    {
        recordLog('进入消息模板模块','消息模板编辑');
        $Shop = D('Shop');

        if ( IS_POST ) {
            $title = trim($_POST['title']);
            $msgType = I('msgType');
            $content = trim(I('content'));
            if ( $title=="" ) {
                $this->error('消息标题不能为空！');
            }
            if ( $msgType=="" ) {
                $this->error('消息类型不能为空！');
            }
            if ( $content == "" ) {
                $this->error('消息内容不能为空');
            }
            try{ 
                if (!empty($_POST['id'])) {
                    $param['id'] = I('id');
                } else {
                    $param['create_time'] = time();
                }
                $param['title'] = $title;
                $param['type'] = $msgType;
                $param['content'] = $content;
                $result = D('Notification')->addMsg($param);
                if ($result!=false) {
                    $this->success('编辑成功', U('index'));
                } else {
                    $this->error('系统内容没有任何改变无需编辑');
                }
                
            }catch(\Exception $e){
                $this->error($e->getMessage());
            }
        } else {
            $id = empty($_GET['id']) ? '' : I('id');
            $info = array();
            if (!empty($id)) {
                $info = M('message')->where('id='.$id)->find();
            } 
            $this->assign('info', $info);
            $this->meta_title = '消息模板添加&修改';
            $this->display();
        }
        
    }

    public function sendjpushinfo()
    {
        if ( IS_POST ) {
            $this->meta_title = '站内消息—消息发送';

            $title = trim($_POST['title']);
            $msgType = I('msgType');
            //$platformTypes = I('platformType');
            $content = trim(I('content'));
            $msgLink = trim(I('msgLink'));

            if ( $msgType=="" ) {
                $this->error('消息类型不能为空！');
            }
            if ( $content == "" ) {
                $this->error('消息内容不能为空');
            }

            $extras =array ();
            if ( $msgLink!="" ) {
                if(is_numeric($msgLink)){
                    $pid = M('shop_period')->where('state=0 and sid='.$msgLink)->getField('id');
                    $extras =array ('type'=>2,'data'=>array ('pid'=>$pid));
                }
            }
            try{ 
                if ( $msgType == 1 ) {
                    // foreach ( $platformTypes as $key => $item ) {
                    //     if ( $item == 1 ) {//IOS
                    //         //$rs = D('Notification')->pushNotification('ios',array('1517bfd3f7cef58961f'), $title,$content,true,$extras,true);
                    //         $rs = D('Notification')->JpushIosAll($title,$content,array());
                    //     }
                    //     if ( $item == 2 ) {
                    //         //$rs = D('Notification')->pushNotification('android',array('1104a89792afbc1dcf1'), $title,$content,true,$extras,true);
                    //         $rs = D('Notification')->JpushAndroidAll($title,$content,array());
                    //     }
                    // }
                    $rs = D('Notification')->JpushAll($title,$content,$extras);
                }

                if ( $msgType == 2 ) {
                    $param['link'] = $msgLink;
                    D('Notification')->addMsg($param);
                }

                $this->success('发送成功');
            }catch(\Exception $e){
                $this->error($e->getMessage());
            }

            $this->display();
        } else {
            $this->meta_title = '站内消息—消息发送';
            $this->display();
        }
    }
}