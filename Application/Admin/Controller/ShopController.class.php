<?php
namespace Admin\Controller;

class ShopController extends WebController
{
    public function _initialize()
    {
        parent::_initialize();
        vendor("phpexcel.Classes.PHPExcel");
        Vendor('phpexcel.Classes.PHPExcel.IOFactory'); 
    }
    /**
     * 商品列表
     * 
     * @return [type] [description]
     */
    public function index()
    {
        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keyword']) ) {//商品名称
            $where['name'] = array('like', '%' . I('keyword') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword'] = I('keyword');
        }
        if ( isset($_GET['type']) ) {//商品分类
            $cid = D('Shop')->getId(I('type'));
            if ( is_numeric($cid) ) {
                $map['category'] = $cid;
            } else {
                $map['category'] = array('in', $cid);
            }
            $conditionarr['type'] = I('type');
        }
        if ( isset($_GET['status']) ) {//商品所有状态
            $map['status'] = I('status');
            $conditionarr['status'] = I('status');
        }
        if ( isset($_GET['ten']) ) {//商品专区
            $map['ten'] = I('ten');
            $conditionarr['ten'] = I('ten');
        }
        if ( isset($_GET['full']) ) {//是否全价兑换
            $map['is_full'] = I('full');
            $conditionarr['full'] = I('full');
        }
        if ( isset($_GET['fictitious']) ) {
            $conditionarr['fictitious'] = I('fictitious');
            if (I('fictitious')==2) {
                $map['fictitious'] = I('fictitious');
            } else {
                $map['_string'] = 'fictitious=1 or fictitious IS NULL';
            }
            
        }
        
        // if ( isset($_GET['private']) ) {
        //     $map['private'] = I('private');
        //     $conditionarr['private'] = I('private');
        // }

        // if ( !empty($_GET['pkset']) ) {
        //     $map['pkset'] = array('in',"".I('pkset').",3");
        //     $conditionarr['pkset'] = I('pkset');
        // }
        $list = $this->lists('Shop', $map, 'hits desc,id desc');
        if ( $list ) {
            foreach ( $list as $key => $item ) {
                if ( isset($item['ten']) ) {
                    $list[$key]['tenunit'] = D('ten')->info($item['ten'], 'unit')['unit'];
                }
                if ( isset($item['id']) ) {
                    $list[$key]['no'] = D('Period')->getPeriodNoBySid($item['id'], 'no');
                    $list[$key]['nocount'] = D('Period')->getPeriodNoBySid($item['id'], 'nocount');
                }
            }
        }
        $status = !isHostProduct() ? 0 : 1;
        $this->assign('shoplist', $list);
        $this->assign('category', D('Shop')->getTree());
        $this->assign('ten', D('ten')->getTree());
        $this->assign('status', $status);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '商品管理';
        $this->display();
    }

