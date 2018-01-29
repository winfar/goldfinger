<?php
/**
 * 积分管理
 * @author liuwei
 * @date    2016-10-17 15:46:56
 */
namespace Admin\Controller;

use Think\Controller;

class PointController extends WebController
{
    /**
     * 积分明细列表
     * @author liuwei
     */
    public function index(){
        $this->meta_title = '积分明细';
        $conditionarr = array();//搜索的值
        $map = array();
        //用户名搜索
        if ( isset($_GET['keyword']) ) {
            $keyword = trim($_GET['keyword']);//把搜索的空格去掉
            $where['u.username'] = array('like', '%' . $keyword . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $conditionarr['keyword'] = $keyword;
        }
        //开始结束时间搜索
        if ( !empty($_GET['starttime']) || !empty($_GET['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['p.create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
            $conditionarr['starttime'] = I('starttime');
            $conditionarr['endtime'] = I('endtime');
        }
        //积分来源搜索
        if ( isset($_GET['pointtype']) ) {
            $map['p.type_id'] = I('pointtype');
            $conditionarr['pointtype'] = I('pointtype');
        }
        //积分列表
        $Model = D('PointRecord');
        $Model->alias('p')->join('LEFT JOIN __USER__ u ON p.user_id = u.id ');
        $list   =   $this->lists($Model, $map ,$order='p.create_time desc',0,$base = array(),'p.id,p.point,p.type_id,p.create_time,u.username,u.nickname');
        $this->assign('point_list', $point_list);
        $this->assign('list', $list);
        $this->assign('conditionarr', json_encode($conditionarr));
        $this->display();
    }
    /**
     * 积分明细详情
     * @author liuwei
     */
    public function detail()
    {
        $item = array();
        if (isset($_GET['id'])) {
            $id = I('id', '', 'int');//积分明细id
            $item = D('PointRecord')->type_info($id);
        }
        $this->assign('item', $item);
        $this->display();
    }
    /**
     * 积分设置
     * @author liuwei
     */
    public function edit()
    {
        $id     =   I('get.id',8);
        $type   =   C('CONFIG_GROUP_LIST');
        $list   =   M("Config")->where(array('group'=>$id,'display'=>array('gt',0)))->field('id,name,title,extra,value,remark,type')->order('sort')->select();
        $this->assign('list',$list);
        $this->meta_title = '积分设置';
        $this->display();
    }
    /**
     * 批量保存配置
     * @author liuwei
     */
    public function save($config){
        if($config && is_array($config)){
            $Config = M('Config');
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                $Config->where($map)->setField('value', $value);
            }
        }
        S('DB_CONFIG_DATA',null);
        $this->success('保存成功！');
    }
}