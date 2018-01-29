<?php
namespace Admin\Controller;

use Think\Controller;
/**
 * 渠道管理控制器
 */
class ChannelController extends WebController
{
    public function _initialize()
    {
        parent::_initialize();
        vendor("phpexcel.Classes.PHPExcel");
    }

    /**
     * 渠道管理列表
     */

    public function index(){

        $map = array();
        $conditionarr=array();
        //名称
        if(isset($_GET['keyword'])){
            $map['channel_name']  = array('like', '%'.I('keyword').'%');
            $conditionarr['keyword']= I('keyword');
        }
        //状态
        if ( isset($_GET['status']) ) {
            $map['status'] = I ('status');
            $conditionarr['status']= I('status');
        }
        //开始时间 结束时间
        if(isset($_GET['starttime']) and isset($_GET['endtime'])){
            $map['create_time']= array(array('egt',I('starttime')),array('lt',I('endtime')." 23:59:59"));
            $conditionarr['starttime']= I('starttime');
            $conditionarr['endtime']= I('endtime');
        }
        $list   = $this->lists('channel',$map,$order='id asc',$rows=0,$base = array('status'=>array('egt',0)),$field=true);
        $cnames = M('Channel')->field('id,channel_name')->select();
        foreach ($list as $k=>$v){
            $list[$k]['id']=$v['id'];
            $list[$k]['channel_name']=$v['channel_name'];
            $list[$k]['contact']=$v['contact'];
            $list[$k]['tel']=$v['tel'];
//            $list[$k]['code']=$v['code'];
            $list[$k]['activity_link']=$v['activity_link'];
            foreach ($cnames as $k2 => $v2){
                if( $v['pid'] == $v2['id']){
                    $list[$k]['pid']=$v2['channel_name'];
                    break;
                }
            }
            //顶级渠道
            $data =  D('Channel')->getRootId($v['id']);
            $list[$k]['rootChannel']  =  $data['channel_name'] == null ? $v['channel_name']  :  $data['channel_name'] ;
            
            $list[$k]['create_time']=$v['create_time'];
        }
        $this->assign('_list', $list);
        $this->assign('level', D('Channel')->getLevelTree());
        $this->assign('conditionarr',json_encode($conditionarr));
        $this->meta_title = '渠道列表';
        $this->display();
    }

