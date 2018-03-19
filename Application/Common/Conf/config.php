<?php
return array(
'DB_TYPE'   => 'mysql', // 数据库类型
'DB_HOST'   => '127.0.0.1', // 服务器地址 127.0.0.1,mysql.molijinbei.com
'DB_NAME'   => 'bogoldfinger', // 数据库名
// 'DB_NAME'   => 'booneshop201703071129', // 数据库名
'DB_CHARSET'=>  'utf8mb4',      // 数据库编码默认采用utf8
// 'DB_USER'   => 'yiyuan', // 用户名
// 'DB_PWD'    => 'VdSgtCz3',  // 密码
'DB_USER'   => 'root', // 用户名goldfinger,root
'DB_PWD'    => 'root',  // 密码 #1fMJ&*^,root
'DB_PORT'   => '3306', // 端口
'DB_PREFIX' => 'bo_', // 数据库表前缀

/*
'DATA_CACHE_TYPE'        => 'Redis',    //缓存类型
'REDIS_HOST'             => 'redis.molijinbei.com',
'REDIS_PASSWORD'         => '1Q2wsz4', // 密码
'DATA_CACHE_PREFIX'      => 'bo_',     // 缓存前缀
'REDIS_PORT'             => 7379,
'DATA_CACHE_TIME'        => 3600,
*/

'DATA_CACHE_TIME'       =>  3600,   // 数据缓存有效期 0表示永久缓存
// 'DATA_CACHE_COMPRESS'   =>  false,   // 数据缓存是否压缩缓存
// 'DATA_CACHE_CHECK'      =>  false,   // 数据缓存是否校验缓存
'DATA_CACHE_PREFIX'     =>  'bo_',     // 缓存前缀
'DATA_CACHE_TYPE'       =>  'File',  // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
'DATA_CACHE_PATH'       =>  TEMP_PATH,// 缓存路径设置 (仅对File方式缓存有效)
// 'DATA_CACHE_SUBDIR'     =>  false,    // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
// 'DATA_PATH_LEVEL'       =>  1,        // 子目录缓存级别

'IMG_URL_CDN' =>'http://img1.oneshop.busonline.com',//CDN图片地址

'WEIXINPAY_CONFIG'       => array(
    'APPID'              => 'wx05fd5e570f8b7b42', // 微信APPID //test:wxa6fea15278d6e77a,product:wx05fd5e570f8b7b42
    'MCHID'              => '1494530772', // 微信支付MCHID 商户收款账号
    'KEY'                => '4d47e8954d984eecf3c1f58b4f321351', // 微信支付KEY
    'APPSECRET'          => '16282b078b0f4d0e9b070565fdb1cef1',  //公众帐号secert //test:1d933ea4ddde1da122875951dbc9878d,product:16282b078b0f4d0e9b070565fdb1cef1
    'NOTIFY_URL'         => 'https://www.molijinbei.com/wx_notify_url.php', // 接收支付状态的连接 http://baijunyao.com/Api/WeixPay/notify/order_number/
    ),
);
