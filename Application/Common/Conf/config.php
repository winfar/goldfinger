<?php
return array(
'DB_TYPE'   => 'mysql', // 数据库类型
'DB_HOST'   => 'mysql.busonline.com', // 服务器地址
'DB_NAME'   => 'bogoldfinger', // 数据库名
// 'DB_NAME'   => 'booneshop201703071129', // 数据库名
'DB_CHARSET'=>  'utf8mb4',      // 数据库编码默认采用utf8
'DB_USER'   => 'yiyuan', // 用户名
'DB_PWD'    => 'VdSgtCz3',  // 密码
'DB_PORT'   => '3306', // 端口
'DB_PREFIX' => 'bo_', // 数据库表前缀

'DATA_CACHE_TYPE'        => 'Redis',/* 系统缓存 */
'REDIS_HOST'             => 'redis.busonline.com',
'REDIS_PASSWORD'         => '1Q2wsz4', // 密码
'DATA_CACHE_PREFIX'      =>  'bo_',     // 缓存前缀
'REDIS_PORT'             => 7379,
'DATA_CACHE_TIME'        => 3600,

'IMG_URL_CDN' =>'http://img1.oneshop.busonline.com'//CDN图片地址
);
