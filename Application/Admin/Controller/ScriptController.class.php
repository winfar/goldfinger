<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 用户首页控制器
 */
class ScriptController extends Controller {
    public function usergold()
    {
        $start_time = strtotime(date("Y-m-d",strtotime("-1 day")));//昨天凌晨时间戳
        $end_time = strtotime(date("Y-m-d"));//今天凌晨时间戳
        //列表
        $sql = "select u.id,u.black,r.id gold_id,r.gold FROM bo_user u left JOIN bo_gold_record r ON u.id=r.uid and r.create_time>=".$start_time." and r.create_time<".$end_time." order by u.id desc";
        $list = M('user')->query($sql, false);
        //区分收入和支出
        $type_list = array();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $type_list[] = $value;
                if ($value['gold']>0) {
                    $type_list[$key]['add_gold'] = $value['gold']; 
                    $type_list[$key]['cost_gold'] = 0; 
                } else {
                    $type_list[$key]['cost_gold'] = $value['gold'];
                    $type_list[$key]['add_gold'] = 0; 
                }
            }
        }

        //按照用户进行分组
        $data = array();
        if (!empty($list)) {
            foreach ($type_list as $k => $v) {
                $data[$v['id']][] = $v;
            }
        }
        //把各个用户的支出 收入 总金币汇总
        if (!empty($data)) {
            
            foreach ($data as $k => $v) {
                $add_gold = array_column($v, 'add_gold');
                $gold_total = 0;
                foreach ($add_gold as $key => $value) {
                    $gold_total = bcadd($gold_total,$value);
                }
                $cost_gold = array_column($v, 'cost_gold');
                $cost_total = 0;
                foreach ($cost_gold as $key => $value) {
                    $cost_total = bcadd($cost_total,$value);
                }
                $black = $v[0]['black'];//用户支出
                $data_list[$k]['add_gold'] = $gold_total;
                $data_list[$k]['cost_gold'] = $cost_total;
                $data_list[$k]['black'] = $black;
            }
        }
        //收入总额
        $add_gold_total = array_column($data_list, 'add_gold');
        $total_gold = 0;
        foreach ($add_gold_total as $key => $value) {
            $total_gold = bcadd($total_gold,$value);
        }
        //支出总额
        $cost_gold_total = array_column($data_list, 'cost_gold');
        $total_cost= 0;
        foreach ($cost_gold_total as $key => $value) {
            $total_cost = bcadd($total_cost,$value);
        }
        //支出总额
        $total_list = array_column($data_list, 'black');
        $total= 0;
        foreach ($total_list as $key => $value) {
            $total = bcadd($total,$value);
        }
        $form['income'] = $total_gold;//用户金币总收入
        $form['cost'] = $total_cost;//用户金币总支出
        $form['total'] = $total;//用户金币总数
        $form['date'] = $start_time;
        $form['create_time'] = time();
        $model = D('gold_log');
        $where  = array();
        $where['date'] = $form['date'];
        $item = $model->where($where)->field('id')->select();
        if (empty($item)) {
            $result = $model->add($form);
        } else {
            $result = $model->where($where)->save($form);
        }
        if ($result!=false) {
            $info = $form['date'].'添加成功';
        } else {
            $info = $form['date'].'添加失败';
        }
        \Think\Log::write($info);
        \Think\Log::save();
    }
}