    public function edit($id){
        $Channel = D('Channel');
        if(IS_POST){
            if(false !== $Channel->update()){
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Channel->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $info=$Channel->info($id);
            $arr_channel_name = M('Channel')->field('channel_name')->where( array("id"=>$info['pid']))->find(); 
            $this->assign('data',       $info);
            $this->assign('channel_name',$arr_channel_name);
            $this->meta_title = '编辑渠道';
            $this->assign('category', D('Channel')->getTree());
            $this->display();
        }
    }

    public function add(){
        $model = D('Channel');
        if(IS_POST){
            if(false !== $model->update()){
                $this->success('新增成功！');
            } else {
                $error = $model->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '添加渠道';
            $this->assign('category', D('Channel')->getTree());
            $this->display('edit');
        }
    }

    /**
     * 更新活动
     */
    public function save(){
        //渠道名称
        $channel_name = I('channel_name'); 
        //联系人
        $contact = I('contact');
        //联系电话
        $tel = I('tel');
        //金券兑换比例
        $proportion = I('proportion');
        //起拍数量
        $starting_number = I('starting_number');
        //溢价
        $premium = I('premium');
        //提取费用
        $extract_cost = I('extract_cost');
        //提取数量
        $cash = I('cash');
        //提现费用(元/毫克)
        $extract_money = I('extract_money');
        //起提克数(mg)
        $extract_number = I('extract_number');
        if (empty($channel_name)) {
            $this->error("请填写渠道名称");
        } 
        // elseif (empty($contact)) {
        //     $this->error("请填写联系人");
        // } elseif (empty($tel)) {
        //     $this->error("请填写联系电话");
        // } 
        elseif (empty($proportion)) {
            $this->error("请填写".C("WEB_CURRENCY")."兑换比例");
        } elseif (empty($starting_number)) {
            $this->error("请填写起拍数量");
        } elseif (!isset($premium)) {
            $this->error("请填写溢价");
        // } elseif (empty($extract_cost)) {
        //     $this->error("请填写提取费用");
        } elseif (empty($cash)) {
            $this->error("请填写提取数量");
        } elseif (empty($extract_money)) {
            $this->error("请填写提现费用");
        } elseif (empty($extract_number)) {
            $this->error("请填写起提克数");
        } else {
            $res = D('Channel')->edit();
            if(!$res){
                $this->error(D('Channel')->getError());
            }else{
                $this->success($res['id']?'更新成功！':'新增成功！', U('index'));
            }
        }
        
    }

    /**
     * 生成二维码图片
     */
    public function genQrCode(){
        $activity_link =  I('get.activity_link') ;
        $id =   I('get.id') ; 

        //拼装生成二维码渠道地址URL
        $activity_url = $activity_link.'/code/'.$id;
        //草料二维码地址
        $CLI_URL_PREFIX = "https://cli.im/api/qrcode/code?text=";
        $CLI_URL_SUFFIX = "&mhid=sELPDFnok80gPHovKdI"; //一元摸金的模板参数
        $qr_url =  $CLI_URL_PREFIX.$activity_url.$CLI_URL_SUFFIX;

        //调用 草料 生成二维码
        //1.返回HTML中包含二维码图片
        //2.解析提取二维码图片地址
        //3.存储图片到本地
        $content = file_get_contents($qr_url);
        // 用正则表达式解析
        preg_match('/<img src="(.*?)"/i',$content,$match);
        $qr_code = $this-> GrabImage('http:'.$match[1],"");
        echo $qr_code;
    }

    protected function GrabImage($url, $filename = "") {
        if ($url == ""):return false;
        endif;
        //如果$url地址为空，直接退出
        if ($filename == "") {
            //TODO 当目录不存在的时候生成文件目录
            $filename = 'Picture/Channel/'.date("YmdHis") .'.jpg';
            //用天月面时分秒来命名新的文件名
        }
        ob_start();//打开输出
        readfile($url);//输出图片文件
        $img = ob_get_contents();//得到浏览器输出
        ob_end_clean();//清除输出并关闭
        $size = strlen($img);//得到图片大小
        $fp2 = @fopen($filename, "a");
        fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名
        fclose($fp2);
        return $filename;//返回新的文件名
    }

    /**
     * 下载图片
     */
    public function downPic(){
        $file =  I('get.file') ;
        $file  =  str_replace("channel", "Channel", $file);
        $file  =  str_replace("picture", "Picture", $file);

        $os_file  =  str_replace("/", DIRECTORY_SEPARATOR, $file);
        ob_end_clean();
        $hfile = fopen($os_file, "rb") or die("Can not find file: $os_file\n");
        Header("Content-type: application/octet-stream");
        Header("Content-Transfer-Encoding: binary");
        Header("Accept-Ranges: bytes");
        Header("Content-Length: ".filesize($os_file));
        Header("Content-Disposition: attachment; filename=\"$os_file\"");
        while (!feof($hfile)) {
            echo fread($hfile, 32768);
        }
        fclose($hfile);
    }

    /**
     * 导出渠道列表信息Excel文件；
     */
    public function exportChannelList()
    {
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '渠道名称', '联系人', '联系电话', '渠道包名', '活动链接', '上级渠道', '状态','添加时间', '更新时间');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }
        $map = array();
        if ( !empty($_POST['keyword']) ) {
            $map['channel_name'] = I('keyword');
        }
        if ( !empty($_POST['channel_level']) ) {
            $map['channel_level'] = I('channel_level');
        }
        if ( !empty($_POST['status']) ) {
            $map['status'] = I('status');
        }

        //根据用户登录信息获取用户的子渠道列表
        if(cookie('rolename') === '渠道'){
            $s_channel_id = cookie('channel_id');
            $s_subChannel = D('Channel')->getSubChannel($s_channel_id,'ids');
            $map['id'] = implode(',', $s_subChannel);
        }
        
        $map['pageindex'] = 0;
        $map['pagesize'] = 99999999;

        $list = D('Channel')->getChannels($map);
        
        $i = 2;
        foreach ( $list as $item ) {
//            channel_name contact  tel code activity_link  pid create_time
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['contact']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['app_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['activity_link']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['pChannel_name'] == null ? '------': $item['pChannel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['status'] == 1 ? "启用" : "停用");
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['create_time'] );
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $item['update_time'] );

            $i++;
        }

        $outputFileName = 'channel-'.date("Y年-m月-d日") . '.xls';
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

