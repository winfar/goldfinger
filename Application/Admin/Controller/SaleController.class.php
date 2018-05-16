<?php
/**
 * 活动管理
 * 
 * @author liuwei
 * @time   2016-10-20 
 */
namespace Admin\Controller;

use Think\Controller;

class SaleController extends WebController
{
	/**
	 * 活动列表
	 */
	public function index(){
		$this->meta_title = '促销活动列表';
        $map=array();
        //类别搜索
        if (!empty($_GET['type'])) {
        	$type = intval($_GET['type']);
        	$map['type'] = $type;
        	$conditionarr['type'] = $type;

        }
        //名称搜索
        if ( isset($_GET['keyword']) ) {
            $keyword = trim($_GET['keyword']);//把搜索的空格去掉
            $where['name'] = array('like', '%' . $keyword . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword'] = $keyword;
        }
        //开始结束时间搜索
        if ( !empty($_GET['starttime']) || !empty($_GET['endtime']) ) {
        	$start_time = trim($_GET['starttime']);
        	$end_time = trim($_GET['endtime']);
            $map['end_time']   = array(array('egt',strtotime($start_time)),array('lt',strtotime($end_time)+86400));
            $conditionarr['starttime'] = $start_time;
            $conditionarr['endtime'] = $end_time;
        }
        //列表
        $list = $this->lists('SalesPromotion',$map,$order='create_time desc',$rows=0,$base = array('id'=>array('egt',0)),$field=true);
        //循环并计算出状态值
        $data = array();
        $time = time();//现在时间戳
        if ($list) {
        	foreach ($list as $key => $value) {
        		$data[] = $value;
        		if ($time<$value['begin_time']) {//未开始
        			$data[$key]['status_name'] = "未开始";
        		} elseif ($time>=$value['begin_time'] and $time<=$value['end_time']) {//进行中
        			$data[$key]['status_name'] = "进行中";
        		} elseif ($time>$value['end_time']) {//已结束
        			$data[$key]['status_name'] = "已结束";
        		} else {
        			$data[$key]['status_name'] = "";
        		}
        	}
        }

        $this->assign('conditionarr',json_encode($conditionarr));
        $this->assign('_list', $data);
        $this->display();
	}

	/**
	 * 活动添加或者修改
	 */
	public function add()
	{
		try{
			if (!empty(IS_POST)) {
				$post = $_POST;
				$range_status = 0;//分类/品牌/商品 id集合是否必填 1是 0否
				$red_status = 0;//红包是否必填 1是 0否
				if (empty($post['name'])) {
					$this->error("活动名称不能为空");
					exit();
				}
				if (empty($post['begin_time'])) {
					$this->error("开始时间不能为空");
					exit();
				}
				if (empty($post['end_time'])) {
					$this->error("结束时间不能为空");
					exit();
				}
				if ($post['end_time'] <= $post['begin_time']) {
					$this->error("结束时间不能小于开始时间");
					exit();
				}
				if (empty($post['type'])) {
					$this->error("活动类型");
					exit();
				}
				
				$rs = D('SalesPromotion')->update($post);
				if($rs ==200){
					$this->success('成功！', U('index'));
				} else {
					$error = D('SalesPromotion')->getError();
					$this->error(empty($error) ? '金额下限/优惠金额/赠送金币/赠送积分格式不正确！' : $error);
				}
			}

			$type_list = M('category')->where(['status'=>1])->field('id,title')->order('create_time desc,sort asc')->select();//分类列表
        	$brand_list = M('brand')->where(['status'=>1])->field('id,title')->order('create_time desc,sort asc')->select();//分类列表
        	$shop_list = M('shop')->where(['status'=>1])->field('id,name')->order('create_time desc')->select();//商品列表
			//活动详情
			$item = array();
			if (!empty($_GET['id'])) {
				$id = intval($_GET['id']);
				$item = D("SalesPromotion")->getInfo($id);
			}
			$time = time();//现在时间
			$red_list = M('red_envelope')->where('status=1 and end_time>'.$time)->field('id,name')->order('create_time desc')->select();
			

			$this->assign('type_list', $type_list);
			$this->assign('brand_list', $brand_list);
			$this->assign('shop_list', $shop_list);
			$this->assign('info', $item);
			$this->assign('red_list', $red_list);	
			$this->display();
		} catch(Exception $e){
            $this->error($e->getMessage());
        }
	}

	/**
	 * 活动详情
	 */
	public function getDetail(){
		$list = array();
		if (!empty($_GET['id'])) {
			$id = intval($_GET['id']);
			$item = D("SalesPromotion")->getInfo($id, 'remark');
			if (!empty($item)) {
				$list = json_decode($item['remark'], ture);
			}

		}

		$this->assign('list', $list);
		$this->display('detail');
	}

	/**
	 * 红包详情
	 */
	public function getRed(){
		$list = array();
		if (!empty($_GET['id'])) {
			$id = intval($_GET['id']);
			$item = D("SalesPromotion")->getInfo($id, 'red_ids');
			if (!empty($item)) {
				$where = array();
				$where['id'] = array('in', $item['red_ids']);
				$list = M('red_envelope')->where($where)->order('create_time desc')->select();
			}

		}

		$this->assign('list', $list);
		$this->display('red');
	}
	
	/**
	 * 查看活动范围
	 */
	public function getRange(){
		$range = 0;
		$list = array();
		if (!empty($_GET['id'])) {
			$id = intval($_GET['id']);
			$item = D("SalesPromotion")->getInfo($id, 'range,range_ids');
			if (!empty($item)) {
				$range = $item['range'];
				$where = array();
				$where['id'] = array('in', $item['range_ids']);
				if ($range==1) {//分类
					$where['display'] = 1;
					$list = M('category')->where($where)->field('id,title,create_time')->order('create_time desc,sort asc')->select();	
				} elseif ($range==2) {//品牌
					$list = M('brand')->where($where)->field('id,title,create_time')->order('create_time desc,sort asc')->select();
				} else {//商品
					$list = M('shop')->where($where)->order('create_time desc')->select();
				}
			}
		}

		$this->assign('range', $range);
		$this->assign('list', $list);
		$this->display('range');
	}

	/**
	 * 禁用或者启用
	 */
	public function setStatus(){
        $id    =   I('request.id');
        $status =   I('request.status');

        if(empty($id)){
            $this->error('请选择要操作的数据');
        }
        $map['id'] = array('in',$id);

        switch ($status){
            case 0  :
            	$rs = M('SalesPromotion')->where($map)->setField('status',$status);
                $this->success('编辑成功！', U('index'));
            case 1  :
                $rs = M('SalesPromotion')->where($map)->setField('status',$status);
                $this->success('编辑成功！', U('index'));
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }
}
?>