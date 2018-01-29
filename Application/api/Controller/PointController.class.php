<?php
/**
 * Created by PhpStorm.
 * User: ppa
 * Date: 2016/6/30
 * Time: 15:12
 */

namespace api\Controller;

use Think\Controller;


class PointController extends BaseController
{
    /**
     * @deprecated 添加积分
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function addPoint()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'addPoint');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            return returnJson('', 1, '您还未登录！');
        }
//        if ( isEmpty($json['point']) ) {
//            return returnJson('', 1, '积分不能为空！');
//        }

        $points = array();
//        $points['point'] = $json['point'];
        $points['tokenid'] = $json['tokenid'];
//        $points['type_id'] = $json['type_id'];
//        $points['remark'] = $json['remark'];
        $rs = D('Point')->addPoint($points);

        return $rs;
    }

    /**
     * @deprecated 获取积分
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function getPoints($tokenid)
    {
        if ( isEmpty($tokenid) ) {
            return returnJson('', 1, '您还未登录！');
        }
        $rs = D('Point')->getPoints($tokenid);

        return $rs;
    }

    public function getPointList($tokenid)
    {
        if ( isEmpty($tokenid) ) {
            return returnJson('', 1, '您还未登录！');
        }
        D('Point')->getPointsByUid($tokenid);
    }
}