//    public function getSubChannel($channel_id){
//        $data = D('Channel')->getSubChannel($channel_id,'ids');
//        var_dump($data);
//    }

    /*
    * 邀请码列表
    */
    public function invitationList(){
        $this->meta_title = '邀请码';
        $channel_id = I('channel_id')?I('channel_id'):-1;
        $sqlMap = '';

        if($channel_id != -1){
            $sqlMap = ' and i.channelid='.$channel_id;
        }

        $where = 'i.channelid=c.id'.$sqlMap;

        $model_invitation = M('Invitation')
            ->table(" __CHANNEL__ c,__INVITATION__ i")
            ->join('LEFT JOIN __USER__ u ON i.id=u.invitationid')
            ->group('i.id');
            // ->where("i.channelid=c.id")
            // ->field('c.channel_name,i.id,i.channelid,i.name,i.contacts,i.tel,i.remark,i.status,FROM_UNIXTIME(i.create_time) create_time')
            // ->order('i.id desc,i.create_time desc')
            // ->select();

        // SELECT c.channel_name,i.id,i.channelid,i.name,i.contacts,i.tel,i.remark,i.status,FROM_UNIXTIME(i.create_time) create_time,count(u.invitationid) cnt 
        // FROM hx_channel c,hx_invitation i
        // LEFT JOIN hx_user u ON i.id=u.invitationid 
        // WHERE ( i.channelid=c.id ) 
        // GROUP BY i.id ORDER BY i.id desc,i.create_time desc LIMIT 0,20  

        //$list = $this->lists($model_channel,$map,$order='id asc',$rows=0,$base = array('status'=>array('egt',0)),$field=true);
        
        $list = $this->lists($model_invitation,$where,'i.id desc,i.create_time desc',0,array (),'c.channel_name,i.id,i.channelid,i.name,i.contacts,i.tel,i.remark,i.status,i.qr_code,FROM_UNIXTIME(i.create_time) create_time,count(u.invitationid) cnt');

        $subQuery = M('Invitation')
        ->field('c.channel_name,i.id,i.channelid,i.name,i.contacts,i.tel,i.remark,i.status,FROM_UNIXTIME(i.create_time) create_time,count(u.invitationid) cnt')
        ->table(" __CHANNEL__ c,__INVITATION__ i")
        ->join('LEFT JOIN __USER__ u ON i.id=u.invitationid')
        ->where($where)
        ->group('i.id')
        ->order('i.id desc,i.create_time desc')->buildSql(); 

        $_totalcount = M('Invitation')->table($subQuery.' t')->getField('SUM(t.cnt) total');

        //select SUM(t.cnt) total FROM(
        //     SELECT i.id,c.channel_name,count(i.id) cnt FROM hx_invitation i,hx_channel c,hx_user u
        //     WHERE i.id=u.invitationid and i.channelid=c.id  AND c.id=91
        //     GROUP BY i.id
        //     ORDER BY i.id DESC
        // ) as t
        $this->assign('_totalcount', $_totalcount);
        $this->assign('channel_list', D('Channel')->getTree());
        $this->assign('invitation_list', $list);
        $this->assign('channel_id', $channel_id);
        $this->display();
    }
    /*
    * 生成邀请码
    */
    public function invitation(){
        try{
            $channel_id = I('pid')?I('pid'):-1;

            $this->meta_title = '生成邀请码';
            $this->assign('channel_list', D('Channel')->getTree());

            if(!IS_POST){
                $this->display();
            }
            else{
                if(empty(I('pid')) || I('pid')==-1){
                    $this->error("请选择渠道");
                    exit();
                }

                if(empty(I('counts')) || !is_numeric(I('counts'))){
                    $this->error("数量必须为数字");
                    exit();
                }

                $map['channelid'] = I('pid');
                $counts = I('counts');

                $channel_counts = M('Invitation')->where($map)->count();

                if($channel_counts>500){
                    $this->error("一个渠道最多生成500个邀请码，请联系管理员修改最大支持数量");
                    exit();
                }
                //获取数据库最新id
                $max_item = M('Invitation')->field('id')->order('id desc')->find();
                $dataList=array ();
                for ($i=0; $i < $counts; $i++) {
                    $id = empty($max_item) ? '100001' : $max_item['id']+$i+1;
                    $url = array("event_type" => array("bind_user_channel"),"invitation_code"=> $value['id']);
                    $data = json_encode($url);
                    $dataList[$i] = array (
                        "channelid"=>$map['channelid'],
                        "status"=>1,
                        "create_time"=>time(),
                        "qr_code" => D('Channel')->genQrCode($data)
                    );
                }

                $rs_invite = M('Invitation')->addAll($dataList);

                for ($j=0; $j < $counts; $j++) { 
                    if(($j+1) % 10 == 0){
                        $result_str .= $j+$rs_invite . '<br>';
                    }
                    else {
                        $result_str .= $j+$rs_invite . '&nbsp;&nbsp;';
                    }
                }

                // $this->success("成功生成".$counts."条记录");
                $this->assign('invitation_list',$result_str);
                $this->assign('counts', $counts);
                $this->assign('channel_id', $channel_id);
                $this->display();
            }
        }
        catch(Exception $e){
            $this->error($e->getMessage());
        }
    }
    /**
     * 渠道数据汇总
     * @return [type] [description]
     */
    public function datalist()
    {
        $channel_list = M('channel')->where('pid = 0')->field('id,channel_name')->order('id asc')->select();//一级渠道列表
        
        $map = array();
        $conditionarr=array();

        $map['pid'] = 0;
        $start_time = '';
        $end_time = '';
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $start_time = strtotime(I('starttime'));
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $end_time = strtotime(I('endtime'). " 23:59:59");
        }
        //渠道名称搜索
        if ( isset($_GET['channelid']) ) {
            $map['id'] = I ('channelid');
            $conditionarr['channelid']= I('channelid');
        }
        $data   = $this->lists('channel',$map,$order='id asc',$rows=0,$base = array('status'=>array('egt',0)),$field=true);
        $list = D('Channel')->summary($data,$start_time,$end_time);

        $this->assign('channel_list', $channel_list);
        $this->assign('_list', $list);
        $this->assign('conditionarr',json_encode($conditionarr));
        $this->meta_title = '渠道数据汇总';
        $this->display();
    }
    /**
     * 渠道数据汇总
     * @return [type] [description]
     */
    public function item()
    {
        $list = array();
        $id = isset($_GET['id']) ? I('id') : '';
        $channel_id = D('Channel')->dataList($id,$id);//一二三级渠道id集合
        $channel_list = D('Channel')->getTree($channel_id);//一二三级渠道列表
        $map = array();
        $map['channelid'] = $channel_id;
        //渠道搜索
        if (isset($_GET['channelid'])) {
            $map['channel_id'] = I('channelid');
        }
        //邀请码搜索
        if (!empty($_GET['invitationid'])) {
            $map['invitationid'] = I('invitationid');
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
        $total   =  D('Channel')->infototal($map);

        //分页
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if ( $total > $listRows ) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $map['pageindex'] = $page->firstRow;
        $map['pagesize'] = $page->listRows;
        $list = D('Channel')->infolist($map);
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $this->assign('channel_list', $channel_list);
        $this->assign('_list', $list);
        $this->assign('conditionarr',json_encode($conditionarr));
        $this->meta_title = '渠道数据明细';
        $this->display();
    }
    /**
     * 渠道数据汇总-导出
     * @return [type] [description]
     */
    public function exportList()
    {
        $map = array();
        $conditionarr=array();

        $map['pid'] = 0;
        $start_time = '';
        $end_time = '';
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $start_time = strtotime(I('starttime'));
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $end_time = strtotime(I('endtime'). " 23:59:59");
        }
        //渠道名称搜索
        if ( isset($_GET['channelid']) ) {
            $map['id'] = I ('channelid');
            $conditionarr['channelid']= I('channelid');
        }
        $data   = $this->lists('channel',$map,$order='id asc',$rows=99999999,$base = array('status'=>array('egt',0)),$field=true);
        $list = D('channel')->summary($data);
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '渠道名称', '用户注册数', '现金充值金额', '现金支付金额', '金币支付金', '总支付金额');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }        
        $i = 2;
        foreach ( $list as $item ) {
//            channel_name contact  tel code activity_link  pid create_time
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['register_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['recharge_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['cash_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['gold_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['total_money']);
            $i++;
        }

        $outputFileName = '渠道数据汇总-'.date("Y年-m月-d日") . '.xls';
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
     * 渠道数据详情-导出
     * @return [type] [description]
     */
    public function exportitem()
    {
        $list = array();
        $id = isset($_GET['id']) ? I('id') : '';
        $channel_id = D('Channel')->dataList($id,$id);//一二三级渠道id集合
        $channel_list = D('Channel')->getTree($channel_id);//一二三级渠道列表
        $map = array();
        $map['channelid'] = $channel_id;
        //渠道搜索
        if (isset($_GET['channelid'])) {
            $map['channel_id'] = I('channelid');
        }
        //邀请码搜索
        if (!empty($_GET['invitationid'])) {
            $map['invitationid'] = I('invitationid');
        }
        //开始时间
        if ( !empty($_GET['starttime']) ) {
            $map['starttime'] = I('starttime');
        }
        //结束时间
        if ( !empty($_GET['endtime']) ) {
            $map['endtime'] = I('endtime');
        }
        $list = D('Channel')->infolist($map);
        // 初始化
        $objPHPExcel = new \PHPExcel();

        //设置参数
        //设值
        // $arrLetter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $arrFiled = array( '渠道名称', '渠道邀请码','用户注册数', '现金充值金额', '现金支付金额', '金币支付金', '总支付金额');
        foreach ( $arrFiled as $key => $value ) {
            $ch = chr(ord('A') + intval($key));
            $objPHPExcel->getActiveSheet()->setCellValue($ch . '1', $value);
        }        
        $i = 2;
        foreach ( $list as $item ) {
//            channel_name contact  tel code activity_link  pid create_time
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['channel_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['invitationid']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['register_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['recharge_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['cash_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['gold_money']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['total_money']);
            $i++;
        }

        $outputFileName = '渠道数据明细-'.date("Y年-m月-d日") . '.xls';
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