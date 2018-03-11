<?php
/**
 * Created by PhpStorm.
 * User: zhangkang
 * Date: 2016/8/9
 * Time: 15:36
 */

namespace Admin\Controller;
use Think\Storage;


class FinanceController extends WebController
{
    public function _initialize()
    {
        parent::_initialize();
        vendor("phpexcel.Classes.PHPExcel");
        Vendor('phpexcel.Classes.PHPExcel.IOFactory'); 
    }

    public function index(){
        $this->display();
    }
    /**
     * 提现单详情
     * @return [type] [description]
     */
    public function orderinfo()
    {
        $map['id'] = I('id');
        $orderdetail = D('Order')->orderinfonew($map);
        $this->assign('orderdetail', $orderdetail);

        $this->assign('display', I('display'));
        $this->meta_title = '提现订单详情';
        $this->display();
    }
    /**
     * 提现单操作
     * @return [type] [description]
     */
    public function cashadd()
    {
        $data['oid'] = I('oid');
        $data['express_name'] = I('express_name');
        $data['express_no'] = I('express_no');
        $data['purchaseno'] = I('purchaseno');
        $data['suppliername'] = I('suppliername');
        $data['purchasecash'] = I('purchasecash');
        $data['purchaseorderstatus'] = I('purchaseorderstatus');
        $data['order_status'] = I('order_status'); 
        $data['contacts'] = I('contacts');
        $data['phone'] = I('phone');
        $data['address'] = I('address');
        $data['email'] = I('email');
        $data['other_expenses'] = I('other_expenses');
        $count = D('Order')->cashadd($data);
        if ( $count > 0 ) {
            $this->success('编辑成功！', U('Finance/fetchgolddetails'));
        } else {
            $this->error('订单前置条件不成立！');
        }
    }
    /**
     * 提现资金
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function fetchgolddetails(){
        //渠道列表
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId);
        $map = array();
        $conditionarr = array();
        //用户ID/用户名
        if ( isset($_GET['keyword']) ) {
            $keyword = I('keyword');
            $map['name'] = $keyword;
            $conditionarr['keyword'] = $keyword;
        }
        //开始时间
        if(!empty(I('starttime'))){
            $starttime = I('starttime');
            $map['starttime'] = $starttime;
            $conditionarr['starttime'] = $starttime;
        }
        //结束时间
        if(!empty(I('endtime'))){
            $endtime = I('endtime');
            $map['endtime'] = $endtime;
            $conditionarr['endtime'] = $endtime;
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $channel = I('channel');
            $map['channel'] = $channel;
            $conditionarr['channel'] = $channel;
        }
        //物流状态
        if ( isset($_GET['status']) ) {
            $status = I('status');
            $map['status'] = $status;
            $conditionarr['status'] = $status;
        }
        //订单号
        if ( isset($_GET['keywordorder']) ) {
            $keywordorder = I('keywordorder');
            $map['keywordorder'] = $keywordorder;
            $conditionarr['keywordorder'] = $keywordorder;
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $model = D('Finance');
        $total = $model->getFetchGoldTotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        $list = $model->getFetchGoldList($map);

        $this->assign('conditionarr', json_encode($conditionarr));
        $this->assign('channelId', $channelId);
        $this->assign('_channelList', $channelList);
        $this->assign('_list', $list);
        $this->meta_title = '提金流水';
        $this->display();
    }
    /**
     * 提现申请
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function fetchcash(){
        //渠道列表
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId);
        $map = array();
        $conditionarr = array();
        //用户ID/用户名
        if ( isset($_GET['keyword']) ) {
            $keyword = I('keyword');
            $map['name'] = $keyword;
            $conditionarr['keyword'] = $keyword;
        }
        //开始时间
        if(!empty(I('starttime'))){
            $starttime = I('starttime');
            $map['starttime'] = $starttime;
            $conditionarr['starttime'] = $starttime;
        }
        //结束时间
        if(!empty(I('endtime'))){
            $endtime = I('endtime');
            $map['endtime'] = $endtime;
            $conditionarr['endtime'] = $endtime;
        }
        //审核状态
        if ( isset($_GET['status']) ) {
            $status = I('status');
            $map['status'] = $status;
            $conditionarr['status'] = $status;
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $model = D('Finance');
        $total = $model->getFetchCashTotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        $list = $model->getFetchCashList($map);

        $this->assign('conditionarr', json_encode($conditionarr));
        $this->assign('channelId', $channelId);
        $this->assign('_channelList', $channelList);
        $this->assign('_list', $list);
        $this->meta_title = '提现申请管理';
        $this->display();
    }
    /**
     * 提现审核
     * @return [type] [description]
     */
    public function examine()
    {
        $model = D('Finance');
        if(IS_POST){ //提交表单
            if(false !== $model->examine()){
                $this->success('审核成功！');
            } else {
                $error = $model->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->assign('id', I('id'));
            $this->meta_title = '提现申请审核';
            $this->display();
        }
    }

    //开奖订单列表
    public function lotterylist()
    {
        $map = array();
        $conditionarr = array();

        $role_id = cookie('roleId');//获取用户角色id
        //查询角色名称为财务的角色id
        $role_item = D('Role')->where("rolename='财务'")->field('id')->find();
        $finance_id = empty($role_item['id']) ? '' : $role_item['id'];
        if ( !empty($_GET['keyword']) ) {
            $map['name'] = I('keyword');
            $conditionarr['keyword'] = I('keyword');
        }
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }

        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }

        if ( $_GET['suppliersname'] != "" ) {
            $map['suppliersname'] = I('suppliersname');
            $conditionarr['suppliersname'] = I('suppliersname');
        }

        if ( $_GET['order_status'] != "" ) {
            $map['order_status'] = I('order_status');
            $conditionarr['order_status'] = I('order_status');
        }

        //角色是财务时-不显示金袋商品内容
        $role_status = 0;//0代表不是财务角色 1代表财务角色
        if ($role_id == $finance_id) {
            $map['shopstatus'] = 1;
            $conditionarr['shopstatus'] = 1;
            $role_status = 1;
        }
        if ( $_GET['shopstatus'] != "" ) {
            $map['shopstatus'] = I('shopstatus');
            $conditionarr['shopstatus'] = I('shopstatus');
        }

        if ( !empty($_GET['iscommon']) ) {
            $map['iscommon'] = I('iscommon');
            $conditionarr['iscommon'] = I('iscommon');
        }

        if ( !empty($_GET['houseno']) ) {
            $map['houseno'] = I('houseno');
            $conditionarr['houseno'] = I('houseno');
        }

        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $total = D('Finance')->lotterylisttotal($map);;
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Finance')->lotterylist($map);

        foreach ( $list as $key => $item ) {
            $qp['pid'] = $item['pid'];
            $userInfo = D('Order')->getShoprecordNum($qp);
            $list[$key]['orderno'] = $userInfo['orderid'];
        }
        $this->assign('shoplist', $list);

