<?php
namespace Admin\Controller;

use Think\Controller;


class StatisticsController extends WebController {

	public function _initialize()
	{
		parent::_initialize();
		vendor("phpexcel.Classes.PHPExcel");
	}
	
	public function index($starttime='',$endtime=''){
		
		$income=D('Statistics')->income($starttime,$endtime);
		
		$this->assign('income',$income);
		
		$this->meta_title="盈利统计";
		
		$this->display();
		
	}
	
	
	public function order($starttime='',$endtime='',$recharge=''){
		
		$order=D('Statistics')->order($starttime,$endtime,$recharge);
		
		$this->assign('order',$order);
		
		$this->meta_title="订单统计";
		
		$this->display();

	}

	public function overview(){
		
		if ( !empty($_POST['starttime']) ) {
			$startDate = I('starttime');
			$startDate = str_replace( "-", "", $startDate );
//			$conditionarr['starttime'] = I('starttime');
		}else{
			$startDate =  date('Ymd',strtotime('-1 month')); 
		}

		if ( !empty($_POST['endtime']) ) {
			$endDate = I('endtime');
			$endDate = str_replace( "-", "", $endDate );
//			$conditionarr['endtime'] = I('endtime');
		}else{
			$endDate =  date('Ymd',strtotime('-1 day'));
		}

		$Statistics = A('api/Statistics');
		$result = $Statistics->operatingSituation($startDate,$endDate);

		$json_data   = getStatisticOperatingData($startDate,$endDate);
		$third_statistic   =  json_decode($json_data);

		$lists = (array)$third_statistic->data;
		
		$lists['averageRate'] = round($lists['averageRate'],2);
		$this->meta_title="运营概况";
		$this->assign('third_statistic', $lists);
		$this->assign('_statistic', $result);
		$this->display();
		
	}

