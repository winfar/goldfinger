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

'DATA_CACHE_TYPE'        => 'Redis',/* 系统缓存 */
'REDIS_HOST'             => 'redis.molijinbei.com',
'REDIS_PASSWORD'         => '1Q2wsz4', // 密码
'DATA_CACHE_PREFIX'      =>  'bo_',     // 缓存前缀
'REDIS_PORT'             => 7379,
'DATA_CACHE_TIME'        => 3600,

'IMG_URL_CDN' =>'http://img1.oneshop.busonline.com',//CDN图片地址

'WECHAT_APP_ID' => 'wx05fd5e570f8b7b42',//test:wxa6fea15278d6e77a,product:wx05fd5e570f8b7b42
'WECHAT_APP_SECRET' => '16282b078b0f4d0e9b070565fdb1cef1',//test:1d933ea4ddde1da122875951dbc9878d,product:16282b078b0f4d0e9b070565fdb1cef1
'WECHAT_MCHID' => '1494530772',
'WECHAT_KEY' => '4d47e8954d984eecf3c1f58b4f321351',
);
