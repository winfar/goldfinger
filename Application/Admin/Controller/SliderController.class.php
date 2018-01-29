<?php
namespace Admin\Controller;
use Think\Storage;

class SliderController extends WebController {

    public function index(){
        $map = array();
        //关键字查询
		if(isset($_GET['keyword'])){
            $where['title'] = array('like', '%' . I('keyword') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword'] = I('keyword');
        }

        //发布位置
        if ( isset($_GET['publishid']) ) {
            if(I('publishid') > 0 ){
                $publish = I('publishid') ;
                switch ($publish){
                    case 1:
                        $map['publish'] = array('in',"1,3");
                        break;
                    case  2:
                        $map['publish'] = array('in',"2,3");
                        break;
                    default:
                        break;
                }
            }
            $conditionarr['publishname'] = I('publishname');
            $conditionarr['publishid'] = I('publishid');
        }

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['start_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));

            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $map['status'] = 1 ;

        $list   =   $this->lists('Slider',$map,'app_order,create_time desc');
        $this->assign('list', $list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '图片管理管理';
        $this->display();
    }

    public function edit($id = null){
        $Slider = D('Slider');
        if(IS_POST){
            if(false !== $Slider->update()){
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Slider->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
			$info=$Slider->info($id);
            $this->assign('info',       $info);
            $this->meta_title = '编辑幻灯';
            $this->display();
        }
    }

    public function add(){
        $Slider = D('Slider');
        if(IS_POST){
            if(false !== $Slider->update()){
                //发送消息推送给所有商城用户
                //有新品上架，需要发送通知
                $Notification = D('api/notification');
                $Notification->pushAllUser();
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Slider->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '新增幻灯';
            $this->display('edit');
        }
    }

    public function del(){
//		$id = array_unique((array)I('id',0));
        $id = I('id',0);
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $res = D('Slider')-> where('id = '.$id)->setField('status','0');
//        $res = D('Slider')->remove($id);
        if($res !== false){
            $this->success('删除幻灯成功！');
        }else{
            $this->error('删除幻灯失败！');
        }
    }
	
	public function uploadPicture(){
        /* 返回标准数据 */
        $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
        /* 调用文件上传组件上传文件 */
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
		$pic_upload = C('PICTURE_UPLOAD');
		$pic_upload['rootPath']="./Picture/Slider/";
		$pic_upload['autoSub']=false;
        $info = $Picture->upload(
            $_FILES,
            $pic_upload,
            C('PICTURE_UPLOAD_DRIVER'),
            C("UPLOAD_{$pic_driver}_CONFIG")
        );
        if($info){
            $return['status'] = 1;
            $return = array_merge($info['download'], $return);
        } else {
            $return['status'] = 0;
            $return['info']   = $Picture->getError();
        }
        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }
    /**
     * 启动图设置
     * @author liuwei
     */
    public function update()
    {
        $model = M("Config");
        $default_item   =   $model->where(array('name'=>"SYSTEM_DEFAULT_DIAGRAM"))->field('value')->find();//获取系统默认图
        $item   =   $model->where(array('name'=>"ADVERTISING_CHART"))->field('value,extra,display')->find();//获取广告系统默认图

        if (!empty(IS_POST)) {

            //系统默认图修改
            $default_data = array();
            if (!empty($_POST['picture1'])) {
                //删除老图片
                if (!file_exists($default_item['value'])) {
                    $result =Storage::unlink('.'.$default_item['value']);
                }
                $default_data['value'] = trim($_POST['picture1']);
            }
            $default_result = 1;//1成功 2 失败
            //有修改则提交
            if (!empty($default_data)) {
                $default_result = $model->where(array('name'=>"SYSTEM_DEFAULT_DIAGRAM"))->save($default_data);
            }
            //广告系统默认图
            $item_data = array();
            if (!empty($_POST['picture2'])) {
                //删除老图片
                if (!file_exists($item['value'])) {
                    $result =Storage::unlink('.'.$item['value']);
                }
                $item_data['value'] = trim($_POST['picture2']);
            }
            if (!empty($_POST['url']) and $_POST['url']!=$item['extra']) {
                $item_data['extra'] = trim($_POST['url']);
            }
            $item_result = 1;//1成功 2 失败
            //有修改则提交
            if (!empty($item_data)) {
                $item_result = $model->where(array('name'=>"ADVERTISING_CHART"))->save($item_data);
            }
            if ($default_result==1 and $item_result==1) {
               $this->success('设置成功！', U('update'));
            } else {
                $this->error('设置失败！');
            }
        }

        $this->assign('default_item', $default_item);
        $this->assign('item', $item);
        $this->display('editor');
    }
    /**
     * 启动图设置-图片上传
     * @author liuwei
     */
    public function editPicture(){
        /* 返回标准数据 */
        $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
        /* 调用文件上传组件上传文件 */
        $picture = D('Picture');
        $driver = C('PICTURE_UPLOAD_DRIVER');
        $upload = C('PICTURE_UPLOAD');
        $upload['rootPath']="./Picture/StartUp/";
        $upload['autoSub']=false;
        $info = $picture->wxupload(
            $_FILES,
            $upload,
            C('PICTURE_UPLOAD_DRIVER'),
            C("UPLOAD_{$driver}_CONFIG")
        );
        if($info){
            $return['status'] = 1;
            $return = array_merge($info['download'], $return);
        } else {
            $return['status'] = 0;
            $return['info']   = $picture->getError();
        }
        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }
    /**
     * 显示不显示修改
     * @author liuwei
     */
    public function pictureDisplay()
    {
        $code = 101;
        if (!empty(IS_POST)) {
            $result = M("Config")->where(array('name'=>"ADVERTISING_CHART"))->save(['display'=>$_POST['status']]);
            if ($result == true) {
                $code = 200;
            }
        }
        echo json_encode($code);
    }
}
