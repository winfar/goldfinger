<?php
return array(
	// 'URL_MODEL' => 2,
	'DATA_CACHE_PREFIX'    => 'bo_', // 缓存前缀
    'DATA_CACHE_TYPE'      => 'File', // 数据缓存类型

    /* 图片上传相关配置 */
    'PICTURE_UPLOAD' => array(
		'mimes'    => '', //允许上传的文件MiMe类型
		'maxSize'  => 50*1024*1024, //上传的文件大小限制 (0-不做限制)
		'exts'     => 'jpg,gif,png,jpeg,mp4', //允许上传的文件后缀
		'autoSub'  => true, //自动子目录保存文件
		'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
		'rootPath' => './Picture/Shared/', //保存根路径
		'savePath' => '', //保存路径
		'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
		'saveExt'  => '', //文件保存后缀，空则使用原后缀
		'replace'  => true, //存在同名是否覆盖
		'hash'     => true, //是否生成hash编码
		'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //图片上传相关配置（文件上传类配
    'PICTURE_UPLOAD_DRIVER'=>'local',
     'UPLOAD_LOCAL_CONFIG'=>array(),
	
	/* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'bo_users', //session前缀
    'COOKIE_PREFIX'  => 'bo_users_', // Cookie前缀 避免冲突
    'TMPL_ACTION_ERROR'   =>  './Template/Web/dispatch_jump.html',
	'TMPL_ACTION_SUCCESS'   =>  './Template/Web/dispatch_jump.html',
	// 'TMPL_EXCEPTION_FILE'  => './Template/Web/think_exception.html',
	
	'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/Static',
        '__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
		'___EXPLAIN__' => __ROOT__ . '/Picture/Explain',
    ),
);