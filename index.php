<?php
// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

	header('Location: ./404.php');
	exit();

if (getenv("HTTP_CLIENT_IP"))
	$ip = getenv("HTTP_CLIENT_IP");
else if(getenv("HTTP_X_FORWARDED_FOR"))
	$ip = getenv("HTTP_X_FORWARDED_FOR");
else if(getenv("REMOTE_ADDR"))
	$ip = getenv("REMOTE_ADDR");
else $ip = "Unknow";


$ipRanges = array(
  array( '111.202.112.1' , '111.202.112.239'),
  array( '192.168.0.1' , '192.168.255.255'),
  array( '127.0.0.1' , '127.0.0.1')
);

if(!is_ip($ip,$ipRanges)){
	// header('Location: ./404.php');
	// exit(); 
}

function is_ip($localIp,$ipRanges) {
    $localIp = ip2long($localIp);
    foreach($ipRanges as $val) {
        if($localIp >= ip2long($val[0]) && $localIp <= ip2long($val[1])) {
            return $val;
        }
    }
    return false;
}


// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false true
define('APP_DEBUG',true);
define('BIND_MODULE','Home');

// 定义应用目录
define('APP_PATH','./Application/');

if(!is_file(APP_PATH . 'Common/Conf/config.php')){
	header('Location: ./install.php');
	exit;
}
// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
// 亲^_^ 后面不需要任何代码了 就是如此简单!