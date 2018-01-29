<?php
namespace Admin\Controller;

class PkController extends WebController {
	/**
	 * pk房间列表
	 * 
	 * @return [type] [description]
	 */
	public function index()
	{
        //获取场次列表
        $peoplenum_list = D('Pk')->peoplenum();
		$map = array();//条件
		if ( !empty($_GET['keyword']) ) {//房间号
            $map['no'] = I('keyword');
        }
        if ( !empty($_GET['invitation_code']) ) {//邀  请  码
            $map['invitation_code'] = I('invitation_code');
        }
        if ( !empty($_GET['starttime']) ) {//开始时间
            $map['create_time'] = I('starttime');
        }
        if ( !empty($_GET['endtime']) ) {//结束时间
            $map['end_time'] = I('endtime');
        }
        if ( isset($_GET['ispublic']) ) {//是否公开
            $map['ispublic'] = I('ispublic');
        }
        if ( isset($_GET['isresolving']) ) {//是否解散
            $map['isresolving'] = I('isresolving');
        }
        if ( isset($_GET['peoplenum']) ) {//场次
            $map['peoplenum'] = I('peoplenum');
        }
		$rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        //总数
        $total = D('Pk')->total($map);
        //分页
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        //列表
		$list = D('Pk')->lists($map);

        $this->assign('peoplenum_list', $peoplenum_list);
		$this->assign('page', $p ? $p : '');
        $this->assign('total', $total);
		$this->assign('list', $list);
        $this->meta_title = 'pk房间列表';
		$this->display();
	}

	/**
	 * 房间内用户列表
	 * 
	 * @return [type] [description]
	 */
	public function user()
	{
		$no = I('no');
		//列表
		$list = D('Pk')->users($no);

		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 商品开奖列表
	 * 
	 * @return [type] [description]
	 */
	public function period()
	{
		$map = array();
		$map['house_id'] = I('id');//房间id
		$map['sid'] = I('shopid');//商品id
        $list = $this->lists('shop_period', $map, 'state asc,create_time desc', 0, '');
        $data = array();
        if (!empty($list)) {
        	foreach ($list as $key => $value) {
        		$data[] = $value;
        		//参与者
        		$data[$key]['users'] = M('shop_record')->alias('r')
		        ->join('LEFT JOIN __USER__ u ON r.uid = u.id ')
		        ->where('r.pid='.$value['id'])
		        ->field('u.nickname,r.num')
		        ->order('r.create_time desc')
		        ->select();

        	}
        	
        }

        $this->assign('price', I('price'));
        $this->assign('periodlist', $data);
        $this->meta_title = '商品管理';
        $this->display();
	}

}	