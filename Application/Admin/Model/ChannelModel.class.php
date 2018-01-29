<?php
namespace Admin\Model;
use Think\Model;
 
class ChannelModel extends Model {

    protected $_validate = array(
        array('channel_name', 'require', '渠道名称必须填写', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        // array('contact', 'require', '联系人必须填写', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        // array('tel', 'require', '联系电话必须填写', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        // array('tel','/^(0|86|17951)?(13[0-9]|15[012356789]|18[0-9]|17[0-9]|14[57])[0-9]{8}$/','电话格式不正确',self::EXISTS_VALIDATE),
        // array('activity_link', 'require', '活动链接必须', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        // array('activity_link', 'url', '活动链接必须为URL格式'),
        array('channel_name', '', '渠道名称已经存在', self::EXISTS_VALIDATE, 'unique', self::MODEL_BOTH),
        // array('pid', '-1', '请选择上级渠道', self::EXISTS_VALIDATE, 'notin', self::MODEL_BOTH),
        // array('pid','checkPChannelCode','上级渠道必须为1,2级！',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
        //array('counts','\D','生成数量不能为空',self::MUST_VALIDATE,'regex',self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('status', 1, self::MODEL_INSERT, 'string'),
        array('create_time','mydate',1,'callback'),
        array('update_time','mydate',3,'callback'),
    );

    public function update(){
        $data = $this->create($_POST);
        
        if(empty($data)){
            return false;
        }
        if(empty($data['id'])){
            $data['channel_level'] =  $this->getChannelLevel($data['pid']);
            $id = $this->add($data);
            if(!$id){
                $this->error = '新增渠道出错！';
                return false;
            }else{
            //生成顶级渠道信息，并存储
                $rootC =  $this->getRootId($id);

                $this->where('id = '.$id )->setField('root_name',$rootC['channel_name'] );

            }
        } else {
            $status = $this->save($data);
            if(false === $status){ 
                $this->error = '更新渠道出错！';
                return false;
            }else{
                $rootC =  $this->getRootId($data['id']);
                $this->where('id = '.$data['id'])->setField('root_name',$rootC['channel_name']);
            }
        }
        return $data;
    }
    /**
     * 渠道编辑
     * 
     * @return [type] [description]
     */
    public function edit(){
        $data = $this->create($_POST);
        
        if(empty($data)){
            return false;
        }
        if (!empty($data['cash'])) {
            $cash = array_filter($data['cash']);
            sort($cash);
            $data['cash'] = implode(',', $cash);
        }
        if(empty($data['id'])){
            $id = $this->add($data);
            if(!$id){
                $this->error = '新增渠道出错！';
                return false;
            }
        } else {
            $status = $this->save($data);
            if(false === $status){ 
                $this->error = '更新渠道出错！';
                return false;
            }
        }
        return $data;
    }


    public function info($id, $field = true){
        $map = array();
        if(is_numeric($id)){
            $map['id'] = $id;
        }
        $info=$this->field($field)->where($map)->find();
        if (!empty($info['cash'])) {
            $info['cash'] = explode(',', $info['cash']);
        }
//        $info["picurl"]=get_cover($info["cover_id"],"path");
        return $info;
    }

	public function remove($id = null){
		$map = array('id' => array('in', $id));
		return $this->where($map)->delete();
	}
	
	protected function getEndTime(){
        $end_time    =   I('post.end_time');
        return $end_time?strtotime($end_time):NOW_TIME;
    }

    protected function mydate(){
        return date("Y-m-d H:i:s");
    }
    /**
     * 是否是官网
     * @param  string $id [description]
     * @return [type]     [description]
     */
    public function ischannelid($id='0')
    {
        $count = $this->where('id='.$id.' and channel_name="guanwang"')->count();
        return $count == 0 ? $id : '';
    }
    public function getTree($ids='')
    {
        if($ids !== ''){
            $map['id'] = array('in',$ids);
        }
        $map['status'] = 1;
        $list = M("Channel")->where($map)->field('id,pid,channel_name')->order('pid asc')->select();
        
        $Tree = new \Org\Tree;
        $Tree::$treeList = array();
        return $Tree->tree($list);
    }

    /**
     * 获取子渠道
     * @param $channel_id
     * @param string $field  ids  = 获取id以逗号分隔
     * @return array
     */
    public function getSubChannel($channel_id,$field='',$sepreate='')
    {
        $list = M("Channel")->where(array('status' => 1))->field('id,pid,channel_name')->order('pid asc')->select();
        $Tree = new \Org\Tree;
        $Tree::$treeList = array();
        $data = $Tree->treeById($list,$channel_id,$count = 0,$char="");
        if($field == 'ids'){
            //返回 逗号分隔的id 列表
            $ids = array_column($data, 'id');
            if($sepreate != ''){
               return implode($sepreate, $ids);    
            }
            return $ids;
        }
    }

    public function getLevelTree($field = true){
        $list = $this->field($field)->select();
        return $list;
    }

    public function getChannels($param = array()){
        $sql = "   select a.id ,a.channel_name,a.contact,a.tel,a.activity_link,b.channel_name as pChannel_name ,a.create_time,a.update_time,a.status from ( select * from bo_channel c where 1=1 ";

        if ( $param['channel_name'] ) {
            $sql .= " and c.channel_name like  '%" . $param['channel_name'] . "%' ";
        }
        if ( $param['channel_level'] ) {
            $sql .= " and c.channel_level = '" . $param['channel_level'] ;
        }
        if ( $param['status'] ) {
            $sql .= " and c.status = '" . $param['status'];
        }
        if ( $param['id'] ) {
            $sql .= " and c.id in (" . $param['id'].")";
        }
        $sql .= " order by c.id desc limit " . $param['pageindex'] . "," . $param['pagesize'] . " ) a ";
        $sql .= " LEFT JOIN bo_channel b on a.pid = b.id ";

        $channels = $this->query($sql, false);
        return $channels;
    }

    protected function checkPChannelCode(){
        $Channel = M('Channel');
        $pid    =   I('post.pid');
        $channel_level = $this->getPChannelLevel($pid);
        if(  $channel_level['channel_level'] > 2) {
            return false;
        }
        return true;
    }

    /**
     * 获取顶级渠道记录信息  id,pid,channel_name
     * @param $id 当前渠道id
     * @return array|mixed
     */
    public function getRootId($id){
        $path = array();
        $nav = $this->where("id={$id}")->field('id,pid,channel_name')->find();
        $path = $nav;
        if( $nav['pid'] > 0 ){
            $path = $this->getRootId($nav['pid']);
        }
        return $path;
    }

    public function getPChannelLevel($pid){
        return $this->field('channel_level')->where('id='.$pid)->find();
    }

    /**
     * 获取当前的渠道等级
     * @param $pid
     * @return mixed
     */
    public function getChannelLevel($pid){
        if($pid == 0){
            return 1;
        }

         $pLevel = $this->getPChannelLevel($pid)['channel_level'];
        if($pLevel == null){
            return false ;
        }
        $pLevel = $pLevel+1;
        return $pLevel;
    }
    /**
     * 渠道 及 所有下级的id集合
     * @param  string $pid [description]
     * @param  string $ids [description]
     * @return [type]      [description]
     */
    public function dataList($pid = "", $ids ="")
    {
        $list = $this->where('pid = '.$pid)->field('id')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $ids.= ','.$value['id']; 
                $ids.= $this->dataList($value['id']);
            }
        }
        return $ids;
    }
    /**
     * 渠道数据汇总计算
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function summary($data = array(),$start_time,$end_time)
    {
        $list = array();
        if ( !empty($data) ) {
            foreach ($data as $key => $value) {
                $list[] = $value;
                $channel_id = D('Channel')->dataList($value['id'],$value['id']);//一二三级渠道id集合
                $user_where = " channelid in (".$channel_id.")";//渠道注册人数条件
                $order_where = "channel_id_profit in ($channel_id)";//现金充值金额 & 金币支付金条件&现金支付金额
               
                if (!empty($start_time)) {
                    $user_where .= " and create_time >= ".$start_time; //注册时间
                    $order_where .= " and create_time >= ".$start_time;//订单支付时间 & pk房间创建时间 & 订单支付时间
                }
                if (!empty($end_time)) {
                    $user_where .= " and create_time <= ".$end_time;
                    $order_where .= " and create_time <= ".$end_time;
                }
                $user_count = M('user')->where($user_where)->count();//渠道注册人数
                
                //现金充值金额
                $recharge_list = M('shop_order')->where($order_where." and pid = 0")->field("sum(cash) cash")->select();
                $recharge_cash = empty($recharge_list[0]['cash']) ? 0.00 : $recharge_list[0]['cash'];
                
                //金币支付金
                $gold_list = M('shop_order')->where($order_where)->field("sum(gold) gold")->select();
                //现金支付金额
                $ordinary_list=M('shop_order')->where($order_where)->field("sum(cash) cash")->select();               
                $ordinary_cash = empty($ordinary_list[0]['cash']) ? 0.00 : $ordinary_list[0]['cash'];//现金支付金额

                $pk_cash = empty($pk_list[0]['cash']) ? 0.00 : $pk_list[0]['cash'];//用户开房间总金额
                $gold = empty($gold_list[0]['gold']) ? 0.00 : $gold_list[0]['gold'];//金币支付金

                $list[$key]['register_number'] = $user_count;//用户注册数
                $list[$key]['recharge_money'] = sprintf("%.2f", $recharge_cash);//现金充值金额
                $list[$key]['cash_money'] = sprintf("%.2f",$ordinary_cash);//现金支付金额
                $list[$key]['gold_money'] = $gold;//金币支付金
                $list[$key]['total_money'] = sprintf("%.2f",$ordinary_cash+$gold);//金币支付金
            }
        }
        return $list;
    }
    
    /**
     * 渠道数据详情 - 总数
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function infototal($param = array())
    {
        $channelid = empty($param['channelid']) ? '' : $param['channelid'];//channelid集合
        $sql = "SELECT o.channel_id_profit as channelid,o.invitation_id_profit as invitationid,c.channel_name FROM bo_shop_order o LEFT JOIN bo_channel c ON o.channel_id_profit = c.id   WHERE o.channel_id_profit IN (".$channelid.") ";//订单
        $user_sql = "SELECT u.channelid,u.invitationid,c.channel_name FROM bo_user u LEFT JOIN bo_channel c ON u.channelid = c.id  WHERE u.channelid IN (".$channelid.") ";//用户
        //渠道搜索
        if (isset($param['channel_id'])) {
            $sql .= " and o.channel_id_profit=".$param['channel_id'];
            $user_sql .= " and u.channelid=".$param['channel_id'];
        }
        //邀请码搜索
        if (!empty($param['invitationid'])) {
            $sql .= " and o.invitation_id_profit like  '%" . $param['invitationid'] . "%' ";
            $user_sql .= " and u.invitationid like  '%" . $param['invitationid'] . "%' ";
        }
        //开始时间
        if ( !empty($param['starttime']) ) {
            $start_time = strtotime($param['starttime']);
        }
        //结束时间
        if ( !empty($param['endtime']) ) {
            $end_time = strtotime($param['endtime']. " 23:59:59");
        }
        $sql .= " GROUP BY o.channel_id_profit,o.invitation_id_profit";
        $user_sql .= " ORDER BY u.channelid asc,u.invitationid desc";
        $order_data = $this->query($sql, false);
        $user_data = $this->query($user_sql, false);
        $data = $this->array_unique($order_data,$user_data);

        $count = count($data,COUNT_NORMAL);
        return $count;
    }

    /**
     * 渠道数据详情 - 列表
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function infolist($param = array())
    {
        $channelid = empty($param['channelid']) ? '' : $param['channelid'];//channelid集合
        $sql = "SELECT o.channel_id_profit as channelid,o.invitation_id_profit as invitationid,c.channel_name FROM bo_shop_order o LEFT JOIN bo_channel c ON o.channel_id_profit = c.id   WHERE o.channel_id_profit IN (".$channelid.") ";//订单
        $user_sql = "SELECT u.channelid,u.invitationid,c.channel_name FROM bo_user u LEFT JOIN bo_channel c ON u.channelid = c.id  WHERE u.channelid IN (".$channelid.") ";//用户
        //渠道搜索
        if (isset($param['channel_id'])) {
            $sql .= " and o.channel_id_profit=".$param['channel_id'];
            $user_sql .= " and u.channelid=".$param['channel_id'];
        }
        //邀请码搜索
        if (!empty($param['invitationid'])) {
            $sql .= " and o.invitation_id_profit like  '%" . $param['invitationid'] . "%' ";
            $user_sql .= " and u.invitationid like  '%" . $param['invitationid'] . "%' ";
        }
        //开始时间
        if ( !empty($param['starttime']) ) {
            $start_time = strtotime($param['starttime']);
        }
        //结束时间
        if ( !empty($param['endtime']) ) {
            $end_time = strtotime($param['endtime']. " 23:59:59");
        }
        $sql .= " GROUP BY o.channel_id_profit,o.invitation_id_profit";
        $user_sql .= " ORDER BY u.channelid asc,u.invitationid desc";
        $order_data = $this->query($sql, false);
        $user_data = $this->query($user_sql, false);
        $data = $this->array_unique($order_data,$user_data);
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $offsize = ($param['pageindex']-1)*$param['pagesize'];
            $data = array_slice($data,$offsize,$param['pagesize']);
        }
        $list = array();
        if ( !empty($data) ) {
            foreach ($data as $key => $value) {
                $list[] = $value;
                $user_where = " invitationid = ".$value['invitationid']." and channelid=".$value['channelid'];//渠道注册人数条件
                $order_where = "channel_id_profit = ".$value['channelid']." and invitation_id_profit=".$value['invitationid'];//现金充值金额 & 金币支付金条件&现金支付金额
               
                if (!empty($start_time)) {
                    $user_where .= " and create_time >= ".$start_time; //注册时间
                    $order_where .= " and create_time >= ".$start_time;//现金充值金额 & 金币支付金条件&现金支付金额
                }
                if (!empty($end_time)) {
                    $user_where .= " and create_time <= ".$end_time;
                    $order_where .= " and create_time <= ".$end_time;
                }
                //渠道注册人数
                $user_count = M('user')->where($user_where)->count();
                //现金充值金额
                $recharge_list = M('shop_order')->where($order_where." and pid = 0")->field("sum(cash) cash")->select();
                $recharge_cash = empty($recharge_list[0]['cash']) ? 0.00 : $recharge_list[0]['cash'];
                //金币支付金
                $gold_list = M('shop_order')->where($order_where)->field("sum(gold) gold")->select();
                $gold = empty($gold_list[0]['gold']) ? 0.00 : $gold_list[0]['gold'];
                //总现金金额  
                $ordinary_list = M('shop_order')->where($order_where)->field("sum(cash) cash")->select();
                $ordinary_cash = empty($ordinary_list[0]['cash']) ? 0.00 : $ordinary_list[0]['cash'];

                $list[$key]['register_number'] = $user_count;//用户注册数
                $list[$key]['recharge_money'] = sprintf("%.2f", $recharge_cash);//现金充值金额
                $list[$key]['cash_money'] = sprintf("%.2f",$ordinary_cash);//现金支付金额
                $list[$key]['gold_money'] = $gold;//金币支付金
                $list[$key]['total_money'] = sprintf("%.2f",$ordinary_cash+$gold);//金币支付金
            }
        }
        return $list;
    }
    /**
     * 两个数组组合去重
     * @param  [type] $data [description]
     * @param  [type] $list [description]
     * @return [type]       [description]
     */
    public function array_unique($data, $list)
    {

        $arr = array_merge($data,$list);
        foreach ($arr as $v) {
            $v=join(',',$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[]=$v;
        }
        $data = array();
        $temp=array_unique($temp);    //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v){

            $array=explode(',',$v);   //再将拆开的数组重新组装
            //下面的索引根据自己的情况进行修改即可
            $data[$k]['channelid'] =$array[0];
            $data[$k]['invitationid'] =$array[1];
            $data[$k]['channel_name'] =$array[2];
        }
        return $data;
    }
    //生成二维码  如： $data = '{"houseid": 10127}'
    function genQRcode($data){
        //拼装生成二维码渠道地址URL
    //        $activity_url = $activity_link.'/code/'.$id;
        //草料二维码地址
        $CLI_URL_PREFIX = "https://cli.im/api/qrcode/code?text=";
        $CLI_URL_SUFFIX = "&mhid=sELPDFnok80gPHovKdI"; //一元摸金的模板参数
        $qr_url =  $CLI_URL_PREFIX.$data.$CLI_URL_SUFFIX;

        $content = file_get_contents($qr_url);
        // 用正则表达式解析
        preg_match('/<img src="(.*?)"/i',$content,$match);
        $qr_code = $this->GrabImage('http:'.$match[1],"");
        return $qr_code;
    }
    //图片存储
    function GrabImage($url, $filename = "") {
        if ($url == ""):return false;
        endif;
        //如果$url地址为空，直接退出
        if ($filename == "") {
            //TODO 当目录不存在的时候生成文件目录
            $path = 'Picture/Invitation/'.date("Y-m-d");
            if (!file_exists($path)){
                $rs = mkdir($path);
            }
            $filename = $path.'/'.date("YmdHis") . rand(100000,999999).'.jpg';
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
}
