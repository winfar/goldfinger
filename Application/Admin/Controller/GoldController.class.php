<?php
namespace Admin\Controller;

class GoldController extends WebController
{

    public function _initialize()
    {
        parent::_initialize();
        vendor("phpexcel.Classes.PHPExcel");
    }
    public function index()
    {
        
        $map = array();
        $conditionarr = array();

        if ( !empty($_GET['keyword']) ) {
            $map['name'] = I('keyword');
            $conditionarr['keyword'] = I('keyword');
        }

        if ( $_GET['state'] != "" ) {
            $map['state'] = I('state');
            $conditionarr['state'] = I('state');
        }

        if ( !empty($_GET['fictitious']) ) {
            $map['fictitious'] = I('fictitious');
            $conditionarr['fictitious'] = I('fictitious');
        }

        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');

        }

        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }

        if ( $_GET['type'] != "" ) {
            $map['ten'] = I('type');
            $conditionarr['ten'] = I('type');
        }

        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $total = D('Shop')->getShopsTotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Shop')->getShops($map);
        if ( $list ) {
            foreach ( $list as $key => $item ) {
//                $list[$key]['end_time']=$list[$key]['end_time']>0?$list[$key]['end_time']:'';
                if ( isset($item['ten']) ) {
                    $list[$key]['tenunit'] = D('ten')->info($item['ten'], 'unit')['unit'];
                }
                if ( isset($item['id']) ) {
//                    $list[$key]['no'] = D('Period')->getPeriodNoBySid($item['id'], 'no');
//                    $list[$key]['nocount'] = D('Period')->getPeriodNoBySid($item['id'], 'nocount');
                    $list[$key]['statename'] = get_state($item['state']);
                }
            }
        }

        $this->assign('shoplist', $list);
        $this->assign('category', D('Shop')->getTree());
        $this->assign('ten', D('ten')->getTree());

        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '活动列表';
        $this->display();
    }

    /**
     * 商品兑换模块
     */
    public function shop(){
        $config  = D('ExchangeConfig');
        $status = $config->where(array('name'=>'bo_exchange_virtual'))->field('status')->find();

        $Shop =  M('ExchangeVirtual');
        $Shop->table('__EXCHANGE_VIRTUAL__ shop');
        $Shop->join(' LEFT JOIN __BRAND__ brand ON shop.bid = brand.id');
        $list =  $this->lists($Shop ,$where=array(),$order='shop.status desc',$rows=0,$base = array('shop.status'=>array('egt',0)),$field='shop.id,brand.title,rate,shop.status');

        $this->assign('status',       $status);
        $this->assign('_list',       $list);
        $this->meta_title = '商品兑换';
        $this->display();
    }

    public function addBrand(){
          $brandId = I('get.id');
        //插入表Exchange_virtual
        $Shop =  D('ExchangeVirtual');
        $data['cid']= '110'; //固定值
        $data['bid'] = $brandId ;

        if($Shop->create($data)){
            $result = $Shop->add(); // 写入数据到数据库
            if($result){
                $this->success('！编辑成功！', U('shop'));
            }else{
                $this->error('！编辑失败！', U('shop'));

            }
        }else{
            $this->error('！数据验证失败！', U('shop'));
        }
    }

    public function addRecharge(){
        //插入表Exchange_virtual
        $Recharge =  D('ExchangeRecharge');
        $data['min'] = '0';
        $data['max'] = '0';
        $result = $Recharge->add($data); // 写入数据到数据库
        if($result){
            $this->success('！编辑成功！', U('recharge'));
        }else{
            $this->error('！编辑失败！', U('recharge'));
        }
    }

    /**
     * 商品兑换比例保存
     */
    public function shopsave(){
        $Shop = D('ExchangeVirtual');
        if(IS_POST){
        	$id = I('POST.id');
        	for ($i=0;$i<count($id);$i++){
        		$id_value = $id[$i];
        		$rate_value = I("POST.rate".$id_value);
	        	if(false !== $Shop->where(array('id'=>$id_value))->setField('rate',$rate_value)){
	            } else {
	                $error = $Shop->getError();
	                $this->error(empty($error) ? '未知错误！' : $error);
	            }
        	}
        	$this->success('编辑成功！',U('shop'));
        	
        }
    }

    /**
     * 商品兑换比例保存
     */
    
 	public function rechargeSave(){
        $Shop = D('ExchangeRecharge');
        if(IS_POST){
           $id = I('POST.id');
        	for ($i=0;$i<count($id);$i++){
        		$id_value = $id[$i];
        		$data['min'] = I("POST.min".$id_value);
        		$data['max'] = I("POST.max".$id_value);
        		$data['gold'] = I("POST.gold".$id_value);
        		$data['point'] = I("POST.point".$id_value);
        		$data['red_packet'] = I("POST.red_packet".$id_value);
        		if(false !== $Shop->where(array('id'=>$id_value))->save($data)){
	            } else {
	                $error = $Shop->getError();
	                $this->error(empty($error) ? '未知错误！' : $error);
	            }
        	}
        	$this->success('编辑成功！',U('recharge'));
        }
    }
    
    
    

    /**
     * 充值送金币
     */

    /**
     * 商品兑换模块
     */
    public function recharge(){
        $config = D('ExchangeConfig');
        $status = $config->getStatus('bo_exchange_recharge');

        $Recharge =  M('ExchangeRecharge');
        $list =  $this->lists($Recharge,array(),$order='min asc',0, array());

        $this->assign('status',  $status);
        $this->assign('_list',   $list);
        $this->meta_title = '充值送金币';
        $this->display();
    }

    /**
     * 商品兑换 删除指定商品设置
     */
    public function shopremove(){
        if(IS_GET){
            $id = I('get.id');
            //删除该栏目信息
            $res = M('ExchangeVirtual')->delete($id);
            if($res !== false){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }
    }

    /**
     * 商品兑换 删除指定商品设置
     */
    public function rechargeRemove(){
        if(IS_GET){
            $id = I('get.id');
            //删除该栏目信息
            $this->remove(M('ExchangeRecharge'),$id);
        }
    }
    /**
     * 删除通用方法
     * @param $model 实例化的model
     * @param $id
     */
    public function remove($model,$id){  
            //删除该栏目信息
            $res = $model->delete($id);
            if($res !== false){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            } 
    }

    /**
     * 查看金币明细
     * @param $model
     * @param $id
     */
    public function detail(){
        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keyword']) ) {
            $where['u.username'] = array('like', '%' . I('keyword') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword'] = I('keyword');
        }

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['a.create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        if ( isset($_GET['tradetype']) ) {
            $map['t.code'] = I('tradetype');
            $conditionarr['tradetype'] = I('tradetype');
        }        
        //获取所有用户金币总和
        $gold_total = D('Shop')->user_gold_total($conditionarr);
        $Model = D('GoldRecord');
        $Model->alias('a')
            ->join(array(' LEFT JOIN __USER__ u ON u.id= a.uid',' LEFT JOIN __TRADE_TYPE__ t ON t.id= a.typeid'));
        $list = $this->lists($Model, $map,'create_time desc',$rows=0,$base = array('status'=>array('egt',0)),'a.id,a.create_time,a.remark,a.gold,u.username,u.nickname,u.id uid,u.phone,t.name');

        $this->assign('gold_total',       $gold_total);
        $this->assign('_list',       $list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '金币明细';
        $this->display();

    }

    /**
     * 查看支付来源
     * wenyuan
     * @param $model
     * @param $id
     */
    public function source(){
        $conditionarr = array();
        $map = array();
        $map_gold = array();
        // if ( isset($_GET['keyword']) ) {
        //     $where['u.username'] = array('like', '%' . I('keyword') . '%');
        //     $where['_logic'] = 'or';
        //     $map['_complex'] = $where;
        //     $conditionarr['keyword'] = I('keyword');
        // }

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['o.create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $map_gold['create_time']   = $map['o.create_time'] ;
            // $conditionarr['starttime'] = I('starttime');
            // $conditionarr['endtime'] = I('endtime');
        }

        // if ( isset($_GET['paytype']) ) {
        //     $map['o.type'] = I('paytype');
        //     // $conditionarr['paytype'] = I('paytype');
        // }        

        //$subQuery = D('shop_order')->field('id')->where(" pid=0 OR p.state>2 OR o.`code`='FAIL' OR (CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))>0 OR (p.state=2 AND s.fictitious=2 AND s.proc_type='goldbag')")->buildSql(); 
        $subQuery = D('shop_order')->field('id')->where(" pid=0 OR (p.state>2 and o.code='OK') OR o.`code`='FAIL' OR (p.state=2 AND s.fictitious=2 AND s.proc_type='goldbag' and o.code='OK' and (CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))=0) OR ((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))>0 and o.`code`='OK' and pid>0) ")->select(false);
        
        //$map['o.id'] = array('in',$subQuery);
        //var_dump($where);

        //$map['_complex'] = $where;
        $map['cash']  = array('egt',1);
        //获取所有用户金币总和
        //$gold_total = D('Shop')->user_gold_total($conditionarr);

        $joins = array(' LEFT JOIN __SHOP_PERIOD__ p on o.pid=p.id',' LEFT JOIN __SHOP__ s on s.id=p.sid',' LEFT JOIN __TEN__ ten on ten.id=s.ten ');
        
        $Model = D('shop_order')->alias('o')->join($joins)->group('o.type');

        // $list = D('shop_order')->alias('o')
        //     ->field('o.type,sum(cash) fee')
        //     ->join(array(' LEFT JOIN __SHOP_PERIOD__ p on o.pid=p.id',' LEFT JOIN __SHOP__ s on s.id=p.sid'))
        //     ->where('o.id in '.$subQuery)
        //     ->where($map)
        //     ->group('o.type')
        //     ->order('o.type ,o.create_time DESC')
        //     ->select();

        $list = $this->lists($Model, 'o.id in '.$subQuery,'o.type ,o.create_time DESC',$rows=0,$map,"o.type, sum(o.number) num, sum(cash) fee, sum(if((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))>0 and o.`code`='OK',(CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED)),cash)) back_gold");

        // exit(M()->getLastSql());

        //$this->assign('gold_total',       $gold_total);
        $this->assign('_list',$list);

        $exchange_gold = M('gold_record')->where('typeid=6')->where($map_gold)->sum('gold');
        $artificial_gold = M('gold_record')->where('typeid=8')->where($map_gold)->sum('gold');

        $this->assign('exchange_gold',$exchange_gold==null?0:$exchange_gold);
        $this->assign('artificial_gold',$artificial_gold==null?0:$artificial_gold);
        
        //$this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '支付来源';
        $this->display();

    }

    /**
    * 支付来源明细
    * wenyuan
    *
    */
    public function sourcedetails(){
        
        $map = array();
        $map['cash']  = array('egt',1);

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['o.create_time'] = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $map_other['gr.create_time'] = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
        }

        if(!empty(I('type'))){
            $map['type']  = I('type');
        }

        $fields = "if(o.pid=0,'充值',if(p.state>2 and o.code='OK','下架', if(o.`code`='FAIL','完全失败',if((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))>0 and o.`code`='OK' and pid>0,'部分失败','金袋')))) t,o.order_id,o.uid,u.username,p.`no`,p.sid,s.`name`,if(o.pid=0,'充值',if(s.fictitious=2,'虚拟','实物')) ordertype,o.create_time,p.kaijang_time,o.`code`,p.state,o.type,o.number,o.gold,o.cash,(o.gold+o.cash) pay_total,if(o.`code`='FAIL',o.gold+o.cash,ifnull((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED)),0)) fail_amount,((o.gold+o.cash)-if(o.`code`='FAIL',o.cash,ifnull((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED)),0))) success_amount";

        $joins = array(' LEFT JOIN __SHOP_PERIOD__ p on o.pid=p.id',' LEFT JOIN __SHOP__ s on s.id=p.sid',' LEFT JOIN __USER__ u on o.uid=u.id ',' LEFT JOIN __TEN__ ten on ten.id=s.ten ');

        $subQuery = D('shop_order')->field('id')->where(" pid=0 OR (p.state>2 and o.code='OK') OR o.`code`='FAIL' OR (p.state=2 AND s.fictitious=2 AND s.proc_type='goldbag' and o.code='OK' and (CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))=0) OR ((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED))>0 and o.`code`='OK' and pid>0) ")->select(false);

        if(I('export')=='export'){
            // 初始化
            $objPHPExcel = new \PHPExcel();

            $rechargeType = get_recharge(I('type'));

            //获取数据
            if(I('type')>20000){
                switch (I('type')) {
                    case 20001:
                        $map_other['typeid']=6;//虚拟商品兑换 
                        $rechargeType = '虚拟商品兑换';
                        break;
                    case 20002:
                        $map_other['typeid']=8;//后台添加（活动） 
                        $rechargeType = '活动';
                        break;
                    default:
                        $this->display();
                        exit();
                        break;
                }

                $list = D('gold_record')->alias('gr')->field('u.username, gr.*')->join(array(' LEFT JOIN __USER__ u on gr.uid=u.id '))
                ->where($map_other)->order('gr.create_time DESC')->select();

                $arrFiled = array('金币来源','用户ID','用户名','兑换时间','金币','备注');

                $i = 3;
                foreach ( $list as $item ) {
                    $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $rechargeType);
                    $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['uid']);
                    $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['username']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, date("Y/m/d H:i:s",$item['create_time']));
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['gold']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['remark']);
                    $i++;
                }
            }
            else{
                $list = D('shop_order')->alias('o')->join($joins)->field($fields)
                ->where('o.id in '.$subQuery)->where($map)->order('o.create_time DESC')->select();

                $arrFiled = array('金币来源','支付流水号','用户ID','用户名','商品期号','商品ID','商品名称','订单类型','支付时间','开奖时间','参与状态','活动状态','支付平台','金币','现金','购买成功','购买失败','总金额');

                $i = 3;
                foreach ( $list as $item ) {
                    $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['t']);
                    $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, "'".$item['order_id']);
                    $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['uid']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['username']);
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['no']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['sid']);//date("Y/m/d H:i:s",$item['create_time'])
                    $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['ordertype']);
                    $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, date("Y/m/d H:i:s",$item['create_time']));
                    $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, empty($item['kaijang_time'])?'':date("Y/m/d H:i:s",$item['kaijang_time']));
                    $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $item['code']);
                    $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, get_state($item['state']));
                    $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, get_recharge($item['type']));
                    $objPHPExcel->getActiveSheet()->setCellValue('N' . $i, $item['gold']);
                    $objPHPExcel->getActiveSheet()->setCellValue('O' . $i, $item['cash']);
                    $objPHPExcel->getActiveSheet()->setCellValue('P' . $i, $item['success_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('Q' . $i, $item['fail_amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('R' . $i, $item['pay_total']);
                    $i++;
                }
            }

            // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            $objPHPExcel->getActiveSheet()->setCellValue('A' . 1, "开始时间：".I('starttime'));
            $objPHPExcel->getActiveSheet()->setCellValue('B' . 1, "结束时间：".I('endtime'));
            $objPHPExcel->getActiveSheet()->setCellValue('C' . 1, "支付类型：".$rechargeType);

            foreach ( $arrFiled as $key => $value ) {
                $ch = chr(ord('A') + intval($key));
                $objPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
            }

            $outputFileName = '金币来源_' . $rechargeType . '_' . date("YmdHis") . '.csv';

            //  $xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $xlsWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            //ob_start(); ob_flush();
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Disposition:inline;filename="' . $outputFileName . '"');
            header("Content-Transfer-Encoding: binary");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            $xlsWriter->save("php://output");
            
        }
        else {

            if(I('type')>20000){

                switch (I('type')) {
                    case 20001:
                        $map_other['typeid']=6;//虚拟商品兑换 
                        break;
                    case 20002:
                        $map_other['typeid']=8;//后台添加（活动） 
                        break;
                    default:
                        $this->display();
                        exit();
                        break;
                }
                
                $Model = D('gold_record')->alias('gr')->join(array(' LEFT JOIN __USER__ u on gr.uid=u.id '));

                $list = $this->lists($Model, '','gr.create_time DESC',$rows=0,$map_other,"u.username, gr.*");
            }
            else {
                $Model = D('shop_order')->alias('o')->join($joins);

                $list = $this->lists($Model, 'o.id in '.$subQuery,'o.create_time DESC',$rows=0,$map,$fields);

                // SELECT if(o.pid=0,'充值',if(p.state>2,'下架', if(o.`code`='FAIL','购买失败','金袋'))) t,o.order_id,o.uid,u.username,p.`no`,p.sid,s.`name`,if(o.pid=0,'充值',if(s.fictitious=2,'虚拟','实物')) ordertype,o.create_time,p.kaijang_time,o.`code`,p.state,o.type,o.number,o.gold,o.cash,(o.gold+o.cash) pay_total,if(o.`code`='FAIL',o.gold+o.cash,ifnull((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED)),0)) fail_amount,((o.gold+o.cash)-if(o.`code`='FAIL',o.cash,ifnull((CONVERT(((o.gold+o.cash)/ten.unit-o.number)*ten.unit,SIGNED)),0))) success_amount
                // FROM hx_shop_order o 
                // LEFT JOIN hx_shop_period p on o.pid=p.id 
                // LEFT JOIN hx_shop s on s.id=p.sid 
                // LEFT JOIN hx_user u on o.uid=u.id 
                // LEFT JOIN hx_ten ten on ten.id=s.ten 
                // WHERE ( o.id in ( SELECT `id` FROM `hx_shop_order` WHERE ( pid=0 OR p.state>2 OR (p.state=2 AND s.fictitious=2 AND s.proc_type='goldbag') ) ) ) 
                // AND `cash` >= 1 
                // ORDER BY o.create_time DESC   
            }         

            $this->assign('_list',$list);

            $this->meta_title = '支付来源明细';
            $this->display();
        }        
    }

    /**
     * 通过id获取金币明细详情
     * @param $id
     */
    public function getDetailById($id){
        $Model = D('GoldRecord');
        $map['id'] = $id;
        $data = $Model->where($map)->getField('remark');
        $remark =  json_decode($data,true);
        $this->assign('remark', $remark);
        $this->meta_title = '金币详情';
        $this->display('detailinfo');
    }

    /**
     * 设置兑换设置的状态（启用，禁用）-通用方法
     *
     */
    public function setConfigState(){
        if(!empty(I('name'))){
            $name = I('name');
        }
        $model = D('ExchangeConfig');
        
        if(!empty(I('get.status'))){
            $status = I('get.status');
        }

        if(false !== $model->where(array('name'=>$name))->setField('status',$status)){
            $this->success('编辑成功！');
        } else {
            $error = $model->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
        }
    }
    /**
     * 导出
     * @author liuwei
     */
    public function export()
    {
        //条件
        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keyword']) ) {
            $where['u.username'] = array('like', '%' . I('keyword') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword'] = I('keyword');
        }

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['a.create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        if ( isset($_GET['tradetype']) ) {
            $map['t.code'] = I('tradetype');
            $conditionarr['tradetype'] = I('tradetype');
        }
        $Model = D('GoldRecord');
        $Model->alias('a')
            ->join(array(' LEFT JOIN __USER__ u ON u.id= a.uid',' LEFT JOIN __TRADE_TYPE__ t ON t.id= a.typeid'));
        $list =$this->lists_data($Model, $map,'create_time desc',$base = array('status'=>array('egt',0)),'a.id,a.create_time,a.remark,a.gold,a.uid,u.username,u.nickname,u.phone,t.name'); 
        //获取所有用户金币总和
        $gold_total = D('Shop')->user_gold_total($conditionarr);  
        // 初始化
        $resultPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $title ='用户支出总数:'.$gold_total['increase'].',  用户收入总数:'.$gold_total['reduce'].',  用户金币总金额:'.$gold_total['total'];
        $resultPHPExcel->getActiveSheet()->mergeCells('A1:M1');
        $resultPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
            array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                )
            )
        );      //合并
        $resultPHPExcel->getActiveSheet()->setCellValue('A1', $title);
        $arrFiled = array('用户编号','商品名称', '期号', '金币支付金额', '现金支付金额', '购买成功金额', '购买失败金额', '日期', '用户名称','昵称', '手机号', '类型','金币数量');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
        }
       

        $i = 3;
        if (!empty($list)) {
            $i = 3;
            foreach ( $list as $item ) {
                $remark = (array)json_decode($item['remark']);
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['uid']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, empty($remark['商品名称']) ? '' : $remark['商品名称']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, empty($remark['商品期号']) ? '-' : $remark['商品期号']);
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, empty($remark['金币支付金额']) ? '0' : $remark['金币支付金额']);
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, empty($remark['现金支付金额']) ? '0' : $remark['现金支付金额']);
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, empty($remark['购买成功金额']) ? '0' : $remark['购买成功金额']);
                $resultPHPExcel->getActiveSheet()->setCellValue('G' . $i, empty($remark['购买失败金额']) ? '0' : $remark['购买失败金额']);
                $resultPHPExcel->getActiveSheet()->setCellValue('H' . $i, date("Y-m-d H:i:s", $item['create_time']));
                $resultPHPExcel->getActiveSheet()->setCellValue('I' . $i, $item['username']);
                $resultPHPExcel->getActiveSheet()->setCellValue('J' . $i, $item['nickname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('K' . $i, $item['phone']);
                $resultPHPExcel->getActiveSheet()->setCellValue('L' . $i, $item['name']);
                $resultPHPExcel->getActiveSheet()->setCellValue('M' . $i, $item['gold']);
                $i++;
            }
        }
        $outputFileName = '金币明细列表 '.date("Y年-m月-d日") . '.csv';
        //  $xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
        $xlsWriter = \PHPExcel_IOFactory::createWriter($resultPHPExcel, 'Excel5');
        //ob_start(); ob_flush();
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outputFileName . '"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $xlsWriter->save("php://output");
    }
    /**
     * 不带分页的列表
     * @author liuwei
     * @param  [type]  $model [description]
     * @param  array   $where [description]
     * @param  string  $order [description]
     * @param  array   $base  [description]
     * @param  boolean $field [description]
     * @return [type]         [description]
     */
    protected function lists_data($model,$where=array(),$order='',$base = array('status'=>array('egt',0)),$field=true){
        $options    =   array();
        $REQUEST    =   (array)I('request.');
        if(is_string($model)){
            $model  =   M($model);
        }
        $OPT        =   new \ReflectionProperty($model,'options');
        $OPT->setAccessible(true);

        $pk         =   $model->getPk();
        if($order===null){
        }else if ( isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']),array('desc','asc')) ) {
            $options['order'] = '`'.$REQUEST['_field'].'` '.$REQUEST['_order'];
        }elseif( $order==='' && empty($options['order']) && !empty($pk) ){
            $options['order'] = $pk.' desc';
        }elseif($order){
            $options['order'] = $order;
        }
        unset($REQUEST['_order'],$REQUEST['_field']);
        $options['where'] = array_filter(array_merge( (array)$base, /*$REQUEST,*/ (array)$where ),function($val){
            if($val===''||$val===null){
                return false;
            }else{
                return true;
            }
        });
        if( empty($options['where'])){
            unset($options['where']);
        }
        $options      =   array_merge( (array)$OPT->getValue($model), $options );
        $model->setProperty('options',$options);
        $rs = $model->field($field)->select();
        return $rs;
    }
}
