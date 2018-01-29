<?php
/**
 * 积分记录
 * @author liuwei
 * @date   2016-11-28
 */
namespace Admin\Model;
use Think\Model;

class PkModel extends Model {
	/**
	 * pk商品总条数
	 * 
	 * @param  array $param 条件
	 * @return 条数
	 */
	public function total($param)
	{
		$sql = "SELECT count(*) AS count FROM bo_house_manage h LEFT JOIN bo_pkconfig p ON h.pksetid = p.id LEFT JOIN bo_user u ON h.uid = u.id LEFT JOIN bo_shop shop ON h.shopid=shop.id  where 1 = 1";
		if ( $param['no'] ) {//房间号
            $sql .= " and h.no like  '%" . $param['no'] . "%' ";
        }
        if ( $param['invitation_code'] ) {//邀请码
            $sql .= " and h.invitecode like  '%" . $param['invitation_code'] . "%' ";
        }
        if ( isset($param['ispublic']) ) {//是否公开
            $sql .= " and h.ispublic = " . $param['ispublic'];
        }
        if ( isset($param['ispublic']) ) {//是否公开
            $sql .= " and h.ispublic = " . $param['ispublic'];
        }
        if ( isset($param['peoplenum']) ) {//场次
            $sql .= " and p.peoplenum = " . $param['peoplenum'];
        }
		if ( $param['create_time'] ) {//开始时间
            $startTime = strtotime($param['create_time']);
            $sql .= " and h.create_time >= " . $startTime;
        }
        if ( $param['end_time'] ) {//结束时间
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and  h.create_time <= " . $endTime;
        }
		$list = $this->query($sql, false);
        return $list[0]['count'];
	}

	/**
	 * pk商品列表
	 * 
	 * @param  array $param 条件
	 * @return 列表
	 */

	public function lists($param)
	{
		$sql = "SELECT h.*,p.peoplenum,p.amount,p.inventory,u.nickname,shop.name as shop_name,CEIL(p.amount/p.peoplenum) AS capita FROM bo_house_manage h LEFT JOIN bo_pkconfig p ON h.pksetid = p.id LEFT JOIN bo_user u ON h.uid = u.id LEFT JOIN bo_shop shop ON h.shopid=shop.id where 1 = 1 ";
		if ( $param['no'] ) {//房间号
            $sql .= " and h.no like  '%" . $param['no'] . "%' ";
        }
        if ( $param['invitation_code'] ) {//邀请码
            $sql .= " and h.invitecode like  '%" . $param['invitation_code'] . "%' ";
        }
        if ( isset($param['ispublic']) ) {//是否公开
            $sql .= " and h.ispublic = " . $param['ispublic'];
        }
        if ( isset($param['isresolving']) ) {//是否解散
            $sql .= " and h.isresolving = " . $param['isresolving'];
        }
        if ( isset($param['peoplenum']) ) {//场次
            $sql .= " and p.peoplenum = " . $param['peoplenum'];
        }
		if ( $param['create_time'] ) {//开始时间
            $startTime = strtotime($param['create_time']);
            $sql .= " and h.create_time >= " . $startTime;
        }
        if ( $param['end_time'] ) {//结束时间
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and  h.create_time <= " . $endTime;
        }
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
        	$sql .= " ORDER BY h.create_time DESC  limit " . $param['pageindex'] . "," . $param['pagesize'];
        } else {
        	$sql .= " ORDER BY h.create_time DESC";
        }
		$list = $this->query($sql, false);
		return $list;		
	}

	/**
	 * 房间内的用户列表
	 * 
	 * @param  string $no 房间号
	 * @return 列表
	 */
	public function users($no='')
	{
		$sql = "select h.houseno,h.create_time,h.userid,u.username,u.nickname from bo_house_user h left join bo_user u on h.userid = u.id where h.houseno = ".$no.' order by h.create_time desc';
		$list = $this->query($sql, false);
		return $list;
	}

    /**
     * 获取所有场次
     * @return [type] [description]
     */
    public function peoplenum()
    {
        $list = array();
        $peoplenum_list = M('pkconfig')->where('1=1')->field('peoplenum')->order('peoplenum asc')->select();
        if ($peoplenum_list) {
            $num_list = array_column($peoplenum_list , 'peoplenum');
            $list = array_merge(array_unique($num_list));
        }
        return $list;
    }

	public function getPkconfiginfo($shopid)
    {
        $pkconfig = D('pkconfig')->where(array('shopid' => $shopid))->select();
        return $pkconfig;
    }

    //解散当前商品的所有公共房间
    public function dissolutionHouse($shopId){

        $map['shopid']=$shopId;
        $commonHouses = M('house_manage')->where($map)->select();

        foreach ($commonHouses as $key => $value) {
            //退还金币，退还库存，并解散房间，下架周期
            D('shop')->takeDownBackGold($value['periodid']);
        }
    }

	/**
     * 创建新的pk公共房间
     * @param intger $shop 商品
     */
    public function createPkCommonHouseByShop($shop){

		if(!$shop){
			return false;
		}

        //是否已有未解散的公共房间，如果有，需解散房间，退还金币后再新增房间
        $this->dissolutionHouse($shop['id']);

        $pkconfiginfo = $this->getPkconfiginfo($shop['id']);

        foreach ( $pkconfiginfo as $key => $item ) {
            //新增pk房间
            $pkhouse['create_time'] = time();
            $pkhouse['shopid'] = $shop['id'];
            $pkhouse['pksetid'] = $item['id'];

            $houseno = M('house_manage')->field('max(no) maxno')->find();
            if ( $houseno['maxno'] ) {
                $pkhouse['no'] = $houseno['maxno'] + 1;
            } else {
                $pkhouse['no'] = 100001;
            }

            //M('pkconfig')->where('id='. $item['id'])->setDec('inventory',1);

            $houseid = M('house_manage')->add($pkhouse);

            if($houseid){
            
				$periodid = D('Period')->createPeriod($shop['id'],$item['amount'],$shop['ten'],$houseid);

                if($periodid){
                    //更新房间最新一期pid
                    $map_house['id'] = $houseid;
                    M('house_manage')->where($map_house)->setField('periodid',$periodid);

                    //减库存
                    $map_shop['id']=$shop['id'];
                    M('shop')->where($map_shop)->setDec('shopstock',1);//库存减1
                }
            }
        }
    }
}