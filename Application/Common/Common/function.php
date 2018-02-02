<?php

function param_signature($method, $normalized, $secret="08DD1A44B8426B65"){

    $methodPart = strtoupper($method); //"GET" "POST"

    ksort($normalized);

    $parts = [];
    foreach ($normalized as $key => $value) {
        array_push($parts,($key.'='.$value));
    }

    $params = percentEncode(join('&',$parts));
    $baseString = $methodPart.'&'.$params;
    $key = percentEncode($secret);
    // echo ("signature:". getSignature($baseString,$key));
    //return kitx.sha1(baseString, key, 'base64');
    return getSignature($baseString,$key);
}

function percentEncode($value) {
    if($value){
        $value = urlencode($value);
        $value = str_replace("+", "%20",$value);
        $value = str_replace("*", "%2A",$value);
        $value = str_replace("%7E", "~",$value);
    }
    return $value;
}

function getSignature($str,$key){
    $signature='';
    if(function_exists('hash_hmac')){
        $signature = base64_encode(hash_hmac('sha1',$str,$key,true));
    }else{
        $blocksize = 64;
        $hashfunc = 'sha1';
        if(strlen($key) > $blocksize){
            $key = pack('H*', $hashfunc($key));
        }
        $key = str_pad($key,$blocksize,chr(0x00));
        $ipad = str_repeat(chr(0x36),$blocksize);
        $opad = str_repeat(chr(0x5c),$blocksize);

        $hmac = pack('H*', $hashfunc(($key ^ $opad). pack('H*',$hashfunc(($key ^ $ipad) . $str))));

        $signature = base64_encode($hmac);
    }
    return $signature;
}

function getNextSsc(){

    $now = NOW_TIME;//strtotime("2017-04-13 05:00:00");
    $dtime=date('H',$now);

    $issue = date('Ymd',$now);//170413060
    $s=$now-strtotime(date('Y-m-d 00:00:00',$now));
    $s10=$now-strtotime(date('Y-m-d 10:00:00',$now));
    $s22=$now-strtotime(date('Y-m-d 22:00:00',$now));
    $cnt=0;

    if($dtime>=10 && $dtime<22){
        $lottery_time=intval($now/600+1)*600;

        $cnt=intval($s10/600)+24;
    }elseif($dtime<2 || $dtime>=22){
        $lottery_time=intval($now/300+1)*300;

        if($dtime<2){
            $cnt=intval($s/300);
        }

        if($dtime>=22){
            $cnt=intval($s22/300)+24+72;
        }
    }else{
        $lottery_time=strtotime(date('Y-m-d 10:00:00',$now));

        $cnt=23;
    }

    //下一期期号
    $cnt+=1;
    $num=str_pad($cnt,3,"0",STR_PAD_LEFT); 

    $issue = substr($issue,2,strlen($issue));

    // echo 'lottery_time:'.$lottery_time;
    // echo '<br>';
    // echo 'lottery_time:'.date('Y-m-d H:i:s', $lottery_time);
    // echo '<br>';
    // echo 'issue:'.$issue.$num;

    return ['lottery_issue'=>$issue.$num,'lottery_time'=>$lottery_time,'lottery_time_format'=>date('Y-m-d H:i:s', $lottery_time),'now'=>time(),'now_format'=>date('Y-m-d H:i:s',time())];
}
//直播道具类型
function get_proptype()
{
     return array(
        array('type'=>'0','name'=>'不属于直播道具'),
        array('type'=>'1','name'=>'升级经验')
    );
}
//获取支付平台array
function get_pointarr() {
    return array(
        array('type'=>'101','name'=>'用户注册'),
        array('type'=>'102','name'=>'签到'),
        array('type'=>'103','name'=>'晒单'),
        array('type'=>'104','name'=>'交易成功'),
        array('type'=>'105','name'=>'折半'),
        array('type'=>'106','name'=>'失效'),
    );
}
function get_user_pic_passport($id){

    $user_passport_avatar = M('user')->field(true)->where('id=' . $id)->getField('headimgurl');

    return completion_pic_passport($user_passport_avatar, 2);
}
/**
 * 获取某商品最新一期的周期id
 * @param intger $sid 商品id
 */
