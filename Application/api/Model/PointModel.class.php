<?php
namespace Common\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;

class PointModel extends Model
{
    public function addPoint($points = array())
    {
        $tokenid = $points['tokenid'];
        $userInfo = isLogin($tokenid);
        if ( !$userInfo ) {
            returnJson('', 100, '请登录！');
        }
        $data['point'] = $points['point'];
        $data['user_id'] = $userInfo['uid'];
        $data['create_time'] = NOW_TIME;
        $data['type_id'] = $points['type_id'];
        //$data['remark'] =  $points['remark'];
        $data['remark'] = get_pointtype($points['type_id']);

        $user = M('User')->where("id=" . $userInfo['uid'])->select();

        $currentPoint = $user[0]['total_point'];
        $total_point = $currentPoint + $points['point'];

        if ( $points['point'] < 0 && $currentPoint < abs($points['point']) ) {
            returnJson('', 1, '您的积分不够！');
        }

        
        // if($points['gold']>0){
        //     //$tradetype= M('trade_type')->where("code=1014")->find();
        //     //签到，金币，积分
        //     //$uid,$typeid,$gold,$remarkArr,$pid=0
        //     // $goldsuid=$userInfo['uid'];
        //     // $goldstypeid=$tradetype['id'];
        //     // $goldsgold=$points['gold'];
        //     // $goldspid=0;
        //     $rs2 = D('GoldRecord')->sign($userInfo['uid'],$points['gold'],$points['point']);
        // }

        $model = new Model();
        $model->startTrans();
        $rs = M('User')->where('id=' . $userInfo['uid'])->save(array('total_point' => $total_point));
        $rs1 = M('point_record')->add($data);

        if ( count($rs) && count($rs1) ) {
            $model->commit();
            returnJson('', 200, 'success');
        } else {
            $model->rollback();
            returnJson('', 1, '处理密码错误！');
        }
    }

    /*
     * point：积分
     * user_id：用户id
     * type_id：积分类别
     * remark：备注
     * */
    public function addPoint_error($points = array())
    {
        $tokenid = $points['tokenid'];
        $userInfo = isLogin($tokenid);
        if ( !$userInfo ) {
            returnJson('', 100, '请登录！');
        }

        $day_start = strtotime(date('Y-m-d')); //strtotime("-".$x." day",time());//当前时间减去一天;   strtotime(date('Y-m-d', strtotime('-' . $x . ' day')));
        $currentDays1 = M('GoldRecord')->where("typeid=15 and uid=" . $userInfo['uid'] . " and create_time>='" . $day_start . "'")->count('id');
        //    $day_start = strtotime(date('Y-m-d')); //strtotime("-".$x." day",time());//当前时间减去一天;   strtotime(date('Y-m-d', strtotime('-' . $x . ' day')));
        $currentDays2 = M('point_record')->where("type_id='102' and user_id=" . $userInfo['uid'] . " and create_time>='" . $day_start . "'")->count('id');
        if ($currentDays1>0|| $currentDays2>0 ) {
            returnJson('', 2, '您今天已经签到过！');
        }


        $signInfo = M('sign_basepoint')->order('id asc')->select();

        if ( $signInfo ) {
            $countDay = count($signInfo);
            $tagDay = 1;
            for ( $x = 1; $x <= $countDay; $x++ ) {
                $day_start = strtotime(date('Y-m-d', strtotime('-' . $x . ' day'))); //strtotime("-".$x." day",time());//当前时间减去一天;   strtotime(date('Y-m-d', strtotime('-' . $x . ' day')));
                $currentDay = M('point_record')->where("type_id='102' and user_id=" . $userInfo['uid'] . " and create_time>='" . $day_start . "'")->count('id');

                if ( $x == 1 && $currentDay == 0 ) {
                    $tagDay = 1;
                    break;
                } else if ( $currentDay < $x ) {
                    $tagDay = $currentDay + 1;
                    break;
                } else if ( $currentDay >= $countDay ) {
                    $tagDay = 1;
                    break;
                }

                $tagDay = $currentDay + 1;
            }

            $dayIndex=$tagDay- 1;
            $sign_basepoint = M('sign_basepoint')->order('id')->limit($dayIndex,1)->select()[0];

            $data['point'] = $sign_basepoint['point'];
            $data['user_id'] = $userInfo['uid'];
            $data['create_time'] = NOW_TIME;
            $data['type_id'] = 102;
            //$data['remark'] =  $points['remark'];
            $data['remark'] = get_pointtype(102);

            $user = M('User')->where("id=" . $userInfo['uid'])->select();

            $currentPoint = $user[0]['total_point'];
            $total_point = $currentPoint + $sign_basepoint['point'];

//            if ( $sign_basepoint['point'] < 0 && $currentPoint < abs($sign_basepoint['point']) ) {
//                returnJson('', 1, '您的积分不够！');
//            }

            if ( $sign_basepoint['gold'] > 0 ) {
              
                //$tradetype= M('trade_type')->where("code=1014")->find();
                //签到，金币，积分
                //$uid,$typeid,$gold,$remarkArr,$pid=0
                // $goldsuid=$userInfo['uid'];
                // $goldstypeid=$tradetype['id'];
                // $goldsgold=$points['gold'];
                // $goldspid=0;
                $rs2 = D('GoldRecord')->sign($userInfo['uid'], $sign_basepoint['gold'], $sign_basepoint['point']);
            }

            if( $sign_basepoint['point']>0){
                $rs = M('User')->where('id=' . $userInfo['uid'])->save(array('total_point' => $total_point));
                $rs2 = M('point_record')->add($data);
            }
            if($rs2){
                returnJson('', 200, 'success');
            }else{
                returnJson('', 1, '处理错误！');
            }
        }

        returnJson('', 1, '签到失败');
    }

