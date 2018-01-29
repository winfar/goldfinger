<?php
namespace api\Controller;

use Think\Controller;
use Think\Storage;

class TestRichieController extends BaseController
{
    //JPush
    protected $app_key = '1332f6f2c55665e4010fae13';// '06c61e785e5745ff9c52c751';
    protected $master_secret = '22bef63563b88d158c3d861e';//'c6dad8a64ebfe1d19c8726ce';

    protected function _initialize()
    {
        //parent::_initialize(); 
        vendor("JPush.JPush");
        vendor("Pay.init");
    }

    public function test($pid){

//        $data = getSSCIssue();
        $data = getNextSsc();
        echo '----> getSSCIssue';
        var_dump($data);
        exit();


        $amount_gold = M('shop_order')->where(array('pid'=>$pid))->sum('buy_gold');

        var_dump($amount_gold);
        echo '----> shop_order';

        $data = M('shop_period')->lock(true)->where('id='.$pid)->setField('state','1');
        var_dump($data);

        $numbers=range(100001,100009);   //用range直接创建1~9共9个数字组成的数组，以“1”开始“9”结束。
        var_dump($numbers);
        exit();
        $data = M('pkconfig')->where('id=' . $pkid)->setDec('inventory');
        var_dump($data);
//        $_shops = M()->table('__HOUSE_MANAGE__ m , __PKCONFIG__ c ')->distinct(true)->where(array('m.ispublic'=>1,'m.isresolving'=>0,'c.peoplenum'=>$number,'m.uid'=>$uid))->where('m.pksetid = c.id')->field('m.shopid')->select();
//        $data = array_column($_shops,'shopid');
//        var_dump($_shops);
//        var_dump($data);
//
//        $map['c.shopid'] = array('NOT IN',array_column($_shops,'shopid'));
//        var_dump($map);


        if(!empty($uid)){
            $_shops = M()->table('__HOUSE_MANAGE__ m , __PKCONFIG__ c ')->distinct(true)->where(array('m.ispublic'=>1,'m.isresolving'=>0,'c.peoplenum'=>$number,'m.uid'=>$uid))->where('m.pksetid = c.id')->field('m.shopid')->select();
            $_shops = array_column($_shops,'shopid');
        }
        if(!empty($_shops)){
            $map['c.shopid'] = array('NOT IN',$_shops);
        }

        var_dump($map);
    }
    public function testExplode($num='1,2,3,4,5'){

//        $user_buy[$key]['num'] = explode(',',$value['num']);
        $arr_num  = explode(',',$num);
        foreach ($arr_num as &$v) {
            $v = intval($v);
        }
        $user_buy[2]['num'] = $arr_num ;

        $data = json_encode($user_buy);
        var_dump($data);
    }

    public function testInviteCode($roomno,$invitecode){
        $res = M('house_manage')->where('no=' . $roomno . ' AND invitecode=' . $invitecode)->find();
        if ( !$res ) {
            returnJson('', 403, '邀请码不对');
        } else {
            returnJson('',200);
        }
    }
    
    public function testPk($number, $room = ''){
//        $rs = D('shop')->pk($pageindex = 1, $pagesize = 20, $room, $number );

        //房间号或手机号
        if ( $room ) {
            $condition = ' and housemanage.no=' . $room;
        }
        //人数
        if ( $number) {
            $condition .= ' and pkconfig.peoplenum=' . $number;
        }
        $defaultOrder = 'housemanage.id desc';
        $list = M('shop_period')
            ->table('__SHOP__ shop,__SHOP_PERIOD__ period,__HOUSE_MANAGE__ housemanage,__PKCONFIG__ pkconfig')
            ->field('shop.id as sid,shop.ten,shop.name,shop.cover_id,period.id as pid,period.number,period.no as nid,housemanage.ispublic,housemanage.no,housemanage.id as houseid,housemanage.uid,pkconfig.peoplenum,pkconfig.amount')
            ->where('shop.id=period.sid and shop.status=1 and shop.display=1 and period.state=0 AND period.iscommon=2 AND period.house_id=housemanage.id AND housemanage.isresolving=0 AND housemanage.pksetid=pkconfig.id' . $condition)
            ->page(0, 1000)
            ->order($defaultOrder)
            ->select();

        var_dump($list);
    }

