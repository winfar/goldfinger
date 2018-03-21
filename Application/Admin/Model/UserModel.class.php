<?php
namespace Admin\Model;
use Think\Model;

class UserModel extends Model {
	

	protected $_validate = array(
		array('username', '5,30', '用户名长度必须在5-30个字符之间！', self::EXISTS_VALIDATE, 'length',self::MODEL_INSERT), //用户名长度不合法
		array('password', '6,30', '密码长度必须在6-30个字符之间！', self::EXISTS_VALIDATE, 'length'),
	);

	protected $_auto = array(
		array('password', 'think_ucenter_md5', self::MODEL_BOTH, 'function'),
		array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
		array('status', 'getStatus', self::MODEL_INSERT, 'callback'),
	);


	public function info($uid){
		$map['id'] = $uid;
		$map['status']=1;
		$info=$this->where($map)->field('id,nickname,black,create_time')->find();
		return $info;
	}
	
	public function edit(){
		if($data=$this->create()){
			unset($data['password']);
			return $this->save($data);
		} else {
			return $this->getError();
		}
	}
	
	public function Password(){
		if(!$data = $this->create()){
            return false;
        }
		if(I('post.password') !== I('post.repassword')){
            $this->error = '您输入的新密码与确认密码不一致！';
			return false;
        }
        $res = $this->save();
        return $res;
    }
	
	protected function getStatus(){
		return true;
	}
	/**
     * 带搜索的用户详情
     * @param string $search 搜索数组 uid用户id phone手机号
     * @author liuwei
     * @return mixed
     */
    public function user_info($search = "", $filed = "*")
    {
        $where = array();
        //搜索-用户id
        if (isset($search['uid'])) {
            $where['id'] = intval($search['uid']);
        }
        //搜索-用户手机号
        if (!empty($search['phone'])) {
            $where['phone'] = trim($search['phone']);
        }
        $item = array();
        if (!empty($where)) {
            $item = $this->where($where)->field($filed)->find();
        }
        if (!empty($item)) {
            if (empty($item['phone'])) {

                //算出其他登录方式
                $passport_uid = $item['passport_uid'];
                $file_contents = file_get_contents("http://passport.busonline.com/wapapi.php?s=/Usertest/loginWay&uid=$passport_uid");
                $value = (json_decode($file_contents, true));
                $loginWay = $value['data'];

                switch ($loginWay) {
                    case 100:
                        $item['loginWay'] = "用户名";
                        break;
                    case 101:
                        $item['loginWay'] = "手机号";
                        break;
                    case 102:
                        $item['loginWay'] = "邮箱";
                        break;
                    case 201:
                        $item['loginWay'] = "微信";
                        break;
                    case 202:
                        $item['loginWay'] = "qq";
                        break;
                    case 203:
                        $item['loginWay'] = "微博";
                        break;
                    case 204:
                        $item['loginWay'] = "百度";
                        break;
                    case 205:
                        $item['loginWay'] = "淘宝";
                        break;
                    case 206:
                        $item['loginWay'] = "163";
                        break;
                    case 207:
                        $item['loginWay'] = "搜狐";
                        break;
                    case 301:
                        $item['loginWay'] = "谷歌";
                        break;
                    case 302:
                        $item['loginWay'] = "twitter";
                        break;
                    case 303:
                        $item['loginWay'] = "facebook";
                        break;
                    case 304:
                        $item['loginWay'] = "instagram";
                        break;
                    case 401:
                        $item['loginWay'] = "游客";
                        break;
                    default:
                        $item['loginWay'] = "其他登录方式";
                }
            } else {
                $item['loginWay'] = "手机号";
            }
        }
        return $item;

    }

    /**
     * 用户积分详情
     * @param $uid 用户id
     * @author liuwei
     * @return mixed
     */
    public function user_point_list($uid)
    {
        //条件-用户id
        $list = array();
        if (!empty($uid)) {
        	$where = array();
        	$where['user_id'] = $uid;
        	$list = D("PointRecord")->where($where)->order('create_time desc,type_id asc')->select();
        }
        
        return $list;
    }