    public function edit($id = null)
    {
        recordLog('进入商品编辑模块','商品编辑');
        $Shop = D('Shop');
        if ( IS_POST ) {
            $is_full = I('post.is_full');//兑换类型
            if (empty($is_full) and !isHostProduct()) {
                $this->error('兑换类型 必须选择');
            }
            recordLog('进入商品编辑模块IS_POST','商品编辑');
            $pics = I('post.pic');
            if ( sizeof($pics) > 5 ) {
                $this->error('最多上传5张图片！');
            }
            $orders = I('post.txtImageOrder');

            $pkconfigdata['id']=I('pkconfigid');
            $pkconfigdata['peoplenum']=I('peoplenum');
            $pkconfigdata['amount']=I('amount');
            $pkconfigdata['inventory']=I('inventory');
            $pkconfigdata['pkconfigremoveids']=I('pkconfigremoveids');

            if ( false !== $Shop->update($pics, $orders,$pkconfigdata) ) {
                recordLog('编辑成功，退出商品编辑模块','商品编辑');
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Shop->getError();
                recordLog('发生未知错误，退出商品编辑模块','商品编辑');
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $info = $Shop->info($id);
            $this->assign('info', $info);
            $this->assign('category', D('Shop')->getTree());
            $this->assign('ten', D('ten')->getTree());
            $this->assign('brand', D('brand')->getBrand(1));
            $pkconfiginfo= D('Pk')-> getPkconfiginfo($id);
            $this->assign('pkconfiginfo', $pkconfiginfo);
            $this->meta_title = '编辑商品';
            $this->display();
        }
    }
    /**
     * 添加商品
     */
    public function add()
    {
        $Shop = D('Shop');
        if ( IS_POST ) {
            $pics = I('post.pic');
            if (empty($pics)) {
                $this->error('请上传图片！');
            }
            if ( sizeof($pics) > 5 ) {
                $this->error('最多上传5张图片！');
            }
            $orders = I('post.txtImageOrder');
            $pkconfigdata['id']=I('pkconfigid');
            $pkconfigdata['peoplenum']=I('peoplenum');
            $pkconfigdata['amount']=I('amount');
            $pkconfigdata['inventory']=I('inventory');
            if ( false !== $Shop->update($pics, $orders,$pkconfigdata) ) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Shop->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '添加商品';
            $status = !isHostProduct() ? 0 : 1;
            $this->assign('status', $status);
            $this->assign('category', D('Shop')->getTree());
            $this->assign('ten', D('ten')->getTree());
//             var_dump(D('brand')->getBrand(1));exit;
            $this->assign('brand', D('brand')->getBrand(1));
            $this->display('edit');
        }
    }

    //设置商品状态
    public function status($id)
    {
        $status = M('Shop')->where('id=' . $id)->getField('status');
        if ( $status == 0 ) {//设置商品上架
            $data = array('status'=>1,'shelve_time'=>time());
            M('Shop')->where('id=' . $id)->setField($data );
            D('Shop')->adddate($id);
            //D('shop')->addPkHouse($id);
            $this->success('上架！');
        } else {
            // $shop = M('shop')->where('id='.$id)->find();
            // $ten = M('ten')->where('id='.$shop['ten'])->find();
            // $pid = M('shop_period')->where(array('sid' => $id, 'state' => 0))->getField('id');
            // $record = M('shop_record')->field('uid,number')->where(array('pid' => $pid))->select();
            // if ( $record ) {
            //     $sid = M('shop_period')->where(array('id' => $pid))->field('sid')->find();
            //     $shopname = M('Shop')->field('name')->where(array('id' => $sid['sid']))->find();
            //     $this->error($shopname['name'] . '当前有购买记录不能下架！');
            //     exit();
            // }
            // foreach ( $record as $key => $value ) {
            //     $backgold = $value['number'] * $ten['unit'];
            //     M('user')->where(array('id' => $value['uid']))->setInc('black', $backgold);
            // }
            // M('shop_period')->where(array('id' => $pid))->setField('state', 3);
            // M('Shop')->where('id=' . $id)->setField('status', 0);
            // M('Shop')->where('id=' . $id)->setInc('shopstock', 1);
            // $this->error('下架！'); 

            $rs = D('shop')->takeDown($id);

            if($rs){
                $this->error('下架！');
            }
            else{
                $this->error('该商品已下架或更新失败！');
            }
        }
    }

    public function updateCommonHouse($id){
        if($id){
           $rs = D('shop')->addPkHouse($id);

           if($rs){
               $this->success('更新成功');
           }
           else{
               $this->error('库存不足或夺宝期数已达成');
           }
        }
        else{
            $this->error('参数错误');
        }
    }

    public function multipleStatus()
    {
        $ids = I('post.ids');
        $status = I('post.status');
        $idstr = implode(',', $ids);
        $map = array('id' => array('in', $idstr));
//        $list = M('Shop')->where($map)->field('id,auto')->select();
//        $status = M('Shop')->where($map)->getField('status');
        if ( $status == 1 ) {
            $count = M('Shop')->where($map)->setField('status', 1);
            foreach ( $ids as $key => $id ) {
                D('Shop')->adddate($id);
            }

            $this->success('上架！');
        } else {

          $pids=  D('Shop')->getShopNo($idstr);
//            $pid = M('shop_period')->where(array('sid' => array('in', $ids)))->getField('id', true);
            foreach ( $pids as $key => $item ) {
                $recordcount = M('shop_record')->where(array('pid' => $item["id"]))->count('id');
                //进行中有购买记录不让下架
                if ( $recordcount > 0 ) {
                    $sid = M('shop_period')->where(array('id' => $item))->field('sid')->find();
                    $shopname = M('Shop')->field('name')->where(array('id' => $sid['sid']))->find();

                     $this->error($shopname['name'].'当前有购买记录不能下架！');
                } else {
                    $period = M('shop_period')->where(array('id' => $item["id"]))->find();
                    $shop = M('shop')->where('id='.$period['sid'])->find();
                    $ten = M('ten')->where('id='.$shop['ten'])->find();

                    $record = M('shop_record')->field('uid,number')->where(array('pid' => $item["id"]))->select();
                    foreach ( $record as $key => $value ) {
                        $backgold = $value['number'] * $ten['unit'];
                        M('user')->where(array('id' => $value['uid']))->setInc('black', $value['number']);
                    }
                    $sid = M('shop_period')->where(array('id' => $item["id"]))->field('sid')->find();
                    M('shop_period')->where(array('id' => $item["id"]))->setField('state', 3);
                    M('Shop')->where(array('id' => $sid['sid']))->setField('status', 0);
                    M('Shop')->where(array('id' => $sid['sid']))->setInc('shopstock', 1);
                }
            }
            $this->error('下架！');
        }
    }

    //删除图片
    public function delImage($pic)
    {
        D('Shop')->delImage($pic);
    }

    public function del()
    {
        $id = array_unique((array)I('id', 0));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $res = D('Shop')->remove($id);
        if ( $res !== false ) {
            $this->success('删除商品成功！');
        } else {
            $this->error('删除商品失败！');
        }
    }

    public function auto()
    {
        $id = array_unique((array)I('id', 0));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map = array('id' => array('in', $id));
        $list = M('Shop')->where($map)->field('id,auto')->select();
        foreach ( $list as $value ) {
            if ( $value['auto'] == 0 ) {
                M('Shop')->where('id=' . $value['id'])->setField('auto', 1);
            } else {
                M('Shop')->where('id=' . $value['id'])->setField('auto', 0);
            }
        }
        $this->success('商品操作成功！');
    }

    public function period($id)
    {
        $map['sid'] = $id;
        $data = $this->lists('shop_period', $map, 'state asc,no desc,create_time desc', 0, '');
        $list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $list[] = $value;
                $shop = M('shop')->alias('s')->join('LEFT JOIN __TEN__ t ON s.ten=t.id')->where('s.id='.$value['sid'])->field('s.price,t.unit')->find();
                $progress = $value["number"] / ($shop["price"]/$shop['unit']) * 100;//进度比例
                $list[$key]['progress'] = ($progress < 1 and $progress >0) ? 1 : intval($progress);    //进度比例
            }
        }