    public function addPointByUid($points = 0, $typeid, $uid)
    {
        if ( $points <= 0 ) {
            return false;
        }

        $data['point'] = $points;
        $data['user_id'] = $uid;
        $data['create_time'] = NOW_TIME;
        $data['type_id'] = $typeid;
        //$data['remark'] = $remark;
        $data['remark'] = get_pointtype($typeid);


        $model = M('point_record');
        $model->startTrans();

        $rs_user = M('User')->where('id=' . $uid)->setInc("total_point", $points);
        $rs_point = $model->add($data);

        if ( count($rs_user) && count($rs_point) ) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }



    //制定用户增加金币
    public function addGoldByUid($gold = 0,$typeid,$uid){
        if($gold < 0){
            return false;
        }

        $data['uid'] = $uid;
        $data['gold'] = $gold;
        $data['create_time'] = time();
        $data['remark'] = '{"活动名称":"注册","金币":"'.$gold.'","用户id":"'.$uid.'"}';
        $data['pid'] = 0;

        $model = M('gold_record');
        $model->startTrans();
        $rs_user = M('User')->where('id=' . $uid)->setInc("black", $gold);
        $rs_gold = $model->add($data);
        if ( count($rs_user) && count($rs_gold) ) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }



    /**
     * @deprecated 用户积分详情API
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function getPoints($tokenid)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $points = M('point_record')->where("user_id=" . $user['uid'])->order('create_time desc')->select();
        $userpoint = M('User')->where('id=' . $user['uid'])->field('total_point')->find();

        $data = array('totalPoint' => $userpoint['total_point'], 'points' => $points);
        returnJson($data, 200, 'success');
    }

    /**
     * @deprecated 获取当天签到信息
     * @author zhangran
     * @date 2016-07-12
     **/
    public function getPointInfo($tokenid)
    {
        $day_start = strtotime(date("Y-m-d"));
        $day_end = strtotime(date('Y-m-d', strtotime('+1 day')));
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $points = M('point_record')->where("type_id='102' and user_id=" . $user['uid'] . " and create_time>='" . $day_start . "' and create_time<='" . $day_end . "'")->select();
        if ( $points ) { //已签到
            return true;
        } else {
            return false;
        }
    }

    public function getPointsByUid($tokenid)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $signconfig = M('sign_config')->where("name='hx_sign_basepoint'")->find();
        if ( $signconfig['status'] != 1 ) {
            returnJson('', 1, '签到功能已经禁用！');
        }

        $signInfo = M('sign_basepoint')->order('id asc')->select();
        $status = 1;
        if ( $signInfo ) {
            $countDay = count($signInfo);
            $tagDay = 1;
            for ( $x = 1; $x <= $countDay; $x++ ) {
                $day_start = strtotime(date('Y-m-d', strtotime('-' . $x . ' day'))); //strtotime("-".$x." day",time());//当前时间减去一天;   strtotime(date('Y-m-d', strtotime('-' . $x . ' day')));
                $currentDay = M('point_record')->where("type_id='102' and user_id=" . $user['uid'] . " and create_time>='" . $day_start . "'")->count('id');

                if ( $x == 1 && $currentDay == 0 ) {
                    $tagDay = 1;
                    break;
                } else if ( $currentDay < $x ) {
                    $tagDay = $currentDay + 1;
                    break;
                } else if ( $currentDay >= $countDay ) {
                    $tagDay = 1;
                    break;
                }

                $tagDay = $currentDay + 1;
            }

            $itempoints = array();
            foreach ( $signInfo as $key => $item ) {
                $itempoints[$key]['id'] = $item['id'];

                if($item['point']>$item['gold']){
                    $itempoints[$key]['type'] = '1';
                    $itempoints[$key]['count'] = $item['point'];
                }else{
                    $itempoints[$key]['count'] = $item['gold'];
                    $itempoints[$key]['type'] = '2';
                }
            }

            $datas['items'] = $itempoints;
            $datas['currentDay'] = $tagDay;

            returnJson($datas, 200, 'success');
        } else {
            returnJson('', 200, 'success');
        }
    }

    
}
