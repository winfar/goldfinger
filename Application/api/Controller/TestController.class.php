<?php
namespace api\Controller;


use Think\Controller;
use Think\Storage;

class TestController extends BaseController
{
    //JPush
    protected $app_key = '1332f6f2c55665e4010fae13';// '06c61e785e5745ff9c52c751';
    protected $master_secret = '22bef63563b88d158c3d861e';//'c6dad8a64ebfe1d19c8726ce';

    protected function _initialize()
    {
        
        //parent::_initialize(); 
        vendor("JPush.JPush");

        vendor("Ks3ClientInfo.Ks3Client#class");
        vendor("Ks3ClientInfo.Utils#class");
    }

    public function testException(){
        try{
            throw new \Exception("test exception");
            returnJson($a,200,"success");
        }
        catch(\Exception $e){
            returnJson($e->getMessage(),500,"error");
        }
    }

 public function FunctionName($value='')
{
    echo "sfsdf";

    echo "hello";
}
    protected function getHttpHeader()
    {

        $headers = array();
        foreach ( $_SERVER as $key => $value ) {
            if ( 'HTTP_' == substr($key, 0, 5) ) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }

        return $headers;
    }

    public function isLogin($token)
    {
        $r = isLogin($token);
        if ( $r ) {
            echo var_dump($r);
        } else {
            echo var_dump($r);
        }
    }

    public function time()
    {
        $time = microtime();

        echo $time . '<BR />';

        $time = NOW_TIME;

        echo $time;
    }

    public function notificationByIos()
    {
        $msg['shopName'] = '北鼎养生壶';
        $msg['no'] = 100035;
        D('Notification')->JpushIos('13165ffa4e0e547f90e', $msg);
    }

    //$regIdArr=array(), $title='', $content='', $productionEnv=false, $extras=array()
    public function pushNotification(){

        try{
            $result = @file_get_contents("php://input");
            $json = json_decode($result, true);

            if($json['platform'] == 'ios' || $json['platform'] == 'android')
            {
                if(count($json['regIdArr'])>0){
                    $result = D("Notification")->pushNotification($json['platform'], $json['regIdArr'], $json['title'], $json['content'], $json['productionEnv'], $json['extras'], $json['debug']);

                    if($result){
                        returnJson($result,200,'success');
                    }else {
                        returnJson($result,410,'error');
                    }
                }
                else {
                    returnJson('',402,'regIdArr参数错误');
                }
            }
            else {
                returnJson('',401,'platform参数错误');
            }
        }catch(\Exception $e){
            returnJson($e,500,$e->getMessage());
        }
	}

    public function sendSms(){
        $phone = I('phone');
        // session_write_close();
        // ignore_user_abort();
        set_time_limit(30);
        sleep(5);
        D('Notification')->winningSendMobile(I('phone'),'50元卡');
    }

