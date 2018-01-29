<?php
namespace api\Model;
use Think\Model;

/**
 * 达人榜
 */
class TopModel extends Model{

	/**
	 * 用户排行
	 * 
	 * @param  integer $uid  [description]
	 * @param  integer $type [description]
	 * @return [type]        [description]
	 */
	public function user_list($uid = 0, $type = 1)
	{
		if ($type==1) {
			$sql = "SELECT u.id,u.username,u.nickname,u.passport_uid,SUM(o.cash) cash,SUM(o.gold) gold,(SELECT COUNT(DISTINCT pid) FROM hx_shop_record WHERE uid = u.id) AS record  FROM hx_user u LEFT JOIN hx_shop_order o ON u.id=o.uid GROUP BY u.id ORDER BY cash DESC,record DESC,gold DESC,id DESC";
		} else {
			$sql = "SELECT u.id,u.username,u.nickname,u.passport_uid,(SELECT COUNT(DISTINCT pid) FROM hx_shop_record r left join hx_shop_period as p on r.pid = p.id where p.state=2 and r.uid = u.id ) AS record,(SELECT COUNT(*) FROM hx_shop_period WHERE uid = u.id) AS period FROM hx_user u GROUP BY u.id ORDER BY period DESC,record asc,id DESC";
		}
		$total_list = $this->query($sql, false);
		$info = NULL;
		if (!empty($total_list)) {
			foreach ($total_list as $key => $value) {
				if ($value['id'] == $uid) {
					$info = $value;
					//$info['username'] = !isMobile($value["username"]) ? $value["username"] : substr_replace($value["username"], '****', 3, 4) ;
					$info['nickname'] = !isMobile($value["nickname"]) ? $value["nickname"] : substr_replace($value["nickname"], '****', 3, 4) ;
					$info['username'] = $info['nickname'];
					$info['cash'] = empty($value['cash']) ? 0.00 : $value['cash'];
					$info['gold'] = empty($value['gold']) ? 0 : $value['gold']; 
					$info['headimgurl'] = ""; 
					$users = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where(['uid'=>$value['passport_uid']])->find();
			        if ( $users ) {
			            $avatar = $users['avatar'];
			            if ( strpos($avatar, 'http') === false ) {
		                    $info['headimgurl'] = completion_pic_passport($avatar);
		                } else {
		                	$info['headimgurl'] = $avatar;
		                }
			        }
	                $info['ranking'] = $key+1;
	                if ($type==2) {
		                if ($value['period']==0) {
							$info['rate'] = '0%';
						} else {
							$info['rate'] = sprintf("%.1f", ($value['period']/$value['record'])*100).'%';
						}
					}
				}
			}
			
		}
		return $info;
	}
	/**
	 * 列表
	 * @param  integer $pageindex [description]
	 * @param  integer $pagesize  [description]
	 * @param  integer $type      [description]
	 * @return [type]             [description]
	 */
	public function top_list($pageindex = 1, $pagesize = 20, $type = 1)
	{
		$offsize = ($pageindex - 1)*$pagesize;
		$data = array();
		if ($offsize<100) {
			if ($type==1) {
				$sql = "SELECT u.id,u.username,u.nickname,u.passport_uid,SUM(o.cash) cash,SUM(o.gold) gold,(SELECT COUNT(DISTINCT pid) FROM hx_shop_record WHERE uid = u.id) AS record  FROM hx_user u LEFT JOIN hx_shop_order o ON u.id=o.uid GROUP BY u.id  HAVING SUM(o.cash) >0  ORDER BY cash DESC,record DESC,gold DESC,id DESC limit ".$offsize.",".$pagesize;
			} else {
				$sql = "SELECT u.id,u.username,u.nickname,u.passport_uid,(SELECT COUNT(DISTINCT pid) FROM hx_shop_record r left join hx_shop_period as p on r.pid = p.id where p.state=2 and r.uid = u.id ) AS record,(SELECT COUNT(*) FROM hx_shop_period WHERE uid = u.id) AS period FROM hx_user u where (SELECT COUNT(*) FROM hx_shop_period WHERE uid = u.id) > 0  GROUP BY u.id  ORDER BY period DESC,record asc,id DESC limit ".$offsize.",".$pagesize;
			}
			$total_list = $this->query($sql, false);
			if ($type==1) {
				if (!empty($total_list)) {
					foreach ($total_list as $key => $value) {
						
						$data[] = $value;
						//$data[$key]['username'] = !isMobile($value["username"]) ? $value["username"] : substr_replace($value["username"], '****', 3, 4) ; 
						$data[$key]['nickname'] = !isMobile($value["nickname"]) ? $value["nickname"] : substr_replace($value["nickname"], '****', 3, 4) ;
						$data[$key]['username'] = $data[$key]['nickname'];
						$data[$key]['headimgurl']  = '';
						$users = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where(['uid'=>$value['passport_uid']])->find();
				        if ( !empty($users) ) {
				            $avatar = $users['avatar'];
				            if ( strpos($avatar, 'http') === false ) {
			                    $data[$key]['headimgurl'] = completion_pic_passport($avatar);
			                } else {
			                	$data[$key]['headimgurl'] = $avatar;
			                }
				        }
			            $data[$key]['ranking'] = $offsize+1+$key;
					}
					
				}
			} else {
				$data = array();
				if (!empty($total_list)) {
					foreach ($total_list as $key => $value) {
						$data[] = $value;
						//$data[$key]['username'] = !isMobile($value["username"]) ? $value["username"] : substr_replace($value["username"], '****', 3, 4) ; 
						$data[$key]['nickname'] = !isMobile($value["nickname"]) ? $value["nickname"] : substr_replace($value["nickname"], '****', 3, 4) ;
						$data[$key]['username'] = $data[$key]['nickname'];
						$data[$key]['headimgurl'] = "";
						$users = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field(true)->where(['uid'=>$value['passport_uid']])->find();
				        if ( $users ) {
				            $avatar = $users['avatar'];
				            if ( strpos($avatar, 'http') === false ) {
			                    $data[$key]['headimgurl'] = completion_pic_passport($avatar);
			                } else {
			                	$data[$key]['headimgurl'] = $avatar;
			                }
				        }
			            $data[$key]['ranking'] = $offsize+1+$key;
			            if ($value['period']==0) {
							$data[$key]['rate'] = '0%';
						} else {
							$data[$key]['rate'] = sprintf("%.1f", ($value['period']/$value['record'])*100).'%';
						}		
					}
					
				}
			}
		}
		return $data;
	}
	/**
	 * [rankList description]
	 * @param  integer $type   [description]
	 * @param  integer $number [description]
	 * @return [type]          [description]
	 */
	public function rankList($uid =0,$type=1,$p=1,$size=20)
	{
		$number=100;
		$offsize = ($p-1)*$size;
		$array = array();
		if ($p*$size<=100) {
			$kai_time = time();//开奖时间
			if ($type==1) {//抽奖榜
				//排名积分=花费钻石*2+抽奖参与期数*5+抽奖中奖次数*100
				$number_one = 2;//花费钻石*2
				$number_two = 5;//抽奖参与期数*2
				$number_three = 100;//抽奖中奖次数*2
				$sql = "SELECT u.id AS uid,u.nickname as username,u.headimgurl,(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) as draw_diamond,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) AS draw_number,(SELECT COUNT(*) FROM bo_shop_period WHERE uid = u.id AND exchange_type=0 and kaijang_time<=$kai_time) AS win_number,((SELECT COUNT(DISTINCT pid)*$number_two FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)+(SELECT COUNT(*)*$number_three FROM bo_shop_period WHERE uid = u.id AND exchange_type=0 and kaijang_time<=$kai_time)+(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end )*$number_one FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)) as integral,(SELECT Max(create_time) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) as create_time FROM bo_user u where u.id=".$uid." limit 1";
				$item= $this->query($sql, false);
				$array['item'] = empty($item) ? array() : $item[0];
				$array['item']['ranking'] = "榜外";
				$array['item']['headimgurl'] = completion_pic_passport($array['item']['headimgurl']);
				// $array['item']['username'] = user_name_change($array['item']['username']);
				
				//机器人列表
				$one_sql = "SELECT uid,username,headimgurl,draw_number,win_number,draw_diamond,(draw_diamond*$number_one+draw_number*$number_two+win_number*$number_three) as integral,create_time FROM bo_rank";
				$one_list = $this->query($one_sql, false);
				$two_sql = "SELECT u.id AS uid,u.nickname as username,u.headimgurl,(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) as draw_diamond,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) AS draw_number,(SELECT COUNT(*) FROM bo_shop_period WHERE uid = u.id AND exchange_type=0 and kaijang_time<=$kai_time) AS win_number,((SELECT COUNT(DISTINCT pid)*$number_two FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)+(SELECT COUNT(*)*$number_three FROM bo_shop_period WHERE uid = u.id AND exchange_type=0 and kaijang_time<=$kai_time)+(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end )*$number_one FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)) as integral,(SELECT Max(create_time) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) as create_time FROM bo_user u";
				//echo $two_sql;
				$two_list = $this->query($two_sql, false);
				$list = array_merge($one_list, $two_list);
				$arr = $this->sortArrByManyField($list,'integral',SORT_DESC,'create_time',SORT_ASC,'uid',SORT_ASC);
				$data = array_slice($arr, $offsize, $size);
				$list = array();
				foreach ($data as $key => $value) {
					$list[] = $value;
					$list[$key]['ranking'] = $key+1;
					$list[$key]['headimgurl'] = completion_pic_passport($value['headimgurl']);
					//$list[$key]['username'] = user_name_change($value['username']);
					$list[$key]['username'] = $value['username'];
					if ($value['uid']==$uid) {
						$array['item']['ranking'] = $offsize+$key+1;
					}
				}

				$array['list'] = $list;
			} elseif ($type==2) {//兑换榜
				$sql = "SELECT u.id AS uid,u.nickname as username,u.headimgurl,(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) AS full_draw,(SELECT COUNT(*) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) AS full_number,(SELECT Max(create_time) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) as create_time FROM bo_user u where u.id=".$uid." limit 1";
				$item= $this->query($sql, false);
				$array['item'] = empty($item) ? array() : $item[0];
				$array['item']['ranking'] = "榜外";
				$array['item']['headimgurl'] = completion_pic_passport($array['item']['headimgurl']);
				//$array['item']['username'] = user_name_change($array['item']['username']);
				
				//机器人列表
				$one_sql = "SELECT uid,username,headimgurl,full_draw,full_number,create_time FROM bo_rank";
				$one_list = $this->query($one_sql, false);
				$two_sql = "SELECT u.id AS uid,u.nickname as username,u.headimgurl,(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) AS full_draw,(SELECT COUNT(*) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) AS full_number,(SELECT Max(create_time) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) as create_time FROM bo_user u";
				$two_list = $this->query($two_sql, false);
				$list = array_merge($one_list, $two_list);
				$arr = $this->sortArrByManyField($list,'full_draw',SORT_DESC,'full_number',SORT_ASC,'create_time',SORT_ASC,'uid',SORT_ASC);
				$data = array_slice($arr, $offsize, $size);
				$list = array();
				foreach ($data as $key => $value) {
					$list[] = $value;
					$list[$key]['ranking'] = $key+1;
					$list[$key]['headimgurl'] = completion_pic_passport($value['headimgurl']);
					//$list[$key]['username'] = user_name_change($value['username']);
					$list[$key]['username'] = $value['username'];
					if ($value['uid']==$uid) {
						$array['item']['ranking'] = $offsize+$key+1;
					}
				}

				$array['list'] = $list;
			} else if ($type==3) {//排名榜
				//排名积分=抽奖参与期数*5+全价兑换次数*200+抽奖花费钻石*2+全价兑换花费钻石*2
				$number_one = 5;//抽奖参与期数*5
				$number_two = 200;//全价兑换次数*200
				$number_three = 2;//抽奖花费钻石*2
				$number_four = 2;//抽奖花费钻石*2			
				$sql = "SELECT u.id AS uid,u.nickname as username,u.headimgurl,(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id) AS diamonds,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) AS draw_number,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) AS full_number,((SELECT COUNT(DISTINCT pid)*$number_one FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)+(SELECT COUNT(DISTINCT pid)*$number_two FROM bo_shop_order WHERE uid = u.id AND exchange_type=1)+(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end )*$number_three FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)+(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end )*$number_four FROM bo_shop_order WHERE uid = u.id AND exchange_type=1)) as integral,(SELECT Max(create_time) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) as create_time FROM bo_user u where u.id=".$uid." limit 1";
				$item= $this->query($sql, false);
				$array['item'] = empty($item) ? array() : $item[0];
				$array['item']['ranking'] = "榜外";
				$array['item']['headimgurl'] = completion_pic_passport($array['item']['headimgurl']);
				//$array['item']['username'] = user_name_change($array['item']['username']);
				
				//机器人列表
				$one_sql = "SELECT uid,username,headimgurl,draw_number,full_number,(draw_diamond+full_draw) as diamonds,(draw_number*$number_one+full_number*$number_two+draw_diamond*$number_three+full_draw*$number_four) as integral,create_time FROM bo_rank";
				$one_list = $this->query($one_sql, false);
				$two_sql = "SELECT u.id AS uid,u.nickname as username,u.headimgurl,(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end ) FROM bo_shop_order WHERE uid = u.id) AS diamonds,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id AND exchange_type=0) AS draw_number,(SELECT COUNT(DISTINCT pid) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) AS full_number,((SELECT COUNT(DISTINCT pid)*$number_one FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)+(SELECT COUNT(DISTINCT pid)*$number_two FROM bo_shop_order WHERE uid = u.id AND exchange_type=1)+(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end )*$number_three FROM bo_shop_order WHERE uid = u.id AND exchange_type=0)+(SELECT (case when SUM(top_diamond+recharge_activity) is NUll then 0 else ABS(SUM(top_diamond+recharge_activity)) end )*$number_four FROM bo_shop_order WHERE uid = u.id AND exchange_type=1)) as integral,(SELECT Max(create_time) FROM bo_shop_order WHERE uid = u.id AND exchange_type=1) as create_time FROM bo_user u";
				$two_list = $this->query($two_sql, false);
				$list = array_merge($one_list, $two_list);
				$arr = $this->sortArrByManyField($list,'integral',SORT_DESC,'create_time',SORT_ASC,'uid',SORT_ASC);
				$data = array_slice($arr, $offsize, $size);
				$list = array();
				foreach ($data as $key => $value) {
					$list[] = $value;
					$list[$key]['ranking'] = $key+1;
					$list[$key]['headimgurl'] = completion_pic_passport($value['headimgurl']);
					//$list[$key]['username'] = user_name_change($value['username']);
					$list[$key]['username'] = $value['username'];
					if ($value['uid']==$uid) {
						$array['item']['ranking'] = $offsize+$key+1;
					}
				}

				$array['list'] = $list;
			}
		}
		return $array;

	}
	function sortArrByManyField(){
        $args = func_get_args();
        if(empty($args)){
            return null;
        }
        $arr = array_shift($args);
        if(!is_array($arr)){
            throw new Exception("第一个参数不为数组");
        }
        foreach($args as $key => $field){
            if(is_string($field)){
                $temp = array();
                foreach($arr as $index=> $val){
                    $temp[$index] = $val[$field];
                }
                $args[$key] = $temp;
            }
        }
        $args[] = &$arr;//引用值
        call_user_func_array('array_multisort',$args);
        return array_pop($args);
    }
}