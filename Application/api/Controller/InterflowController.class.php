<?php
namespace api\Controller;
use Think\Controller;
/**
 * 7天自动收货
 */
class InterflowController extends Controller {
	public function index()
	{
		$order_list = M('shop_period')->where("order_status=101")->field('id,suppliername,purchaseno,order_status_time')->order('id desc')->select();
		if (!empty($order_list)) {
			foreach ($order_list as $key => $value) {
				if (strstr($value['suppliername'], '京东')) {
                    $receiving_state = D('api/order')->selectJdOrder($value['purchaseno']);//是否收货 1是
                    $now_time = time();//现在时间戳
                    $jd_list = D('api/order')->orderTrack($value['purchaseno']);
                    $jd_time = strtotime($jd_list[0]->msgTime);//收货时间戳
                    $day = 60*60*24*7;//七天时间戳
                    if ($receiving_state == 1 and ($now_time - $jd_time > $day)) {
                    	$order_time_array = !empty($value['order_status_time']) ? json_decode($value['order_status_time'], true) : array();
                    	$data = array();
                    	$data['order_status'] = 102;
                    	$order_time_array = $now_time;//收货时间
                		$data['order_status_time'] = json_encode($order_time_array);
                    	$result = M('shop_period')->where('id='.$value['id'])->save($data);
                    	if ($result!=false) {
                    		recordLog("shop_period表id为".$value['id']."收货成功！".date("Y-m-d H:i:s"));
                    	}
                    }
                }
			}
		}
	}
}	