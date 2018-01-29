<?php

/** * Created by PhpStorm. * User: wenyuan * Date: 2016/9/28 * Time: 15:51 */

namespace Admin\Controller;

class SalesController extends WebController {

    public function redEnvelopeList(){
        $this->meta_title = '红包列表';

        $map=array();
        if(I('category')!==''){
            $map['category']= I('category');
        }
        if(!empty(I('keyword'))){
            $map['name']=array('like','%'.I('keyword').'%');
        }
        if(!empty(I('starttime'))){
            $map['begin_time']= array('egt',strtotime(I('starttime')));
        }
        if(!empty(I('endtime'))){
            $map['end_time']=  array('lt',strtotime(I('endtime')));
        }

        $list = $this->lists('RedEnvelope', $map,'create_time desc');

        $this->assign('_list', $list);
        $this->display();
    }

    public function redEnvelopeRecord(){
        $this->meta_title = '发放/使用记录';

        $map=array();
        // if(I('category')!==''){
        //     $map['category']= I('category');
        // }
        if(!empty(I('isuse'))){
            if(I('isuse')!="2"){
                $map['status']=intval(I('isuse'));
            }
        }
        if(!empty(I('phone'))){
            $map['u.phone']=array('like','%'.I('phone').'%');
        }
        if(!empty(I('starttime')) && !empty(I('endtime'))){
            $map['rer.create_time']= array('BETWEEN',array(strtotime(I('starttime')),strtotime(I('endtime'))));
            //array_push($map['create_time'], array("egt", strtotime(I('starttime'))));
        }

        // SELECT u.phone,re.id,re.`name`,re.category,re.category_values,re.specialarea,re.total_amount,re.min_amount,re.amount,re.begin_time,re.end_time,rer.uid,
		// IF(UNIX_TIMESTAMP(NOW())>re.end_time,1,0) as outdate,rer.`status`,rer.create_time 
		// FROM hx_red_envelope re,hx_red_envelope_record rer,hx_user u 
		// WHERE ( re.id=rer.red_envelope_id AND rer.uid=u.id AND re.`status`>0 AND re.quantity>0)
		// ORDER BY rer.create_time DESC

        $model = D('RedEnvelopeRecord')
        ->table('__RED_ENVELOPE__ re,__RED_ENVELOPE_RECORD__ rer,__USER__ u');

        if(I('isuse')=="2"){
            $model->having('outdate=1');
        }

        $list = $this->lists($model, 're.id=rer.red_envelope_id AND rer.uid=u.id','rer.create_time DESC',0,$map,'u.phone,re.id,re.`name`,re.category,re.category_values,re.specialarea,re.total_amount,re.min_amount,re.amount,re.begin_time,re.end_time,rer.uid,IF(UNIX_TIMESTAMP(NOW())>re.end_time,1,0) as outdate,rer.`status`,rer.create_time');

        $this->assign('_list', $list);
        $this->assign('_params', I('get.'));
        $this->display();
    }