        $this->assign('price', I('price'));
        $this->assign('periodlist', $list);
        $this->meta_title = '商品管理';
        $this->display();
    }

    public  function cardlist(){
        $conditionarr = array();
        $map = array();
        if ( isset($_GET['keyword']) ) {
            $map['name'] = array('like', '%' . I('keyword') . '%');
            $conditionarr['keyword'] = I('keyword');
        }
        if ( isset($_GET['type']) ) {
            $map['s.category'] = $_GET['type'];
            $conditionarr['type'] = I('type');
        }
        if ( isset($_GET['status']) ) {
            $map['c.status'] = I('status');
            $conditionarr['status'] = I('status');
        }
        if ( !empty($_GET['starttime']) ||  !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['c.create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }

        $Model = D('card');
        $Model->alias('c')
            ->join(array(' LEFT JOIN __SHOP__ s ON s.id= c.type',' LEFT JOIN __CATEGORY__ t ON t.id= s.category'));
        $data = $this->lists($Model, $map,'c.id desc',$rows=0,$map,'s.id,c.no,c.parvalue,c.description,c.create_time,c.status,c.issend,c.send_time,s.name,t.title,t.id,c.type,c.id as cid,c.password');
        $list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $list[] = $value;
                $length = mb_strlen($value['password'], 'utf8')-8;//密码长度
                $list[$key]['password']=substr_replace($value['password'], '********', 4, $length);
            }
        }

        $this->assign('cardlist', $list);
        $this->assign('category', D('Shop')->getTree());

        $this->assign('conditionarr', json_encode($conditionarr));
        $this->meta_title = '虚拟商品导入记录';
        $this->display();
    }
    /**
     * 虚拟商品导入
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function cardimport()
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
                    $error_id = '';
                    $patten_one = "/^\d{4}[\/](0?[1-9]|1[012])[\/](0?[1-9]|[12][0-9]|3[01])(\s+(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9]))?$/";
                    $patten_two = "/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])(\s+(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9]))?$/";
                    for ($j = 2; $j <= $rowCount; $j++){
                        $pay_time = trim($objPHPExcel->getActiveSheet()->getCell("E".$j)->getValue());//有效截止日期
                        if (!empty($pay_time)) {
                            if (is_numeric($pay_time)) {
                                 $pay_time = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell("E".$j)->getValue()));//有效截止日期  
                            } 
                            if (!preg_match ( $patten_one, $pay_time ) and !preg_match ( $patten_two, $pay_time )) {
                                    $error_id .= "第".$j."行时间格式错误  ";
                            }
                        }    

                    }
                    if (empty($error_id)) {
                        for ($i = 2; $i <= $rowCount; $i++){
                            $data = array();
                            $a = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();//商品id
                            $data['type'] = $a;
                            $b = $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();//金额
                            $data['parvalue'] = $b;
                            $c = $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();//卡号
                            $data['no'] = $c;
                            $d = $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();//密码
                            $data['password'] = $d;
                            $e = $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();//有效截止日期
                            if (is_numeric($e)) {
                                $e = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue()));//获取支付时间  
                            }
                            $data['description'] = trim($e);
                            $data['create_time'] = time();
                            $result = D('card')->add($data);
                            if ($result!=false) {

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
                            $this->success('导入成功！', U('cardlist'));
                        } else {
                            $error = "导入失败,第:".$test_id.'行中导入失败';
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