	public function daily(){

		if ( !empty($_POST['starttime']) ) {
			$startDate = I('starttime');
			$startDate = str_replace( "-", "", $startDate );

//			$conditionarr['starttime'] = I('starttime');
		}else{
			$startDate =  date('Ymd',strtotime('-1 month'));
		}

		if ( !empty($_POST['endtime']) ) {
			$endDate = I('endtime');
			$endDate = str_replace( "-", "", $endDate );
//			$conditionarr['endtime'] = I('endtime');
		}else{
			$endDate =  date('Ymd',strtotime('-1 day'));
		}

		$this->meta_title="每日运营数据";
		$Statistics = M ( 'statistics_day') ;
		$lists =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('elt',$endDate))))->order('date')->select();
		$this->assign('_list', $lists);
		$this->display();
		
	}

	public function sku(){

		if ( !empty($_POST['starttime']) ) {
			$startDate = I('starttime');
			$starttime = strtotime($startDate);
			$startDate = str_replace( "-", "", $startDate );

//			$conditionarr['starttime'] = I('starttime');
		}else{
			$starttime = strtotime("today -1 month");
		}

		if ( !empty($_POST['endtime']) ) {
			$endDate = I('endtime');
			$endtime = strtotime($endDate)+86400;
			$endDate = str_replace( "-", "", $endDate );
//			$conditionarr['endtime'] = I('endtime');
		}else{
			$endtime = strtotime("today");
		}

		$Model = M();
		$sql_time = "";

		if(!empty($starttime) && !empty($endtime)){
			$sql_time = " and  p.kaijang_time <  '".$endtime."' and p.kaijang_time >= '".$starttime."'";

		}
//		$sql = "select  s.id,s.`name`,s.buy_price,b.count from bo_shop s LEFT JOIN (select p.sid,count(p.id) as count from bo_shop_period p where p.state in (1,2) ".$sql_time."  GROUP BY p.sid ) b ON s.id = b.sid  where s.`status` = 1 and s.display = 1 order by b.count desc  ;";

//		$sql = "select  s.id,s.`name`,s.buy_price,b.count,b.total from bo_shop s LEFT JOIN
//
//(select p.sid,count(p.id) as count,sum(o.recharge_activity+o.top_diamond) as total from bo_shop_period p ,bo_shop_order o where  p.id = o.pid and    p.state in (1,2) ".$sql_time." GROUP BY p.sid ) b
//
//ON s.id = b.sid  where s.`status` = 1 and s.display = 1 order by b.count desc  ;";

		$sql =" select  s.id,s.`name`,s.buy_price,b.total,e.total_now,c.count as count_exchange ,d.count as count_full from bo_shop s 

LEFT JOIN
		(select p.sid,sum(o.recharge_activity+o.top_diamond) as total from bo_shop_period p ,bo_shop_order o where  p.id = o.pid and    p.state in (1,2) ".$sql_time." GROUP BY p.sid  ) b 
ON s.id = b.sid 

LEFT JOIN
		(select p.sid,count(p.id) as count from bo_shop_period p where  p.state in (1,2) and p.exchange_type = 0 ".$sql_time." GROUP BY p.sid ) c
ON s.id = c.sid 

LEFT JOIN
		(select p.sid,count(p.id) as count from bo_shop_period p where  p.state in (1,2) and p.exchange_type = 1 ".$sql_time." GROUP BY p.sid ) d
ON s.id = d.sid 

LEFT JOIN
		(select p.sid,sum(o.recharge_activity+o.top_diamond) as total_now from bo_shop_period p ,bo_shop_order o where  p.id = o.pid ".$sql_time." GROUP BY p.sid  ) e
ON s.id = e.sid 

 where s.`status` = 1 and s.display = 1   ORDER BY b.total desc ,e.total_now DESC ";

		$list = $Model->query($sql);

		$this->meta_title="商品SKU";
		$this->assign('_list', $list);
		$this->display();
		
	}

	public function behavior(){
		if ( !empty($_POST['starttime']) ) {
			$startDate = I('starttime');
			$starttime = strtotime($startDate);
			$startDate = str_replace( "-", "", $startDate );

//			$conditionarr['starttime'] = I('starttime');
		}else{
			$startDate =  date('Ymd',strtotime('-1 month'));
		}

		if ( !empty($_POST['endtime']) ) {
			$endDate = I('endtime');
			$endtime = strtotime($endDate);
			$endDate = str_replace( "-", "", $endDate );
//			$conditionarr['endtime'] = I('endtime');
		}else{
			$endDate =  date('Ymd');
		}

		$Statistics = A('api/Statistics');
		$result = $Statistics->anchorBehavior($startDate,$endDate);

		$this->meta_title="主播行为概况";
		$this->assign('_list', $result);
		$this->display();
	}

	public function exportOverview(){
		if ( !empty($_GET['starttime']) ) {
			$startDate = I('starttime');
			$startDate = str_replace( "-", "", $startDate );
		}else{
			$startDate =  date('Ymd',strtotime('-1 month'));
		}

		if ( !empty($_GET['endtime']) ) {
			$endDate = I('endtime');
			$endDate = str_replace( "-", "", $endDate );
		}else{
			$endDate =  date('Ymd',strtotime('-1 day'));
		}

		$Statistics = A('api/Statistics');
		$result = $Statistics->operatingSituation($startDate,$endDate);

		$json_data   = getStatisticOperatingData($startDate,$endDate);
		$third_statistic   =  json_decode($json_data);

		$lists = (array)$third_statistic->data;

		// 初始化
		$objPHPExcel = new \PHPExcel();

		//设置参数
		//设值
		// $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');


		$arrFiled = array( C("WEB_CURRENCY").'兑换金额','兑换人数','新用户兑换人数','老用户兑换人数',C("WEB_CURRENCY").'平均兑换量');
		foreach ( $arrFiled as $key => $value ) {
			$ch = chr(ord('A') + intval($key));
			$objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
		}
		$i = 2;
		$objPHPExcel->getActiveSheet()->setCellValue($ch = 'A' . $i , $lists['chargeGameGold']);
		$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $lists['rateUserCount']);
		$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $lists['newUserCount']);
		$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $lists['oldUserCount']);
		$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $lists['averageRate']);


		$arrFiled = array( '激活','平均次日留存','活跃','老用户活跃','老用户活跃占比','抽奖金额','全价兑换金额','总消费金额','充值'.C("WEB_CURRENCY").'消耗','活动'.C("WEB_CURRENCY").'消耗','抽奖人数','全价兑换人数','新用户消费人数(当天消费)','老用户消费人数(登录两次以上的消费人数)','消费人数','消费率','ARPU','ARPPU');
		foreach ( $arrFiled as $key => $value ) {
			$ch = chr(ord('A') + intval($key));
			$objPHPExcel->getActiveSheet()->setCellValue($ch . '5', $value);
		}
		$i = 6;
		foreach ( $result as $item ) {
			$objPHPExcel->getActiveSheet()->setCellValue($ch = 'A' . $i, $item['sum_new']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['avg_day1retention']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_active']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['old_active']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['old_active_rate']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_draw_amount']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_exchange_amount']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_full_amount']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_rechage_amount']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_active_amount']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['draw_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['fullprice_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['sum_newer_consume_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['old_consume_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['consume_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['consumption_rate']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['ARPU']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['ARPPU']);
			$i++;
		}


		$outputFileName = '统计-运营概况-'.$startDate.'-'.$endDate. '.xls';
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

	public function exportDaily(){
		if ( !empty($_GET['starttime']) ) {
			$startDate = I('starttime');
			$startDate = str_replace( "-", "", $startDate );
		}else{
			$startDate =  date('Ymd',strtotime('-1 month'));
		}

		if ( !empty($_GET['endtime']) ) {
			$endDate = I('endtime');
			$endDate = str_replace( "-", "", $endDate );
		}else{
			$endDate =  date('Ymd',strtotime('-1 day'));
		}
		$Statistics = M ( 'statistics_day') ;
		$lists =  $Statistics->where(array('date'=>array(array('egt',$startDate),array('elt',$endDate +1 ))))->order('date')->select();

		// 初始化
		$objPHPExcel = new \PHPExcel();

		//设置参数
		$arrFiled = array( '日期','激活	','活跃','次留');
		foreach ( $arrFiled as $key => $value ) {
			$ch = chr(ord('A') + intval($key));
			$objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
		}
		$i = 2;
		foreach ( $lists as $item ) {
			$objPHPExcel->getActiveSheet()->setCellValue($ch = 'A' . $i, $item['date']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['active_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['uv']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['day1retention']);
			$i++;
		}

		$outputFileName = '统计-每日运营数据-'.$startDate.'-'.$endDate. '.xls';
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

	public function exportSku(){
		if ( !empty($_GET['starttime']) ) {
			$startDate = I('starttime');
			$starttime = strtotime($startDate);
			$startDate = str_replace( "-", "", $startDate );
		}else{
			$starttime = strtotime('-1 month');
			$startDate =  date('Ymd',$starttime);
		}

		if ( !empty($_GET['endtime']) ) {

			$endDate = I('endtime');
			$endtime = strtotime('+1 day',strtotime($endDate));
			$endDate = str_replace( "-", "", $endDate );
		}else{
			$endtime = strtotime('+1 day');
			$endDate =  date('Ymd',$endtime);
		}


		$Model = M();
		$sql_time = "";

		if(!empty($starttime) && !empty($endtime)){
			$sql_time = " and  p.kaijang_time <  '".$endtime."' and p.kaijang_time >= '".$starttime."'";

		}

		$sql =" select  s.id,s.`name`,s.buy_price,b.total,e.total_now,c.count as count_exchange ,d.count as count_full from bo_shop s 

LEFT JOIN
		(select p.sid,sum(o.recharge_activity+o.top_diamond) as total from bo_shop_period p ,bo_shop_order o where  p.id = o.pid and    p.state in (1,2) ".$sql_time." GROUP BY p.sid  ) b 
ON s.id = b.sid 

LEFT JOIN
		(select p.sid,count(p.id) as count from bo_shop_period p where  p.state in (1,2) and p.exchange_type = 0 ".$sql_time." GROUP BY p.sid ) c
ON s.id = c.sid 

LEFT JOIN
		(select p.sid,count(p.id) as count from bo_shop_period p where  p.state in (1,2) and p.exchange_type = 1 ".$sql_time." GROUP BY p.sid ) d
ON s.id = d.sid 

LEFT JOIN
		(select p.sid,sum(o.recharge_activity+o.top_diamond) as total_now from bo_shop_period p ,bo_shop_order o where  p.id = o.pid ".$sql_time." GROUP BY p.sid  ) e
ON s.id = e.sid 

 where s.`status` = 1 and s.display = 1   ORDER BY b.total desc ,e.total_now DESC ";

		$list = $Model->query($sql);

		// 初始化
		$objPHPExcel = new \PHPExcel();
		//设置参数
		$arrFiled = array( 'ID','商品名称','实时消耗的总'.C("WEB_CURRENCY").'数量','消耗的总'.C("WEB_CURRENCY").'数量','成本价格','商品已开奖期数','全价兑换次数');
		foreach ( $arrFiled as $key => $value ) {
			$ch = chr(ord('A') + intval($key));
			$objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
		}
		$i = 2;
		foreach ( $list as $item ) {

//			s.id,s.`name`,s.buy_price,b.total,e.total_now,c.count as count_exchange ,d.count as count_full
			$objPHPExcel->getActiveSheet()->setCellValue($ch = 'A' . $i, $item['id']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['name']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, empty($item['total_now'])?0:$item['total_now']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, empty($item['total'])?0:$item['total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, empty($item['buy_price'])?0:$item['buy_price']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, empty($item['count_exchange'])?0:$item['count_exchange']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, empty($item['count_full'])?0:$item['count_full']);
			$i++;
		}

		$outputFileName = '统计-商品SKU-'.$startDate.'-'.$endDate. '.xls';
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

	public function exportBehavior(){
		if ( !empty($_GET['starttime']) ) {
			$startDate = I('starttime');
			$startDate = str_replace( "-", "", $startDate );
		}else{
			$startDate =  date('Ymd',strtotime('-1 month'));
		}

		if ( !empty($_GET['endtime']) ) {
			$endDate = I('endtime');
			$endDate = str_replace( "-", "", $endDate );
		}else{
			$endDate =  date('Ymd');
		}

		$Statistics = A('api/Statistics');
		$lists = $Statistics->anchorBehavior($startDate,$endDate);

		// 初始化
		$objPHPExcel = new \PHPExcel();

		//设置参数
		$arrFiled = array( '日期','主播访问人数	','主播兑换人数','兑换'.C("WEB_CURRENCY").'数','兑换夺宝'.C("WEB_CURRENCY").'数','兑换全价'.C("WEB_CURRENCY").'数','ARPU','ARPPU');
		foreach ( $arrFiled as $key => $value ) {
			$ch = chr(ord('A') + intval($key));
			$objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
		}
		$i = 2;
		foreach ( $lists as $item ) {
			$objPHPExcel->getActiveSheet()->setCellValue($ch = 'A' . $i, $item['daterange']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['uv']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['ucv']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['consume_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['consume_exchange_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['consume_full_total']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['ARPU']);
			$objPHPExcel->getActiveSheet()->setCellValue($ch = chr(ord($ch) + 1) . $i, $item['ARPPU']);
			$i++;
		}

		$outputFileName = '统计-主播行为概况-'.$startDate.'-'.$endDate. '.xls';
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