    public function redEnvelopeStatus(){
        $id    =   I('request.id');
        $status =   I('request.status');

        if(empty($id)){
            $this->error('请选择要操作的数据');
        }
        $map['id'] = array('in',$id);

        switch ($status){
            case 0  :
            case 1  :
                $rs = M('RedEnvelope')->where($map)->setField('status',$status);
                $this->success('编辑成功！', U('redenvelopelist'));
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }
	
	public function redenvelopeDetails($id = null){
        $this->meta_title = '生成红包';

        $type_list = M('category')->where(['status'=>1])->field('id,title')->order('create_time desc,sort asc')->select();//分类列表
        $brand_list = M('brand')->where(['status'=>1])->field('id,title')->order('create_time desc,sort asc')->select();//分类列表
        $specialarea_list = M('ten')->where('status=1')->order('sort')->select();
        $info = array();

        $this->assign('type_list',$type_list);
        $this->assign('brand_list',$brand_list);
        $this->assign('specialarea_list', $specialarea_list);
        $this->assign('_params', I('get.'));
        
        if(IS_POST){
            $rs = D('RedEnvelope')->update();

            if($rs!==false){
                $this->success('编辑成功！', U('redenvelopelist'));
            } else {
                $error = D('RedEnvelope')->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }
        else{
            if($id){
                $this->meta_title = '编辑红包';

                $map = array();
                if ( is_numeric($id) ) {
                    $map['id'] = $id;
                }
                $info = M('RedEnvelope')->field(true)->where($map)->find();
                $category_data = array();//已选中的分类或者品牌列表
                if (!empty($info)) {
                    if ($info['category']==101) {//分类
                        $category_data = M('category')->where(['status'=>1,'id'=>array('in', $info['category_values'])])->field('id,title')->order('create_time desc,sort asc')->select();//分类列表

                    } elseif($info['category']==102) {//品牌
                        $category_data = M('brand')->where(['status'=>1,'id'=>array('in', $info['category_values'])])->field('id,title')->order('create_time desc,sort asc')->select();//分类列表
                    }

                }

                $this->assign('category_data', $category_data);
                $this->assign('info', $info);
                // $this->assign('category', D('Shop')->getTree());
                // $this->assign('ten', D('ten')->getTree());
                // $this->assign('brand', D('brand')->getBrand(1));
                // $this->meta_title = '编辑商品';
            }
            $this->display();
        }
	}
    /**
     * 分类详情
     *
     * @author liuwei
     * @return json
     */
    public function cateInfo() {
        $result = array();
        $result['code'] = 101;
        $result['data'] = array();
        $result['msg'] = "提交方式不正确";
        if (IS_POST) {
            $cate_id = intval($_POST['cate_id']);//要添加的分类id
            $category = trim($_POST['category']);//已经添加的分类id
            $category_code = 200;
            if (!empty($category)) {
                $cate_array = explode(',',$category);
                if (in_array($cate_id, $cate_array)) {
                    $category_code = 101;
                }
            }
            if ($category_code==200) {
                $result['code'] = 200;
                $result['data'] = M('category')->where('id='.$cate_id)->field('id,title')->find();
                $result['msg'] = "成功";
            } else {
                $result['msg'] = "不能重复选择";
            }
        }
        echo json_encode($result);
    }
    /**
     * 品牌详情
     *
     * @author liuwei
     * @return json
     */
    public function brandInfo() {
        $result = array();
        $result['code'] = 101;
        $result['data'] = array();
        $result['msg'] = "提交方式不正确";
        if (IS_POST) {
            $brand_id = intval($_POST['brand_id']);//要添加的品牌id
            $brand = trim($_POST['brand']);//已经添加的分类id
            $category_code = 200;
            if (!empty($brand)) {
                $cate_array = explode(',',$brand);
                if (in_array($brand_id, $cate_array)) {
                    $category_code = 101;
                }
            }
            if ($category_code==200) {
                $result['code'] = 200;
                $result['data'] = M('brand')->where('id='.$brand_id)->field('id,title')->find();
                $result['msg'] = "成功";
            } else {
                $result['msg'] = "不能重复选择";
            }
        }
        echo json_encode($result);
    }
    /**
     * 商品详情
     *
     * @author liuwei
     * @return json
     */
    public function shopInfo() {
        $result = array();
        $result['code'] = 101;
        $result['data'] = array();
        $result['msg'] = "提交方式不正确";
        if (IS_POST) {
            $shop_id = intval($_POST['shop_id']);//要添加的品牌id
            $brand = trim($_POST['brand']);//已经添加的分类id
            $category_code = 200;
            if (!empty($brand)) {
                $cate_array = explode(',',$brand);
                if (in_array($shop_id, $cate_array)) {
                    $category_code = 101;
                }
            }
            if ($category_code==200) {
                $result['code'] = 200;
                $result['data'] = M('shop')->where('id='.$shop_id)->field('id,name')->find();
                $result['msg'] = "成功";
            } else {
                $result['msg'] = "不能重复选择";
            }
        }
        echo json_encode($result);
    }
	
}
