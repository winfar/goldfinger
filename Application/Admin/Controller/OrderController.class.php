<?php
namespace Admin\Controller;

class OrderController extends WebController
{
    public function _initialize()
    {
        parent::_initialize();
        vendor("phpexcel.Classes.PHPExcel");
    }

    //导出活动列表信息；
    public function exportShoplist()
    {
        // 初始化
        $objPHPExcel = new \PHPExcel();
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        $map = array();
        //活动状态
        if ( $_GET['state'] != "" ) {
            $map['state'] = I('state');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');

        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
        }
        // $map['channel'] = $channelId;
        // //渠道id
        // if ( isset($_GET['channel']) and empty($channelId)) {
        //     $map['channel'] = I('channel');
        // }
        $model = D('Shop');
        $map['pageindex'] = 0;
        $map['pagesize'] = 99999999;
        $total = $model->getShopsNewTotal($map);
        $list = D('Shop')->getNewShops($map);
        $total_info = array();
        $total_info['total_page'] = $total;//总记录数
        $total_info['total_buy_gold'] = 0;//黄金成交量
        $total_info['total_gold_price'] = 0;//虚拟币总额
        $total_info['total_price'] = 0;//现金
        if (!empty($list)) {
            $total_info['total_buy_gold'] = array_sum(array_column($list, 'total_buy_gold'));
            $total_info['total_gold_price'] = array_sum(array_column($list, 'total_gold_price'));
            $total_info['total_price'] = array_sum(array_column($list, 'total_price'));
        }
        $title = "";
        if (!empty($_GET['endtime']) and !empty($_GET['starttime'])) {
            $title = "时间段为:".$_GET['starttime']." 至 ".$_GET['endtime'].',';
        }
        $title .=C("WEB_CURRENCY")."总额:".$total_info['total_gold_price'].",黄金成交量:".$total_info['total_buy_gold']."g,总期数:".$total_info['total_page']."g,现金:".$total_info['total_price'];
        
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
        $arrFiled = array('期号','开始时间','结束时间','活动状态','参与人次','支付'.C("WEB_CURRENCY"),'现金金额','黄金总数量（mg）','开奖号码','预估成本（元）');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
        }
        $i = 3;
        foreach ( $list as $item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $objPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i, $item['channel_id']."-".$item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, empty($item['create_time']) ? '' : date('Y-m-d H:i:s', $item['create_time']));
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, empty($item['end_time']) ? '' : date('Y-m-d H:i:s', $item['end_time']));
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, get_state($item['state']));
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total_number']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total_gold_price']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total_price']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total_buy_gold']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['kaijang_num']);
            $objPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total_price']);
            $i++;
        }

        $outputFileName = '活动列表 '.date("Y年-m月-d日") . '.csv';
        //	$xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
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

    public function index()
    {
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId); 
        $map = array();
        $conditionarr = array();
        //活动状态
        if ( $_GET['state'] != "" ) {
            $map['state'] = I('state');
            $conditionarr['state'] = I('state');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');

        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        // $map['channel'] = $channelId;
        // //渠道id
        // if ( isset($_GET['channel']) and empty($channelId)) {
        //     $map['channel'] = I('channel');
        //     $conditionarr['channel'] = I('channel');
        // }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $model = D('Shop');
        $total = $model->getShopsNewTotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $total_info = array();
        $total_info['total_page'] = $total;//总记录数
        $total_info['total_buy_gold'] = 0;//黄金成交量
        $total_info['total_gold_price'] = 0;//虚拟币总额
        $total_info['total_price'] = 0;//现金
        $all_list = $model->getNewShops($map);
        if (!empty($all_list)) {
            $total_info['total_buy_gold'] = array_sum(array_column($all_list, 'total_buy_gold'));
            $total_info['total_gold_price'] = array_sum(array_column($all_list, 'total_gold_price'));
            $total_info['total_price'] = array_sum(array_column($all_list, 'total_price'));
        }
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = $model->getNewShops($map);
        
        if ( $list ) {
            foreach ( $list as $key => $item ) {
                if ( isset($item['ten']) ) {
                    $list[$key]['tenunit'] = D('ten')->info($item['ten'], 'unit')['unit'];
                }
            }
        }
        $this->assign('channelId', $channelId);
        $this->assign('_channelList', $channelList);
        $this->assign('total_info', $total_info);
        $this->assign('shoplist', $list);
        $this->assign('category', D('Shop')->getTree());
        $this->assign('ten', D('ten')->getTree());
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '活动列表';
        $this->display();
    }
    /**
     * 活动明细
     * 
     * @return [type] [description]
     */
    public function orderlist()
    {
        $map = array();
        $conditionarr = array();
        if ( I('pid') == "" ) {
            $this->display('index');
            exit;
        }
        $this->assign('pid', I("pid"));
        $map['pid'] = I('pid');
        //兑换流水号
        if ( !empty($_GET['keyword']) ) {
            $map['keyword'] = I('keyword');
            $conditionarr['keyword'] = I('keyword');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
            $conditionarr['create_time'] = I('starttime');
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
            $conditionarr['end_time'] = I('endtime');
        }

        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $total = D('Order')->getNewOrdersTotal($map);
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = D('Order')->getNewOrders($map);
        if ( $list ) {
            foreach ( $list as $key => $item ) {
                $queryparam = array();
                $queryparam['uid'] = $item['uid'];
                $queryparam['pid'] = $item['id'];
                $queryparam['order_id'] = $item['order_id'];
                $channelpid = $item['channelpid'];
//                if ( $channelpid > 0 ) {
//                    $topchannelname = $this->getTopChannelname($channelpid);
//                    $list[$key]['topchannelname'] = $topchannelname;
//                }
//                root_name 
                $list[$key]['topchannelname'] = $item['root_name'];
                
                $info = D('Order')->getShoprecordInfo($queryparam);
                $infonum = explode(',', $info['num']);
                $list[$key]['numbersinfo'] = implode('  ', $infonum);
            }
        }
        //中奖信息
        $qp['pid'] = I('pid');
        $userInfo = D('Order')->getShoprecordNum($qp);
        $this->assign('display', sizeof($userInfo) > 0 ? 'block' : 'none');
        $this->assign('userInfo', $userInfo);
        $this->assign('shoplist', $list);
        $this->assign('channelTree', $channel_list);
        $this->assign('channel_top_list', $channel_top_list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '活动明细';
        $this->display();
    }

    //开奖订单列表
    public function  lotteryorder()
    {
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId);
        $map = array();
        $conditionarr = array();
        //用户ID/用户昵称
        if ( !empty($_GET['keyword']) ) {
            $map['name'] = I('keyword');
            $conditionarr['keyword'] = I('keyword');
        }
        //订单id
        if ( !empty($_GET['keywordorder']) ) {
            $map['keywordorder'] = I('keywordorder');
            $conditionarr['keywordorder'] = I('keywordorder');
        }
        //采购状态
        if ( $_GET['purchaseorderstatus'] != "" ) {
            $map['purchaseorderstatus'] = I('purchaseorderstatus');
            $conditionarr['purchaseorderstatus'] = I('purchaseorderstatus');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
            $conditionarr['create_time'] = I('starttime');
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
            $conditionarr['end_time'] = I('endtime');
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel'] = I('channel');
            $conditionarr['channel'] = I('channel');
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $model = D('Order');
        $total = $model->lotterytotal($map);
        $all_list = $model->lottery($map);
        $total_info = array();
        $total_info['total_number'] = $total;//总记录数
        $total_info['all_number'] = 0;//用户购买总数量
        $total_info['total'] = 0;//总支付虚拟币
        if (!empty($all_list)) {
            //用户购买总数量
            $total_info['all_number'] = array_sum(array_column($all_list, 'total_number'));
            $total_info['total'] = array_sum(array_column($all_list, 'total_price'));
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;

        $list = $model->lottery($map);
        $this->assign('channelId', $channelId);
        $this->assign('channelList', $channelList);
        $this->assign('type_list', $type_list);        
        $this->assign('role_status', $role_status);
        $this->assign('shoplist', $list);
        $this->assign('total_info', $total_info);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '开奖订单';
        $this->display();
    }
    /**
     * 虚拟卡绑定卡号ID
     * 
     * @return [type] [description]
     */
    public function bindcard()
    {
        $result = array();
        $result['code'] = 101;
        $result['msg'] = "非法请求";    
        if(IS_POST){ //POST
            $model = D('shop');
            $data = $_POST;
            $result = $model->getBidCard($data);
        }
        echo json_encode($result);
    }

//    private function getTopChannelname($channelpid)
//    {
//        $channel = M("channel")->where(array('id' => $channelpid))->field('pid,id,channel_name')->find();
//        if ( $channel['pid'] > 0 ) {
//            $a = $this->getTopChannelname($channel['pid']);
//            if ( $a['pid'] <= 0 ) {
//                return $a['channel_name'];
//            }
//        }
//
//        return $channel;
//    }

    //订单详情；
    public function orderdetail()
    {
        if ( I('pid') == "" ) {
            $this->display('index');
            exit;
        }

        $map['pid'] = I('pid');
        $map['order_id'] = I('orderid');
        D('order')->getAddAddress($map);
        $orderdetail = D('Order')->orderinfo($map);
        $this->assign('orderdetail', $orderdetail);

        $this->assign('pid', I('pid'));
        $this->assign('display', I('display'));
        $this->meta_title = '订单详情';
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
    public function purchaseorderadd()
    {
        $data['pid'] = I('pid');
        $data['express_name'] = I('express_name');
        $data['express_no'] = I('express_no');
        $data['purchaseno'] = I('purchaseno');
        $data['suppliername'] = I('suppliername');
        $data['purchasecash'] = I('purchasecash');
        $data['purchaseorderstatus'] = I('purchaseorderstatus');
        $data['order_status'] = I('order_status'); 
        $data['contacts'] = I('contacts');
        $data['phone'] = I('phone');
        $data['address'] = I('contacts');
        $data['email'] = I('email');
        $count = D('Order')->purchaseorderadd($data);
        if ( $count > 0 ) {
            $this->success('编辑成功！');
        } else {
            $this->error('订单前置条件不成立！');
        }
    }
    public function  exportlotterylist()
    {
        // 初始化
        $resultPHPExcel = new \PHPExcel();
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        $map = array();
        //用户ID/用户昵称
        if ( !empty($_GET['keyword']) ) {
            $map['name'] = I('keyword');
        }
        //订单id
        if ( !empty($_GET['keywordorder']) ) {
            $map['keywordorder'] = I('keywordorder');
        }
        //采购状态
        if ( $_GET['purchaseorderstatus'] != "" ) {
            $map['purchaseorderstatus'] = I('purchaseorderstatus');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
        }
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel'] = I('channel');
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 1;
        }
        $model = D('Order');
        $total = $model->lotterytotal($map);
       
        $map['pageindex'] = 0;
        $map['pagesize'] = 9999999;
        $list = D('Order')->lottery($map);
        $total_info = array();
        $total_info['total_number'] = $total;//总记录数
        $total_info['all_number'] = 0;//用户购买总数量
        $total_info['total'] = 0;//总支付虚拟币
        if (!empty($list)) {
            //用户购买总数量
            $total_info['all_number'] = array_sum(array_column($list, 'total_number'));
            $total_info['total'] = array_sum(array_column($list, 'total_price'));
        }
        $title = "";
        if (!empty($_GET['endtime']) and !empty($_GET['starttime'])) {
            $title = "时间段为:".$_GET['starttime']." 至 ".$_GET['endtime'].',';
        }
        $title .="共:".$total_info['total_number']."条记录,用户购买总数量:".$total_info['all_number']."mg,总支付".C("WEB_CURRENCY").":".$total_info['total'];
        
        $resultPHPExcel->getActiveSheet()->mergeCells('A1:M1');
        $resultPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
        $resultPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
            array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                )
            )
        );      //合并
        $resultPHPExcel->getActiveSheet()->setCellValue('A1', $title);
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        // $arrFiled = array('期号','订单号','ID-渠道','用户ID','购买数量（mg）','参与'.C("WEB_CURRENCY").'（充值）','参与'.C("WEB_CURRENCY").'（活动）','总参与'.C("WEB_CURRENCY"),'实时金价','开奖号码','开奖总数量（mg）','成单'.C("WEB_CURRENCY"),'开奖时间');
        $arrFiled = array('期号','订单号','ID-渠道','用户ID','购买数量（mg）','总参与'.C("WEB_CURRENCY"),'实时金价','开奖号码','开奖总数量（mg）','成单'.C("WEB_CURRENCY"),'开奖时间');

        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
        }
        foreach($list as $k=>$v){
            $list[$k]['invitationid'] = M('user')->where('id='.$v['user_id'])->getField('invitationid');
            $list[$k]['discount_total'] = sprintf("%.2f", ($v['discount_cash']+$v['discount_gold']));
        }
        $i = 3;
       
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i, $item['no']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, "'".$item['order_id']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['channel_id'].'-'.$item['channel_name']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['uid']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['win_number']);
            // $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, empty($item['total_top_diamond']) ?0 :$item['total_top_diamond'] );
            // $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, empty($item['total_recharge_activity']) ?0 :$item['total_recharge_activity']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, empty($item['total_win_price']) ? 0 :$item['total_win_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, empty($item['gold_price']) ? 0 :$item['gold_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['kaijang_num']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, empty($item['total_number']) ? 0 :$item['total_number']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, empty($item['total_price']) ? 0 :$item['total_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, date("Y-m-d H:i:s", $item['kaijang_time']));
            $i++;
        }

        $outputFileName = '订单开奖订单 '.date("Y年-m月-d日") . '.csv';
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
     * 活动汇总列表
     * 
     * @author liuwei
     */
    public function activity()
    {
        //活动状态列表
        $state_list = state_list();
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        //渠道列表
        $channelList = D('Channel')->getTree($channelId); 
        $map = array();
        $conditionarr = array();
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel'] = I('channel');
            $conditionarr['channel'] = I('channel');
        }
        //活动状态类型搜索
        if ( $_GET['state'] != "" ) {
            $map['state'] = I('state');
            $conditionarr['state'] = I('state');
        }
        //开始时间搜索
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');
            $conditionarr['starttime'] = I('starttime');

        }
        //结束时间搜索
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
            $conditionarr['endtime'] = I('endtime');
        }
        //总条数
        $total = D('Order')->getNewActivityTotal($map);
        $total_info = array();
        $total_info['total_price'] = 0;//虚拟币总额
        $total_info['total_buy_gold'] = 0;//黄金成交量
        $total_info['total_count'] = 0;//总期数
        $all_list = D('Order')->getNewActivity($map);
        if (!empty($all_list)) {
            $total_info['total_price'] = array_sum(array_column($all_list, 'total_price'));//虚拟币总额
            $total_info['total_buy_gold'] = array_sum(array_column($all_list, 'total_buy_gold'));//黄金成交量
            $total_info['total_count'] = array_sum(array_column($all_list, 'total_count'));//总期数
        }
        $rows = 20;
        if ( isset($REQUEST['r']) ) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = $rows > 0 ? $rows : 20;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $listRows;
        $list = D('Order')->getNewActivity($map);
        $this->assign('channelId', $channelId);
        $this->assign('total_info', $total_info);
        $this->assign('state_list', $state_list);
        $this->assign('channelList',$channelList);
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $this->assign('list', $list);
        $this->assign('total_data', $total_data);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->assign('role_status', $role_status);
        $this->display();
    }

    /**
     * 活动汇总导出
     * 
     * @author liuwei
     */
    public function  exportactivity()
    {
        $map = array();
        $channel_id = empty(cookie('channel_id')) ? '' : cookie('channel_id');//所属渠道id
        $channelId = D('Channel')->ischannelid($channel_id);
        $map['channel'] = $channelId;
        //渠道id
        if ( isset($_GET['channel']) and empty($channelId)) {
            $map['channel'] = I('channel');
        }
        //活动状态类型搜索
        if ( $_GET['state'] != "" ) {
            $map['state'] = I('state');
        }
        //开始时间搜索
        if ( !empty($_GET['starttime']) ) {
            $map['create_time'] = I('starttime');

        }
        //结束时间搜索
        if ( !empty($_GET['endtime']) ) {
            $map['end_time'] = I('endtime');
        }
        //总条数
        $total = D('Order')->getNewActivityTotal($map);
        $total_info = array();
        $total_info['total_price'] = 0;//虚拟币总额
        $total_info['total_buy_gold'] = 0;//黄金成交量
        $total_info['total_count'] = 0;//总期数
        $list = D('Order')->getNewActivity($map);
        if (!empty($all_list)) {
            $total_info['total_price'] = array_sum(array_column($list, 'total_price'));//虚拟币总额
            $total_info['total_buy_gold'] = array_sum(array_column($list, 'total_buy_gold'));//黄金成交量
            $total_info['total_count'] = array_sum(array_column($list, 'total_count'));//总期数
        }
        // 初始化
        $resultPHPExcel = new \PHPExcel();
        $title = "";
        if (!empty($_GET['endtime']) and !empty($_GET['starttime'])) {
            $title = "时间段为:".$_GET['starttime']." 至 ".$_GET['endtime'].',';
        }
        $title .=C("WEB_CURRENCY")."总额：".$total_info['total_price']."，黄金成交量：".$total_info['total_buy_gold']."g, 总期数:".$total_info['total_count'];
        
        $resultPHPExcel->getActiveSheet()->mergeCells('A1:H1');
        $resultPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
        $resultPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
            array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                )
            )
        );      //合并
        $resultPHPExcel->getActiveSheet()->setCellValue('A1', $title);
        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array('ID-渠道','总期数',C("WEB_CURRENCY").'总额','黄金总量（mg）','用户参与次数','开奖次数','预估成本（元）');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $resultPHPExcel->getActiveSheet()->setCellValue($ch . '2', $value);
        }
        $i = 3;
       
        foreach ( $list as $key=>$item ) {
            $j=0;
            $ch = chr(ord('A') + $j);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval($ch) . $i,$item['channel_id']."-".$item['channel_name']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))). $i, $item['total_count']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['total_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['total_buy_gold']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['total_number']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['total_kaijiang_count']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['total_buy_price']);
            $resultPHPExcel->getActiveSheet()->setCellValue(strval(chr(ord('A') + (++$j))) . $i, $item['total_actual_price']);
            $i++;
        }

        $outputFileName = '活动汇总 '.date("Y年-m月-d日") . '.csv';
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
    public function flow()
    {
        $purchaseno = I('purchaseno');
        $list = (array)D('api/order')->orderTrack($purchaseno);
        //print_r($list);exit;
        $this->assign('list', $list);
        $this->display('flow');
    }
    public function sendsms()
    {
        $pid = I('pid');
        D('order')->getSendSms($pid);
    }
}