        $suppliers = D('Finance')->getsuppliersnamelist();
        $this->assign('role_status', $role_status);
        $this->assign('suppliers', $suppliers);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '开奖订单';
        $this->display();
    }

    /**
     * 查看虚拟币明细
     * @param $model
     * @param $id
     */
    public function goldcoupon(){
        $conditionarr = array();
        $map = array(); 

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        if ( isset($_GET['activityType']) ) {
            $map['activity_type'] = I('activityType');
            $conditionarr['activityType'] = I('activityType');
        }
//        $list = $this->lists($Model, $map,'create_time desc',$rows=0,$base = array('status'=>array('egt',0)),'a.id,a.create_time,a.remark,a.gold,u.username,u.nickname,u.id uid,u.phone,t.name');
        $list = $this->lists('GcouponRecord', $map ,'create_time desc', $rows=0,$base = array());

        $gcouponAmount = D('api/user')->getGcouponAmount();
        
        $this->assign('_list',       $list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->assign('gcouponAmount', $gcouponAmount);
        $this->meta_title = C("WEB_CURRENCY").'发放';
        $this->display();

    }

    /**
     * 虚拟币明细导出
     * @param $model
     * @param $id
     */
    public function goldcouponExport(){
        $map = array();

        /************************************************************************/
        // 初始化
        $resultPHPExcel = new \PHPExcel();

        //设置参数
        $arrFiled = array('用户ID','方式',C("WEB_CURRENCY"),'消耗'.C("WEB_CURRENCY").'（充值）','消耗'.C("WEB_CURRENCY").'（活动）','发放时间');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        $map = array();
        //开始时间
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
        }

        if ( isset($_GET['activityType']) ) {
            $map['activity_type'] = I('activityType');
        }

        $list = $this->lists('GcouponRecord', $map ,'create_time desc', 99999,$base = array());
        $i = 2;
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i,"'". $item['uid']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, get_activityTypeName($item['activity_type']));
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['num']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['d_recharge']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['d_active']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, date("Y-m-d" ,$item['create_time']));
            $i++;
        }

        $outputFileName = C("WEB_CURRENCY").'发放明细 '.date("Y年-m月-d日") . '.csv';
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
     * 查看金明细
     * @param $model
     * @param $id
     */
    public function gcouponInvoicing(){
        $conditionarr = array();
        $map = array();

        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $list = $this->lists('statistics_gc_m', $map ,'month desc', $rows=0,$base = array());
        
        $this->assign('_list',       $list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->assign('gcouponAmount', $this->getCurrGC());
        $this->meta_title = C("WEB_CURRENCY").'进销存';
        $this->display();

    }

    protected function getCurrGC(){
        $pre_month = date('Y-m',strtotime(date('Y',NOW_TIME).'-'.(date('m',NOW_TIME)-1).'-01'));
        $curr_month = date('Y-m',NOW_TIME);

        $month_start_f = date("Y-m-01",strtotime($pre_month));
        $month_start = strtotime($month_start_f);
//        $month_end = strtotime(date("Y-m-d",strtotime("$month_start_f +1 month -1 day")));
        $month_end = strtotime(date("Y-m-01",strtotime($curr_month)));

        //虚拟币收入
        $item['income_gcoupon'] = M('gcoupon_record')->where(      array('num'=>array('gt',0),'create_time'=> array(array('gt',$month_start),array('lt',$month_end  ) )))->sum('num');

        //虚拟币消耗
        $item['expend_gcoupon'] = M('gcoupon_record')->where(      array('num'=>array('lt',0),'create_time'=> array(array('gt',$month_start),array('lt',$month_end  ) )))->sum('num');

        $item['curr_surplus']  = M('user')->sum('gold_coupon');
        $item['pre_surplus']  = M('statistics_gc_m')->where(array('month'=>$pre_month))->getField('curr_surplus');

        $item['month']  = $curr_month;
        return $item;
    }

    public function  exportlotterylist()
    {
        $role_id = cookie('roleId');//获取用户角色id
        //查询角色名称为财务的角色id
        $role_item = D('Role')->where("rolename='财务'")->field('id')->find();
        $finance_id = empty($role_item['id']) ? '' : $role_item['id'];
        // 初始化
        $resultPHPExcel = new \PHPExcel();
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array('订单号', '期号', '商品ID', '商品名称', '是否实物', '虚拟卡号', '总支付金额（单位：元）', '现金支付金额（单位：元）',
            '开奖时间', '中奖号码', '用户ID', '中奖用户', '采购平台', '采购订单号', '采购订单状态', '采购订单物流状态', '采购订单实际金额（单位：元）',
            '采购订单预估价（单位：元）','类型','房间号');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        $map = array();

        if ( !empty($_GET['keyword']) ) {
            $map['name'] = I('keyword');
        }
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
        }

        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
        }

        if ( $_GET['suppliersname'] != "" ) {
            $map['suppliersname'] = I('suppliersname');
        }

        if ( $_GET['order_status'] != "" ) {
            $map['order_status'] = I('order_status');
        }

        //角色是财务时-不显示金袋商品内容
        $role_status = 0;//0代表不是财务角色 1代表财务角色
        if ($role_id == $finance_id) {
            $map['shopstatus'] = 1;
            $conditionarr['shopstatus'] = 1;
            $role_status = 1;
        }
        if ( $_GET['shopstatus'] != "" ) {
            $map['shopstatus'] = I('shopstatus');
            $conditionarr['shopstatus'] = I('shopstatus');
        }
        if ( !empty($_GET['iscommon']) ) {
            $map['iscommon'] = I('iscommon');
            $conditionarr['iscommon'] = I('iscommon');
        }

        if ( !empty($_GET['houseno']) ) {
            $map['houseno'] = I('houseno');
            $conditionarr['houseno'] = I('houseno');
        }

        $map['pageindex'] = 0;
        $map['pagesize'] = 9999999;

        $list = D('Finance')->lotterylist($map);

        foreach ( $list as $key => $item ) {
            $qp['pid'] = $item['pid'];
            $userInfo = D('Order')->getShoprecordNum($qp);
            $list[$key]['orderno'] = $userInfo['orderid'];
        }

        $i = 2;
       
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i,"'". $item['orderno']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['no']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['shopid']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['name']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['fictitious'] == 2 ? "虚拟" : "实物");
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['cardno']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['gold']+$item['cash']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['cash']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, date("Y-m-d H:i:s", $item['kaijang_time']));
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['kaijang_num']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['userid']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['username']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['suppliername']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['purchaseno']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, purchaseorderstatus($item['purchaseorderstatus']));
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, get_order_status($item['order_status'])?get_order_status($item['order_status']):'-');
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['purchasecash']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['buy_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['iscommon']==2?"PK摸金":"普通摸金");
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['house_no']);
            $i++;
        }

        $outputFileName = '开奖订单 '.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
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
     * 提金流水 - 导出
     * @return [type] [description]
     */
    public function exportFetchGold()
    {
        // 初始化
        $resultPHPExcel = new \PHPExcel();
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //设置参数
        //设值
        $arrFiled = array('提金订单号','用户ID','ID-渠道','提金数量(g)','平台手续费(充值)','平台手续费(活动)','总支付平台手续费','剩余黄金(mg)','实时金价','预估采购成本','实际采购成本','提取时间','物流状态');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        $map = array();

        //用户ID/用户名
        if ( isset($_GET['keyword']) ) {
            $keyword = I('keyword');
            $map['name'] = $keyword;
        }
        //开始时间
        if(!empty(I('starttime'))){
            $starttime = I('starttime');
            $map['starttime'] = $starttime;
        }
        //结束时间
        if(!empty(I('endtime'))){
            $endtime = I('endtime');
            $map['endtime'] = $endtime;
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $channel = I('channel');
            $map['channel'] = $channel;
        }
        //物流状态
        if ( isset($_GET['status']) ) {
            $status = I('status');
            $map['status'] = $status;
        }
        //订单号
        if ( isset($_GET['keywordorder']) ) {
            $keywordorder = I('keywordorder');
            $map['keywordorder'] = $keywordorder;
        }

        $map['pageindex'] = 0;
        $map['pagesize'] = 9999999;

        $list = D('Finance')->getFetchGoldList($map);
        $i = 2;
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i,"'". $item['order_id']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['uid']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['channel_id'].'-'.$item['channel_name']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['number']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['top_diamond']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['recharge_activity']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['gold_balance']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['gold_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['buy_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['actual_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, date("Y-m-d" ,$item['create_time']));
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, get_order_status($item['order_status']));
            $i++;
        }

        $outputFileName = '提金流水 '.date("Y年-m月-d日") . '.csv';
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
     * 提现申请 - 导出
     * @return [type] [description]
     */
    public function exportFetchCash()
    {
        // 初始化
        $resultPHPExcel = new \PHPExcel();
        //设置参数
        //设值
        $arrFiled = array('渠道ID','渠道名称','用户ID','实时黄金价','提现黄金数(mg)','总金额(元)','剩余黄金(mg)','手续费百分比(%)','手续费(元)','实际金额(元)','申请时间','提现方式','提现账号','提现姓名','审核状态');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        $map = array();

        //用户ID/用户名
        if ( isset($_GET['keyword']) ) {
            $keyword = I('keyword');
            $map['name'] = $keyword;
        }
        //开始时间
        if(!empty(I('starttime'))){
            $starttime = I('starttime');
            $map['starttime'] = $starttime;
        }
        //结束时间
        if(!empty(I('endtime'))){
            $endtime = I('endtime');
            $map['endtime'] = $endtime;
        }
        //审核状态
        if ( isset($_GET['status']) ) {
            $status = I('status');
            $map['status'] = $status;
        }

        $map['pageindex'] = 0;
        $map['pagesize'] = 9999999;

        $list = D('Finance')->getFetchGoldList($map);
        $i = 2;
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i,"'". $item['channel_id']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['channel_name']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['uid']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['gold_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['number']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['recharge_activity']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['gold_balance']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['gold_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['buy_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['actual_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, date("Y-m-d" ,$item['create_time']));
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, get_order_status($item['order_status']));
            $i++;
        }

        $outputFileName = '提金流水 '.date("Y年-m月-d日") . '.csv';
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
    public function fundsflow(){
        $map = array();
        //渠道列表
        $channel_id = cookie('channel_id');
        $map = array();
        $channel_list = D('Channel')->getTree();//一二三级渠道列表
        
        if (!empty($channel_id)) {
            $channel_top_list[0] = D('Channel')->getRootId($channel_id);
            $channel_ids = D('Channel')->dataList($channel_id,$channel_id);//一二三级渠道id集合
            $channel_list = D('Channel')->getTree($channel_ids);
            $map['channel_ids'] = $channel_ids;
        } else {
            //顶级渠道列表
            $channel_top_list = M('Channel')->where('pid=0')->field('id,channel_name')->select();
        }
        //支付方式
        $type_list = D('Finance')->get_platform_list();
        $channel_level = cookie('channel_level');

        $conditionarr = array();
        //支付方式
        if ( isset($_GET['pay_platform'])) {
            $map['pay_platform'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }
        //是否是实物
        if ( !empty($_GET['fictitious'])) {
            $map['fictitious'] = I('fictitious');
            $conditionarr['fictitious'] = I('fictitious');
        }
        //开始时间
        if ( !empty(I('starttime'))) {
            $map['starttime'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }
        //结束时间
        if ( !empty(I('endtime'))) {
            $map['endtime'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        //用 户 名搜索
        if ( isset($_GET['keyworduser']) ) {
            $map['keyworduser'] = I('keyworduser');
            $conditionarr['keyworduser'] = I('keyworduser');
        }
        //商品名称搜索
        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
            $conditionarr['keywordshop'] = I('keywordshop');
        }
        //渠道列表
        if ( isset($_GET['channelid']) ) {
            //获取所有下属渠道,sql查询完之后再进行渠道过滤
            $channel_ids_search = D('Channel')->dataList(I('channelid'),I('channelid'));//一二三级渠道id集合
            $map['channel_id'] = $channel_ids_search;
            $conditionarr['channelid'] = I('channelid');
            $conditionarr['channelname'] = I('channelname');
        }
        //夺宝类型
        if ( isset($_GET['iscommon']) ) {
            $map['iscommon'] = I('iscommon');
            $conditionarr['iscommon'] = I('iscommon');
        }
        //房间号
        if ( !empty($_GET['houseno']) ) {
            $map['houseno'] = I('houseno');
            $conditionarr['houseno'] = I('houseno');
        }
        //利润归属渠道名称
        if ( !empty($_GET['profitchannelid']) ) {
            $profitchannelid = D('Channel')->dataList(I('profitchannelid'),I('profitchannelid'));
            $map['profitchannelid'] = $profitchannelid;
            $conditionarr['profitchannelid'] = I('profitchannelid');
        }
        //利润归属一级渠道
        if ( !empty($_GET['profitid']) ) {
            $profitid = D('Channel')->dataList(I('profitid'),I('profitid'));
            $map['profitid'] = $profitid;
            $conditionarr['profitid'] = I('profitid');
        }
        //邀请码
        if ( !empty($_GET['invitation']) ) {
            $invitation =I('invitation');
            $map['invitation'] = $invitation;
            $conditionarr['invitation'] = $invitation;
        }
       
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $total = D('Finance')->fundstotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        //不带分页列表
        $list_total = D('Finance')->funds($map);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        //带分页列表
        $list = D('Finance')->funds($map);
        $cash_total = sprintf("%.2f", array_sum(array_column($list_total,'cash')));//支付现金总金额
        $gold_total = sprintf("%.2f", array_sum(array_column($list_total,'gold')));//支付金币总金额
        $pay_total_total = sprintf("%.2f", array_sum(array_column($list_total,'pay_total')));//支付总金额
        $profit_total = sprintf("%.2f", array_sum(array_column($list_total,'profit')));//渠道利润总金额
        foreach($list as $k=>$v){
            $list[$k]['discount_cash'] = sprintf("%.2f", ($v['cash']-$v['after_rebates_cash']));
            $list[$k]['discount_gold'] = sprintf("%.2f", ($v['gold']-$v['after_rebates_gold']));
            //红包详情
            $red_item = M('red_envelope')->where('id='.$v['red_id'])->field('id,name')->find();
            $list[$k]['red_name'] = empty($red_item) ? '' : $red_item['name'];
        }

        $this->assign('cash_total', $cash_total);
        $this->assign('gold_total', $gold_total);
        $this->assign('pay_total_total', $pay_total_total);
        $this->assign('profit_total', $profit_total);
        $this->assign('type_list', $type_list);
        $this->assign('list', $list);
        $this->assign('channelTree', $channel_list);
        $this->assign('channel_top_list', $channel_top_list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '资金流水';
        $this->display();
    }

    /**
     * 不带分页的列表
     * @param  [type]  $model [description]
     * @param  array   $where [description]
     * @param  string  $order [description]
     * @param  array   $base  [description]
     * @param  boolean $field [description]
     * @return [type]         [description]
     */
    public function list_data ($model,$where=array(),$order='',$base = array('status'=>array('egt',0)),$field=true){
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
    /**
     * 红包详情
     * @return [type] [description]
     */
    public function fundsinfo()
    {
        $id = empty($_GET['id']) ? : intval($_GET['id']);
        $map = array();
        if ( is_numeric($id) ) {
            $map['id'] = $id;
        }
        $item = M('RedEnvelope')->field(true)->where($map)->find();

        $this->assign('item', $item);
        $this->display();
    }
    /**
     * 实时资金流水
     * @return [type] [description]
     */
    public function fundsflowlist(){
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId);
        $conditionarr = array();
        //商品名称
        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
            $conditionarr['keywordshop'] = I('keywordshop');
        }
        //活动状态
        if ( isset($_GET['state']) ) {
            $map['state'] = I('state');
            $conditionarr['state'] = I('state');
        }
        //支付时间开始时间
        if ( !empty($_GET['starttime'])  ) {
            $map['starttime'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }
        //支付时间结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        //开奖时间开始时间
        if ( !empty($_GET['kstarttime'])  ) {
            $map['kstarttime'] = I('kstarttime');
            $conditionarr['kstarttime'] = I('kstarttime');
        }
        //开奖时间结束时间
        if ( !empty($_GET['kendtime']) ) {
            $map['kendtime'] = I('kendtime');
            $conditionarr['kendtime'] = I('kendtime');
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel'] = I('channel');
            $conditionarr['channel'] = I('channel');
        }
        //支付流水号
        if ( isset($_GET['orderid']) ) {
            $map['orderid'] = I('orderid');
            $conditionarr['orderid'] = I('orderid');
        }
        //用户id
        if ( isset($_GET['uid']) ) {
            $map['uid'] = I('uid');
            $conditionarr['uid'] = I('uid');
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }

        $total   =   D('Finance')->newfundsflowtotal($map);
        
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $total_info = array();
        $total_info['total_number'] = $total;//总记录数
        $total_info['all_number'] = 0;//用户购买总数量
        $total_info['total'] = 0;//总支付虚拟币
        $all_list = D('Finance')->newfundsflowlist($map);
        if (!empty($all_list)) {
            //用户购买总数量
            $total_info['all_number'] = array_sum(array_column($all_list, 'number'));
//            $total_info['total'] = array_sum(array_column($all_list, 'top_diamond'))+array_sum(array_column($all_list, 'recharge_activity'));
            $total_info['total'] = array_sum(array_column($all_list, 'pay_total'));
        }
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Finance')->newfundsflowlist($map);
        // exit(D('Finance')->getLastSql());
        $this->assign('channelId', $channelId);
        $this->assign('total_info', $total_info);
        $this->assign('list', $list);
        $this->assign('channelList', $channelList);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '实时资金流水';
        $this->display();
    }

    /**
     * 渠道-资金流水
     */
    public function qdfundsflow(){
        //渠道列表
        $channel_id = cookie('channel_id');
        $map = array();
        $channel_list = D('Channel')->getTree();//一二三级渠道列表
        
        if (!empty($channel_id)) {
            $channel_top_list[0] = D('Channel')->getRootId($channel_id );
            $channel_ids = D('Channel')->dataList($channel_id,$channel_id);//一二三级渠道id集合
            $channel_list = D('Channel')->getTree($channel_ids);
            $map['channel_ids'] = $channel_ids;
        } else {
            //顶级渠道列表
            $channel_top_list = M('Channel')->where('pid=0')->field('id,channel_name')->select();
        }
        $conditionarr = array();
        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
            $conditionarr['keywordshop'] = I('keywordshop');
        }

        if ( isset($_GET['keyword_invitationid']) ) {
            $map['keyword_invitationid'] = I('keyword_invitationid');
            $conditionarr['keyword_invitationid'] = I('keyword_invitationid');
        }

        //根据用户登录信息获取用户的子渠道列表
        if(cookie('rolename') === '渠道'){
            $s_channel_id = cookie('channel_id');
            $s_subChannel = D('Channel')->getSubChannel($s_channel_id,'ids');
        }

        //渠道列表
        if ( isset($_GET['channelid']) ) {
            //获取所有下属渠道,sql查询完之后再进行渠道过滤
            $inParm = D('Channel')->dataList(I('channelid'),I('channelid'));//一二三级渠道id集合
            $map['channel_id'] = $inParm;
            $conditionarr['channelid'] = I('channelid');
            $conditionarr['channelname'] = I('channelname');
        }
        if ( !empty($_GET['starttime'])  ) {
            $map['starttime'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }

        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        //利润归属渠道名称
        if ( !empty($_GET['profitchannelid']) ) {
            $profitchannelid = D('Channel')->dataList(I('profitchannelid'),I('profitchannelid'));
            $map['profitchannelid'] = $profitchannelid;
            $conditionarr['profitchannelid'] = I('profitchannelid');
        }
        //利润归属一级渠道
        if ( !empty($_GET['profitid']) ) {
            $profitid = D('Channel')->dataList(I('profitid'),I('profitid'));
            $map['profitid'] = $profitid;
            $conditionarr['profitid'] = I('profitid');
        }
        $map['cash_nonzero'] = true;

        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }

        $total   =   D('Finance')->fundsflowtotal($map );

        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Finance')->fundsflowlist($map);

        $this->assign('list', $list);
        $this->assign('channelTree', $channel_list);
        $this->assign('channel_top_list', $channel_top_list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '渠道-资金流水';
        $this->display();
    }

    public function summary(){
        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keywordroot']) ) {
            $where['channel_root'] = array('like', '%' . I('keywordroot') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keywordroot'] = I('keywordroot');
        }
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['record_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        //根据用户登录信息获取用户的子渠道列表
        if(cookie('rolename') === '渠道'){
            $s_channel_id = cookie('channel_id');
            $s_subChannel = D('Channel')->getSubChannel($s_channel_id,'ids');
            $inParm =  implode(',', $s_subChannel);
            $map['channel_id'] = array('in',$inParm);
        }

        $model = D('ChannelSettle');
        $list   =   $this->lists($model->group('channel_root'), $map,'',20,array(),' channel_root,sum(gold) as gold ,SUM(cash) as cash ,SUM(profit) as profit ,SUM(pay_total) as pay_total'  );
        $this->assign('list', $list);
        $this->meta_title = '汇总信息';
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->display();
    }

    public function qdsummary(){
        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keywordroot']) ) {
            $where['channel_root'] = array('like', '%' . I('keywordroot') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keywordroot'] = I('keywordroot');
        }
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['record_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        //根据用户登录信息获取用户的子渠道列表
        if(cookie('rolename') === '渠道'){
            $s_channel_id = cookie('channel_id');
            $s_subChannel = D('Channel')->getSubChannel($s_channel_id,'ids');
            //用户为一级渠道用户的显示总数
            if(cookie('channel_level') === '1'){
                $Settle =  D('ChannelSettle');
                $total = $Settle->group('channel_root')->where($map)->where("channel_root='".cookie('channel_root')."'" )->field('channel_name,SUM(cash) as cash ,SUM(profit) as profit ')->find();
                $this->assign('total', $total);
            }
        }

        $inParm =  implode(',', $s_subChannel);
        $map['channel_id'] = array('in',$inParm);

        $model = D('ChannelSettle');
        $list   =   $this->lists($model->group('channel_id'), $map,'',20,array(),' channel_name,sum(gold) as gold ,SUM(cash) as cash ,SUM(profit) as profit ,SUM(pay_total) as pay_total'  );


        $this->assign('list', $list);
        $this->meta_title = '汇总信息';
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->display();
    }

    /**
     * 获取下属渠道列表（不包含当前渠道）
     * @param $id
     * @return \Org\Array
     */
    public function getChannelTree($id){
            $list = M("Channel")->where(array('status' => 1))->field('id,pid,channel_name')->order('pid asc')->select();
            $Tree = new \Org\Tree;
            $Tree::$treeList = array();
            return  $Tree->tree($list,$id);
    }

    /**
     * 获取下属渠道列表（包含当前渠道）
     * @param $id
     * @return \Org\Array
     */
    public function getAllChannelTree($id){
        $data = $this->getChannelTree($id);
        $data [] = array('id'=>$id);
        return $data;
    }

    public function exportFundsflow(){
        
        // 初始化
        $objPHPExcel = new \PHPExcel();
        
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        // $arrFiled = array( '支付流水号', '用户ID', '用户名', '商品期号', '商品ID', '商品名称', '是否实物','支付时间', '参与状态','支付平台', '渠道名称', '推荐码','一级渠道名称', '渠道利润', '现金支付金额（单位：元）', '金币支付金额（单位：元）','优惠现金支付金额（单位：元）', '优惠金币支付金额（单位：元）', '优惠支付金额（单位：元）','红包名称(id)','红包金额','总支付金额（单位：元）');
        $arrFiled = array( '支付流水号', '用户ID', '用户名', '商品期号', '商品ID', '商品名称', '是否实物','支付时间', '参与状态','支付平台', '渠道名称', '推荐码','一级渠道名称', '渠道利润', '现金支付金额（单位：元）', '金币支付金额（单位：元）','总支付金额（单位：元）','类型','房间号','利润归属渠道名称','利润归属一级渠道');
        
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        $map = array();
        $channel_id = cookie('channel_id');
        if (!empty($channel_id)) {
            $channel_ids = D('Channel')->dataList($channel_id,$channel_id);//一二三级渠道id集合
            $map['channel_ids'] = $channel_ids;
        }
        //支付方式
        $type_list = D('Finance')->get_platform_list();
        $channel_level = cookie('channel_level');

        $conditionarr = array();
        //支付方式
        if ( isset($_GET['pay_platform'])) {
            $map['pay_platform'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }
        //是否是实物
        if ( !empty($_GET['fictitious'])) {
            $map['fictitious'] = I('fictitious');
            $conditionarr['fictitious'] = I('fictitious');
        }
        //开始时间
        if ( !empty(I('starttime'))) {
            $map['starttime'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }
        //结束时间
        if ( !empty(I('endtime'))) {
            $map['endtime'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        //用 户 名搜索
        if ( isset($_GET['keyworduser']) ) {
            $map['keyworduser'] = I('keyworduser');
            $conditionarr['keyworduser'] = I('keyworduser');
        }
        //商品名称搜索
        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
            $conditionarr['keywordshop'] = I('keywordshop');
        }
        //渠道列表
        if ( isset($_GET['channelid']) ) {
            //获取所有下属渠道,sql查询完之后再进行渠道过滤
            $channel_ids_search = D('Channel')->dataList(I('channelid'),I('channelid'));//一二三级渠道id集合
            $map['channel_id'] = $channel_ids_search;
            $conditionarr['channelid'] = I('channelid');
            $conditionarr['channelname'] = I('channelname');
        }
        //夺宝类型
        if ( isset($_GET['iscommon']) ) {
            $map['iscommon'] = I('iscommon');
            $conditionarr['iscommon'] = I('iscommon');
        }
        //房间号
        if ( !empty($_GET['houseno']) ) {
            $map['houseno'] = I('houseno');
            $conditionarr['houseno'] = I('houseno');
        }
        //利润归属渠道名称
        if ( !empty($_GET['profitchannelid']) ) {
            $profitchannelid = D('Channel')->dataList(I('profitchannelid'),I('profitchannelid'));
            $map['profitchannelid'] = $profitchannelid;
            $conditionarr['profitchannelid'] = I('profitchannelid');
        }
        //利润归属一级渠道
        if ( !empty($_GET['profitid']) ) {
            $profitid = D('Channel')->dataList(I('profitid'),I('profitid'));
            $map['profitid'] = $profitid;
            $conditionarr['profitid'] = I('profitid');
        }
         //邀请码
        if ( !empty($_GET['invitation']) ) {
            $invitation =I('invitation');
            $map['invitation'] = $invitation;
            $conditionarr['invitation'] = $invitation;
        }
         //不带分页列表
        $list = D('Finance')->funds($map);
        foreach($list as $k=>$v){
            $list[$k]['discount_cash'] = sprintf("%.2f", ($v['cash']-$v['after_rebates_cash']));
            $list[$k]['discount_gold'] = sprintf("%.2f", ($v['gold']-$v['after_rebates_gold']));
            $list[$k]['discount_total'] = sprintf("%.2f", ($v['pay_total']-$v['after_rebates_total']));
            //红包详情
            $red_item = M('red_envelope')->where('id='.$v['red_id'])->field('id,name,amount')->find();
            $list[$k]['red_name'] = empty($red_item) ? '' : $red_item['name'];
            $list[$k]['red_amount'] = empty($red_item) ? '' : $red_item['amount'];
           
            
        }
        //print_r($list);exit;
        $i = 2;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "'".$item['pay_order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['user_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['username']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['action_no']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['shop_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['shop_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['fictitious']==1?"实物":"虚拟");
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, date("Y-m-d H:i:s",$item['order_time'] ));
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, isset($item['code'])?$item['code']:'--');
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, get_recharge($item['pay_platform']) );
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $item['channel_name'] );
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, $item['invitationid'] );
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, $item['channel_root'] );
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $i, $item['profit'] );
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $i, $item['cash'] );
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $i, $item['gold'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('Q' . $i, $item['discount_cash'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('R' . $i, $item['discount_gold'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('S' . $i, $item['discount_total'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('T' . $i, empty($item['red_name']) ? '' :$item['red_name'].'('.$item['red_id'].')' );
            // $objPHPExcel->getActiveSheet()->setCellValue('U' . $i, empty($item['red_amount']) ? '' :$item['red_amount'] );
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $i, $item['pay_total'] );
            $objPHPExcel->getActiveSheet()->setCellValue('R' . $i, $item['iscommon']==2?"PK摸金":"普通摸金" );
            $objPHPExcel->getActiveSheet()->setCellValue('S' . $i, $item['house_no'] );
            $objPHPExcel->getActiveSheet()->setCellValue('T' . $i, $item['profit_channel_name'] );
            $objPHPExcel->getActiveSheet()->setCellValue('U' . $i, $item['profit_root_name'] );
            $i++;
        }
        
        $outputFileName = '资金流水-'.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
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



    public function exportSummary(){
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '利润归属一级渠道名称', '总利润（单位：元）', '总支付金额（单位：元）	', '现金总支付金额（单位：元）', '金币总支付金额（单位：元）');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }


        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keywordroot']) ) {
            $where['channel_root'] = array('like', '%' . I('keywordroot') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keywordroot'] = I('keywordroot');
        }
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            $map['record_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $model = D('ChannelSettle');
        $list   =   $this->lists($model->group('channel_root'), $map,'',999999,array(),' channel_root,sum(gold) as gold ,SUM(cash) as cash ,SUM(profit) as profit ,SUM(pay_total) as pay_total'  );

            $i = 2;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "'".$item['channel_root']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['profit']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['pay_total']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['cash']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['gold']);

            $i++;
        }

        $outputFileName = '渠道结算-'.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
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

    public function exportCSummary(){
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '利润归属渠道名称', '现金总支付金额（单位：元）');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }


        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keywordroot']) ) {
            $where['channel_root'] = array('like', '%' . I('keywordroot') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keywordroot'] = I('keywordroot');
        }
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            $map['record_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $model = D('ChannelSettle');
        $list   =   $this->lists($model->group('channel_id'), $map,'',999999,array(),' channel_name,sum(gold) as gold ,SUM(cash) as cash ,SUM(profit) as profit ,SUM(pay_total) as pay_total'  );

        $i = 2;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['cash']);

            $i++;
        }

        $outputFileName = '渠道结算-'.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
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
    /**
     * 实时资金流水 - 导出
     * @return [type] [description]
     */
    public function exportActiveFundsflow(){
        // 初始化
        $objPHPExcel = new \PHPExcel();
        $map = array();
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //商品名称
        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
        }
        //活动状态
        if ( isset($_GET['state']) ) {
            $map['state'] = I('state');
        }
        //支付时间开始时间
        if ( !empty($_GET['starttime'])  ) {
            $map['starttime'] = I('starttime');
        }
        //支付时间结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
        }
        //开奖时间开始时间
        if ( !empty($_GET['kstarttime'])  ) {
            $map['kstarttime'] = I('kstarttime');
        }
        //开奖时间结束时间
        if ( !empty($_GET['kendtime']) ) {
            $map['kendtime'] = I('kendtime');
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel'] = I('channel');
        }
        //支付流水号
        if ( isset($_GET['orderid']) ) {
            $map['orderid'] = I('orderid');
        }
        //用户id
        if ( isset($_GET['uid']) ) {
            $map['uid'] = I('uid');
        }
        $total = D('Finance')->newfundsflowtotal($map);
        $map['pageindex'] = 0;
        $map['pagesize'] = 999999;
        $list = D('Finance')->newfundsflowlist($map);
        $total_info = array();
        $total_info['total_number'] = $total;//总记录数
        $total_info['all_number'] = 0;//用户购买总数量
        $total_info['total'] = 0;//总支付虚拟币
        if (!empty($list)) {
            //用户购买总数量
            $total_info['all_number'] = array_sum(array_column($list, 'number'));
            $total_info['total'] = array_sum(array_column($list, 'top_diamond'))+array_sum(array_column($list, 'recharge_activity'));
        }
        $title = "";
        if (!empty($_GET['endtime']) and !empty($_GET['starttime'])) {
            $title = "时间段为:".$_GET['starttime']." 至 ".$_GET['endtime'].',';
        }
        $title .="共:".$total_info['total_number']."条记录,用户购买总数量:".$total_info['all_number']."g,总支付".C("WEB_CURRENCY").":".$total_info['total'];
        
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
            array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                )
            )
        );      //合并
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        // $arrFiled = array( '支付流水号', '用户ID', '用户名', '商品期号', '商品ID', '商品名称', '是否实物','支付时间','开奖时间','参与状态','活动状态', '支付平台', '渠道名称','推荐码', '一级渠道名称', '现金支付金额（单位：元）', '金币支付金额（单位：元）','优惠现金支付金额（单位：元）', '优惠金币支付金额（单位：元）', '优惠支付金额（单位：元）','红包名称(id)','红包金额','总支付金额（单位：元）');
        $arrFiled = array('期号','ID-渠道','订单号','用户ID','购买数量/mg','总参与'.C("WEB_CURRENCY"),'实时金价','活动状态','开奖时间','参与时间');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
        }
        foreach($list as $k=>$v){
            $list[$k]['invitationid'] = M('user')->where('id='.$v['user_id'])->getField('invitationid');
            $list[$k]['discount_total'] = sprintf("%.2f", ($v['discount_cash']+$v['discount_gold']));
        }
        $i = 3;
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $objPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i,"'". $item['period_no']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['channel_id']."-".$item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, "'".$item['pay_order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['user_id']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['number']);
            // $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['top_diamond']);
            // $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['recharge_activity']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['pay_total']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['gold_price']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, get_state($item['state']));
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, empty($item['kaijang_time']) ? '' :date("Y-m-d" ,$item['kaijang_time']));
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, date("Y-m-d" ,$item['order_time']));
            $i++;
        }
        $outputFileName = '实时资金流水-'.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
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

    /**
     * 导出渠道资金流水
     * @throws \PHPExcel_Reader_Exception
     */
    public function exportCFundsflow(){
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '用户名', '商品期号', '商品名称',  '现金支付金额（单位：元）', '支付时间', '渠道名称','一级渠道名称','利润归属渠道名称','利润归属一级渠道');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        //渠道列表
        $channel_id = cookie('channel_id');
        $map = array();
        if (!empty($channel_id)) {

            $channel_ids = D('Channel')->dataList($channel_id,$channel_id);//一二三级渠道id集合
            $map['channel_ids'] = $channel_ids;
        }
        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
        }

        if ( isset($_GET['keyword_invitationid']) ) {
            $map['keyword_invitationid'] = I('keyword_invitationid');
            $conditionarr['keyword_invitationid'] = I('keyword_invitationid');
        }

        //根据用户登录信息获取用户的子渠道列表
        if(cookie('rolename') === '渠道'){
            $s_channel_id = cookie('channel_id');
            $s_subChannel = D('Channel')->getSubChannel($s_channel_id,'ids');
        }
        //渠道列表
        if ( isset($_GET['channelid']) ) {
            //获取所有下属渠道,sql查询完之后再进行渠道过滤
            $inParm = D('Channel')->dataList(I('channelid'),I('channelid'));//一二三级渠道id集合
            $map['channel_id'] = $inParm;
            $conditionarr['channelid'] = I('channelid');
            $conditionarr['channelname'] = I('channelname');
        }
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            $map['starttime']  = I('starttime');
            $map['endtime'] =I('endtime');
            $map['order_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
        }
        //利润归属渠道名称
        if ( !empty($_GET['profitchannelid']) ) {
            $profitchannelid = D('Channel')->dataList(I('profitchannelid'),I('profitchannelid'));
            $map['profitchannelid'] = $profitchannelid;
            $conditionarr['profitchannelid'] = I('profitchannelid');
        }
        //利润归属一级渠道
        if ( !empty($_GET['profitid']) ) {
            $profitid = D('Channel')->dataList(I('profitid'),I('profitid'));
            $map['profitid'] = $profitid;
            $conditionarr['profitid'] = I('profitid');
        }
        $map['cash_nonzero'] = true;
        
        $map['pageindex'] = 0;
        $map['pagesize'] = 999999;

        $list = D('Finance')->fundsflowlist($map);

        $i = 2;
        foreach ( $list as $item ) {
//            channel_name contact  tel code activity_link  pid create_time
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['username']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['action_no']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['shop_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['cash']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, date("Y-m-d H:i:s",$item['order_time'] ));
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['root_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['profit_channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $item['profit_root_name']);

            $i++;
        }

        $outputFileName = '渠道-资金流水-'.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
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
    /**
     * 实时资金流水汇总
     * @author liuwei
     */
    public function fundsflowsummary()
    {
        //支付方式
        $type_list = D('Finance')->get_type_list();
        $map = array();
        $conditionarr = array();
        //支付平台
        if ( isset($_GET['pay_platform']) ) {
            $map['pay_platform'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }
        //开始时间
        if (!empty($_GET['starttime'])) {
            $map['starttime']   = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }
        //结束时间
        if (!empty($_GET['endtime'])) {
            $map['endtime']   = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        $result = D('Finance')->fundsflowsummary($map);
        $list = $result['list'];
        $cash_total = $result['cash_total'];
        $gold_total = $result['gold_total'];

        $this->assign('cash_total', $cash_total);
        $this->assign('gold_total', $gold_total);
        $this->assign('list', $list);
        $this->assign('type_list', $type_list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->display();
    }
    public function exportFlowsSummary()
    {
        // 初始化
        $objPHPExcel = new \PHPExcel();

        


        $conditionarr = array();
        $map = array();
        $conditionarr = array();
        //支付平台
        if ( isset($_GET['pay_platform']) ) {
            $map['pay_platform'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }
        //开始时间
        if (!empty($_GET['starttime'])) {
            $map['starttime']   = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }
        //结束时间
        if (!empty($_GET['endtime'])) {
            $map['endtime']   = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        $result = D('Finance')->fundsflowsummary($map);
        $list = $result['list'];
        $cash_total = $result['cash_total'];
        $gold_total = $result['gold_total'];
        $title ='现金总支付金额:'.$cash_total.',  金币总支付:'.$gold_total;
        $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
            array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                )
            )
        );      //合并
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '支付方式', '总金币支付金额（单位：元）', '总现金支付金额（单位：元）');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
        }

        $i = 3;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, get_recharge($item['type_id'])!=false ? get_recharge($item['type_id']) :'未知');
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['type_id']==1 ? $gold_total : 0.00);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['cash']);
            $i++;
        }

        $outputFileName = '实时资金流水汇总列表-'.date("Y年-m月-d日") . '.csv';
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

    /**
     * 测试资金流水列表
     * 
     * @author liuwei
     */
    public function test()
    {
        //支付方式列表
        $type_list = D('Finance')->test_type_list();
        $map = array();
        //用户名称搜索
        if ( isset($_GET['keyworduser']) ) {
            $where['user_name'] = array('like', '%' . I('keyworduser') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyworduser'] = I('keyworduser');
        }
        //商品名称搜索
        if ( isset($_GET['keywordshop']) ) {
            $where['shop_name'] = array('like', '%' . I('keywordshop') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keywordshop'] = I('keywordshop');
        }
        //商品名称搜索
        if ( isset($_GET['keyword_orderno']) ) {
            $where['order_no'] = array('like', '%' . I('keyword_orderno') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword_orderno'] = I('keyword_orderno');
        }
         //支付平台搜索
        if ( isset($_GET['pay_platform']) ) {
            $map['type'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }
        //开始时间结束时间
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['pay_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $list   =   $this->lists('shop_test_order', $map,'create_time desc,pay_time desc',20,array(),'*,(gold+cash) total,(discount_cash+discount_gold) discount_total');
        $this->assign('list', $list);
        $this->meta_title = '汇总信息';
        $this->assign('type_list', $type_list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->display();
    }

    /**
     * 测试资金流水添加
     * 
     * @author liuwei
     */
    public function testinsert()
    {
        if (IS_POST) {
            $rs = D("ShopTestOrder")->update();
            if($rs!==false){
                $this->success('添加成功！', U('test'));
            } else {
                $error = D('ShopTestOrder')->getError();
                $this->error(empty($error) ? '支付流水号不能重复!' : $error);
            }
        }
        $this->display();
    }

    /**
     * 测试资金流水导入
     * 
     * @author liuwei
     */
    public function testimport()
    {
        if (IS_POST) {
            if(!empty($_FILES['import']['name'])){
                $upload= $this->uploadfile('import','StartUp',5242880,array('xls','xlsx'));
            if($upload['status']){
                $path=$upload['filepath'];
                $objPHPExcel = \PHPExcel_IOFactory::load("Picture/$path");
                $objPHPExcel->setActiveSheetIndex(0);
                $sheet0=$objPHPExcel->getSheet(0);
                $rowCount=$sheet0->getHighestRow();//excel行数
                $data=array();
                $test_id = '';
                $error_id = '';//时间格式或者支付方式不存在的id集合
                $patten_one = "/^\d{4}[\/](0?[1-9]|1[012])[\/](0?[1-9]|[12][0-9]|3[01])(\s+(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9]))?$/";
                $patten_two = "/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])(\s+(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9]))?$/";
                for ($j = 2; $j <= $rowCount; $j++){
                    $name = trim($objPHPExcel->getActiveSheet()->getCell("L".$j)->getValue());//获取支付方式
                    $type_name = get_recharge($name);//根据支付方式获取支付id
                    $pay_time = trim($objPHPExcel->getActiveSheet()->getCell("M".$j)->getValue());//获取支付时间
                    if (is_numeric($pay_time)) {
                         $pay_time = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell("M".$j)->getValue()));//获取支付时间  
                    } 
                    if (empty($type_name)) {
                        $error_id .= "第".$j."行支付方式不存在  ";
                    } else {
                        if (!preg_match ( $patten_one, $pay_time ) and !preg_match ( $patten_two, $pay_time )) {
                            $error_id .= "第".$j."行时间格式错误  ";
                        }
                    }

                }
                if (empty($error_id)) {
                    for ($i = 2; $i <= $rowCount; $i++){
                        $a = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                        $b=str_replace("'", "", $a);
                        $c=str_replace('"', '', $b);
                        $d=str_replace('`', '', $c);
                        $qian=array(" ","　","\t","\n","\r");
                        $hou=array("","","","","");
                        $order_no = trim(str_replace($qian,$hou,$d)); 
                        $objPHPExcel->getActiveSheet()->getCell("L".$i)->getValue();
                        //获取支付方式
                        $name = trim($objPHPExcel->getActiveSheet()->getCell("L".$i)->getValue());
                        $type_name = get_recharge($name);//根据支付方式获取支付id
                        $item['order_no']=$order_no;
                        $item['uid']=$objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
                        $item['user_name']=$objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
                        $item['no']=$objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
                        $item['shop_id']=$objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
                        $item['shop_name']=$objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
                        $item['cash']=$objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
                        $item['gold']=$objPHPExcel->getActiveSheet()->getCell("H".$i)->getValue();
                        $item['discount_cash']=$objPHPExcel->getActiveSheet()->getCell("I".$i)->getValue();
                        $item['discount_gold']=$objPHPExcel->getActiveSheet()->getCell("J".$i)->getValue();
                        $item['red_id']=$objPHPExcel->getActiveSheet()->getCell("K".$i)->getValue();
                        $item['type']=$type_name;
                        $pay_time = $objPHPExcel->getActiveSheet()->getCell("M".$i)->getValue();
                        if (is_numeric($pay_time)) {
                            $pay_time = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell("M".$i)->getValue()));//获取支付时间  
                        } 
                        // $n = intval(($pay_time - 25569) * 3600 * 24); //转换成1970年以来的秒数
                        // $date_pay = gmdate('Y-m-d H:i:s', $n);//格式化时间
                        $item['pay_time']=strtotime($pay_time);
                        //order_no是否存在
                        $model = M('shop_test_order');
                        $info = $model->where(['order_no'=>$item['order_no']])->field('id')->find();
                        if (empty($info)) {
                            $item['create_time'] = time();
                            $model->add($item);
                        } else {
                            if ($i==$rowCount) {
                                $test_id .= $i; 
                            } else  {
                                $test_id .= $i.",";
                            }
                           
                        }
                    }
                    //删除xls
                    $path = './Picture/'.$upload['path'];
                    if (file_exists($path)) {
                        $result =$this->delDirAndFile($path, ture);
                    }
                    if (empty($test_id)) {
                        $this->success('导入成功！', U('test'));
                    } else {
                        $error = "导入失败,第:".$test_id.'行中订单id重复';
                        $this->error($error);
                    }
                } else {
                    $error_code = "导入失败,".$error_id;
                    $this->error($error_code);
                }
            }else{
                $this->error($upload['msg']);
            }
            }
        }
        $this->display();
    }
    /**
     * 删除文件夹
     *
     * @author liuwei
     * @param  [type]  $path   [description]
     * @param  boolean $delDir [description]
     * @return [type]          [description]
     */
    function delDirAndFile($path, $delDir = FALSE) {
        if (is_array($path)) {
            foreach ($path as $subPath)
                delDirAndFile($subPath, $delDir);
        }
        if (is_dir($path)) {
            $handle = opendir($path);
            if ($handle) {
                while (false !== ( $item = readdir($handle) )) {
                    if ($item != "." && $item != "..")
                        is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
                }
                closedir($handle);
                if ($delDir)
                    return rmdir($path);
            }
        } else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return FALSE;
            }
        }
        clearstatcache();
    }
    /**
     * 上传文件
     * 
     * @param  [type]  $fileid   [description]
     * @param  [type]  $dir      [description]
     * @param  integer $maxsize  [description]
     * @param  array   $exts     [description]
     * @param  integer $maxwidth [description]
     * @return [type]            [description]
     */
    function uploadfile($fileid,$dir,$maxsize=5242880,$exts=array('gif','jpg','jpeg','bmp','png'),$maxwidth=430){
        $config['maxSize']   =     $maxsize;// 设置附件上传大小，单位字节(微信图片限制1M)
        $config['exts']      =     $exts;// 设置附件上传类型
        $config['rootPath']  =     './Picture/'; // 设置附件上传根目录
        $config['savePath']  =     $dir.'/'; // 设置附件上传（子）目录
        $upload = new \Think\Upload($config);// 实例化上传类
        
        // 上传文件
        $info   =   $upload->upload();

        if(!$info) {// 上传错误提示错误信息
            return array(status=>0,msg=>$upload->getError());
        }else{// 上传成功
            return array(status=>1,msg=>'上传成功',filepath=>$info[$fileid]['savepath'].$info[$fileid]['savename'],path=>$info[$fileid]['savepath']);
        }
    }

    /**
     * 导出表格
     * 
     * @author liuwei
     */
    public function exportTest()
    {
        $map = array();
        //用户名称搜索
        if ( isset($_GET['keyworduser']) ) {
            $where['user_name'] = array('like', '%' . I('keyworduser') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyworduser'] = I('keyworduser');
        }
        //商品名称搜索
        if ( isset($_GET['keywordshop']) ) {
            $where['shop_name'] = array('like', '%' . I('keywordshop') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keywordshop'] = I('keywordshop');
        }
        //商品名称搜索
        if ( isset($_GET['keyword_orderno']) ) {
            $where['order_no'] = array('like', '%' . I('keyword_orderno') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword_orderno'] = I('keyword_orderno');
        }
         //支付平台搜索
        if ( isset($_GET['pay_platform']) ) {
            $map['type'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }
        //开始时间结束时间
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['pay_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $list   =   $this->lists('shop_test_order', $map,'pay_time desc,create_time desc',9999990,array(),'*,(gold+cash) total,(discount_cash+discount_gold) discount_total');
        // 初始化
        $objPHPExcel = new \PHPExcel();
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '支付流水号','用户ID','用户名','商品期号','商品ID','商品名称','实际现金支付金额（单位：元','实际金币支付金额','实际总支付金额（单位：元','优惠现金金额（单位：元','优惠金币金额','实际总优惠金额（单位：元','红包id','支付方式','支付时间');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }

        $i = 2;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "'".$item['order_no']."'");
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['uid']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['user_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['no']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['shop_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['shop_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['cash']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['gold']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $item['total']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $item['discount_cash']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $item['discount_gold']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, $item['discount_total']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, $item['red_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $i, get_recharge($item['type']));
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $i, date("Y-m-d H:i:s",$item['pay_time']));

            $i++;
        }

        $outputFileName = '测试资金流水-'.date("Y年-m月-d日") . '.csv';
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

    /**
     * 金币每日余额列表
     * 
     * @author liuwei
     */
    public function gold()
    {
        $map = array();
        //开始时间结束时间
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['date']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $list   =   $this->lists('gold_log', $map,'date desc,create_time desc',20,array(),'*');
        $this->assign('list', $list);
        $this->meta_title = '金币每日余额管理';
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->display();
    }

    /**
     * 导出表格 - 金币每日余额管理
     * 
     * @author liuwei
     */
    public function exportgold()
    {
        $map = array();
        //开始时间结束时间
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['date']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $list   =   $this->lists('gold_log', $map,'date desc,create_time desc',9999990,array(),'*');
        // 初始化
        $objPHPExcel = new \PHPExcel();
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '时间','金币数量','用户支出','用户收入','添加时间');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }

        $i = 2;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, date("Y/m/d",$item['date']));
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['total']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['cost']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['income']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, date("Y-m-d H:i:s",$item['create_time']));
            $i++;
        }

        $outputFileName = '金币每日数据汇总-'.date("Y年-m月-d日") . '.csv';
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
    public function callbacklist(){
        //支付方式
        $type_list = D('Finance')->get_type_list();
        $conditionarr = array();
        $map = array();

        if ( isset($_GET['keyworduser']) ) {
            $map['keyworduser'] = I('keyworduser');
            $conditionarr['keyworduser'] = I('keyworduser');
        }

        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
            $conditionarr['keywordshop'] = I('keywordshop');
        }

        if ( isset($_GET['keyword_invitationid']) ) {
            $map['keyword_invitationid'] = I('keyword_invitationid');
            $conditionarr['keyword_invitationid'] = I('keyword_invitationid');
        }

        //根据用户登录信息获取用户的子渠道列表
        if(cookie('rolename') === '渠道'){
            $s_channel_id = cookie('channel_id');
            $s_subChannel = D('Channel')->getSubChannel($s_channel_id,'ids');
        }
        
        //渠道列表
        if ( isset($_GET['channelid']) ) {
            //获取所有下属渠道,sql查询完之后再进行渠道过滤
            $channelTree = $this->getAllChannelTree(I('channelid'));
            if($channelTree){
                $ids = array_column($channelTree, 'id');
            }
            $conditionarr['channelid'] = I('channelid');
            $conditionarr['channelname'] = I('channelname');
        }

        if( $s_subChannel && isset($ids)){
            $subChannel = array_intersect($s_subChannel,$ids);
        }elseif($s_subChannel){
            $subChannel = $s_subChannel;
        }else{
            $subChannel = $ids;
        }
        $inParm =  implode(',', $subChannel);
        $map['channel_id'] = $inParm;

        //实物
        if ( isset($_GET['fictitious']) ) {
            $map['fictitious'] = I('fictitious');
            $conditionarr['fictitious'] = I('fictitious');
        }
        //支付平台
        if ( isset($_GET['pay_platform']) ) {
            $map['pay_platform'] = I('pay_platform');
            $conditionarr['pay_platform'] = I('pay_platform');
        }

        //活动状态
        if ( isset($_GET['state']) ) {
            $map['state'] = I('state');
            $conditionarr['state'] = I('state');
        }
        
        if ( !empty($_GET['starttime'])  ) {
            $map['starttime'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');
        }
        
        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }

        if ( !empty($_GET['kstarttime'])  ) {
            $map['kstarttime'] = I('kstarttime');
            $conditionarr['kstarttime'] = I('kstarttime');
        }

        if ( !empty($_GET['kendtime']) ) {
            $map['kendtime'] = I('kendtime');
            $conditionarr['kendtime'] = I('kendtime');
        }

        if ( isset($_GET['callback']) ) {
            $map['callback'] = I('callback');
            $conditionarr['callback'] = I('callback');
        }


        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }

        $total   =   D('Finance')->callbacklisttotal($map);
        
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Finance')->callbacklist($map);

        $this->assign('list', $list);
        $this->assign('type_list',$type_list);
        $this->assign('channelTree', D('Channel')->getTree());
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '异常资金流水';
        $this->display();
    }
    
    public function exportCallback(){
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        // $arrFiled = array( '支付流水号', '用户ID', '用户名', '商品期号', '商品ID', '商品名称', '是否实物','支付时间','开奖时间','参与状态','活动状态', '支付平台', '渠道名称','推荐码', '一级渠道名称', '现金支付金额（单位：元）', '金币支付金额（单位：元）','优惠现金支付金额（单位：元）', '优惠金币支付金额（单位：元）', '优惠支付金额（单位：元）','红包名称(id)','红包金额','总支付金额（单位：元）');
        $arrFiled = array( '支付流水号', '用户ID', '用户名', '商品期号', '商品ID', '商品名称', '是否实物','支付时间','开奖时间','参与状态','活动状态', '支付平台', '渠道名称','推荐码', '一级渠道名称', '现金支付金额（单位：元）', '金币支付金额（单位：元）','总支付金额（单位：元）','支付是否回调');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }

        $map = array();

        if ( isset($_GET['keyworduser']) ) {
            $map['keyworduser'] = I('keyworduser');
        }

        if ( isset($_GET['keywordshop']) ) {
            $map['keywordshop'] = I('keywordshop');
        }

        if ( isset($_GET['keyword_invitationid']) ) {
            $map['keyword_invitationid'] = I('keyword_invitationid');
            $conditionarr['keyword_invitationid'] = I('keyword_invitationid');
        }
        
        //渠道列表
        if ( isset($_GET['channelid']) ) {
            //获取所有下属渠道,sql查询完之后再进行渠道过滤
            $channelTree = $this->getAllChannelTree(I('channelid'));
            if($channelTree){
                $inParm = null;
                foreach ($channelTree as $k => $v) {
                    isset($inParm) ? $inParm = $inParm .','.$v['id']: $inParm = $v['id'] ;
                }
                $map['channel_id'] = $inParm;

            }
        }

        //实物
        if ( isset($_GET['fictitious']) ) {
            $map['fictitious'] = I('fictitious');
        }
        //支付平台
        if ( isset($_GET['pay_platform']) ) {
            $map['pay_platform'] = I('pay_platform');
        }

        //活动状态
        if ( isset($_GET['state']) ) {
            $map['state'] = I('state');
        }

        if ( !empty($_GET['starttime']) ) {
            $map['starttime'] = I('starttime');
        }

        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
        }

        if ( !empty($_GET['kstarttime']) ) {
            $map['kstarttime'] = I('kstarttime');
        }

        if ( !empty($_GET['kendtime']) ) {
            $map['kendtime'] = I('kendtime');
        }
        if ( isset($_GET['callback']) ) {
            $map['callback'] = I('callback');
            $conditionarr['callback'] = I('callback');
        }

        $map['pageindex'] = 0;
        $map['pagesize'] = 999999;

        $list = D('Finance')->callbacklist($map);