    public function testfoo($phone)
    {
        post_str('http://local.oneshop.busonline.com/api.php?s=/Cron/sendSms/', array("phone"=>$phone,"name"=>"100元电信充值卡"),true);
        //D("Pay")->recharge(101900,1111111111111,1,2,1,22222222222222222);
        //echo strlen('华为P青春版 联通移动双4G手机 颜色随机 2015年华为旗舰产品');
        //echo date("Y-m-d h:i:s",  floor(1470410649990 * 1000000));

        // $year = date("Y");
        // $month = date("m");
        // $day = date("d");
        // $start = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
        // $end= mktime(23,59,59,$month,$day,$year);//当天结束时间戳
        // echo date('Ymd H:i:s',$start) .'#'.date('Ymd H:i:s',$end);

        //$this->JpushAndroid('1a0018970aa776525a0','北鼎养生壶');

        // $msg['shopName']='北鼎养生壶';
        // $msg['no']=100035;
        // //D('Notification')->JpushIos('13165ffa4e0e547f90e', $msg);

        // D('Notification')->JpushAndroid('1a0018970aa776525a0', $msg);

        // D('Notification')->JpushAndroid('1104a89792a859d7fac', $msg);


        // $res = strpos(strtolower($_SERVER['REQUEST_URI']),'/test/testfoo');
        // if($res <= 0){
        // 	echo 'no';
        // }
        // else{
        // 	echo 'yes';
        // }
        // echo $res===false.'<br>';
        // echo '/api.php?s=/test/testfoo';

        //echo md5('1.6' . '09a9049e74cdf3135e84d8819e6b34e6');

        //echo (1471176000 - NOW_TIME + 10) * 1000;

        //echo var_dump($this->getHttpHeader());

        // $r = D("Message")->getListByUserId(1, 20, 101900);
        // returnJson($r, 200, 'success');

        //  $uidArr = M('shop_record')->distinct(true)->field('uid')->where('pid=1055')->order('uid')->select();
        //  $r = D('Message')->addAllUserMessage(104,1055,$uidArr);
        //  returnJson($r,200,'success');

        //$r = D('User')->luckyshow(101900,997,'晒单',array('111','222'));

        // $r = D('UserPassport')->userinfo('61ebaa03efde927d8a31a7a15b2d9cff');
        // returnJson($r);


        // $r = D("User")->displays(1, 101898,20);
        // returnJson($r,200,'success');

        // $arraylist["商品金额"]=100;
        // $arraylist["商品期号"]=100002;


        // echo var_dump($arraylist);
        

        // $r = D('UserPassport')->cellcode(18600186900);
        // returnJson($r);

        // $result = @file_get_contents("php://input");
        // $json = json_decode($result, true);
        // $base64Img = $json;

        // $rs = $this->uploadImages($base64Img);
        // echo $rs;
        // $stringss = "transdata=%7B%22appid%22%3A%223006977611%22%2C%22appuserid%22%3A%22101900%22%2C%22cporderid%22%3A%22766538623460962304%22%2C%22cpprivate%22%3A%22%22%2C%22currency%22%3A%22RMB%22%2C%22feetype%22%3A0%2C%22money%22%3A1.00%2C%22paytype%22%3A103%2C%22result%22%3A0%2C%22transid%22%3A%2232441608191533450517%22%2C%22transtime%22%3A%222016-08-19+15%3A34%3A20%22%2C%22transtype%22%3A0%2C%22waresid%22%3A1%7D&sign=Ma%2FttFP2gWnvnPz2mKhsJmerza0TNOF3Cj1E%2BooVOKW%2FnE7llnLrKsou6l8aouA2yVq%2BqwTJM4stycx6iR0J0disjoSmqPdyHVPkDQ1%2B6mEhfNGvd6cqaKD9M4cLVgc7w2nRDh0ffWRy%2FtYpgWkjUDKKRX%2FgJb8nLT06DoL2xcE%3D&signtype=RSA";
        // $stringss = urldecode($stringss);//array_map('urldecode',$stringss);
        // echo $stringss;

        //$aa="onlinetest.1.busonline.com";
        //echo $_SERVER["HTTP_HOST"];
    }

    private function uploadImages($pic)
    {
        $picpath = array();
        $thumbpicpath = array();
        foreach ( $pic['pic'] as $key => $value ) {
            $picture = $value['picture'];
            if ( preg_match('/^(data:\s*image\/(\w+);base64,)/', $picture, $result) ) {
                $type = $result[2];
                $new_file = './Picture/shared/' . uniqid() . '.' . $type;
                $new_filei = './Picture/shared/' . uniqid() . '.' . $type;
                if ( Storage::put($new_file, base64_decode(str_replace($result[1], '', $picture))) ) {
                    $picpath[] = substr($new_file, 1);
                    $thumbpicpath[] = substr($new_filei, 1);

                    img2thumb($new_file, $new_filei, $width = 170, $height = 170, $cut = 0, $proportion = 0);
                } else {
                    returnJson('', 1, '上传图片失败！');//上传图片或者文件失败
                }
            }
        }

        return array('picpath' => $picpath,
            'thumbpicpath' => $thumbpicpath);
    }

