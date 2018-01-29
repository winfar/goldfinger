<?php
namespace Admin\Model;
use Think\Model;
 
class GoldRecordModel extends Model {
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
    );

    /**
     * 系统人工操作金币记录
     */
    public function addAdminEdit($id,$uid){
        //构建子查询
        $MemberRole = D('MemberRole');
        $subQuery = $MemberRole->alias('mr')->join('LEFT JOIN __ROLE__ r ON mr.roleID = r.id ')->field('mr.userID,r.rolename')->buildSql();

        $Member = D('Member');
        $rs = $Member->alias('m')->join('LEFT JOIN '.$subQuery.' a ON a.userID = m.id') ->field('m.id,m.username,m.fullname,m.phone,a.rolename')->where('m.id='.$uid)->find();


        // 获取当前的用户金币值
        $pre_gold = D('User')->where('id='.$id)->getField('black');
        $gold_change  =I('post.black') - $pre_gold ;

        $data['uid'] = $id;
        $data['typeid'] = '8';
        $data['gold'] = $gold_change;
        $remarkArr=array();
        $remarkArr["后台用户"]=$rs['username'];
        $remarkArr["所属角色"]=$rs['rolename'];
        $remarkArr["姓名"]= $rs['fullname'];
        $remarkArr["电话"]= $rs['phone'];

        $data['remark'] = json_encode($remarkArr,JSON_UNESCAPED_UNICODE);
        $data['pid'] = $uid;
        $this->create($data);
        return $this->add();
    }

    /*
    * 商品下架退还
    */
    public function takeDown($uid,$name,$pid,$no,$cash,$gold,$backgold){
        $typeid = 3;

        $remarkArr=array();
        $remarkArr["商品名称"]=$name;
        $remarkArr["商品期号"]=$no;
        $remarkArr["现金支付"]= $cash;
        $remarkArr["金币支付"]= $gold;
        $remarkArr["购买成功"]= intval($cash+$gold);
        $remarkArr["下架退还"]= $backgold;

        $rs = $this->addGoldRecord($uid,$typeid,$backgold,$remarkArr,$pid);
        return $rs;
    }

    /**
     * 新增金币记录
     */
    protected function addGoldRecord($uid,$typeid,$gold,$remarkArr,$pid=0){

        $data["uid"]=$uid;
        $data["typeid"]=$typeid;
        $data["gold"]=$gold;
        $data["create_time"]=time();
        $data["remark"]=json_encode($remarkArr,JSON_UNESCAPED_UNICODE);;
        $data["pid"]=$pid;

        $model = M('gold_record');
        $model->startTrans();

        if(abs($gold)>0){
            $rs_gold = M('User')->where('id=' . $uid)->setInc('black', $gold);
            $rs_gold_record = $model->add($data);
        }

        if($rs_gold>0 && $rs_gold_record>0){
            $model->commit();
            return true;
        }
        else{
            $model->rollback();
            return false;
        }
    }
}