//        $list   =   $this->lists('Capitalflow', $map,'',999999 );

        foreach($list as $k=>$v){
            $list[$k]['invitationid'] = M('user')->where('id='.$v['user_id'])->getField('invitationid');
            $list[$k]['discount_total'] = sprintf("%.2f", ($v['discount_cash']+$v['discount_gold']));
        }


        $i = 2;
        foreach ( $list as $item ) {
//            channel_name contact  tel code activity_link  pid create_time
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "'".$item['pay_order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['user_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['username']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['action_no']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['shop_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['shop_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['fictitious']==1?"实物":"虚拟");
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, date("Y-m-d H:i:s",$item['order_time'] ));
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, isset($item['kaijang_time'])?date("Y-m-d H:i:s", $item['kaijang_time']):  '--');
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, isset($item['code'])?$item['code']:'--');
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, get_state($item['state']) );
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, get_recharge($item['pay_platform']) );
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, $item['channel_name'] );
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $i, $item['invitationid'] );
            
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $i, $item['root_name'] );
//            $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, $item['profit'] );
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $i, $item['cash'] );
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $i, $item['gold'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('R' . $i, $item['discount_cash'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('S' . $i, $item['discount_gold'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('T' . $i, $item['discount_total'] );
            // $objPHPExcel->getActiveSheet()->setCellValue('U' . $i, empty($item['red_name']) ? '' :$item['red_name'].'('.$item['red_id'].')' );
            // $objPHPExcel->getActiveSheet()->setCellValue('V' . $i, empty($item['red_amount']) ? '' :$item['red_amount'] );
            $objPHPExcel->getActiveSheet()->setCellValue('R' . $i, $item['pay_total'] );
            $objPHPExcel->getActiveSheet()->setCellValue('S' . $i, $item['is_callback']==1?"是":"否" );

            $i++;
        }

        $outputFileName = '异常资金流水-'.date("Y年-m月-d日") . '.csv';
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

    /*
    * 退款列表
    */
    function refund(){
        
        $this->meta_title = '退款列表';

        $map=array();
        if(I('platform')!==''){
            $map['platform']= I('platform');
        }
        if(!empty(I('keyword'))){
            $map['CONCAT(order_id,platform_order_no)']=array ('like','%'.I('keyword').'%');
        }

        if(!empty(I('starttime'))){
            $map['refund_time']= array('egt',strtotime(I('starttime')));
        }

        if(!empty(I('endtime'))){
            $map['refund_time']= array('elt',strtotime(I('endtime'))+86400);
        }

        if(!empty(I('starttime')) && !empty(I('endtime'))){
            $map['refund_time'] = array(array('egt',strtotime(I('starttime'))),array('elt',strtotime(I('endtime'))+86400));
            //$map['refund_time']=array('between',array(strtotime(I('starttime')),strtotime(I('endtime'))));
        }
        $paytype_list = M('shop_order_refund')->field('DISTINCT platform')->select();
        $list = $this->lists(M('shop_order_refund'),$map,'refund_time desc',0,array(),true);

        $this->assign('paytype_list',$paytype_list);
        $this->assign('list',$list);
        $this->display();
    }

    /*
    * 退款导出
    */
    function refundexport(){
        $map=array();
        if(I('platform')!==''){
            $map['platform']= I('platform');
        }
        if(!empty(I('keyword'))){
            $map['CONCAT(order_id,platform_order_no)']=array ('like','%'.I('keyword').'%');
        }

        if(!empty(I('starttime'))){
            $map['refund_time']= array('egt',strtotime(I('starttime')));
        }

        if(!empty(I('endtime'))){
            $map['refund_time']= array('elt',strtotime(I('endtime'))+86400);
        }

        if(!empty(I('starttime')) && !empty(I('endtime'))){
            $map['refund_time'] = array(array('egt',strtotime(I('starttime'))),array('elt',strtotime(I('endtime'))+86400));
            //$map['refund_time']=array('between',array(strtotime(I('starttime')),strtotime(I('endtime'))));
        }

        //$list = $this->lists(M('shop_order_refund'),$map,'refund_time desc',0,array(),true);
        $list = M('shop_order_refund')->where($map)->order('refund_time desc')->select();

        // 初始化
        $objPHPExcel = new \PHPExcel();
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '退款时间','支付平台','支付平台流水号','订单号','退款金额');
        				
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }

        $i = 2;
        foreach ( $list as $item ) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, date("Y/m/d H:i:s",$item['refund_time']));
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['platform']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['platform_order_no']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['refund_amount']);
            $i++;
        }

        $outputFileName = '退款-'.date("Y年-m月-d日") . '.csv';
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

    /*
    * 退款导入
    */
    function refundimport(){
        if (IS_POST) {
            if(!empty($_FILES['import']['name'])){
                $upload= $this->uploadfile('import','OrderRefund',5242880,array('xls','xlsx'));
                if($upload['status']){
                    $path=$upload['filepath'];
                    $objPHPExcel = \PHPExcel_IOFactory::load("Picture/$path");
                    $objPHPExcel->setActiveSheetIndex(0);
                    $sheet0=$objPHPExcel->getSheet(0);
                    $rowCount=$sheet0->getHighestRow();//excel行数
                    $data=array();

                    $model = M('shop_order_refund');
                    $model->startTrans();
                    $flag_error = false;
                    $line_list = array ();

                    for ($i = 2; $i <= $rowCount; $i++){
                        $item['platform'] = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();  

                        $isDateTiem = \PHPExcel_Shared_Date::isDateTime($objPHPExcel->getActiveSheet()->getCell("B".$i));

                        if($isDateTiem){
                            $item['refund_time'] = strtotime(gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP( $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue() )));
                        } 
                        else{
                            //$item['refund_time'] = strtotime(gmdate("Y-m-d H:i:s",$objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue()));
                            $item['refund_time'] = strtotime($objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue());
                        }

                        $item['order_id'] = str_replace('\t','',$objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue());
                        $item['platform_order_no'] = $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
                        $item['refund_amount'] = $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();

                        //添加退款数据
                        $model = M('shop_order_refund');
                        $rs = $model->add($item);
                        if($rs === false){
                            $flag_error = true;

                            array_push($line_list,$i);
                        }
                    }

                    if($flag_error){
                        $model->rollback();
                        //$this->error('添加失败！');
                        $error_content = '行号为'.implode(",", $line_list).'的数据添加失败，请检查数据！';
                        $this->assign('error_content',$error_content);
                    }
                    else{
                        $model->commit();

                        //删除xls
                        $path = './Picture/'.$upload['path'];
                        if (file_exists($path)) {
                            $result =$this->delDirAndFile($path, ture);
                        }
                        $this->success('添加成功！');
                    }
                }else{
                    $this->error($upload['msg']);
                }
            }
        }
        $this->display();
    }
    /**
     * 房间利润汇总
     * 
     * @return [type] [description]
     */
    public function house()
    {
        $channel_id = cookie('channel_id');
        $map = array();
        $channel_list = D('Channel')->getTree();//一二三级渠道列表
        if (!empty($channel_id)) {
            $channel_ids = D('Channel')->dataList($channel_id,$channel_id);//一二三级渠道id集合
            $channel_list = D('Channel')->getTree($channel_ids);
            $map['channel_id'] = $channel_ids;
        }
        
        //渠道搜索
        if (isset($_GET['channelid'])) {
            $map['channelid'] = I('channelid');
        }
        //邀请码搜索
        if (!empty($_GET['invitationid'])) {
            $map['invitationid'] = I('invitationid');
        }
        //房间号
        if (!empty($_GET['no'])) {
            $map['no'] = I('no');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['starttime'] = I('starttime');
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        //总条数
        $total   =  D('Finance')->housetotal($map);

        //分页
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        $list = D('Finance')->houselist($map);

        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $this->assign('channel_list', $channel_list);
        $this->assign('_list', $list);
        $this->meta_title = '房间利润汇总';
        $this->display();
    }
    /**
     * 房间利润汇总 - 导出
     * @return [type] [description]
     */
    public function exporthouse()
    {
        $channel_id = cookie('channel_id');
        $map = array();
        $channel_list = D('Channel')->getTree();//一二三级渠道列表
        if (!empty($channel_id)) {
            $channel_ids = D('Channel')->dataList($channel_id,$channel_id);//一二三级渠道id集合
            $channel_list = D('Channel')->getTree($channel_ids);
            $map['channel_id'] = $channel_ids;
        }
        //渠道搜索
        if (isset($_GET['channelid'])) {
            $map['channelid'] = I('channelid');
        }
        //邀请码搜索
        if (!empty($_GET['invitationid'])) {
            $map['invitationid'] = I('invitationid');
        }
        //房间号
        if (!empty($_GET['no'])) {
            $map['no'] = I('no');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['starttime'] = I('starttime');
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
        }
        $list = D('Finance')->houselist($map);
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '房间号','创建者ID','用户名','邀请码','所属渠道','所属一级渠道','现金支付金额','利润','利率','创建时间');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }        
        $i = 2;
        foreach ( $list as $item ) {
//            channel_name contact  tel code activity_link  pid create_time
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['no']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['uid']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['username']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['invitecode']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['root_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['cash']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['rate_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $item['rate']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, date('Y-m-d H:i:s', $item['create_time']));
            $i++;
        }

        $outputFileName = '房间利润汇总-'.date("Y年-m月-d日") . '.xls';
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
}