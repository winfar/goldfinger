<?php
/**
 * 用户配置文件
 */
return array(

    'URL_MODEL'             =>  3,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 

    /* 数据缓存设置 */
    'DATA_CACHE_PREFIX'    => 'bo_', // 缓存前缀
    'DATA_CACHE_TYPE'      => 'File', // 数据缓存类型

    /* 日志设置 */
    'LOG_RECORD'            =>  true,   // 默认不记录日志
    'LOG_TYPE'              =>  'File', // 日志记录类型 默认为文件方式
    'LOG_LEVEL'             =>  'EMERG,ALERT,CRIT,ERR,INFO,DEBUG,SQL',// 允许记录的日志级别array('EMERG','ALERT','CRIT','ERR','WARN','NOTIC','INFO','DEBUG','SQL')
    'LOG_FILE_SIZE'         =>  2097152,	// 日志文件大小限制
    'LOG_EXCEPTION_RECORD'  =>  false,    // 是否记录异常信息日志
	
	/* 图片上传相关配置 */
    'PICTURE_UPLOAD' => array(
		'mimes'    => '', //允许上传的文件MiMe类型
		'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
		'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
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
	
	'WEIXIN_UPLOAD' => array(
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 30*1024*1024, //上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg,arm,mp3,mp4,wma,wav,amr,flv,avi', //允许上传的文件后缀
        'autoSub'  => false, //自动子目录保存文件
        'subName'  => '', //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Picture/Weixin/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => '', //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'  => '', //文件保存后缀，空则使用原后缀
        'replace'  => true, //存在同名是否覆盖
        'hash'     => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), 
	'WEIXIN_UPLOAD_DRIVER'=>'local',
	
    //本地上传文件驱动配置
    'UPLOAD_LOCAL_CONFIG'=>array(),
	
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/Static',
        '__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
		'__FONTS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/fonts',
    ),

    /* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'bo_web', //session前缀
    'COOKIE_PREFIX'  => 'bo_web_', // Cookie前缀 避免冲突
    'VAR_SESSION_ID' => 'session_id',	//修复uploadify插件无法传递session_id的bug
	'TMPL_ACTION_ERROR'   =>  'Public/dispatch_jump',
	'TMPL_ACTION_SUCCESS'   =>  'Public/dispatch_jump',
);