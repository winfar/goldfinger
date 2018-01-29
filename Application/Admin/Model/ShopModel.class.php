<?php
namespace Admin\Model;

use Think\Model;
use Think\Storage;

class ShopModel extends Model
{

    protected $_validate = array(
        array('name', 'require', '商品名称不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('category', 'checkCategory', '该分类下还有分类请选择下属分类', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH),
        // array('cover_id', 'require', '请上传商品图片', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
        array('content', 'require', '商品介绍不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('price', 'require', '请填写商品价格', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        // array('edit_price', 'require', '请填写商品价格', self::MUST_VALIDATE , 'regex', self::MODEL_UPDATE),
        array('buy_price', 'require', '请填写商品购买价格', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('name', 'htmlspecialchars', self::MODEL_BOTH, 'function'),
        array('create_time', 'getCreateTime', self::MODEL_BOTH, 'callback'),
        array('update_time', NOW_TIME, self::MODEL_BOTH),
        array('position', 'getPosition', self::MODEL_BOTH, 'callback'),
        array('proc_type', 'getProcType', self::MODEL_BOTH, 'callback'),

    );

    public function info($id, $field = true)
    {
        $map = array();
        if ( is_numeric($id) ) {
            $map['id'] = $id;
        }
        $info = $this->field($field)->where($map)->find();
        $imgs = $this->get_covers($info["cover_id"]);
        $info["picurl"] = $imgs['picarr'];
        $info["picarrimages"] = $imgs['picarrimages'];
        return $info;
    }

    public function shopinfo($id, $field = true)
    {
        $map = array();
        if ( is_numeric($id) ) {
            $map['id'] = $id;
        }
        $info = $this->field($field)->where($map)->find();
        return $info;
    }

    //商品图片和排序信息
    private function get_covers($cover_id)
    {
        if ( empty($cover_id) ) {
            return false;
        }
        $map_cover = array('id' => array('in', $cover_id), 'status' => 1);
        $cover_list = M("picture")->where($map_cover)->field('id,path,imageorder')->select();

        $picarr = array();
        $picarrimages = array();
        if ( $cover_list ) {
            foreach ( $cover_list as $key => $item ) {
                $picarr[$item['id']] = array($item['imageorder'], completion_pic(__ROOT__ . $item['path']));
                $picarrimages[$item['id']] = array($item['imageorder'], $item['path']);
            }
            $picture['path'] = $picarr;
            $picarrimages['path'] = $picarrimages;
        }
        $arr['picarr'] = $picture['path'];
        $arr['picarrimages'] = $picarrimages['path'];
        return $arr;
    }

    public function delImage($pic)
    {
        $map['path'] = $pic;
        $cover_list = M("picture")->where($map)->field('id')->find();
        $map_cover = array('id' => array('in', $cover_list['id']));

        Storage::unlink('.' . $pic);
    }

    public function editpkconfig($data, $shopid)
    {
        $pkconfig = M("pkconfig");
        $peoplenums = $data['peoplenum'];
        $amounts = $data['amount'];
        $inventorys = $data['inventory'];
        $ids = $data['id'];

        foreach ( $peoplenums as $key => $item ) {
            $rs['peoplenum'] = $item;
            $rs['amount'] = $amounts[$key];
            $rs['inventory'] = $inventorys[$key];
            $rs['id'] = $ids[$key];

            if ( $rs['id'] ) {

                // $config = M('pkconfig')->where(array('id'=>$rs['id']))->find();
                // if($config){
                    // if($config['peoplenum']!=$rs['peoplenum'] || $config['amount']!=$rs['amount']  ){
                    //     $this->addPkHouse($shopid);
                    // }               
                    //$pkconfig->save($rs);
                // }
                $pkconfig->save($rs);
            } else {
                $rs['shopid'] = $shopid;
                $rs['create_time'] = time();
                $pkconfig->add($rs);

                //$this->addPkHouse($shopid);
            }
        }

        $pkconfigremoveids = $data['pkconfigremoveids'];
        $pkconfig->delete($pkconfigremoveids);
    }

    //商品下架
    public function takeDown($id){
        $map_shop['id'] = $id;
        $map_shop['status'] = 1;

        $shop = M('shop')->where($map_shop)->find();

        if($shop){
            $map_period['sid'] = $id;
            $map_period['state'] = 0;
            $periods = M('shop_period')->where($map_period)->select();

            foreach ($periods as $k => $v) {
                //退还金币，退还库存，并解散房间，下架周期
                $this->takeDown4Do($v['id']);
            }

            $rs_shop = M('Shop')->where(array('id'=>$id))->setField('status', 0);

            if($rs_shop){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
            //$this->error('该商品已下架！');
        }
    }

    //退还金币，退还库存，并解散房间，下架周期
    public function takeDownBackGold($pid){

        if(empty($pid)){
            return false;
        }

        $period = M('shop_period')->where(array('id' => $pid, 'state' => 0))->find();

        if(!$period){
            return false;
        }

        //退还金币
        $user_record = M("shop_record")
        ->table("__SHOP_RECORD__ record,__SHOP_ORDER__ o,__SHOP_PERIOD__ p,__SHOP__ s, __TEN__ ten")
        ->field("record.uid,SUM(record.number) number,SUM(o.cash) cash,SUM(o.gold) gold,(SUM(o.cash)+SUM(o.gold)) backgold,p.id pid,p.no,p.state,s.id sid,s.`name`,ten.unit")
        ->where("record.order_id=o.order_id and record.pid=p.id and p.sid=s.id and s.ten=ten.id and p.state=0 and p.id=" . $pid)
        ->group("uid")
        ->order("record.create_time")
        ->select();

        //您参与的${name}商品，因平台商品调整，已下架，您参与的${count}人次，现金支付${cash}，金币支付${gold}，以金币方式返现于您的金币账户，总计${backgold}，请到我的金币查看，给您带来的不便，深表抱歉！
        //亲，您参与的${name}商品，已下架，支付的金额将以金币方式返还给您，请在“我的金币”中查看，感谢您的参与！
        //name,number,cash,gold,(cash+gold)
        // select record.uid,SUM(record.number) number,SUM(o.cash) cash,SUM(o.gold) gold,(SUM(o.cash)+SUM(o.gold)) backgold,p.id pid,p.state,s.id sid,s.`name`,ten.unit 
        // from bo_shop_record record,bo_shop_order o,bo_shop_period p,bo_shop s,bo_ten ten
        // where record.order_id=o.order_id and record.pid=p.id and p.sid=s.id and s.ten=ten.id and p.state=0
        // and p.id=1057
        // GROUP BY uid
        // order by record.create_time 

        $passport_uid_list = array();

        foreach ( $user_record as $key => $value ) {
            //M('user')->where(array('id' => $value['uid']))->setInc('black', $value["backgold"]);

            array_push($passport_uid_list,$value['uid']);

            //GoldRecord
            $rs_gold_record = D("GoldRecord")->takeDown($value['uid'],$value['name'],$value['pid'],$value['no'],$value['cash'],$value['gold'],$value['backgold']);
            if($rs_gold_record){

                //Message 
                $rs_message = D('Message')->addUserMessage($value['uid'],105,$value['pid']);
                
                //SMS ??
                $mobile = M('User')->where('id='.$value['uid'])->getField('phone');
                $smsParams = array (
                    "name" => $shop["name"]
                );
                $rs_sms = D('Sms')->sendSms($mobile,'SMS_15440131',$smsParams,$this->web_title);
            }
        }

        //下架
        $period_takedown = M('shop_period')->where(array('id' => $pid))->setField('state', 3);

        if($period_takedown){
            $map_shop['id']=$period['sid'];

            if($period['iscommon']==2){
                //PK,修改PK房间的状态为解散
                $house['id'] = $period['house_id'];
                $house_rs = M('house_manage')->where($house)->setField('isresolving', 1);//解散房间

                if($house_rs){
                    //更新库存
                    $house_manage = M('house_manage')->where($house)->find();
                    if($house_manage){
                        $map_pkconfig['id'] = $house_manage['pksetid'];

                        if($house_manage['ispublic']==0){
                            //公开房间
                            $rs_stock = M('Shop')->where($map_shop)->setInc('shopstock', 1);//PK公共房间库存
                        }
                        else{
                            //私密房间
                            $rs_stock = M('pkconfig')->where($map_pkconfig)->setInc('inventory',1);//PK私密房间库存
                        }
                    }
                }
            }
            else{
                //普通摸金
                M('Shop')->where($map_shop)->setInc('shopstock', 1);//库存
            }
        }

        //Notification
        // try{
        //     if(count($passport_uid_list)>0){
        //         $takedown_msg = M('Message')->where('type=105')->getField('title');
        //         $takedown_msg = str_replace('${name}',$takedown_msg,$shop['name']);

        //         $data["title"] = $takedown_msg;
        //         $data["content"] = $data["title"];
        //         $data["passport_uid_list"] = $passport_uid_list;
                
        //         $postdata = http_build_query($data);

        //         $opts = array ('http' =>
        //             array (
        //                 'method'  => 'POST',
        //                 'header'  => 'Content-type: application/x-www-form-urlencoded\r\n'.'APPVERSION: 475d514697e0aee1d56c24a1332a3cd8',
        //                 'content' => $postdata
        //             )
        //         );
        
        //         $context = stream_context_create($opts);

        //         $baseUrl = '/wapapi.php?s=/notification/goldSendBack';

        //         if($_SERVER['HTTP_HOST']=='passport.busonline.com'){
        //             $url = 'http://'.$_SERVER['HTTP_HOST'].$baseUrl;
        //         }
        //         else {
        //             $url = 'http://test.passport.busonline.com'.$baseUrl;
        //         }

        //         $result = file_get_contents($url, false, $context);
        //     }
        // }
        // catch(Exception $e){
        //     recordLog($e->getMessage(),"Notification");
        // }

        return true;
    }

    //退还库存，下架周期,退还用户钻石
    public function takeDown4Do($pid){

        if(empty($pid)){
            return false;
        }

        $period = M('shop_period')->where(array('id' => $pid, 'state' => 0))->find();

        if(!$period){
            return false;
        }

        //退还金币
        $user_record = M("shop_record")
            ->table("__SHOP_RECORD__ record,__SHOP_ORDER__ o,__SHOP_PERIOD__ p,__SHOP__ s, __TEN__ ten")
            ->field("record.uid,SUM(record.number) number,SUM(o.gold) gold,o.order_id,o.exchange_transaction as billid , o.top_diamond ,o.recharge_activity ,p.id pid,p.no,p.state,s.id sid,s.`name`,ten.unit")
            ->where("record.order_id=o.order_id and record.pid=p.id and p.sid=s.id and s.ten=ten.id and p.state=0 and p.id=" . $pid)
            ->group("uid")
            ->order("record.create_time")
            ->select();

        //您参与的${name}商品，因平台商品调整，已下架，您参与的${count}人次，现金支付${cash}，金币支付${gold}，以金币方式返现于您的金币账户，总计${backgold}，请到我的金币查看，给您带来的不便，深表抱歉！
        //亲，您参与的${name}商品，已下架，支付的金额将以金币方式返还给您，请在“我的金币”中查看，感谢您的参与！
        //name,number,cash,gold,(cash+gold)
        // select record.uid,SUM(record.number) number,SUM(o.cash) cash,SUM(o.gold) gold,(SUM(o.cash)+SUM(o.gold)) backgold,p.id pid,p.state,s.id sid,s.`name`,ten.unit
        // from bo_shop_record record,bo_shop_order o,bo_shop_period p,bo_shop s,bo_ten ten
        // where record.order_id=o.order_id and record.pid=p.id and p.sid=s.id and s.ten=ten.id and p.state=0
        // and p.id=1057
        // GROUP BY uid
        // order by record.create_time

        $passport_uid_list = array();

        foreach ( $user_record as $key => $value ) {
            //M('user')->where(array('id' => $value['uid']))->setInc('black', $value["backgold"]);
            array_push($passport_uid_list,$value['uid']);
            //TODO 原路退还钻石
            $result =  D('api/pay')->expend($value['uid'], $value['billid'],$value['order_id'],abs($value['top_diamond']),abs($value['recharge_activity']),'商城下架退还');

            //GoldRecord
//            $rs_gold_record = D("GoldRecord")->takeDown($value['uid'],$value['name'],$value['pid'],$value['no'],$value['cash'],$value['gold'],$value['backgold']);
            if($result['code'] == '200'){
                //Message
                $rs_message = D('Message')->addUserMessage($value['uid'],105,$value['pid']);

                //SMS ??
                $mobile = M('User')->where('id='.$value['uid'])->getField('phone');
                $smsParams = array (
                    "name" => $user_record["name"]
                );
                $rs_sms = D('Sms')->sendSms($mobile,'SMS_15440131',$smsParams,$this->web_title);
//            }
            }
        }

        //下架
        $period_takedown = M('shop_period')->where(array('id' => $pid))->setField('state', 3);

        if($period_takedown){
            $map_shop['id']=$period['sid'];

            if($period['iscommon']==2){
                //PK,修改PK房间的状态为解散
                $house['id'] = $period['house_id'];
                $house_rs = M('house_manage')->where($house)->setField('isresolving', 1);//解散房间

                if($house_rs){
                    //更新库存
                    $house_manage = M('house_manage')->where($house)->find();
                    if($house_manage){
                        $map_pkconfig['id'] = $house_manage['pksetid'];

                        if($house_manage['ispublic']==0){
                            //公开房间
                            $rs_stock = M('Shop')->where($map_shop)->setInc('shopstock', 1);//PK公共房间库存
                        }
                        else{
                            //私密房间
                            $rs_stock = M('pkconfig')->where($map_pkconfig)->setInc('inventory',1);//PK私密房间库存
                        }
                    }
                }
            }
            else{
                //普通摸金
                M('Shop')->where($map_shop)->setInc('shopstock', 1);//库存
            }
        }

        //Notification
        // try{
        //     if(count($passport_uid_list)>0){
        //         $takedown_msg = M('Message')->where('type=105')->getField('title');
        //         $takedown_msg = str_replace('${name}',$takedown_msg,$shop['name']);

        //         $data["title"] = $takedown_msg;
        //         $data["content"] = $data["title"];
        //         $data["passport_uid_list"] = $passport_uid_list;

        //         $postdata = http_build_query($data);

        //         $opts = array ('http' =>
        //             array (
        //                 'method'  => 'POST',
        //                 'header'  => 'Content-type: application/x-www-form-urlencoded\r\n'.'APPVERSION: 475d514697e0aee1d56c24a1332a3cd8',
        //                 'content' => $postdata
        //             )
        //         );

        //         $context = stream_context_create($opts);

        //         $baseUrl = '/wapapi.php?s=/notification/goldSendBack';

        //         if($_SERVER['HTTP_HOST']=='passport.busonline.com'){
        //             $url = 'http://'.$_SERVER['HTTP_HOST'].$baseUrl;
        //         }
        //         else {
        //             $url = 'http://test.passport.busonline.com'.$baseUrl;
        //         }

        //         $result = file_get_contents($url, false, $context);
        //     }
        // }
        // catch(Exception $e){
        //     recordLog($e->getMessage(),"Notification");
        // }

        return true;
    }

    public function addPkHouse($shopid)
    {
       $data = D('shop')->shopinfo($shopid, 'id,price,ten,status,periodnumber,shopstock,pkset,iscreatehouse');

        if($data){
            //判断夺宝期数是否达到或者是否有库存
            if ( $data['periodnumber'] > 0 && $data['shopstock'] > 0 ) {
                //如果是上架状态
                if ( $data['status'] > 0 ) {
                    if ( $data['pkset'] == 1 || $data['pkset'] == 0) {
                        //普通摸金
                        D('Period')->createPeriod($shopid,$data['price'],$data['ten']);

                        //解散所有公共房间并退金币，退金币未完成
                        // $map_hosue_manage['shopid'] = $shopid;
                        // M('house_manage')->where($map_hosue_manage)->setField('isresolving',1);
                    } 
                    else if ( $data['pkset'] == 2 && $data['iscreatehouse'] == 1 ) {
                        //PK专区,创建公共房间
                        D('Pk')->createPkCommonHouseByShop($data);
                    }
                    else if($data['pkset'] == 3  && $data['iscreatehouse'] == 1){
                        D('Period')->createPeriod($shopid,$data['price'],$data['ten']);
                        D('Pk')->createPkCommonHouseByShop($data);
                    }
                }

                return true;
            }
            else{

                //$this->error = '库存不足或夺宝期数已达成!';
                return false;
            }
        }
    }

    public function update($pics, $orders, $pkconfigdata)
    {
        recordLog('进入更新商品编辑模块2', '商品编辑');
        $list = $_POST;
        $data = array();
        $data['id'] = $list['id'];
        $data['name'] = $list['name'];
        $data['category'] = $list['category'];
        $data['ten'] = $list['ten'];
        $data['buy_price'] = $list['buy_price'];
        $data['price'] = $list['price'];
        $data['shopstock'] = $list['shopstock'];
        $data['shopinterval'] = $list['shopinterval'];
        $data['periodnumber'] = $list['periodnumber'];
        $data['keywords'] = $list['keywords'];
        $data['description'] = $list['description'];
        $data['hits'] = $list['hits'];
        $data['fictitious'] = empty($list['fictitious']) ? 1 : intval($list['fictitious']);
        $data['content'] = htmlspecialchars($list['content']);
        //是否支持全价兑换
        $is_full = count($list['is_full']) ==2 ? 2 : $list['is_full'][0];
        $data['full_price'] = $is_full==0 ? 0 : $list['full_price'];
        $data['is_full'] = $is_full;
        $data['prop_type'] = $list['prop_type'];
        if ($data['prop_type']==1) {//类型为升级经验时
            $data['ten'] = 5;//普通专区
            $data['price'] = 0;//售价为0
            $data['fictitious'] = 2;//虚拟商品
            $data['is_full'] = 1;//全价兑换
            $data['full_price'] = 0;//全价兑换金额

        }
        $data['prop_id'] = $list['prop_id']; 
        //print_r($data);exit;
        if ( !$data ) {
            return false;
        }
        recordLog('检查价格是否能够被专区整除', '商品编辑');
        $data['edit_price'] = $data['price'];
        if ( $data['ten'] != 0 ) {
            if ( $data['price'] % get_ten_unit($data['ten']) != 0 || $data['edit_price'] % get_ten_unit($data['ten']) != 0 ) {
                $this->error = '价格必须要能够被专区单位整除!';
                return false;
            }
        }
        recordLog('构建图片信息...', '商品编辑');

        $coverid = $data['cover_id'];
        $coveridt = explode(',', $coverid);
        $saveId = array();
        $pictureids = array();
        foreach ( $pics as $k => $v ) {
            $mapt['path'] = $v;
            $picture = M("picture")->where($mapt)->field('id')->find();
            if ( !$picture ) {
                $pic['path'] = $v;
                $pic['md5'] = md5($v);
                $pic['sha1'] = sha1($v);
                $pic['status'] = 1;
                $pic['create_time'] = time();
                $pic['imageorder'] = $orders[$k];
                $pictureid = M("picture")->add($pic);
                $pictureids[] = $pictureid;
            } else {
                $picorder['imageorder'] = $orders[$k];
                M("picture")->where('id=' . $picture['id'])->save($picorder);;
                $saveId[] = $picture['id'];
                $pictureids[] = $picture['id'];
            }
        }

        recordLog('处理数组元素......', '商品编辑');
        foreach ( $coveridt as $key => $v1 ) {
            foreach ( $saveId as $key2 => $v2 ) {
                if ( $v1 == $v2 ) {
                    unset($coveridt[$key]);//删除$a数组同值元素
                }
            }
        }

        recordLog('处理coverid......', '商品编辑');
        if ( sizeof($coveridt) > 0 ) {
            $picidss = implode(',', $coveridt);
            $delecoverids = array('id' => array('in', $picidss));
            M("picture")->where($delecoverids)->delete();
        }

        recordLog('完成处理coverid......', '商品编辑');
        $picids = implode(',', $pictureids);
        $data['cover_id'] = $picids;

        $period['jiang_num'] = jiang_num($data['price'] - 1);
        $period['create_time'] = NOW_TIME;
        $period['state'] = 0;
        if ( sizeof($data['pkset']) > 1 ) {
            $data['pkset'] = 3;
        } else {
            $data['pkset'] = $data['pkset'][0];
        }
        recordLog('商品信息保存......', '商品编辑');

        if ( empty($data['id']) ) {
            $this->edit_price = $data['price'];
            $data['status'] = 0;//默认商品下架；
            $data['pkset']=1;
            $res = $this->add($data);
            $configshopid = $res;
            // $period['sid'] = $res;
            // $period['no'] = 100001;
            // M('shop_period')->data($period)->add();
        } else {
            $res = $this->save($data);
            // if($res){
            //     //判断夺宝期数是否达到或者是否有库存
            //     if($data['periodnumber']>0 && $data['shopstock']>0 ){
            //         $period['sid'] = $data['id'];
            //         $no = M('shop_period')->where('sid=' . $period['sid'])->max('no');
            //         $period['no'] = $no ? $no + 1 : 100001;
            //         if ( $data['status'] > 0 ) {
            //             M('shop_period')->data($period)->add();
            //         }
            //     }
            // }
            $configshopid = $data['id'];
        }

        $this->editpkconfig($pkconfigdata, $configshopid);
        
        //recordLog('商品信息处理完成', '商品编辑');
        return $res;
    }

    /**
     * 获取某商品期的限制
     * @param intger $id 限制条件id，对应数据库ten表内容
     */
    function getRestrictions($id)
    {
        $restrictions = M('ten')->field('id,title,unit,restrictions,restrictions_num')->where(array('id' => $id, 'status' => 1))->find();
        return $restrictions;
    }

    public function adddate($id)
    {
        $data = D('shop')->shopinfo($id, 'id,price,ten,status,periodnumber,shopstock,pkset,iscreatehouse');

        if($data){
            //判断夺宝期数是否达到或者是否有库存
            if ( $data['periodnumber'] > 0 && $data['shopstock'] > 0 ) {
                //如果是上架状态
                if ( $data['status'] > 0 ) {
                    $p = D('Period')->createPeriod($data['id'],$data['price'],$data['ten']);


                    // if ( $data['pkset'] == 1 || $data['pkset'] == null) {
                    //     //普通摸金或不参加pk
                    //     $p = D('Period')->createPeriod($data['id'],$data['price'],$data['ten']);
                    //     //解散所有公共房间并退金币，退金币未完成
                    //     // $map_hosue_manage['shopid'] = $shopid;
                    //     // M('house_manage')->where($map_hosue_manage)->setField('isresolving',1);
                    // } 
                    // else if ( $data['pkset'] == 2 && $data['iscreatehouse'] == 1 ) {
                    //     //PK专区,创建公共房间
                    //     D('Pk')->createPkCommonHouseByShop($data);
                    // }
                    // else if($data['pkset'] == 3  && $data['iscreatehouse'] == 1){
                    //      //同时，普通和PK专区摸金,创建公共房间
                    //     $p = D('Period')->createPeriod($data['id'],$data['price'],$data['ten']);
                    //     D('Pk')->createPkCommonHouseByShop($data);
                    // }
                }
            }
            else{
                $this->error = '库存不足或夺宝期数已达成!';
                return false;
            }
        }
    }

    public function remove($id = null)
    {
        $map = array('id' => array('in', $id));
        $movie_list = $this->where($map)->field('cover_id,content')->select();
        foreach ( $movie_list as $key => $value ) {
            $picture[$key] = $value['cover_id'];
            $content[$key] = $value['content'];
        }
        $map_cover = array('id' => array('in', $picture));
        $cover_list = M("picture")->where($map_cover)->field('path')->select();
        foreach ( $cover_list as $value ) {
            Storage::unlink('.' . $value['path']);
        }
        foreach ( $content as $v ) {
            preg_match_all('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', $v, $match);
            foreach ( $match[2] as $a ) {
                Storage::unlink('.' . $a);
            }
        }
        $res = $this->where($map)->delete();
        M("shop_period")->where(array('sid' => array('in', $id)))->delete();
        M("picture")->where($map_cover)->delete();
        return $res;
    }

    protected function getPosition()
    {
        $position = I('post.position');
        if ( !is_array($position) ) {
            return 0;
        } else {
            $pos = 0;
            foreach ( $position as $key => $value ) {
                $pos += $value;
            }
            return $pos;
        }
    }

    protected function getCreateTime()
    {
        $create_time = I('post.create_time');
        return $create_time ? strtotime($create_time) : NOW_TIME;
    }


    public function getTree()
    {
        $list = M("Category")->where(array('status' => 1))->field('id,pid,title')->order('pid asc,sort asc')->select();
        $Tree = new \Org\Tree;
        $Tree::$treeList = array();
        return $Tree->tree($list);
    }

    public function getId($id)
    {
        $map["status"] = 1;
        $map["display"] = 1;
        if ( $id ) {
            $map["pid"] = $id;
            $info = M("Category")->field("id")->where($map)->order('sort')->select();
            if ( $info ) {
                foreach ( $info as $key => $val ) {
                    $ids[] = $val["id"];
                }
            } else {
                $ids[] = $id;
            }
            return $ids;
        }
    }

    protected function checkCategory($cate_id)
    {
        $child = M('Category')->where(array('pid' => $cate_id))->field('id')->select();
        if ( !empty($child) ) {
            return false;
        }
        return true;
    }

    public function getShops($param = array())
    {
        $sql = " SELECT shop.*, period.no, period.iscommon ,period.house_id,period.id pid,period.create_time,period.sid,period.id,end_time,kaijang_num,period.state FROM bo_shop shop INNER JOIN bo_shop_period period ON period.sid = shop.id  WHERE	1 = 1  ";
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            $sql .= " and period.end_time>='" . $startTime . "000' ";
        }
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and period.end_time<='" . $endTime . "000' ";
        }
        if ( $param['ten'] ) {
            $sql .= " and ten=" . $param['ten'];
        }
        if ( $param['fictitious'] ) {
            $sql .= " and fictitious=" . $param['fictitious'];
        }
        //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and period.iscommon =".$iscommon;
            } else {
                $sql .= " and (period.iscommon =".$iscommon." or period.iscommon is null )";
            }
            
        }
         //房间号
        if ( isset($param['house_id']) ) {
            $sql .= " and period.house_id =".$param['house_id'];
            
        }
        if ( isset($param['shopstatus'])) {
            $status = $param['shopstatus'];
            //品牌名称为金袋的品牌id
            $barnd_item = D('Brand')->where("title = '金袋'")->field('id')->find();
             if ($status==2) {
                $sql .= " and shop.fictitious=2";//1.非金袋2.金袋
            }
            if (!empty($barnd_item)) {
                if ($status==1) {
                    $sql .= " and shop.brand_id!=".$barnd_item['id'];
                } else {
                    $sql .= " and shop.brand_id=".$barnd_item['id'];  
                }
            }
        }
        $sql .= " order by period.end_time desc limit " . $param['pageindex'] . "," . $param['pagesize'];
        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 活动列表总数量
     * @param  array  $param 条件
     * @author liuwei(修改)
     * @return array
     */
    public function getShopsTotal($param = array())
    {
        $sql = " SELECT count(*) count FROM bo_shop shop INNER JOIN bo_shop_period period ON period.sid = shop.id WHERE 1 = 1  ";
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            //$sql .= " and period.end_time>='" . $startTime . "000' ";
            $sql .= " and period.create_time>='" . $startTime . "' ";
        }
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            //$sql .= " and period.end_time<='" . $endTime . "000' ";
            $sql .= " and period.create_time<='" . $endTime . "' ";
        }
        if ( $param['ten'] ) {
            $sql .= " and ten=" . $param['ten'];
        }
        if ( $param['fictitious'] ) {
            $sql .= " and fictitious=" . $param['fictitious'];
        }
         //类型
        if ( isset($param['iscommon']) ) {
            $iscommon = $param['iscommon'];
            if ( $iscommon == 2 ) {
                $sql .= " and period.iscommon =".$iscommon;
            } else {
                $sql .= " and (period.iscommon =".$iscommon." or period.iscommon is null )";
            }
            
        }
        //房间号
        if ( isset($param['house_id']) ) {
            $sql .= " and period.house_id =".$param['house_id'];
            
        }
        if ( isset($param['shopstatus'])) {
            $status = $param['shopstatus'];
            //品牌名称为金袋的品牌id
            $barnd_item = D('Brand')->where("title = '金袋'")->field('id')->find();
             if ($status==2) {
                $sql .= " and shop.fictitious=2";//1.非金袋2.金袋
            }
            if (!empty($barnd_item)) {
                if ($status==1) {
                    $sql .= " and shop.brand_id!=".$barnd_item['id'];
                } else {
                    $sql .= " and shop.brand_id=".$barnd_item['id'];  
                }
            }
        }
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }
    /**
     * 活动列表 - new
     * @author liuwei
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getNewShops($param = array())
    {
        $sql = " SELECT shop.*, period.no, period.iscommon ,period.house_id,period.id pid,period.create_time,period.sid,period.id,end_time,kaijang_num,period.state,period.exchange_type,period.total_number,period.total_buy_gold,period.total_gold_price,period.total_price FROM bo_shop shop INNER JOIN (SELECT p.*,c.channel_name,c.proportion,(SELECT SUM(number) FROM bo_shop_order WHERE pid=p.id) AS total_number,(SELECT SUM(buy_gold) FROM bo_shop_order WHERE pid=p.id) AS total_buy_gold,(SELECT SUM(gold) FROM bo_shop_order WHERE pid=p.id) AS total_gold_price,(SELECT cast((SUM(gold)/10) as decimal(18,2)) FROM bo_shop_order WHERE pid=p.id) AS total_price  FROM bo_shop_period p LEFT JOIN bo_channel c ON p.channel_id = c.id) period ON period.sid = shop.id  WHERE   1 = 1  ";
        //活动状态
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        //开始时间
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            //$sql .= " and period.end_time>='" . $startTime . "000' ";
            $sql .= " and period.create_time>='" . $startTime . "' ";
        }
        //结束时间
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            //$sql .= " and period.end_time<='" . $endTime . "000' ";
            $sql .= " and period.create_time<='" . $endTime . "' ";
        }
        //渠道
        if ( $param['channel'] != "") {
            $sql .= " and period.channel_id = " . $param['channel'];
        }
        $sql .= " order by period.id desc ";
        if (isset($param['pageindex']) and isset($param['pagesize'])) {
            $sql .= " limit " . $param['pageindex'] . "," . $param['pagesize'];
        }
        $users = $this->query($sql, false);
        return $users;
    }
    /**
     * 活动列表总数量 - new
     * @param  array  $param 条件
     * @author liuwei
     * @return array
     */
    public function getShopsNewTotal($param = array())
    {
        $sql = " SELECT count(*) count FROM bo_shop shop INNER JOIN bo_shop_period period ON period.sid = shop.id WHERE 1 = 1  ";
        //活动状态
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        //开始时间
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            //$sql .= " and period.end_time>='" . $startTime . "000' ";
            $sql .= " and period.create_time>='" . $startTime . "' ";
        }
        //结束时间
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            //$sql .= " and period.end_time<='" . $endTime . "000' ";
            $sql .= " and period.create_time<='" . $endTime . "' ";
        }
        //渠道
        if ( $param['channel'] != "") {
            $sql .= " and channel_id = " . $param['channel'];
        }
        $users = $this->query($sql, false);
        return $users[0]['count'];
    }

    public function getShopNo($sid)
    {
        $sql = "select MAX(no) no,MAX(id) id  from bo_shop_period where sid in (" . $sid . ") GROUP BY sid ";
        $rs = $this->query($sql, false);
        return $rs;
    }

    /**
     * 获取处理类型
     */
    public function getProcType()
    {
        $fictitious = I('post.fictitious');
        $brandId = I('post.brand_id');

        $title = D('Brand')->where(array('id' => $brandId))->getField('title');

        //如果为实体则设置为
        if ( $fictitious === "1" ) {
            return 'shop';
        }
        if ( $fictitious === "2" ) {
            //商品品牌为金袋的设置为goldbag
            if ( $title == '金袋' ) {
                return 'goldbag';
            }
            //其它虚拟商品设置为card
            return 'card';
        }
    }

    /**
     * 用户金币总金额
     * @author liuwei
     * @return array
     */
    public function user_gold_total($conditionarr)
    {
        $map = array();
        $map['status'] = array('egt',0);
        if ( isset($conditionarr['keyword']) ) {
            $where['u.username'] = array('like', '%' . I('keyword') . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
        }

        if ( !empty($conditionarr['starttime']) ||  !empty($conditionarr['endtime']) ) {
            //结束日期取23:59:59 时间戳 +86400
            $map['a.create_time']   = array(array('egt',strtotime(I('starttime'))),array('lt',strtotime(I('endtime'))+86400));
        }

        if ( isset($conditionarr['tradetype']) ) {
            $map['t.code'] = I('tradetype');
        }
        $Model = D('GoldRecord');
        $list = $Model->alias('a')
            ->join(array(' LEFT JOIN __USER__ u ON u.id= a.uid',' LEFT JOIN __TRADE_TYPE__ t ON t.id= a.typeid'))
            ->where($map)
            ->field('a.gold')
            ->select();
        $gold_list = empty($list) ? array() : array_column($list, 'gold');//金币总和
        $reduce = array();//收入
        $increase = array();//支出
        if (!empty($gold_list)) {
            foreach ($gold_list as $key => $value) {
                if ($value>=0) {
                    $reduce[] = $value;
                } else {
                    $increase[] = $value;
                }
            }
        }
        $data = array();
        $data['reduce'] = array_sum($reduce);
        $data['increase'] = array_sum($increase);
        $data['total'] = array_sum($gold_list);
        return $data;
    }
    /**
     * 全部金币明细
     * @param  array  $param [条件]
     * @author liuwei
     * @return array
     */
    public function getShopsList($param = array())
    {
        $sql = " SELECT shop.*, period.no ,period.id pid,period.create_time,period.sid,period.id,end_time,kaijang_num,period.state FROM bo_shop shop INNER JOIN bo_shop_period period ON period.sid = shop.id WHERE 1 = 1  ";
        if ( $param['name'] ) {
            $sql .= " and shop.name like  '%" . $param['name'] . "%' ";
        }
        if ( is_numeric($param['state']) ) {
            $sql .= " and state=" . $param['state'];
        }
        if ( $param['create_time'] ) {
            $startTime = strtotime($param['create_time']);
            //TODO end_time时间戳为13位，故需补0
            $sql .= " and period.end_time>='" . $startTime . "000' ";
        }
        if ( $param['end_time'] ) {
            $endTime = strtotime($param['end_time'] . " 23:59:59");
            $sql .= " and period.end_time<='" . $endTime . "000' ";
        }
        if ( $param['ten'] ) {
            $sql .= " and ten=" . $param['ten'];
        }
        if ( $param['fictitious'] ) {
            $sql .= " and fictitious=" . $param['fictitious'];
        }
        $sql .= " order by period.end_time desc";
        $users = $this->query($sql, false);
        return $users;
    }

    /**
     * 商品虚拟卡绑定
     * 
     * @author liuwei
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getBidCard($data)
    {
        $result = array();
        $result['code'] = 101;
        $result['msg'] = '非法操作';
        if (!empty($data)) {
            if (empty($data['sid'])) {
                $result['msg'] = '非法操作';
            } elseif (empty($data['pid'])) {
                $result['msg'] = '非法操作';
            } else {
                $sid = $data['sid'];//商品id
                $pid = $data['pid'];//开奖订单pid
                $card_model = M('card');
                $period_model = M('shop_period');
                $card_id = $card_model->where('type = '.$sid.' and status = 0')->getField('id');//卡号是否正确
                if (!empty($card_id)) {
                    $period_info = $period_model->where('id = '.$pid.' and sid = '.$sid)->field('card_id')->find();//开奖订单详情
                    if (!empty($period_info) and $period_info['card_id'] < 1) {
                        $period_result = $period_model->where('id = '.$pid)->save(array('card_id'=>$card_id));//绑卡
                        $card_result = $card_model->where('id = '.$card_id)->save(array('status'=>1));//把卡变成已绑定
                        if ($period_result!= false and $card_result!=flase) {
                            $result['code'] = 200;
                            $result['msg'] = '绑定成功';
                        } else {
                            $result['msg'] = '系统繁忙!';
                        }

                    } else {
                       $result['msg'] = '该订单无法绑定卡号!'; 
                    }

                } else {
                    $result['msg'] = '没有可用虚拟卡号请导入!';
                }
            }
        }
        return $result;
    }

    /**
     * 判断是否有新商品上架
     * @param $date  指定日期 格式：2017-03-09
     * @return bool
     */
    public function hasNewArrival($date){
        $curr_time = time();
        if(empty($date) ){
            $starttime = $curr_time - 86440  ; //当前时间向前推1天
            $endtime = $curr_time;
        }else{
            $starttime = strtotime($date);
            $endtime = $starttime+ 86440; //当前时间向后推1天
        }

        $map['shelve_time']   = array(array('egt',$starttime),array('lt',$endtime));
        $count = $this->where($map)->count();
        return $count;
    }
}
