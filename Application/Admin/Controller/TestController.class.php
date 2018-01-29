<?php
namespace Admin\Controller;

use Think\Controller;


class TestController extends Controller {
	
	
	public function index(){
		
		try{
			
			//$menu = M("WebMenus") -> where('hide=0') -> order('pid,sort') -> select();
			//var_dump($menu);
		}
		catch(\Exception $e){
			
			echo $e->getMessage();
			
		}
	}

	public function shuffle($num=1000){
		$numbers = range(10000001,$num+10000001);
		recordLog('shuffle处理=>numbers'.$num,'商品编辑');
		try {
			shuffle($numbers);
		} catch (Exception $e) {
			recordLog('进行shuffle处理发生异常=>'.$e->getMessage(),'商品编辑');
			recordLog('进行shuffle处理发生异常=>'.$e->getTraceAsString(),'商品编辑');
		}
		recordLog('jiang_num完成shuffle处理','商品编辑');
		$data = implode(',',$numbers);
		var_dump($data);
		return $data;

	}

	/**
	 * @param $cacheDir
	 */
	public function clearCache($cacheDir='Runtime')
	{
		$type = $cacheDir;
		//将传递过来的值进行切割，我是已“-”进行切割的
		$name = explode('-', $type);
		//得到切割的条数，便于下面循环
		$count = count($name);
		//循环调用上面的方法
		for ($i = 0; $i < $count; $i++)
		{
			//得到文件的绝对路径
			$abs_dir = dirname(dirname(dirname(dirname(__FILE__))));
			//组合路径
			$pa = $abs_dir . str_replace("/", "\\", str_replace("./", "\\", RUNTIME_PATH)); //得到运行时的目录
			$runtime = $pa . 'common~runtime.php';
			if (file_exists($runtime))//判断 文件是否存在
			{
				unlink($runtime); //进行文件删除
			}
			//调用删除文件夹下所有文件的方法
			$this->rmFile($pa, $name[$i]);
		}
	}
	public function invitation()
	{
		$item = M('invitation')->field('id')->select();
		$info = "";
		if ($item) {
			foreach ($item as $key => $value) {
				$url = array("event_type" => array("bind_user_channel"),"invitation_code"=> $value['id']);
				$data = json_encode($url);
			    $qr_code = D('Channel')->genQrCode($data);
			    $re = M('invitation')->where('id='.$value['id'])->save(array('qr_code'=>$qr_code));
			    if ($re) {
			    	$info.="二维码id为".$value['id'].",二维码修改成功".$qr_code;
			    } else {
			    	$info.="二维码id为".$value['id'].",二维码修改失败".$qr_code;
			    }

			}
		}
		echo $info;
	}
	public function channel()
	{
		$list = M('shop_order')->where('invitation_id_profit=0')->field('id,pid,uid')->select();
		if ($list) {
			$i = 0;
			$valid_i = 0;
			foreach ($list as $key => $v) {
				$i++;
				$item = array();
				//获取房主的归属渠道 //判断该期商品是否为私有PK专场
				$rs_house = M()->table('__SHOP_PERIOD__ p,__HOUSE_MANAGE__ m,__USER__ u ')
					->field('u.channelid,u.invitationid')
					->where(array('m.ispublic'=>1,'p.id'=>$v['pid']))
					->where('m.id = p.house_id and m.uid = u.id')->find();

				if($rs_house){
					$item =$rs_house; 
				}
				// 获取地推邀请归属渠道  //获取用户的归属渠道
				$rs_user = M('user')->where(array('id'=>$v['uid']))->field('channelid,invitationid')->find();
				if($rs_user){
					$item =$rs_user;
				}
				if ($item) {
					$rs = M('shop_order')->where(array('id'=>$v['id']))->setField('invitation_id_profit',$item['invitationid']);
					//echo M()->getLastSql();exit;
					recordLog('计数=>'.$i.' 重新绑定用户uid['.$v['uid'].']订单id['.$v['id'].']的利润归属渠道邀请码['.$item['invitationid'].'] 处理结果=>'.$rs,__METHOD__);
					if($rs) $valid_i++;
				}
			}
		}
		$str = '完成利润归属绑定处理：计数=>'.$i.' 重新绑定用户处理结果>0 计数为=>'.$valid_i;
        recordLog($str,__METHOD__);
        echo $str;
	}

	/**
	 * 删除文件和目录
	 * @param type $path 要删除文件夹路径
	 * @param type $fileName 要删除的目录名称
	 */
	private function rmFile($path, $fileName)
	{//删除执行的方法
		//去除空格
		$path = preg_replace('/(\/){2,}|{\\\}{1,}/', '/', $path);
		//得到完整目录
		$path.= $fileName;
		//判断此文件是否为一个文件目录
		if (is_dir($path))
		{
			//打开文件
			if ($dh = opendir($path))
			{
				//遍历文件目录名称
				while (($file = readdir($dh)) != false)
				{
					$sub_file_path = $path . "\\" . $file;
					if ("." == $file || ".." == $file)
					{
						continue;
					}
					if (is_dir($sub_file_path))
					{
						$this->rmFile($sub_file_path, "");
						rmdir($sub_file_path);
					}
					//逐一进行删除
					unlink($sub_file_path);
				}
				//关闭文件
				closedir($dh);
			}
			rmdir($sub_file_path);//删除当前目录
		}
	}

}

