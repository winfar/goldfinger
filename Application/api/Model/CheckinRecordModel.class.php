<?php
namespace Common\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;

class CheckinRecordModel extends Model
{
    /**
     * 获取连续签到次数
     * @param $uid
     * @return int 连续签到次数
     */
    public function getCheckinCount($uid){
        //查询用户的连续签到次数
        $map['uid'] = $uid;
        $rs =  $this->where($map)->order('check_time desc')->getField('check_time',365); //获取前365条签到记录
        $checkflag = 0;
        $valid_flag = false;
        foreach ($rs as $k) {
            if($k >= (date("Ymd",strtotime("-1 day")))){
                $valid_flag = true;
            }

            if($checkflag == 0){
                $checkflag = $k ;
                $arr_checkin[] = $k;
                continue;
            }elseif($checkflag - $k == 1){
                $checkflag = $k;
                $arr_checkin[] = $k;
            }


        }
        //计算签到周期连续天数  7天为一周期  连续总数%7
        $count = count($arr_checkin) % 7 ;
        //取最近周期的签到数据
        $arr_checkin = array_slice($arr_checkin,0,$count);
        return ( $valid_flag && $count ) ?  $arr_checkin : null;
    }

    /**
     * 获取详细签到奖励信息
     * @param $uid
     * @param $check_times  如：20161006,20161005,20161004
     * @return string
     */
    public function getCheckinfo($uid,$check_times){
        //查询用户的连续签到次数
        $map['uid'] = $uid;
        if(is_null($check_times)){
            $check_times = '';
        }

        $map['check_time'] = array('in',$check_times);

//        $rs =  $this->alias('c')
//            ->join('LEFT JOIN __GOLD_RECORD__ g ON c.gold_record = g.id ')
//            ->join(' LEFT JOIN __POINT_RECORD__ p ON c.point_record = p.id ' )
//            ->where($map)->order('check_time')
//            ->field('c.id,c.uid,c.check_time,g.gold,p.point')->select();

        $rs =  $this
            ->where($map)->order('check_time')
            ->field('id,uid,check_time,gold_record as gold,point_record as point')->select();

        $rs_checkInfo = M('sign_basepoint')->order('id asc')->select();
        $i = 0;

        //获取默认配置的签到奖励规则
        foreach ($rs_checkInfo as $k => $v) {
            $items[$i]['is_checked'] = 0;
            if($v['point'] > 0){
                $items[$i]['type'] = 1;
                $items[$i]['value'] = intval($v['point']);
            }
            if($v['gold'] > 0 ){
                $items[$i]['type'] = 2;
                $items[$i]['value'] = intval($v['gold']) ;
            }
            $i++;
        }

        $checkinfo['curr_check'] = 0 ;
        $checkinfo['uid'] = $uid ;
        $checkinfo['check_count'] = empty($check_times)? 0 : substr_count($check_times,',') + 1;

        $curr_date = date("Ymd"); //当前日期

        //重新构造数据结构
        for ($i = 0; $i< count($rs) ;$i++){
            $items[$i]['date'] = $rs[$i]['check_time'];
            $items[$i]['is_checked'] = 1;
            //判断今天是否签到
            if($curr_date == $rs[$i]['check_time']){
                $checkinfo['curr_check'] = 1 ;
            }

            if($rs[$i]['point'] > 0 ){
                $items[$i]['type'] = 1;
                $items[$i]['value'] = intval($rs[$i]['point']);
            }

            if($rs[$i]['gold'] > 0 ){
                $items[$i]['type'] = 2;
                $items[$i]['value'] = intval($rs[$i]['gold']);
            }
        }
        $checkinfo['items'] = $items;

        return $checkinfo;
    }

    /**
     * 更新数据
     */
    public function update($id,$gold_id=null,$point_id=null){
        $map['id'] = $id ;
        if(!is_null($gold_id)){
            $data['gold_record'] = $gold_id;
        }
        if(!is_null($point_id)){
            $data['point_record'] = $point_id;
        }
        $this->where($map)->save($data);
    }

    /**
     * 用户签到
     * @param null $tokenid
     */
    public function checkin($tokenid = null){
        $userInfo = isLogin($tokenid);
        if ( !$userInfo ) {
            returnJson('', 100, '请登录！');
        }
        $uid =  $userInfo['uid'];
        $data1['check_time'] = date("Ymd");
        //检查是否连续
        $count = M('CheckinRecord')->where('uid = '.$uid.' and check_time = '. $data1['check_time'])->count();
        if($count > 0){
            //TODO 签到记录失败
            returnJson('', 2, '您今天已经签到过！');
        }

        $data1['uid'] = $uid;
        $data1['create_time'] = NOW_TIME;
        $rs = M('CheckinRecord')->add($data1);
        if($rs){
            $r_id = $rs;
        }

        //检查是否连续及连续天数
        $arr_checkin = D('CheckinRecord')->getCheckinCount($uid);

        $sign_basepoint = M('sign_basepoint')->order('id')->limit(count($arr_checkin)-1,1)->select()[0];

        $point = $sign_basepoint['point'];
        $gold = $sign_basepoint['gold'];;
        $data['point'] = $point;
        $data['user_id'] = $userInfo['uid'];
        $data['create_time'] = NOW_TIME;
        $data['type_id'] = 102;
        //$data['remark'] =  $points['remark'];
        $data['remark'] = get_pointtype(102);

        $user = M('User')->where("id=" . $userInfo['uid'])->select();

        $currentPoint = $user[0]['total_point'];
        $total_point = $currentPoint + $point;
        $rs1 = D('CheckinRecord')->update($r_id,$gold > 0 ? $gold : null,$point > 0 ? $point : null);
        if ( $gold > 0 ) {
            $rs2 = D('GoldRecord')->sign($userInfo['uid'], $gold,$point);
        }

        if( $point>0){
            $rs = M('User')->where('id=' . $userInfo['uid'])->save(array('total_point' => $total_point));
            $rs2 = D('point_record')->add($data);
        }

        if($rs2){
            returnJson('', 200, 'success');
        }else{
            returnJson('', 1, '处理错误！');
        }
        returnJson('', 1, '签到失败');
    }

    /**
     * 获取用户签到信息
     * @param null $tokenid
     */
    public function getCheck($tokenid = null){
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $uid  = $user['uid'] ;
        $signconfig = M('sign_config')->where("name='hx_sign_basepoint'")->find();
        if ( $signconfig['status'] != 1 ) {
            returnJson('', 1, '签到功能已经禁用！');
        }
        //获取签到信息 从签到记录表读取数据
        $arr_checkin = D('CheckinRecord')->getCheckinCount($uid);

        if ( count($arr_checkin) >= 0 ) {
            $data =  D('CheckinRecord')->getCheckinfo($uid, implode(",", $arr_checkin));
            returnJson($data, 200, 'success');
        }else {
            returnJson('', 200, 'success');
        }
    }
}