    public function testParticipation($pid){
        $user_buy = M('shop_record')
            ->table('__SHOP_RECORD__ record,__USER__ user')
            ->field('record.number,record.uid,record.create_time,record.num,user.nickname,user.headimgurl')
            ->where('record.pid=' . $pid . ' AND record.uid=user.id')
            ->select();

        $info['participation'] = empty($user_buy)?$user_buy : $user_buy;

        returnJson($info, 200, 'everything OK');
    }

    public function testPkAmount($room_id){
        $amount  = M('pkconfig')->where(array('id'=>$room_id))->getField('amount');

        $info['price'] =10;
        echo  'amount->'.$amount;
        $info['price']  = $amount;
        var_dump($info);

    }

    public function genMD5($mch_tradeno){
        $md5key = think_md5($mch_tradeno, $this->md5_key);
        echo 'md5key->'.$md5key;

    }

    public function testInfo($pid){
        //普通摸金区域，正价商品
        $info=M('shop_period')->
        table('__SHOP__ shop,__SHOP_PERIOD__ period')
            ->lock(true)
            ->field('shop.id as sid,shop.name,shop.price,shop.buy_price,shop.status,shop.edit_price,shop.ten,shop.periodnumber,shop.shopstock,period.no,period.number,period.state,period.jiang_num,period.iscommon')
            ->where('shop.id=period.sid and period.id='.$pid)
            ->find();

        //如果是pk的重新赋值商品价格
        if($info && $info['iscommon'] == 2){
            $rs_amount  = M()->table('__SHOP_PERIOD__ period,__PKCONFIG__ pk,__HOUSE_MANAGE__ house')
                ->field('amount')
                ->where('house.periodid=period.id and pk.id = house.pksetid and period.id='.$pid)
                ->find();
            if(!empty($rs_amount['amount'])){
                $info['price']  = $rs_amount['amount'];
            }
        }
        var_dump($info['price']);
    }

    public function testInt($p_time = 1479716095 ){

        $countdown_time = 86400 * getPKValid() - (time() - $p_time ) ;
        $info['countdown_time'] =  ( is_int($countdown_time) && $countdown_time >= 0 ) ? $countdown_time : 0;

        var_dump($info);
    }

    public function testPkinfo($tokenid, $houseid){
        \Think\Log::record('测试日志信息，这是警告级别','WARN',true);
        \Think\Log::save();
        \Think\Log::write('测试日志信息，这是警告级别，并且实时写入s','WARN');
        $data = D('Shop')->newPkInfo($tokenid, $houseid);
        \Think\Log::write('测试日志信息，这是警告级别，并且实时写入e','WARN');
        var_dump($data);
    }

    public function genQRcode($data = '{"houseid": 10127}'){
        //拼装生成二维码渠道地址URL
//        $activity_url = $activity_link.'/code/'.$id;
        //草料二维码地址
        $CLI_URL_PREFIX = "https://cli.im/api/qrcode/code?text=";
        $CLI_URL_SUFFIX = "&mhid=thORDl3rzsIhMHcvKtdcOa8"; //一元摸金的模板参数
        $qr_url =  $CLI_URL_PREFIX.$data.$CLI_URL_SUFFIX;

        $content = file_get_contents($qr_url);
        // 用正则表达式解析
        preg_match('/<img src="(.*?)"/i',$content,$match);
        $qr_code = $this-> GrabImage('http:'.$match[1],"");
        return $qr_code;
    }