function getLatestPeriodByShopId($sid){//Latest
    $pid = M('shop_period')->where('sid=' . $sid . ' and state=0')->order('create_time desc')->getField('id');
    return $pid;
}
function get_pointtype($type){
    foreach (get_pointarr() as $v ){
        if($v['type'] === $type){
            return $v['name'] ;
        }
    }
    return '';
//    switch ($type){
//        case 101  : return    '用户注册';     break;
//        case 102  : return    '签到';     break;
//        case 103  : return    '晒单';     break;
//        case 104  : return    '交易成功';     break;
//        case 105  : return    '折半';     break;
//        case 106  : return    '失效';     break;
//        default : return    '';      break;
//    }
}

function get_redenvelope_category($category){
    switch ($category){
        case 0  : return    '全部商品';     break;
        case 101  : return    '部分分类';     break;
        case 102  : return    '部分品牌';     break;
        case 103  : return    '实物商品';     break;
        case 104  : return    '虚拟商品';     break;
        default : return    '-';      break;
    }
}

function is_login(){
    $user = cookie('user_auth');
    if (empty($user)){
        return 0;
    } else {
        return cookie('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

function data_auth_sign($data) {
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data);
    $code = http_build_query($data);
    $sign = sha1($code);
    return $sign;
}

function think_ucenter_md5($str, $key = 'busonlinepassport'){
	return '' === $str ? '' : md5(sha1($str) . $key);
}

function think_admin_md5($str, $key = 'HXyiyuanhuanlego'){
	return '' === $str ? '' : md5(sha1($str) . $key);
}

/**
 *  系统非常规MD5加密方法
 * @author zhangran
 * @date 2016-07-07
 * @param  string $str 要加密的字符串
 * @return string
 */
function think_md5($str, $key = 'ThinkUCenter'){
    return '' === $str ? '' : md5($str.$key);
}

function think_app_md5($str, $key = '0123456789abcdef'){
	return '' === $str ? '' : sha1($str);
}

function check_verify($code, $id = 1){
    ob_clean();
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

function config_lists(){
	$data   = M('Config')->field('type,name,value')->select();
	$config = array();
	if($data && is_array($data)){
		foreach ($data as $value) {
			$config[$value['name']] = config_parse($value['type'], $value['value']);
		}
	}
	return $config;
}


function config_parse($type, $value){
	switch ($type) {
		case 3:
			$array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
			if(strpos($value,':')){
				$value  = array();
				foreach ($array as $val) {
					list($k, $v) = explode(':', $val);
					$value[$k]   = $v;
				}
			}else{
				$value =    $array;
			}
			break;
	}
	return $value;
}

function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    $tree = array();
    if(is_array($list)) {
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

function tree_to_list($tree, $child = '_child', $order='id', &$list = array()){
    if(is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if(isset($reffer[$child])){
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby='asc');
    }
    return $list;
}

function time_format($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? NOW_TIME : intval($time);
    //  return date($format, $time);
    if($time>0){
        return date($format, $time);
    }else{
        return "";
    }
}

function time_formats($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? 0 : intval($time);
    //  return date($format, $time);
    if($time>0){
        return date($format, $time);
    }else{
        return "";
    }
}

//获取年月日时分秒毫秒
function get_timestamp() {
    $datetime = date("Y-m-d H:i:s");
    $datetime = preg_replace('/\s|:|-/','',$datetime);

    $microtime = get_millisecond();
    $timestamp = $datetime.$microtime;

    return $timestamp;
}

function get_millisecond()  
{  
    list($usec, $sec) = explode(" ", microtime());  
    
    $msec=round($usec*1000);  
    if(strlen($msec) == 2) {
    $msec = '0'.$msec;
    } else if(strlen($msec) == 1) {
    $msec = '00'.$msec;
    } else if(strlen($msec) == 0){
    $msec = '000';
    }
    return $msec;  
} 

/* 毫秒时间戳转换成日期 */
function msecdate($tag, $time)
{
    $a = substr($time,0,10);
//    $b = substr($time,10);
    $date = date($tag,$a);
   return $date;
}

function url_change($model,$params,$createl=false){
	unset($params['name']);
	$reurl = U($model,$params);
	return $reurl;
}


function get_cover($cover_id, $field = null){
    if(empty($cover_id)){
        return completion_pic('');
    }
    //$picture = M('Picture')->where(array('status'=>1))->getById($cover_id);
    $picture = M('Picture')->where(array('status'=>1,'id'=>array('in',$cover_id)))->order('imageorder asc,id asc')->field('path')->find();
    $picture['path'] = completion_pic($picture['path']);
    return empty($field) ? $picture : $picture[$field];
}

function completion_pic($url){
    if (empty($url)) {
        return C("WEB_URL").C('TMPL_PATH').'/Shop/images/mr.jpg';
    } else {
       if(strpos($url,"http://") === 0){
            return $url;
        }else{
            return C("WEB_URL").$url;
        } 
    }
}

/**
 * 头像处理
 * 
 * @param  [type]  $url  [description]
 * @param  integer $type  1用户头像 2商品图片
 * @return [type]        [description]
 */
function completion_pic_passport($url){
    if (empty($url)) {
        return C("WEB_URL").C('TMPL_PATH').'/Shop/images/man.png';
    } else {
       if(strpos($url,"http") === 0){
            return $url;
        }else{
            if (strpos($url,"/") !== 0) {
                $url = '/' . $url;
            }
            //201607121 替换图片资源域名 wenyuan update begin
            return C("WEB_URL").$url;
            //original
            //return C("WEB_URL").$url;
            //20160721 wenyuan update end
        } 
    }
    
}

function get_category_name($cid = 0){
    $info = M('Category')->field('title')->find($cid);
    if($info !== false && $info['title'] ){
        $name = $info['title'];
    } else {
        $name = '';
    }
    return $name;
}

function get_ten_unit($cid = 0){
    $info = M('Ten')->field('unit')->find($cid);
    return $info['unit'];
}

function get_ten_name($cid = 0){
    $info = M('Ten')->field('title')->find($cid);
    if($info !== false && $info['title'] ){
        $name = $info['title'];
    } else {
        $name = '';
    }
    return $name;
}

if(!function_exists('array_column')){
    function array_column(array $input, $columnKey, $indexKey = null) {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

function jiang_num($num){
    recordLog('jiang_num处理=>'.$num,'商品编辑');
    $numbers = range(10000001,$num+10000001);
    recordLog('shuffle处理=>numbers'.$num,'商品编辑');
    try {
        shuffle($numbers);
    } catch (Exception $e) {
        recordLog('进行shuffle处理发生异常=>'.$e->getMessage(),'商品编辑');
        recordLog('进行shuffle处理发生异常=>'.$e->getTraceAsString(),'商品编辑');
    }
    recordLog('jiang_num完成shuffle处理','商品编辑');
    return implode(',',$numbers);
}

function get_shop_name($id){
    return M('Shop')->where('id='.$id)->getField('name');
}

function get_user_name($id){
    $name = M('User')->where('id='.$id)->getField('nickname');
    // $end_name = $name;
    // $number_length = mb_strlen($name, 'utf8');//长度
    // if (is_numeric($name)) {//全是数字
    //     if ($number_length==11) {
    //         $end_name = substr_replace($name, '****', 3, 4);
    //     } elseif ($number_length>11) {
    //         $end_name = mb_substr($name, 0, 11, 'utf8').'...';
    //     }
    // } elseif (preg_match("/^[a-zA-Z\s]+$/",$name)) {//全部为字母
    //     if ($number_length>11) {
    //         $end_name = mb_substr($name, 0, 11, 'utf8').'...';
    //     }
    // } elseif (ctype_alnum($name)) {//字母和数字混编
    //     if ($number_length>11) {
    //         $end_name = mb_substr($name, 0, 11, 'utf8').'...';
    //     }
    // } else {
    //     if (preg_match("/^[0-9-*]*$/", $name) and $number_length==11) {
    //         $end_name = $name;
    //     } else {
    //         if ($number_length>5) {
    //             $end_name = mb_substr($name, 0, 4, 'utf8').'...';
    //         }
    //     }
    // }    

    return $name;
}
function user_name_change($name)
{
    $number_length = mb_strlen($name, 'utf8');//长度
    $end_name = $name;
    if (is_numeric($name)) {//全是数字
        if ($number_length==11) {
            $end_name = substr_replace($name, '****', 3, 4);
        } elseif ($number_length>11) {
            $end_name = mb_substr($name, 0, 11, 'utf8').'...';
        }
    } elseif (preg_match("/^[a-zA-Z\s]+$/",$name)) {//全部为字母
        if ($number_length>11) {
            $end_name = mb_substr($name, 0, 11, 'utf8').'...';
        }
    } elseif (ctype_alnum($name)) {//字母和数字混编
        if ($number_length>11) {
            $end_name = mb_substr($name, 0, 11, 'utf8').'...';
        }
    } else {
        if (preg_match("/^[0-9-*]*$/", $name) and $number_length==11) {
            $end_name = $name;
        } else {
            if ($number_length>5) {
                $end_name = mb_substr($name, 0, 4, 'utf8').'...';
            }
        }
    }    
    return $end_name;
}

function get_user_pic($id){
    return completion_pic(M('User')->where('id='.$id)->getField('headimgurl'));
}

function sendMail($to, $title, $content){
    import('Com.PHPMailer.PHPMailerAutoload');
    $mail = new \PHPMailer();
    $mail->IsSMTP(); // 启用SMTP
    $mail->Host=C('MAIL_HOST'); //smtp服务器的名称（这里以QQ邮箱为例）
    $mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
    $mail->Username = C('MAIL_USERNAME'); //你的邮箱名
    $mail->Password = C('MAIL_PASSWORD') ; //邮箱密码
    $mail->From = C('MAIL_FROM'); //发件人地址（也就是你的邮箱地址）
    $mail->FromName = C('MAIL_FROMNAME'); //发件人姓名
    $mail->AddAddress($to,"尊敬的客户");
    $mail->WordWrap = 50; //设置每行字符长度
    $mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
    $mail->CharSet=C('MAIL_CHARSET'); //设置邮件编码
    $mail->Subject =$title; //邮件主题
    $mail->Body = $content; //邮件内容
    $mail->AltBody = "这是一个纯文本的身体在非营利的HTML电子邮件客户端"; //邮件正文不支持HTML的备用显示
    return($mail->Send());
}

function activity($type,$record_id = null, $user_id = null){
    $activity=M('Activity')->field('name')->where('type='.$type)->select();
    foreach((array)$activity as $value){
        activity_log($value['name'],$record_id,$user_id);
    }
}

function activity_log($activity = null,$record_id = null, $user_id = null){
    if(empty($activity) || empty($record_id)){
        return '参数不能为空';
    }
    $activity_info = M('Activity')->getByName($activity);
    if($activity_info['status'] != 1){
        return '该活动被禁用或删除';
    }

    $data['type']      =   $activity_info['type'];
    $data['activity_id']      =   $activity_info['id'];
    $data['user_id']        =   $user_id;
    $data['activity_ip']      =   ip2long(get_client_ip());
    $data['record_id']      =   $record_id;
    $data['create_time']    =   NOW_TIME;

    if(!empty($activity_info['log'])){
        if(preg_match_all('/\[(\S+?)\]/', $activity_info['log'], $match)){
            $log['user']    =   $user_id;
            $log['record']  =   $record_id;
            $log['time']    =   NOW_TIME;
            $log['data']    =   array('user'=>$user_id,'record'=>$record_id,'time'=>NOW_TIME);
            foreach ($match[1] as $value){
                $price = explode('=', $value);
                if(isset($price[1])){
                    $prices = explode('|', $price[1]);
                    if(isset($prices[1])){
                        $data[$price[0]] = call_user_func($prices[1],$log[$prices[0]]);
                    }else{
                        $data[$price[0]] = is_numeric($price[1])?$price[1]:$log[$price[1]];
                    }
                }else{
                    $param = explode('|', $value);
                    if(isset($param[1])){
                        $replace[] = call_user_func($param[1],$log[$param[0]]);
                    }else{
                        $replace[] = $log[$param[0]];
                    }
                }
            }
            $data['remark'] =   str_replace($match[0], $replace, $activity_info['log']);
        }else{
            $data['remark'] =   $activity_info['log'];
        }
    }else{
        $data['remark']     =   '操作url：'.$_SERVER['REQUEST_URI'];
    }

    if(!empty($activity_info['rule']) && $activity_info['end_time']>=NOW_TIME){
        $rules = parse_activity($activity, $user_id,$record_id);
        $res = execute_activity($rules, $activity_info['id'], $user_id);
        if($res){
             M('ActivityLog')->add($data);
        }
    }
}


function parse_activity($activity = null,$self,$relf){
    if(empty($activity)){
        return false;
    }
    if(is_numeric($activity)){
        $map = array('id'=>$activity);
    }else{
        $map = array('name'=>$activity);
    }

    $info = M('Activity')->where($map)->find();
    if(!$info || $info['status'] != 1){
        return false;
    }

    $rules = $info['rule'];
    $rules = str_replace(array('{$self}','{$relf}'), array($self,$relf), $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key=>&$rule){
        $rule = explode('|', $rule);
        foreach ($rule as $k=>$fields){
            $field = empty($fields) ? array() : explode(':', $fields);
            if(!empty($field)){
                $return[$key][$field[0]] = $field[1];
            }
        }
        if(!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])){
            unset($return[$key]['cycle'],$return[$key]['max']);
        }
    }
    return $return;
}

function execute_activity($rules = false, $activity_id = null, $user_id = null){
    if(!$rules || empty($activity_id) || empty($user_id)){
        return false;
    }

    $return = true;
    foreach ($rules as $rule){
        $map = array('activity_id'=>$activity_id, 'user_id'=>$user_id);
        if($rule['ip']){
            $map['activity_ip'] = ip2long(get_client_ip());
        }
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActivityLog')->where($map)->count();
        if($exec_count >= $rule['max']){
            $return = false;
            continue;
        }
        $Model = M(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));
        if(!$res){
            $return = false;
        }
    }
    return $return;
}

function activity_mod($price){
    return floor($price/100)*5;
}

function union_price($price,$buy_price,$num){
    return (float)substr(sprintf("%.3f",($price-$buy_price)*($num/$price)/2),0,-1);
}

/**
 * 检查$pos(推荐位的值)是否包含指定推荐位$contain
 * @param number $pos 推荐位的值
 * @param number $contain 指定推荐位
 * @return boolean true 包含 ， false 不包含
 */
function check_document_position($pos = 0, $contain = 0){
    if(empty($pos) || empty($contain)){
        return false;
    }

    //将两个参数进行按位与运算，不为0则表示$contain属于$pos
    $res = $pos & $contain;
    if($res !== 0){
        return true;
    }else{
        return false;
    }
}

function check_document_pkconfig($value,$val){
    if($value==3){
        return true;
    }else if($val==$value){
        return true;
    }else{
        return false;
    }
}

function check_document_iscreatehouse($value,$val){
 if($val==$value){
        return true;
    }else{
        return false;
    }
}

/**
 * 通过type类型id获取支付平台
 * @param $type 支付类型id
 * @return bool
 */
function get_recharge($type) {
    foreach (get_payarr() as $k => $v){
        if($v['type'] === $type ){
            return $v['name'];
        } elseif ($v['name'] === $type ) {
            return $v['type'];
        }

    }
    return false;
}


//获取支付平台array
function get_payarr() {
    return array(
        array('type'=>'1','name'=>'金币'),
        array('type'=>'2','name'=>'ping++微信'),
        array('type'=>'3','name'=>'ping++支付宝'),
        array('type'=>'4','name'=>'盛付通微信'),
        array('type'=>'255','name'=>'易宝网页'),
        array('type'=>'10001','name'=>'充值卡'),
        array('type'=>'10002','name'=>'游戏点卡'),
        array('type'=>'10004','name'=>'银行卡'),
        array('type'=>'10103','name'=>'京东支付'),
        array('type'=>'10401','name'=>'爱贝支付宝'),
        array('type'=>'10402','name'=>'财付通'),
        array('type'=>'10501','name'=>'支付宝网页'),
        array('type'=>'10502','name'=>'财付通网页'),
        array('type'=>'10403','name'=>'爱贝微信'),
        array('type'=>'10005','name'=>'爱贝币'),
        array('type'=>'10006','name'=>'爱贝一键支付'),
        array('type'=>'10016','name'=>'百度钱包'),
        array('type'=>'10030','name'=>'移动话费'),
        array('type'=>'10031','name'=>'联通话费'),
        array('type'=>'10032','name'=>'电信话费'),
        );
}

//获取支付平台array
function get_tradeTypearr() {
    $model = M('trade_type');
    $data = $model->field('id,code,name')->select();
    return $data;
}

//获取支付平台array
function get_activityType() {
    return array(
//        1=大转盘 2=牛气冲天 3=系统赠送
        array('activity_type'=>'1','name'=>'大转盘'),
        array('activity_type'=>'2','name'=>'牛气冲天'),
        array('activity_type'=>'3','name'=>'系统赠送'),
    );
}

function get_activityTypeName($activity_type) {
    foreach (get_activityType() as $k => $v){
        if($v['activity_type'] == $activity_type){
            return $v['name'];
        }
    }
    return   '-';
}

/**
 * 记录LOG日志(支持数组与字符串记录)
 * @param  $data log数据,$msg 记录业务说明
 * @return 无
 **/
function recordLog($data, $msg = '')
{
    if ( !$data ) return;
    if ( is_array($data) ) {
        $data = print_r($data, true);
    }
    \Think\Log::record(date('Y-m-d H:i:s') . "--" . time() .'--'. $msg . "--:" . print_r($data, true), "INFO",true);
    \Think\Log::save();
}

function isHostProduct(){
    return $_SERVER['HTTP_HOST']=='www.molijinbei.com';
}

function isHostTest(){
    return $_SERVER['HTTP_HOST']=='test.goldfinger.molijinbei.com' || $_SERVER['HTTP_HOST']=='local.goldfinger.molijinbei.com' || $_SERVER['HTTP_HOST']=='127.0.0.1';
}

function isHostOnlineTest(){
    return $_SERVER['HTTP_HOST']=='onlinetest.goldfinger.busonline.cn';
}

function getHost(){
    switch ($_SERVER['HTTP_HOST']) {
        case 'www.molijinbei.com':
            return 'product';
            break;
        case 'onlinetest.oneshop.busonline.cn':
            return 'onlinetest';
            break;
        default:
            return 'test';
            break;
    }
}

//促销活动类型列表
function getSaleArr()
{
    return array(
        // array('type'=>'1','name'=>'满减'),
        array('type'=>'2','name'=>'满赠'),
        array('type'=>'3','name'=>'注册'),
    );
}
//获取促销活动类型名称
function getSaleName($type)
{
    switch ($type){
        case 0  : return    '全部类型';     break;
        // case 1  : return    '满减';     break;
        case 2  : return    '满赠';     break;
        case 3  : return    '注册';     break;
        default : return    '-';      break;
    }
}
//促销活动范围列表
function getSaleRangeArr()
{
    return array(
        array('type'=>'0','name'=>'无限制'),
        array('type'=>'1','name'=>'分类限制'),
        array('type'=>'2','name'=>'品牌限制'),
        array('type'=>'3','name'=>'商品限制'),
    );
}
//获取促销活动范围名称
function getSaleRangeName($type)
{
    switch ($type){
        case 0  : return    '无限制';     break;
        case 1  : return    '分类限制';     break;
        case 2  : return    '品牌限制';     break;
        case 3  : return    '商品限制';     break;
        default : return    '-';      break;
    }
}

//获取登录方式
function getLoginTypeList($type)
{
    return array(
        array('type'=>100,'name'=>'用户名'),
        array('type'=>101,'name'=>'手机'),
        array('type'=>201,'name'=>'微信'),
        array('type'=>202,'name'=>'QQ'),
        array('type'=>401,'name'=>'游客')
    );
}

//获取登录方式
function getLoginType($type)
{
    switch ($type){
        case 100  : return '用户名';   break;
        case 101  : return '手机';    break;
        case 201  : return '微信';    break;
        case 202  : return 'QQ';    break;
        case 401  : return '游客';    break;
        default   : return '-';     break;
    }
}

function getLoginTypeByPassportUid($passport_uid){
    //5ab42ed908c353d32f3a37556a09e25c  1.7.58
    $opts = array(
        'http'=>array(
            //'method'=>"GET",
            'header'=>"APPVERSION: 5ab42ed908c353d32f3a37556a09e25c\r\n"
        )
    );

    $context = stream_context_create($opts);

    $host = $_SERVER['HTTP_HOST'];
    if(getHost() == 'test'){
        $host = 'test.passport.busonline.com';
    }
    else if(getHost() == 'onlinetest'){
        $host = 'onlinetest.passport.busonline.com';
    }
    else{
        $host = 'passport.busonline.com';
    }

    $file_contents = file_get_contents("http://".$host."/wapapi.php?s=/Usertest/loginWay&uid=$passport_uid", null, $context);
    $value = (json_decode($file_contents,true));

    if($value['code']==200){
        return getLoginType($value['data']);
    }
    else{
        return '-';
    }
}
/**
 * 获取某商品期的限制
 * @param intger $id 限制条件id，对应数据库ten表内容
 */
function getRestrictions($id){
    $restrictions = M('ten')->field('id,title,unit,restrictions,restrictions_num,iconpath')->where(array('id' => $id, 'status' => 1))->find();
    $restrictions['iconpath'] = completion_pic($restrictions['iconpath']);
    return $restrictions;
}

/**
 * 获取某商品周期的限制
 * @param intger $pid 周期id
 */
function getUnit($pid){
    if ( $pid > 0 ) {
        $info = M('shop_period')->table('__SHOP__ shop,__SHOP_PERIOD__ period')->field('shop.price,shop.ten,period.number')->where('shop.id=period.sid and period.id=' . intval($pid))->find();
        $ten = M('ten')->where(array('id' => $info["ten"], 'status' => 1))->find();
        $unit = $info["ten"] ? $ten['unit'] : 1;
        return $unit;
    } else {
        return 1;
    }
}
/**
 * api调用输出JSON数据
 * @author zhangran
 * @date   2016-07-01
 * @param $data 返回数据、$code 返回码, $msg 返回信息
 * @return json
 **/
function returnJson($data=array(), $code = 200, $msg = 'success')
{
    if($code>1500){
        recordLog($code,$msg);
    }
    
    header('Content-Type:application/json; charset=utf-8');
    if($data === false || $data==''){
        $data = null ;
    }
    
    $r = array(
        'data' => $data,
        'code' => $code,
        'msg' => $msg,
        'url' => $_SERVER['REQUEST_URI'],
        'ip'=> getIP(),
        'response_time' => time()
    );
    exit(json_encode($r));
}
/**
 *  获取IP
 * @author zhangran
 * @date 2016-07-01
 * @return string
 */
function getIP()
{
    if ( getenv("HTTP_CLIENT_IP") )
        $ip = getenv("HTTP_CLIENT_IP");
    else if ( getenv("HTTP_X_FORWARDED_FOR") )
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if ( getenv("REMOTE_ADDR") )
        $ip = getenv("REMOTE_ADDR");
    else $ip = "127.0.0.1";
    return $ip;
}

/*
 * post method
 */
function post($url, $param=array()){
    if(!is_array($param)){
        throw new Exception("参数必须为array");
    }
    $httph =curl_init($url);
    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
    curl_setopt($httph, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($httph, CURLOPT_POSTFIELDS, $param);
    curl_setopt($httph, CURLOPT_RETURNTRANSFER,1);
//    curl_setopt($httph, CURLOPT_HEADER,1);
//    $rst=curl_exec($httph);

    $response = curl_exec($httph);
    $httpCode = curl_getinfo($httph, CURLINFO_HTTP_CODE);

//    $res=json_encode($response);

    return array('code'=>$httpCode, 'data'=> $response);
//    curl_close($httph);
//
//    $res=json_decode($rst);
//    return $res;
}

/*
 * post method 通过拼装的方式  如：app=request&version=beta
 */
function post_str($url, $param=array(),$timeout=10){

    if(!is_array($param)){
        throw new Exception("参数必须为array");
    }

    $flag = false;
    $post_str = '';
    foreach ($param as $k=>$v) {
        if(!$flag){
            $flag = true;
            $post_str = $k.'='.$v;
            continue;
        }
        $post_str .= '&'.$k.'='.$v;
    }

    //TODO 记录接口访问日志
    $log_id = M('accessdo_log')->add(array('method'=>'post','url'=>$url,'request'=>json_encode($param),'create_time'=>time()));

    $httph = curl_init();
    curl_setopt($httph, CURLOPT_URL, $url);
    curl_setopt($httph, CURLOPT_POSTFIELDS, $post_str);
    curl_setopt($httph, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($httph, CURLOPT_USERAGENT, "busonline.com's CURL beta");

    if($timeout){
        curl_setopt($httph, CURLOPT_TIMEOUT, $timeout);
    }

    $response = curl_exec($httph);
    $httpCode = curl_getinfo($httph, CURLINFO_HTTP_CODE);
    curl_close($httph);


    $result = array('code'=>$httpCode, 'data'=> $response); 

    //TODO 更新接口访问日志
    M('accessdo_log')->where('id='.$log_id)->save(array('response'=>$result['data'],'code'=>$result['code'],'update_time'=>time()));

    return $result;
}

function get($url) {
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_TIMEOUT,500);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($curl,CURLOPT_URL,$url);
    
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}

/**
 * 调用游戏后台，获取钻石的统计信息
 * 数据如下：
 * "钻石兑换金额(金币兑换钻石）"
 * "兑换人数(金币兑换钻石）"
 * 新用户兑换人数
 * 老用户兑换人数
 * 钻石平均兑换量（钻石兑换金额/兑换人数）
 */
function getStatisticOperatingData($startDate,$endDate){
    if(isHostProduct()){
        $url = "http://service.busonline.com/backend/web/index.php?r=running/statisticsuser&beginTime=".$startDate."&endTime=".$endDate;
    }else{
        $url = "http://onlinetest.service.busonline.cn/backend/web/index.php?r=running/statisticsuser&beginTime=".$startDate."&endTime=".$endDate;
    }
    return get($url);
}

/**
 * 获取下一日期  日期格式20170312
 * @param $s_date
 */
function getNextDate($s_date){
    return date('Ymd',strtotime($s_date.' +1 day'));
}

function getGoldprice(){
    $rs_data = M('gold_price')->order('create_time desc')->find();
    $curr_time = date("His");  //当前时间转日期
    $gold_price = $rs_data['gold_price'];
    // 早于9点晚于15点皆取收盘金价
    if($curr_time < 60000 || $curr_time > 160000 ){
        return $gold_price;
    }

//    //正常取金价频率5分钟
//    if( !empty( $rs_data['create_time']) && (time() - $rs_data['create_time']) < 300 ){
//        return $gold_price;
//    }

    $url = "http://web.juhe.cn:8080/finance/gold/shgold?v=1&key=e1e4f5af9274fce32382d8caed17d19c";
    $data = get($url);
    $jdata = json_decode($data,true);
    if(!empty($jdata['result'][0]['Au99.99']['latestpri'])){
        $gold_price = $jdata['result'][0]['Au99.99']['latestpri'];
    }
    if( (!empty( $rs_data['create_time']) && (time() - $rs_data['create_time']) > 60 ) || $rs_data['gold_price'] != $gold_price){
        M('gold_price')->add(array('jdata'=>$data,'gold_price'=>$gold_price,'create_time'=>time()));
    }
    return $gold_price;
}
/**
 * 获取当前时间的期号及开奖时间
 * */
function getSSCIssue(){
    $now_time =  NOW_TIME;
    $dtime=date('His',$now_time);
    $pre_issue = date('ymd',$now_time);
    $map['opentime'] = array('GT',$dtime + 100 );
    $data = M('3kj_record')->where($map)->order('suffix_issue')->limit(1)->select();
    $arr_SSCIssue = array();
    if($data){ 

        $arr_SSCIssue['opentime'] = strtotime($data[0]['opentime']); //转时间戳
        $suffix_issue =  str_pad($data[0]['suffix_issue'],3,'0',STR_PAD_LEFT);
        $arr_SSCIssue['issue'] = $pre_issue.$suffix_issue;
    }
    return $arr_SSCIssue;
}

/**
 * 获取开奖时间
 * @param $time
 * @return int
 */
function kjtime($time=NOW_TIME){

    $dtime=date('H',$time);
    if($dtime>=10 && $dtime<22){
        $kj_time=intval(NOW_TIME/600+1)*600;
    }elseif($dtime<2 || $dtime>=22){
        $kj_time=intval(NOW_TIME/300+1)*300;
    }else{
        $kj_time=strtotime(date('Y-m-d 10:00:00',NOW_TIME));
    }

    return $kj_time;

}
