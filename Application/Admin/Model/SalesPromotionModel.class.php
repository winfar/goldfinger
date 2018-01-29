<?php
/**
 * 活动管理
 * 
 * @author liuwei
 * @time   2016-10-20 
 */
namespace Admin\Model;

use Think\Model;

class SalesPromotionModel extends Model
{
    /**
     * 添加and修改方法
     * 
     * @param  [array] $params 修改或者添加的值
     * @author liuwei  
     * @return [int] 200成功 101不成功
     */
    public function update($params)
    {
    	$code = 101;
    	$remark_result = $this->remark_list($params['type'],$params['remark']);
    	if ($remark_result['code']==1) {
            $data = array();
            $data['name'] = trim($params['name']);
            $data['begin_time'] = strtotime($params['begin_time']);
            $data['end_time'] = strtotime($params['end_time']);
            $data['type'] = intval($params['type']);
            $data['range'] = $data['type']==3 ? '' :intval($params['range']);
            //满减的时候红包不记录
            if ($data['type']!=1) {
                $data['red_ids'] = empty($params['red']) ? '' : implode(',',$params['red']);
            } else {
                $data['red_ids'] = "";
            }
            //注册的时候红包不记录 活动范围为不限制的时候不记录
            if ($data['type']!=3 and $data['range']!=0) {
                $data['range_ids'] = trim($params['range_ids']);
            } else {
                $data['range_ids'] = "";
            }
            $data['remark'] = $remark_result['list'];
	        if (empty($params['id'])) {//添加活动
	        	$data['create_time'] = time();
	        	$result = $this->add($data);
	        	if (!empty($result)) {
	        		$code = 200;
	        	}
	        } else {
                $result = $this->where('id='.$params['id'])->save($data);
                if (isset($result)) {
                    $code = 200;
                }
	        }
    	}
    	return $code;
    }

    /**
     * 将amount_limit/amount/gold/point变成json
     * 
     * @param  [int] $type   类型1满减2满赠3注册
     * @param  [array] $params amount_limit/amount/gold/point集合
     * @author liuwei
     * @return array
     */
    protected  function remark_list($type, $params)
    {
    	$count = 1;
    	$list = explode(',', $params);
    	$data = array();
    	if ($type==3 and count($list)>1) {
    		$count = 0;
    	}
    	if (!empty($list)) {
    		foreach ($list as $key => $value) {
    			$array = explode('/', $value);
    			if ($type ==1) {
    				if (count($array)==2) {
    					$data[$key]['amount_limit'] = $array[0];
	    				$data[$key]['money'] = $array[1];
	    				$data[$key]['gold'] = '';
	    				$data[$key]['point'] = '';
	    				$count *= 1;
    				} else {
    					$count *= 0;
    				}
    				
    			} elseif ($type ==2) {
    				if (count($array)==3) {
	    				$data[$key]['amount_limit'] = $array[0];
	    				$data[$key]['money'] = '';
	    				$data[$key]['gold'] = $array[1];
	    				$data[$key]['point'] = $array[2];
    					$count *= 1;
    				} else {
    					$count *= 0;
    				}
    			} else {
    				if (count($array)==2) {
	    				$data[$key]['amount_limit'] = '';
	    				$data[$key]['money'] = '';
	    				$data[$key]['gold'] = $array[0];
	    				$data[$key]['point'] = $array[1];
    					$count *= 1;
    				} else {
    					$count *= 0;
    				}
    			}

    		}
    	}
    	$return_data = array();
    	$return_data['code'] = $count;
    	$return_data['list'] = empty($data) ? '' : json_encode($data);
    	return $return_data;
    }

    /**
     * 活动详情
     * 
     * @param  [int] $id 活动id
     * @param  [string] $field 字段
     * @author liuwei
     * @return array
     */
    public function getInfo($id, $field="*")
    {
        $item = $this->where('id='.$id)->field($field)->find();
        if (!empty($item)) {
            $item['range_array'] = explode(',', $item['red_ids']);
        }
        if (!empty($item['remark'])) {
            $remark = json_decode($item['remark'], true);
            if (!empty($remark)) {
                $data = array();
                foreach ($remark as $key => $value) {
                    $array = array_filter($value);
                    $data[] = implode('/', $array);

                }
                $item['remark_content'] = implode(',', $data);
            }
        }
        return $item;
    }
}