<?php
namespace Admin\Controller;

class RankController extends WebController {
    public function _initialize()
    {
        parent::_initialize();
        vendor("phpexcel.Classes.PHPExcel");
        Vendor('phpexcel.Classes.PHPExcel.IOFactory'); 
    }
	/**
	 * 排行榜列表页
	 *
	 * @author liuwei
	 * @return [type] [description]
	 */
	public function index()
	{
		$map = array();
        $conditionarr=array();
        //用户id
        if ( !empty($_GET['keyword']) ) {
            $map['uid']  = array('like', '%'.I('keyword').'%');
            $conditionarr['keyword'] = I('keyword');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
        	$map['create_time']= array('egt',strtotime(I('starttime')));
            $conditionarr['starttime'] = I('starttime');

        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
        	$map['create_time']= array('elt',strtotime(I('endtime'))+86400);
            $conditionarr['endtime'] = I('endtime');
        }
        $list   = $this->lists('rank',$map,$order='id desc',$rows=0,$base = array(),$field=true);

        $this->assign('_list', $list);
        $this->assign('conditionarr',json_encode($conditionarr));
		$this->meta_title = "排行榜管理";
		$this->display();
	}

	/**
	 * 添加&编辑页面
	 * 
	 * @author liuwei
	 * @return [type] [description]
	 */
	public function add(){
		$data = array();
		$this->meta_title = "排行榜添加";
		if (!empty($_GET['id'])) {
			$id = I('id');
			$data = D('Rank')->getInfo($id, '*');
			$this->meta_title = "排行榜编辑";
		}
		
		$this->assign('data', $data);
		$this->display();
	}

	/**
	 * 添加&编辑 操作
	 *
	 * @author liuwei
	 * @return [type] [description]
	 */
	public function save()
	{
		if (IS_POST) {
			$model = D('Rank');
			if(false !== $model->update()){
				$success = empty($_POST['id']) ? '添加成功！' : '编辑成功！';
                $this->success($success, U('index'));
            } else {
                $error = $model->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }

		} else {
			$this->error('未知错误!');
		}
	}

	/**
	 * 删除
	 *
	 * @author liuwei
	 * @return [type] [description]
	 */
	public function del()
	{
		if (IS_GET) {
			$model = D('Rank');
			if(false !== $model->del()){
                $this->success('删除成功!', U('index'));
            } else {
                $error = $model->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }

		} else {
			$this->error('请需选择要删除的数据!');
		}
	}
	public function rankimport()
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
                if (empty($error_id)) {
                    $head_img_url = "/Picture/default/";
                    for ($i = 2; $i <= $rowCount; $i++){
                        $a = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                
                        $objPHPExcel->getActiveSheet()->getCell("L".$i)->getValue();
                        //获取支付方式
                        $name = trim($objPHPExcel->getActiveSheet()->getCell("L".$i)->getValue());
                        $type_name = get_recharge($name);//根据支付方式获取支付id
                        $data['uid']= intval($objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue());
                        $data['username']= trim($objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue());
                        $data['draw_diamond']=intval($objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue());
                        $data['draw_number']=intval($objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue());
                        $data['win_number']=intval($objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue());
                        $data['full_draw']=intval($objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue());
                        $data['full_number']=intval($objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue());
                        $data['headimgurl'] = $head_img_url.rand(1,300).'.jpg';
                        $data['create_time'] = time();
                        //order_no是否存在
                        $model = M('rank');
                        $result = $model->add($data);
                        if ($result==false) {
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
                        $this->success('导入成功！', U('rankimport'));
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
}	