    public function info()
    {
        echo phpinfo();
        exit;
    }

    public function img()
    {
        $Verify = new \Think\Verify();
        $Verify->fontSize = 20;
        $Verify->length = 4;
        $Verify->expire = 600;
        $Verify->entry(1);
    }


    public function test()
    {
        //测试returnJson
        $array = array(
            'a' => array('1', '2', '3'),
            'b' => 'bbb',
            'c' => 'ccc'
        );
        $code = '200';
        $msg = "success";
        //	returnJson($array);

        //测试encrypt & decrypt
        $s = encrypt('zhangran', 'yiyuandoubao');
        //	echo $s."<br>";
        $d_s = decrypt('MDAwMDAwMDAwMJuensya0sqyu4tpng', 'yiyuandoubao');
        //	echo $d_s;exit;

        //测试uuid
        $uuid = uuid();
        echo $uuid;

        exit;
    }

    /* *测试**/
    public function testPush()
    {
        //添加整体极光推送
        //商品id
        //$pid  = $info['id'];
        $pid = 100052;
        //查询本期购买者
        $now_period_users = M('shop_record')->
        table('__USER__ user,__SHOP_RECORD__ record')
            ->distinct('user.passport_uid')
            ->field('user.passport_uid')
            ->where("record.pid=" . $pid . " AND user.id = record.uid")
            ->select();

        for ( $i = 0; $i < count($now_period_users); $i++ ) {
            $arr[] = $now_period_users[$i]['passport_uid'];
        }

        //多个
        //获取用户regid 与 os 用于极光推送
        //$arr = array_unique($arr);
        //$arr = array_merge($arr);
        //var_dump($arr);
        foreach ( $arr as $k => $v ) {
            $if_have_regid = M('member_device', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->where("uid=" . $v . " and regid is not null and regid !='' and os is not null")->field('regid,os')->select();

            for ( $i = 0; $i < count($if_have_regid); $i++ ) {
                if ( $if_have_regid[$i]['os'] == "iOS" ) {
                    $ios[] = $if_have_regid[$i]['regid'];
                } else {
                    $android[] = $if_have_regid[$i]['regid'];
                }
            }
        }

        $msg = array(
            'name'=>"这是一个测试",
            'no'=>112321,
			'pid'=>$pid
        );    
        //$ios = implode(",", $ios);
        //$android = implode(",", $android);
        $extras = array("type" => 2, "data" =>array("pid"=>'10001'));

        $regIdArr[] ="141fe1da9ea310e5a63";
        $regIdArr[] ="1114a89792a1c9b2bd4";
        //D('Notification')->pushNotification('ios',$regIdArr,'hello','now',true,$extras);
        $info = array(
            'shopName'=>"2元金袋",
            'no'=>100052,
			'pid'=>100052
        );
        D('Notification')->pushToPeriodComplate($pid,$info);



    }
    /* *测试**/

    //上传图片
    public function testUploadImageByUrl()
    {
        //!!第三个参数endpoint需要对应bucket所在region!! 详见http://ks3.ksyun.com/doc/api/index.html  Region（区域）一节
        //外网：ks3-cn-beijing.ksyun.com
        //内网：ks3-cn-beijing-internal.ksyun.com
        $client = new \Ks3Client("bU2WYDSmu5ZJOu+8RXv5", "zBLpY/jlAzExesOlA7gWjMcqXoASqHJyuQlrVCN+", "ks3-cn-beijing-internal.ksyun.com");

        $file = "C:\\Users\\ppa\\Desktop\\images\\BingWallpaper-2016-04-09.jpg";
        $ext = substr($file, strrpos($file, '.') + 1);//取得文件扩展
        $filename = basename($file);//取得文件名称
        if ( \Utils::chk_chinese($file) ) {
            $file = iconv('utf-8', 'gbk', $file);
        }
//		$content = $file;
        $args = array(
            "Bucket" => "picturetest",
            "Key" => $filename,
            "ACL" => "public-read",
            "ObjectMeta" => array(
                "Content-Type" => "image/" . $ext,//只传0-10字节,
            ),
            "Content" => array(
                "content" => $file,
                "seek_position" => 0
            )
        );
        $result = $client->putObjectByFile($args);
        echo json_encode($result);
    }

    //获取图片
    public function testGetImageByETag()
    {
        print_r("http://picturetest.ks3-cn-beijing.ksyun.com/19203_en_1.jpg@base@tag=imgScale&h=200&w=200&m=1");
    }

    //101911  102003
    public function test1(){
        $sql = "SELECT * FROM hx_point_record WHERE user_id = 101911  AND type_id = 102";
        $resutl = M()->query($sql);

        $sql1 = "SELECT * FROM hx_point_record WHERE user_id = 102003  AND type_id = 102";
        $resutl1 = M()->query($sql1);

        $haha = array();
        foreach($resutl as $k=>$v ){
            //$v['create_time'] = date('Y-m-d',$v['create_time']);
            $create_time= date('Y-m-d',$v['create_time']);
            foreach($resutl1 as $k=>$v ){
                //$v['create_time'] = date('Y-m-d',$v['create_time']);
                $create_time_old = date('Y-m-d',$v['create_time']);
                if($create_time_old == $create_time){
                    $haha[] = array('');
                }
            }

        }
         foreach($resutl as $k=>$v ){
            $resutl[$k]['create_time'] = date('Y-m-d',$v['create_time']);

        }
        var_dump($resutl);

        foreach($resutl1 as $k=>$v ){
                $resutl1[$k]['create_time'] = date('Y-m-d',$v['create_time']);
        }
        
        var_dump($resutl1);

         $uid_register_point_record_id = M('point_record')->where("type_id = 101 AND user_id= 102003")->getField('id');

         echo $uid_register_point_record_id;
    }



    public function test111(){
                   $uid = 101907;
                   $uid_info = M('user')->where('id='.$uid)->field(true)->find();
                    $user_record = array();
                    $user_record['id'] = $uid_info['id'];
                    $user_record['nickname'] = $uid_info['nickname'];
                    $user_record['username'] = $uid_info['username'];
                    $user_record['phone'] = $uid_info['phone'];
                    $user_record['password'] = $uid_info['password'];
                    $user_record['create_time'] = $uid_info['create_time'];
                    $user_record['black'] = $uid_info['black'];
                    $user_record['login_ip'] = $uid_info['login_ip'];
                    $user_record['login_time'] = $uid_info['login_time'];
                    $user_record['hongbao'] = $uid_info['hongbao'];
                    $user_record['total_point'] = $uid_info['total_point'];
                    $user_record['invitationid'] = $uid_info['invitationid'];
                    $user_record['channelid'] = $uid_info['channelid'];
                    $user_record['market_channel'] = $uid_info['market_channel'];
                    $user_record['passport_uid'] = $uid_info['passport_uid'];
                    
                    M('delete_user_record')->add($user_record);
                   //var_Dump($uid_info);
    }

    public function test222(){
        $subQuery = M('shop_record')->field(true)->where('uid= 101900')->order('create_time desc')->select(false);
        
        echo $subQuery;
    }
    
    public function testmz(){
//        $activity = null,$record_id = null, $user_id = null
        activity_log($name='manzeng_jl',$record_id = 220.00, $user_id = '101919');
    }



    /***
     * 生成tokenId
     * @return string
     */
    private function getTokenId()
    {
        return $tokenId = "tokenId:" . time();
    }


    public function testCheckInCount($uid){

        $map['uid'] = $uid;
        $rs =  D('CheckinRecord')->where($map)->order('check_time desc')->getField('check_time',10); //获取前10条签到记录
        $flag = false;
        foreach ($rs as $k => $v){
            if($v == date("Ymd")){
                $flag = true;
                break;
            }
        }

        $checkflag = 0;

//        $arr_checkin = D('CheckinRecord')->getCheckinCount($uid);
//        echo count($arr_checkin);
//        var_dump($arr_checkin);
    }

    public function testGetCheckinfo($uid){
        $data = D('CheckinRecord')->getCheckinfo($uid,'20161017,20161018');
        returnJson($data, 200, 'success');
    }

    public function testGoldRecord()    {
        $data['uid'] = 101900;
        $data['check_time'] = date("Ymd");
        $data['create_time'] = NOW_TIME;
        $rs = M('CheckinRecord')->add($data);
        var_dump($rs);
    }

    public function testCheckin(){
//        $rs = D('CheckinRecord')->checkin('aa6f998634ac280ac8933054da4c3b66' );

        //检查是否连续
        $uid =  101913;
        $data1['uid'] = $uid;
        $data1['check_time'] = date("Ymd");
        $data1['create_time'] = NOW_TIME;
        $rs = M('CheckinRecord')->add($data1);
    }

//    public function testCheckCount(){
//        $count = M('CheckinRecord')->where('uid = 101914 and check_time = 20161018')->count();
//        echo $count;
//        if($count >= 0){
//            //TODO 签到记录失败
//            returnJson('', 2, '您今天已经签到过！');
//        }
//    }

    public function testJson(){
        $records = array();
        $records['uid'] = 198382;
        $records['check_count'] = 3;
        for($i=0 ;$i< 5;$i++ ){
            $list[$i]['a'] = 'xxx'.$i;
            $list[$i]['b'] = 'xxx'.$i;
            $list[$i]['c'] = 'xxx'.$i;
            $list[$i]['d'] = 'xxx'.$i;
        }
        $records['items'] = $list;
        returnJson($records, 200, 'success');
    }

    public function testPCode(){
        $pidsStr = implode(',', null);
        echo $pidsStr;


        $pidsStr = implode(',','');
        echo $pidsStr;

//        echo('->'.isPCodeProc('').'</br>');
//        echo('->'.isPCodeProc(null).'</br>');
//        echo('->'.isPCodeProc('11111111').'</br>');
//        echo('->'.isPCodeProc('c285c89665589f1bdd39d7a5624e4b19').'</br>');
//        echo('->'.isPCodeProc('c285c89665589f1bdd39d7a5624e4b59').'</br>');
//        echo('->'.isPCodeProc('c285C89665589f1bdd39d7a5624e4B59').'</br>');

    }

    public function testMod(){
        $arr_checkin = array(
            20161001,20161002,20161003,20161004,20161005,20161006,20161007,
            20161008,20161009,20161010,20161011,20161012,20161013,20161014,
            20161015,20161016,20161017,20161018,20161019,20161020
        );
//        $arr_checkin = array();
//        $arr_checkin = null;
        // null= -1 , array() =-1

        $count = count($arr_checkin) % 7 ;

        $arr_checkin = array_slice($arr_checkin,-$count);

        echo count($arr_checkin)-1 ;
//        return $valid_flag ?  $arr_checkin : null;
        var_dump($arr_checkin);
    }

    public function testDate(){
         $date = (date("Ymd",strtotime("-1 day"))) ;
        echo $date;
    }

    public function testRangeDesc($uid,$isuse){
        $data  = D('RedEnvelope')->getRedEnvelopeByUid($uid,$isuse);
        returnJson($data, 200, 'success');
//        var_dump($data);
    }

    public function testGetRedEnvelopeByUid($uid,$isUse = 0){
        $rs = D('RedEnvelope')->getRedEnvelopeByUid($uid,$isUse);
        returnJson($rs, 200, 'success');
    }

    public function testFloatVal(){
        $sval='122.55343';
        echo number_format($sval,3,'.','') ;
    }

    public function testAddr(){

        $data = D('Common/shop_address')->test();
        echo $data;
    }

}