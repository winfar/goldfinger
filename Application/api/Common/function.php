<?php

///**
// * 通过type类型id获取支付平台
// * @param $type 支付类型id
// * @return bool
// */
//function get_recharge($type) {
//    foreach (get_payarr() as $k => $v){
//        if($v['type'] === $type ){
//            return $v['name'];
//        }
//    }
//    return false;
//}


////获取支付平台array
//function get_payarr() {
//    return array(
//        array('type'=>'1','name'=>'金币'),
//        array('type'=>'2','name'=>'ping++微信'),
//        array('type'=>'3','name'=>'ping++支付宝'),
//        array('type'=>'255','name'=>'易宝网页'),
//        array('type'=>'10001','name'=>'充值卡'),
//        array('type'=>'10002','name'=>'游戏点卡'),
//        array('type'=>'10004','name'=>'银行卡'),
//        array('type'=>'10401','name'=>'支付宝'),
//        array('type'=>'10402','name'=>'财付通'),
//        array('type'=>'10501','name'=>'支付宝网页'),
//        array('type'=>'10502','name'=>'财付通网页'),
//        array('type'=>'10403','name'=>'微信支付'),
//        array('type'=>'10005','name'=>'爱贝币'),
//        array('type'=>'10006','name'=>'爱贝一键支付'),
//        array('type'=>'10016','name'=>'百度钱包'),
//        array('type'=>'10030','name'=>'移动话费'),
//        array('type'=>'10031','name'=>'联通话费'),
//        array('type'=>'10032','name'=>'电信话费'),
//    );
//}

/**
 *  开奖倒计时--参考PY生成机制
 * @author zhangran
 * @date 2016-07-06
 **/
function kj_djs(){
	if(C('KJ_THIRD_PARTY')!=1){
		$kj_time=intval(NOW_TIME/300+1)*300+50;
	}else{
		$dtime=date('H',NOW_TIME);
		if($dtime>=10 && $dtime<22){
			$kj_time=intval(NOW_TIME/600+1)*600+50;
		}elseif($dtime<2 || $dtime>=22){
			$kj_time=intval(NOW_TIME/300+1)*300+50;
		}else{
			$kj_time=strtotime(date('Y-m-d 10:00:50',NOW_TIME));
		}
	}
	return $kj_time;
}

///**
// *  记录Wapapi下LOG日志(支持数组与字符串记录)
// * @author zhangran
// * @date   2016-07-01
// * @param  $data log数据,$msg 记录业务说明
// * @return 无
// **/
//function recordLog($data, $msg = '')
//{
//    if ( !$data ) return;
//    if ( is_array($data) ) {
//        $data = print_r($data, true);
//    }
//    \Think\Log::record('[==Debug==]'.date('Y-m-d H:i:s') . "--" . $msg . "--:" . print_r($data, true), "INFO");
//    \Think\Log::save();
//}

/**
 *  生成一个唯一ID(生产环境与测试环境)
 * @author zhangran
 * @date 2016-07-01
 * @return string
 */
function uuid()
{
    if ( !extension_loaded('ukey') ) {
        $guid = time() . rand(10000000, 99999999);
    } else {
        $guid = ukey_next_id();
    }
    return $guid;
}

/**
 *  系统加密方法
 * @author zhagnran
 * @date   2016-07-01
 * @param  string $data 要加密的字符串
 * @param  string $key 加密密钥
 * @param  int $expire 过期时间 单位 秒
 * @return string
 */
function encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ( $i = 0; $i < $len; $i++ ) {
        if ( $x == $l ) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ( $i = 0; $i < $len; $i++ ) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 *  系统解密方法
 * @author zhagnran
 * @date   2016-07-01
 * @param  string $data 要解密的字符串 （必须是encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 */
function decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ( $mod4 ) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ( $expire > 0 && $expire < time() ) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ( $i = 0; $i < $len; $i++ ) {
        if ( $x == $l ) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ( $i = 0; $i < $len; $i++ ) {
        if ( ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)) ) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 *  生成随机字符串
 * @author zhagnran
 * @date   2016-07-06
 * @param  string $length 字符串长度
 * @return string
 */
function randStr($length=6){
	$str = null;
	$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
	$max = strlen($strPol)-1;

	for($i=0;$i<$length;$i++){
		$str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
	}
	return $str;
}

/**
 * 判断字符串是否为空
 * @return boolean
 */
function isEmpty()
{
    foreach ( func_get_args() as $arg ) {
        if ( !$arg || $arg == '' || preg_replace('/\s(?=\s)/', '', trim($arg)) == '' ) {
            return TRUE;
        }
    }
    return FALSE;
}

/**
 * 生成缩略图
 * @author
 * @param string     源图绝对完整地址{带文件名及后缀名}
 * @param string     目标图绝对完整地址{带文件名及后缀名}
 * @param int        缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
 * @param int        缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
 * @param int        是否裁切{宽,高必须非0}
 * @param int/float  缩放{0:不缩放, 0<this<1:缩放到相应比例(此时宽高限制和裁切均失效)}
 * @return boolean
 */
function img2thumb($src_img, $dst_img, $width = 75, $height = 75, $cut = 0, $proportion = 0)
{
    if(!is_file($src_img))
    {
        return false;
    }
    $ot = strtolower(trim(substr(strrchr($dst_img, '.'), 1, 10)));//取得文件扩展

    $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
    $srcinfo = getimagesize($src_img);
    $src_w = $srcinfo[0];
    $src_h = $srcinfo[1];
    $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
    $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);

    $dst_h = $height;
    $dst_w = $width;
    $x = $y = 0;

    /**
     * 缩略图不超过源图尺寸（前提是宽或高只有一个）
     */
    if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0))
    {
        $proportion = 1;
    }
    if($width> $src_w)
    {
        $dst_w = $width = $src_w;
    }
    if($height> $src_h)
    {
        $dst_h = $height = $src_h;
    }

    if(!$width && !$height && !$proportion)
    {
        return false;
    }
    if(!$proportion)
    {
        if($cut == 0)
        {
            if($dst_w && $dst_h)
            {
                if($dst_w/$src_w> $dst_h/$src_h)
                {
                    $dst_w = $src_w * ($dst_h / $src_h);
                    $x = 0 - ($dst_w - $width) / 2;
                }
                else
                {
                    $dst_h = $src_h * ($dst_w / $src_w);
                    $y = 0 - ($dst_h - $height) / 2;
                }
            }
            else if($dst_w xor $dst_h)
            {
                if($dst_w && !$dst_h)  //有宽无高
                {
                    $propor = $dst_w / $src_w;
                    $height = $dst_h  = $src_h * $propor;
                }
                else if(!$dst_w && $dst_h)  //有高无宽
                {
                    $propor = $dst_h / $src_h;
                    $width  = $dst_w = $src_w * $propor;
                }
            }
        }
        else
        {
            if(!$dst_h)  //裁剪时无高
            {
                $height = $dst_h = $dst_w;
            }
            if(!$dst_w)  //裁剪时无宽
            {
                $width = $dst_w = $dst_h;
            }
            $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
            $dst_w = (int)round($src_w * $propor);
            $dst_h = (int)round($src_h * $propor);
            $x = ($width - $dst_w) / 2;
            $y = ($height - $dst_h) / 2;
        }
    }
    else
    {
        $proportion = min($proportion, 1);
        $height = $dst_h = $src_h * $proportion;
        $width  = $dst_w = $src_w * $proportion;
    }

    $src = $createfun($src_img);
    $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);

    if(function_exists('imagecopyresampled'))
    {
        imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
    else
    {
        imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
    $otfunc($dst, $dst_img);
    imagedestroy($dst);
    imagedestroy($src);
    return true;
}

/**
 * 解析发布状态
 * @param $publish 发布状态
 */
function parse_slider_publish($publish){
    $binary  = decbin($publish);
    $app = substr($binary,-1);
    $h5 = substr($binary,-2,-1);

    $_publish = array();
    if(isset($app) && $app == 1 ){
        $_publish[] = 'APP';
    }
    if(isset($h5) && $h5 == 1){
        $_publish[] = 'H5';
    }
    return implode('|',$_publish);
}
/**
 * 排序
 * @param  [type] $ArrayData  [description]
 * @param  [type] $KeyName1   [description]
 * @param  string $SortOrder1 [description]
 * @param  string $SortType1  [description]
 * @return [type]             [description]
 */
function array_sort($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR")  
    {  
        if(!is_array($ArrayData)) return $ArrayData;  
          
        // Get args number.  
        $ArgCount = func_num_args();  
        // Get keys to sort by and put them to SortRule array.  
        for($I = 1;$I < $ArgCount;$I ++)  
        {  
            $Arg = func_get_arg($I);  
            if(!eregi("SORT",$Arg))  
            {  
                $KeyNameList[] = $Arg;  
                $SortRule[]    = '$'.$Arg;  
            }  
            else $SortRule[]   = $Arg;  
        }  
        // Get the values according to the keys and put them to array.  
        foreach($ArrayData AS $Key => $Info)  
        {  
            foreach($KeyNameList AS $KeyName) ${$KeyName}[$Key] = strtolower($Info[$KeyName]);  
        }  
          
        // Create the eval string and eval it.  
        $EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';  
        eval ($EvalString);  
        return $ArrayData;  
    }   

function server_param(){
    $indicesServer = array('PHP_SELF', 
                            'argv', 
                            'argc', 
                            'GATEWAY_INTERFACE', 
                            'SERVER_ADDR', 
                            'SERVER_NAME', 
                            'SERVER_SOFTWARE', 
                            'SERVER_PROTOCOL', 
                            'REQUEST_METHOD', 
                            'REQUEST_TIME', 
                            'REQUEST_TIME_FLOAT', 
                            'QUERY_STRING', 
                            'DOCUMENT_ROOT', 
                            'HTTP_ACCEPT', 
                            'HTTP_ACCEPT_CHARSET', 
                            'HTTP_ACCEPT_ENCODING', 
                            'HTTP_ACCEPT_LANGUAGE', 
                            'HTTP_CONNECTION', 
                            'HTTP_HOST', 
                            'HTTP_REFERER', 
                            'HTTP_USER_AGENT', 
                            'HTTPS', 
                            'REMOTE_ADDR', 
                            'REMOTE_HOST', 
                            'REMOTE_PORT', 
                            'REMOTE_USER', 
                            'REDIRECT_REMOTE_USER', 
                            'SCRIPT_FILENAME', 
                            'SERVER_ADMIN', 
                            'SERVER_PORT', 
                            'SERVER_SIGNATURE', 
                            'PATH_TRANSLATED', 
                            'SCRIPT_NAME', 
                            'REQUEST_URI', 
                            'PHP_AUTH_DIGEST', 
                            'PHP_AUTH_USER', 
                            'PHP_AUTH_PW', 
                            'AUTH_TYPE', 
                            'PATH_INFO', 
                            'ORIG_PATH_INFO') ; 

    echo '<table cellpadding="10">' ; 
    foreach ($indicesServer as $arg) { 
        if (isset($_SERVER[$arg])) { 
            echo '<tr><td>'.$arg.'</td><td>' . $_SERVER[$arg] . '</td></tr>' ; 
        } 
        else { 
            echo '<tr><td>'.$arg.'</td><td>-</td></tr>' ; 
        } 
    } 
    echo '</table>' ; 
}
?>