<?php
namespace Admin\Model;
use Think\Model;
 
class RankModel extends Model {
	/**
	 * 详情
	 *
	 * @author liuwei
	 * @param  [type]  $id    id
	 * @param  boolean $field 查询字段
	 * @return array
	 */
	public function getInfo($id, $field = true){
        $map = array();
        if(is_numeric($id)){
            $map['id'] = $id;
        }
        $info=$this->field($field)->where($map)->find();
        return $info;
    }

	/**
	 * 新增或者修改
	 *
	 * @author liuwei
	 * @return [type] [description]
	 */
	public function update(){
        $data = $this->create($_POST);
        if(empty($data['uid'])){
        	$this->error = '请填写用户id！';
            return false;
        }
        if(empty($data['username'])){
        	$this->error = '请填写用户名称！';
            return false;
        }
        if(empty($data['draw_diamond'])){
        	$this->error = '请填写抽奖话费'.C("WEB_CURRENCY").'数量！';
            return false;
        }
        if(empty($data['draw_number'])){
        	$this->error = '抽奖参与期数！';
            return false;
        }
        if(empty($data['win_number'])){
        	$this->error = '请填写中奖次数！';
            return false;
        }
        if(empty($data['full_draw'])){
        	$this->error = '请填写全价兑换话费'.C("WEB_CURRENCY").'次数！';
            return false;
        }
        if(empty($data['full_number'])){
        	$this->error = '请填写全价兑换次数！';
            return false;
        }
        $head_img_url = "/Picture/default/";
        if(empty($data['id'])){
        	//查看uid是否重复
        	$count = $this->where("uid=".$data['uid'])->count();
        	if ($count==0) {
                $data['headimgurl'] = $head_img_url.rand(1,300).'.jpg';
        		$data['create_time'] = time();
        		$result = $this->add($data);
	            if(!$result){
	                $this->error = '新增渠道出错！';
	                return false;
	            } else {
	            	return true;
	            }
        	} else {
        		$this->error = '该用户id排行已添加';
	            return false;
	        }
            
        } else {
        	//查看uid是否重复
        	$count = $this->where("uid=".$data['uid']." and id !=".$data['id'])->count();
        	if ($count==0) {
                $item = $this->where("uid=".$data['uid']." and id =".$data['id'])->getField('headimgurl');
                if (empty($item)) {
                    $data['headimgurl'] = $head_img_url.rand(1,300).'.jpg';
                }
            	$result = $this->save($data);
	            if(false === $result){ 
	                $this->error = '更新排行出错！';
	                return false;
	            }else{
	                return true;
	            }
            } else {
        		$this->error = '该用户id排行已添加';
	            return false;
	        }
        }
        return $data;
    }

    /**
     * 删除
     *
     * @author liuwei
     * @return [type] [description]
     */
    public function del()
    {
    	$data = $_GET;
    	if (empty($data['id'])) {
			$this->error = '请选着要删除的数据!';
	        return false;
    	} else {
    		$result = $this->delete($data['id']);
    		if(false === $result){ 
                $this->error = '删除排行出错！';
                return false;
            }else{
                return true;
            }
    	}
    }
}