    /**
     * 用户购买的记录
     * @param $uid 用户id
     * @author liuwei
     * @return array
     */
    public function user_record_list($uid)
    {
        //条件-用户id
        $where = array();
        $where['uid'] = $uid;
        $data = array();
        $list = D("ShopOrder")->where($where)->order('create_time desc')->select();
        if (!empty($list)) {
            foreach ( $list as $k => $v ) {
                $data[] = $v;
                $record = M('shop_record')->where('order_id=' . $v['order_id'])->field('create_time,number,num')->find();
                $period = M('shop_period')->where('id=' . $v['pid'])->field('no,sid')->find();
                $shop = M('shop')->where('id=' . $period['sid'])->field('name,price')->find();
                $data[$k]['no'] = $period['no'];
                $data[$k]['name'] = $shop['name'];
                $data[$k]['add_time'] = $record['create_time'];
                $data[$k]['number'] = $record['number'];
                $data[$k]['num'] = $record['num'];
            }
        }
        return $data;
    }
    /**
     * 用户临时订单的记录
     * @param $uid 用户id
     * @author liuwei
     * @return array
     */
    public function user_temporary_list($uid)
    {
        //条件-用户id
        $where = array();
        $where['uid'] = $uid;
        $data = array();
        $list = M("temporary_order")->where($where)->order('create_time desc')->select();
        if (!empty($list)) {
            foreach ( $list as $k => $v ) {
                $data[] = $v;
                $period = M('shop_period')->where('id=' . $v['pid'])->field('sid,no,state,kaijang_time,kaijang_num,number')->find();
                $data[$k]['no'] = $period['no'];
            }
        }
        return $data;
    }
    /**
     * 用户分享记录
     * @param $uid  用户id
     * @author liuwei
     * @return mixed
     */
    public function user_shared_list($uid){
        $list = array();
        $shared = M('shop_shared')->table('__SHOP_SHARED__ shared,__SHOP_PERIOD__ period') ->field('shared.id,shared.content,shared.create_time,period.uid,period.number,period.no,period.id as pid,period.sid')->where('shared.pid=period.id and shared.uid='.$uid)->order('shared.create_time desc')->select();
        if($shared){
            foreach ($shared as $k=>$v){
                $list[$k]["uid"]=$v["uid"];
                $list[$k]["no"]=$v["no"];
                $list[$k]["shop_name"]=get_shop_name($v["sid"]);
                $list[$k]['shared_time']=time_format($v['create_time']);
                $list[$k]['shared_id']=$v['id'];
                $list[$k]['content']=$v['content'];
                $list[$k]['count']=M('shop_record')->where("pid=".$v["pid"]." and uid=".$v['uid'])->sum('number');
            }
        }
        return $list;
    }
    /**
     * 用户金币明细
     * @param $uid  用户id
     * @author liuwei
     * @return mixed
     */
    public function user_gold_info($uid)
    {
        //条件-用户id
        $where = array();
        $where['a.uid'] = $uid;
        $list = D("GoldRecord")->alias('a')->join(array(' LEFT JOIN __TRADE_TYPE__ t ON t.id= a.typeid'))->field('a.id,a.create_time,a.remark,a.gold,t.name')->where($where)->order('a.create_time desc,a.typeid asc,a.gold desc')->select();
        return $list;
    }
    /**
     * 用户红包明细
     * @param $uid  用户id
     * @author liuwei
     * @return mixed
     */
    public function user_envelope_list($uid)
    {
        $list = M('red_envelope_record')->table('__RED_ENVELOPE_RECORD__ record,__RED_ENVELOPE__ envelope')->field('record.create_time,record.use_time,record.status,envelope.name,envelope.amount')->where('envelope.id=record.red_envelope_id and record.uid='.$uid)->select();
        $data = array();
        foreach ($list as $key => $value) {
            $data[] = $value;
            switch ($value['status']) {
                case 0:
                    $data[$key]['status_name'] = '未使用';
                    break;
                case 1:
                    $data[$key]['status_name'] = '已使用';
                    break;
                case 2:
                    $data[$key]['status_name'] = '已过期';
                    break;    
                default:
                    $data[$key]['status_name'] = '其他';
                    break;
            }
        }

        return $data;
    }
    /**
     * 用户数量
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getUsersTotal($param = array())
    {
        $map = array();
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $map['_string'] = "CONCAT_WS('-',id,nickname) like '%".$param['name']."%'";
        }
        //开始时间
        if(!empty($param['starttime'])){
            $map['create_time']= array('egt',strtotime($param['starttime']));
        }
        //结束时间
        if(!empty($param['endtime'])){
            $map['create_time']= array('elt',strtotime($param['endtime'])+86400);
        }
        //渠道id
        if ( !empty($param['channel']) ) {
            $map['passport_uid'] = $param['channel'];
        }

        $count = $this->where($map)->count();
        return $count;
    }
    /**
     * 用户详情
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getUsersList($param = array())
    {
        $sql = "select u.*,c.channel_name,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id) AS number,(SELECT (case when SUM(top_diamond) is NUll then 0 else ABS(SUM(top_diamond)) end ) FROM bo_shop_order WHERE uid = u.id) as top_diamond,(SELECT (case when SUM(recharge_activity) is NUll then 0 else ABS(SUM(recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id) as recharge_activity,(SELECT ( CASE WHEN SUM(gold) IS NULL THEN 0 ELSE ABS(SUM(gold)) END ) FROM bo_shop_order WHERE uid = u.id ) AS gold,(SELECT COUNT(*) FROM bo_shop_period WHERE uid = u.id) as win_number,(SELECT (case when SUM(number) is NUll then 0 else ABS(SUM(number)) end ) FROM bo_user_cash WHERE uid = u.id) as cash from bo_user u left join bo_channel c on u.channelid = c.id where 1 = 1 ";
        //用户ID/用户名
        if ( isset($param['name']) ) {
            $sql .= " and ( CONCAT_WS('-',u.id,u.nickname) like '%".$param['name']."%' )";
        }
        //开始时间
        if(!empty($param['starttime'])){
            $sql .= " and u.create_time >= ".strtotime($param['starttime']);
        }
        //结束时间
        if(!empty($param['endtime'])){
            $endtime = strtotime($param['endtime'])+86400;
            $sql .= " and u.create_time < ".$endtime;
        }
        //渠道id
        if ( !empty($param['channel']) ) {
            $sql .= " and u.passport_uid = ".$param['channel'];
        }
        $sql .= " order by u.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 参与详情
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function participation($param = array())
    {
        $data = array();
        $sql = "select o.*,p.kaijang_num,u.nickname,r.num as numbersinfo,sgr.gold_price 
            from bo_shop_order o 
            LEFT JOIN bo_shop_period p on o.pid = p.id 
            LEFT JOIN bo_user u on o.uid = u.id 
            LEFT JOIN bo_shop_record r on o.order_id = r.order_id 
            LEFT JOIN bo_shop_gold_record sgr ON sgr.id=o.gr_id 
            where o.pid>0";//o.pid=0为充值
        $sql_total = "select count(*) as count from bo_shop_order where pid>0";//数量
        if (!empty($param['uid'])) {
            $sql .= ' and o.uid ='.$param['uid'];
            $sql_total .= ' and uid ='.$param['uid'];
        }
        //数量
        $count = $this->query($sql_total, false);
        $data['count'] = $count[0]['count'];
        $sql .= " order by o.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $data['list'] = $this->query($sql, false);
        return $data;
    }
    /**
     * 中奖纪录
     *
     * @author liuwei 
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function winlist($param = array())
    {
        $data = array();
        $sql = "select u.nickname,u.gold_balance,o.*,
                    (SELECT (case when SUM(number) is NUll then 0 else ABS(SUM(number)) end) 
                        FROM bo_shop_order WHERE o.uid = uid and o.id = pid) as number,
                    (SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end) 
                        FROM bo_shop_order WHERE o.uid = uid and o.id = pid) as total,
                    sgr.gold_price 
                    from bo_shop_period p 
                    join bo_user u on p.uid = u.id 
                    LEFT JOIN bo_shop_order o on p.id = o.pid 
                    LEFT JOIN bo_shop_gold_record sgr ON sgr.id=o.gr_id 
                    where 1=1 and p.state=2";//数据
        $sql_total = "select count(*) as count from bo_shop_period where 1=1 and state = 2";//数量
        if (!empty($param['uid'])) {
            $sql .= ' and p.uid ='.$param['uid'];
            $sql_total .= ' and uid ='.$param['uid'];
        }
        //数量
        $count = $this->query($sql_total, false);
        $data['count'] = $count[0]['count'];
        $sql .= " group by p.id order by p.id  desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $data['list'] = $this->query($sql, false); 
        return $data;
    }
    /**
     * 提现详情
     *
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function cashlist($param = array())
    {
        $data = array();
        $sql = "select c.*,u.nickname,u.gold_balance from bo_user_cash c join bo_user u on c.uid = u.id where 1=1";//数据
        $sql_total = "select count(*) as count from bo_user_cash where 1=1";//数量
        if (!empty($param['uid'])) {
            $sql .= ' and c.uid ='.$param['uid'];
            $sql_total .= ' and uid ='.$param['uid'];
        }
        //数量
        $count = $this->query($sql_total, false);
        $data['count'] = $count[0]['count'];
        $sql .= " order by c.id desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $data['list'] = $this->query($sql, false);
        return $data;
    }

}