    protected function GrabImage($url, $filename = "") {
        if ($url == ""):return false;
        endif;
        //如果$url地址为空，直接退出
        if ($filename == "") {
            //TODO 当目录不存在的时候生成文件目录
            $filename = 'Picture/Share/'.date("YmdHis") .'.jpg';
            //用天月面时分秒来命名新的文件名
        }
        ob_start();//打开输出
        readfile($url);//输出图片文件
        $img = ob_get_contents();//得到浏览器输出
        ob_end_clean();//清除输出并关闭
        $size = strlen($img);//得到图片大小
        $fp2 = @fopen($filename, "a");
        fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名
        fclose($fp2);
        return $filename;//返回新的文件名
    }

    public function genJson($houseid){
        $data['houseid'] =intval($houseid) ;
        $json = json_encode($data);
        echo $json ;
    }

    public function testGenCode($houseid){
        $data['houseid'] =intval($houseid) ;
        $json = json_encode($data);
        $filename = genQRcode($json);
        echo  $filename;
    }

    public function testCompic($url=''){
        echo completion_pic('Picture/Share/20161123143321.jpg');
    }
    
    public function testRecordOrder($periodid=0){
        $user_buy = M('shop_record')
            ->table('__SHOP_RECORD__ record,__USER__ user')
            ->field('record.number,record.uid,record.create_time,record.num,user.nickname,user.headimgurl')
            ->where('record.pid=' . $periodid . ' AND record.uid=user.id')
            ->order('record.create_time desc')
            ->select();
        
        var_dump($user_buy);
    }

    public function testSetisresolving($houseid){
        echo  '11111111111111111->';
        $rs = M('house_manage')->where('id=' . $houseid)->setField('isresolving',1);
        echo  '22222222222222->';
        var_dump($rs);
    }

    public function testPklist(){
//        $pklist = M('shop_period')
//            ->table('__SHOP_PERIOD__ period,__HOUSE_MANAGE__ house')
//            ->field('period.id as pid,period.create_time,house.pksetid as pkid,house.id as houseid')
//            ->where('period.state = 0 AND period.iscommon = 2 AND period.id = house.periodid AND house.ispublic=1 ')
//            ->select();

        $condition = '';

        $pklist = M('shop_period')
            ->table('__SHOP__ shop,__SHOP_PERIOD__ period,__HOUSE_MANAGE__ housemanage,__PKCONFIG__ pkconfig')
            ->field('shop.id as sid,shop.ten,shop.name,shop.cover_id,period.id as pid,period.number,period.no as nid,housemanage.ispublic,housemanage.no,housemanage.id as houseid,housemanage.uid,pkconfig.peoplenum,pkconfig.amount')
            ->where('shop.id=period.sid and shop.status=1 and shop.display=1 and period.state=0 AND period.iscommon=2 AND period.number>0 AND period.id=housemanage.periodid AND housemanage.isresolving=0 AND housemanage.pksetid=pkconfig.id' . $condition)
//            ->page($pageindex, $pagesize)
//            ->order($defaultOrder)
            ->select();

        var_dump($pklist);
    }

    public function testDateF(){
        echo  GrabImage('http://qr.api.cli.im/qr?data=123123123&level=H&transparent=false&bgcolor=%23ffffff&forecolor=%23000000&blockpixel=12&marginblock=1&logourl=&size=280&kid=cliim&key=3d46cb735dd43911744fbcdaed9854db');
//        $path = '/Picture/PK/'.date("Y-m-d");
//        if (!file_exists($path)){
//            $rs = mkdir($path);
//            echo '创建结果:'.$rs;
//        } else {
//            echo '\n 需创建的文件夹test已经存在';
//        }
//        $filename = $path.'/'.date("YmdHis") .'.jpg';
//        echo $filename;
    }

