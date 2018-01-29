<?php
namespace api\Model;

use Think\Model;

// +----------------------------------------------------------------------
// | JuhePHP [ NO ZUO NO DIE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010-2015 http://juhe.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: Juhedata <info@juhe.cn-->
// +----------------------------------------------------------------------

//----------------------------------
// 黄金数据调用示例代码 － 聚合数据
// 在线接口文档：http://www.juhe.cn/docs/29
//----------------------------------
class JuHeModel extends Model{
	
	// 配置您申请的appkey
	const appkey = "e1e4f5af9274fce32382d8caed17d19c";
	
	//************1.上海黄金交易所************
	public function getAuShangHai(){
		
		header('Content-type:text/html;charset=utf-8');
		
		$url = "http://web.juhe.cn:8080/finance/gold/shgold";
		
		$params = array(
            "key" => self::appkey,//APP Key
            "v" => "1",//JSON格式版本(0或1)默认为0
		);
		
		$paramstring = http_build_query($params);
		$content = $this->juhecurl($url,$paramstring);
		$result = json_decode($content,true);
		
		if($result){
			if($result['error_code']=='0'){
				// print_r($result);
                return $result['result'][0]['Au99.99'];
			}
			else{
				return $result['error_code'].":".$result['reason'];
			}
		}
		else{
			echo "请求失败";
		}
	}
	
	//**************************************************
	
	//************2.上海期货交易所************
	public function getFuturesShangHai(){
		
		header('Content-type:text/html;charset=utf-8');
		
		$url = "http://web.juhe.cn:8080/finance/gold/shfuture";
		
		$params = array(
		"key" => self::appkey,// APP Key
		"v" => "",// JSON格式版本(0或1)默认为0
		);
		
		$paramstring = http_build_query($params);
		
		$content = $this->juhecurl($url,$paramstring);
		
		$result = json_decode($content,true);
		
		if($result){
			
			if($result['error_code']=='0'){
				
				print_r($result);
				
			}
			else{
				
				echo $result['error_code'].":".$result['reason'];
				
			}
			
		}
		else{
			
			echo "请求失败";
			
		}
        return $result;
	}
	//*	*************************************************

	//*	***********3.银行账户黄金************
	public function getAuBank(){
		
		header('Content-type:text/html;charset=utf-8');
		
		$url = "http://web.juhe.cn:8080/finance/gold/bankgold";
		
		$params = array(
		"key" => self::appkey,// APP Key
		);
		
		$paramstring = http_build_query($params);
		
		$content = $this->juhecurl($url,$paramstring);
		
		$result = json_decode($content,true);
		
		if($result){
			
			if($result['error_code']=='0'){
				
				print_r($result);
				
			}
			else{
				
				echo $result['error_code'].":".$result['reason'];
				
			}
			
		}
		else{
			
			echo "请求失败";
			
		}
		return $result;
	}
	
	//*	*************************************************

	/**
    * 请求接口返回内容
    * @param  string $url [请求的URL地址]
    * @param  string $params [请求的参数]
    * @param  int $ipost [是否采用POST形式]
    * @return  string
    */
	private function juhecurl($url,$params=false,$ispost=0){
		
		$httpInfo = array();
		
		$ch = curl_init();
		
		curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
		
		curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
		
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
		
		curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
		
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
		if( $ispost )
		{
			
			curl_setopt( $ch , CURLOPT_POST , true );
			
			curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
			
			curl_setopt( $ch , CURLOPT_URL , $url );
			
		}
		
		else
		{
			
			if($params){
				
				curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
				
			}
			else{
				
				curl_setopt( $ch , CURLOPT_URL , $url);
				
			}
			
		}
		
		$response = curl_exec( $ch );
		
		if ($response === FALSE) {
			
			//echo "cURL Error: " . curl_error($ch);
			
			return false;
			
		}
		
		$httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
		
		$httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
		
		curl_close( $ch );
		
		return $response;
		
	}
	
}