    public function mkFile($path){
        $path = str_replace('@','/',$path);
        echo $path.'<br>';
        if (is_dir($path)){
            echo "对不起！目录 " . $path . " 已经存在！";
        }else{
            //第三个参数是“true”表示能创建多级目录，iconv防止中文目录乱码
            $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true);
            if ($res){
                echo "目录 $path 创建成功";
            }else{
                echo "目录 $path 创建失败";
            }
        }
    }

    public function isShopValid($shopid){
        var_dump(D('Shop')->isShopValid($shopid));
    }

    public function isValidHouse($houseid){
        $validFlag = D('HouseManage')->isValidRoom($houseid);
        if(!$validFlag){
            returnJson('', 404, '房间已解散或商品已下架！');
        }else{
            returnJson('', 200, '房间有效');
        }
    }

    public function testWinner($kaijiang_num='10000005'){
//        $arr_num  = explode(',',$num);
//        $info['state'] = 2;
//        foreach ($arr_num as &$v) {
//            $v = intval($v);
//            if($info['state'] == 2 &&  $v == $kaijiang_num ){
////                $user_buy[$key]['winner'] = 1; //中奖用户
//                $user_buy[1]['winner'] = 1; //中奖用户
//            }
//        }
//        var_dump($user_buy);

        $num = '10000006,10000004,10000001,10000005';
        $info['state'] = 2;
        $info['kaijiang_num'] = '10000005';
        $arr_num  = explode(',',$num );
        foreach ($arr_num as &$v) {
            $v = intval($v);
            if($info['state'] == 2 && $v == $info['kaijiang_num'] ){
                $user_buy[1]['winner'] = 1; //中奖用户
            }
        }
        $user_buy[1]['num'] = $arr_num ;
        var_dump($user_buy);
    }
    
    public function testUserInfo($orderid){
//        $res = M()->table('__SHOP_ORDER__ s ,__USER__ u')->field('s.uid,u.username,s.gold,s.cash')->where(' s.uid = u.id')->where(array('s.order_id'=>$orderid))->find();
        $res = M()->table('__SHOP_ORDER__ s ,__USER__ u')->field('s.uid,u.username,s.gold,s.cash,u.phone')->where(' s.uid = u.id')->where(array('s.order_id'=>$orderid))->find();
        var_dump($res);
    }
    
    public function testGetShopList($area_type=1,$room_type=2,$number=0,$uid=''){
        $data = D('Shop')->getShopList($area_type,$room_type,$number,$uid);
        var_dump( $data);
//        D('Shop')->getShopList(); //获取普通商品
//        D('Shop')->getShopList(2);  //获取PK公开商品
//        D('Shop')->getShopList(2,0);  //获取PK私有所有场次商品
//        D('Shop')->getShopList(2,0,2);  //获取PK私有商品  2人场
//        D('Shop')->getShopList(2,0,4);  //获取PK私有商品  4人场
//        D('Shop')->getShopList(2,0,4,);  //获取PK私有商品  4人场 指定用户的商品
    }

    public function testSelectPK($number = 2 ,$tokenid= ''){

        $data = D('Shop')->selectPk(null,null, $number,$tokenid);
        var_dump($data);
    }

    public function testGetRoomList($number,$room_type=0){
        $data =   D('HouseManage')->getRoomList($number,$room_type);
        var_dump($data);
    }

    //私密房间实时解散接口
    public function pkDissolve2($key)
    {
        if($key != 'richie'){
            echo ' key value error ' ;
            return ;
        }
        //获取私密房间正在进行期
        $pklist = M('shop_period')
            ->table('__SHOP_PERIOD__ period,__HOUSE_MANAGE__ house')
            ->field('period.id as pid,period.create_time,house.pksetid as pkid,house.id as houseid')
//            ->where('period.state = 0 AND period.iscommon = 2 AND period.id = house.periodid AND house.ispublic=1 AND period.create_time <= '.(time()-C('ROOM_OPEN_TIME')))  // 259200 =  3 * 24 * 60 * 60
            ->where('period.state = 0 AND period.iscommon = 2 AND period.id = house.periodid AND house.ispublic=1 ')  // 259200 =  3 * 24 * 60 * 60
            ->order('house.create_time')
            ->select();
        if ( $pklist ) {
            foreach ( $pklist as $k => $v ) {
                \Think\Log::record('房间ID['.$v['houseid'].']解散开始','INFO');
                $update['state'] = 3;
                $result = M('ShopPeriod')->where('id=' . $v['pid'])->save($update);
                if ( $result ) {
                    //pk商品库存增加1
                    M('pkconfig')->where('id=' . $v['pkid'])->setInc('inventory');

                    //修改这个房间isresolving状态为1
                    M('house_manage')->where('id=' . $v['houseid'])->setField('isresolving',1);

                    $recordlist = M('shop_record')->field('order_id')->where('pid=' . $v['pid'])->select();
                    if ( $recordlist ) {
                        foreach ( $recordlist as $k1 => $v1 ) {
                            $res = M()->table('__SHOP_ORDER__ s ,__USER__ u')->field('s.uid,u.username,s.gold,s.cash,u.phone')->where(' s.uid = u.id')->where(array('s.order_id'=>$v1['order_id']))->find();
                            if ( $res ) {
                                //退还金币
                                //更新新用户金币
                                $add_black = $res['gold'] + $res['cash'];
                                $rs_black = M('user')->where('id=' . $res['uid'])->setInc('black', $add_black);
                                //增加返还金币记录
                                $data['uid'] = $res['uid'];
                                $data['typeid'] = 16;
                                $data['gold'] = $add_black;
                                $data['create_time'] = time();
                                $data['remark'] = '{"所属房间house_manage-id":"' . $v['houseid'] . '","商品pkid":"' . $v['pkid'] . '","用户id":"' . $res['uid'] . '","用户名":"' . $res['username'] . '","电话":'. $res['phone']. ' }';
                                $data['pid'] = $v['pid'];
                                $rs_gold = M('gold_record')->add($data);

                                \Think\Log::record('房间ID['.$v['houseid'].']解散,信息['.json_encode($data).'],退换金币处理返回值 增加金币result=>'.$rs_black.' 保存金币明细result=>'.$rs_gold.';','INFO');                            }
                        }
                    }
                }
                \Think\Log::record('房间ID['.$v['houseid'].']解散结束','INFO');
                \Think\Log::save();
            }
        }

    }

    public function recordLog($data, $msg = ''){
        recordLog($data,$msg);
    }

    public function getEndTime(){
        echo 'time=>'.time_format(time());

//        $pictures = M('picture')->field('path')->where(array('id'=>array('in', $coverid)))->select();
//        var_dump($pictures);
//        M('house_manage')->where('id=' . $houseid)->setField(array('isresolving'=>1,'end_time'=>time()));
//        $end_time = time() - (empty(C('ROOM_OPEN_TIME'))? 86400 * getPKValid():C('ROOM_OPEN_TIME'));
//        echo 'end_time =>' .$end_time;
    }

    public function getConfig($key){
        $_key = explode(',',$key);
        foreach ($_key as $k =>$v  ) {
            echo  'key'.$k.'=>'.$v.' value=>'.(string)C($v).' <= <br/>';

        }
    }

    public function testOpen(){

        $pid =  '411';
        $winNumber = 123124;

        D('period')->execLottery($pid,$winNumber);
//        D('period')->getKaijiang();
//        D('period')->lottery($pid,$winNumber);
    }

    public function getGoldprice(){
        $data = getGoldprice();
        var_dump($data);
    }

    public function preOrder($uid = '123'){
        $data = D('pay')->preOrder($uid);
        var_dump($data);
    }

    public function pay($uid = '123'){

//        $pid, $sid = 0, $price, $uid, $type, $gold_do,$md5_gp
        $data = D('pay')->pay($uid);
        var_dump($data);
    }

    /**
     * 手动开奖
     * @param int $delay
     */
    public function manualLottery($delay =  60 ){
        if(isHostProduct()){
            echo ' isHostProduct  not allow manual lottery! ';
        }else{
            $data = M('shop_period')->where('state = 0 ')->setField('kaijang_time',time()+$delay);
            var_dump($data);
            echo 'manual lottery exec success ! ';
        }
    }

    public function getPeriodInfo(){
        $data = D('api/Period')->periodInfo();
        var_dump($data);
    }
}