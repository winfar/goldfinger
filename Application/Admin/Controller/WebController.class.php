<?php
namespace Admin\Controller;
use Think\Controller;

class webController extends Controller {

    protected function _initialize(){
        define('UID',is_login());
        if(!UID){
            $this->redirect('public/login');
        }

        $menus = $this->getMenus2();
        $count = -1;

        //权限开放公共 Ueditor
        if(CONTROLLER_NAME == 'Ueditor' ){
            $count = 1;
        }else{
            $this->recursive($menus,CONTROLLER_NAME,$count);
        }

        if($count < 0 ){
             $this->error('该页面无权访问！正在退出！',U('Public/logout'));
        }


        $config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =  config_lists();
            S('DB_CONFIG_DATA',$config);
        }

        C($config);

        $this->web_path=__ROOT__."/";
        $this->web_title=C("WEB_SITE_TITLE");
        $this->web_logo="/".C('TMPL_PATH')."/Web/images/".C("WEB_LOGO");
        $this->web_keywords=C("WEB_SITE_KEYWORD");
        $this->web_description=C("WEB_SITE_DESCRIPTION");
        $this->web_icp=C("WEB_SITE_ICP");
        $this->web_url=C("WEB_URL");
        $this->web_currency=C("WEB_CURRENCY");
        $this->wx_pay=C('WX_PAY_MCHID');
        $this->ali_pay=C('ALI_PAY_PARTNER');
        $this->band_pay=C('BAND_PAY_MID');
        $this->yun_pay=C('YUN_PAY_ID');
        $this->pay_pal=C('PAY_PAL');
        $this->web_time=NOW_TIME;
        $this->tplpath="./".C('TMPL_PATH')."/Web/";
        $this->web_tplpath=$this->web_path.C('TMPL_PATH')."/Web/";
        C('CACHE_PATH',RUNTIME_PATH."/Cache/".MODULE_NAME."/Web/");

        if(!C('WEB_SITE_CLOSE')){
            $this->error('站点已经关闭，请稍后访问~');
        }
        $this->assign('__MENU__', $menus );
	}
	
    protected function getMenus($controller=CONTROLLER_NAME){
        $menus  =   session('WEB_MENU_LIST'.$controller);
        if(empty($menus)){

            $where['pid']   =   0;
            $where['hide']  =   0;
            $menus['main']  =   M('WebMenu')->where($where)->order('sort asc')->select();
            $menus['child'] = array();

            $current = M('WebMenu')->where("url like '%{$controller}/".ACTION_NAME."%'")->field('id')->find();
            if($current){
                $nav = D('WebMenu')->getPath($current['id']);
                $nav_first_title = $nav[0]['title'];
                foreach ($menus['main'] as $key => $item) {
                    if (!is_array($item) || empty($item['title']) || empty($item['url']) ) {
                        $this->error('控制器基类$menus属性元素配置有误');
                    }
                    if( stripos($item['url'],MODULE_NAME)!==0 ){
                        $item['url'] = MODULE_NAME.'/'.$item['url'];
                    }
                    if($item['title'] == $nav_first_title){
                        $menus['main'][$key]['class']='active';
                        $groups = M('WebMenu')->where("pid = {$item['id']}")->distinct(true)->field("`group`")->order('sort asc')->select();
                        if($groups){
                            $groups = array_column($groups, 'group');
                        }else{
                            $groups =   array();
                        }
                        $where          =   array();
                        $where['pid']   =   $item['id'];
                        $where['hide']  =   0;
                        $second_urls = M('WebMenu')->where($where)->getField('id,url');
                        foreach ($groups as $g) {
                            $map = array('group'=>$g);
                            if(isset($second_urls)){
                                    $map['url'] = array('in', $second_urls);
                            }
                            $map['pid'] =   $item['id'];
                            $map['hide']    =   0;
                            $menuList = M('WebMenu')->where($map)->field('id,pid,title,url,tip,icon,group')->order('sort asc')->select();
                            $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
                        }
                    }
                }
            }
            session('WEB_MENU_LIST'.$controller,$menus);
        }
        return $menus;
    }

    protected function getMenus2($controller=CONTROLLER_NAME){
        $menus  =   session('WEB_MENU_LIST'.$controller);
        if(empty($menus)){
            //获取roleId
            $roleId = M('MemberRole')->where("userID=".UID)->field('roleID')->find();

            $where['pid']   =   0;
            $where['hide']  =   0;
            $menus['main']  =   M('WebMenus')->where($where)->order('sort asc')->select();
            $menus['child'] = array();
            //获取当前激活的一级菜单
            $current = M('WebMenus')->where("url like '%{$controller}/".ACTION_NAME."%'")->field('id')->find();
            if($current){
                $nav = D('WebMenus')->getPath($current['id']);
                $nav_first_title = $nav[0]['title'];
                foreach ($menus['main'] as $key => $item) {
                    if (!is_array($item) || empty($item['title']) || empty($item['url']) ) {
                        $this->error('控制器基类$menus属性元素配置有误');
                    }
                    if( stripos($item['url'],MODULE_NAME)!==0 ){
                        $item['url'] = MODULE_NAME.'/'.$item['url'];
                    }
                    if($item['title'] == $nav_first_title){
                        $menus['main'][$key]['class']='active';
                        //获取第二级菜单信息 需增加roleid检查
                        $groups = M('WebMenus')->where("pid = {$item['id']} and hide = 0 ")->distinct(true)->field("`title`")->order('sort asc')->select();
                        $ids2 = M('WebMenus')->where("pid = {$item['id']} and hide = 0 ")->distinct(true)->field("`id`")->order('sort asc')->select();

                        if($groups){
                            $groups = array_column($groups, 'title');
                        }else{
                            $groups =   array();
                        }

                        for ($x=0; $x<=count($ids2); $x++) {

                            $ljoin = ' LEFT JOIN __ROLE_PRIVILEGE__ ON menu.id = __ROLE_PRIVILEGE__.privilegeID ';
                            //获取所有3级菜单
                            if(isset($second_urls)){
                                $map['url'] = array('in', $second_urls);
                            }
                            $map['pid'] =   $ids2[$x]['id'];
                            $map['hide']    =   0;
                            $map['roleID']    =   $roleId['roleID'];

                            $menuList = M('WebMenus')->table('__WEB_MENUS__ menu')->join($ljoin)->where($map)->field('menu.id,menu.pid,menu.title,menu.url,menu.tip,menu.icon')->order('sort asc')->select();
                            $g = $groups[$x];
                            $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater',  $ids2[$x]['id']);
                        }
                    }
                }
            }
            session('WEB_MENU_LIST'.$controller,$menus);
        }
        return $menus;
    }


    protected function lists ($model,$where=array(),$order='',$rows=0,$base = array('status'=>array('egt',0)),$field=true){
        $options    =   array();
        $REQUEST    =   (array)I('request.');
        if(is_string($model)){
            $model  =   M($model);
        }
        $OPT        =   new \ReflectionProperty($model,'options');
        $OPT->setAccessible(true);

        $pk         =   $model->getPk();
        if($order===null){
        }else if ( isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']),array('desc','asc')) ) {
            $options['order'] = '`'.$REQUEST['_field'].'` '.$REQUEST['_order'];
        }elseif( $order==='' && empty($options['order']) && !empty($pk) ){
            $options['order'] = $pk.' desc';
        }elseif($order){
            $options['order'] = $order;
        }
        unset($REQUEST['_order'],$REQUEST['_field']);
        $options['where'] = array_filter(array_merge( (array)$base, /*$REQUEST,*/ (array)$where ),function($val){
            if($val===''||$val===null){
                return false;
            }else{
                return true;
            }
        });
        if( empty($options['where'])){
            unset($options['where']);
        }
        $options      =   array_merge( (array)$OPT->getValue($model), $options );
        $total=0;
        // if(array_key_exists('group',$options)){
        //     $total = count($model->where($options['where'])->select());
        // }else{
        //     $total = count($model->where($options['where'])->select());
        // }

        $total = count($model->where($options['where'])->select());
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = $rows > 0 ? $rows : 20;
        }
        $page = new \Think\Page($total, $listRows, $REQUEST);
        //if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        //}
        $p =$page->show();
        $this->assign('_page', $p? $p: '');
        $this->assign('_total',$total);
        $options['limit'] = $page->firstRow.','.$page->listRows;
        $model->setProperty('options',$options);
        $rs = $model->field($field)->select();

        // exit($model->getLastSql());

        return $rs;
    }

    final protected function editRow ( $model ,$data, $where , $msg, $url='index'){
        $id    = array_unique((array)I('id',0));
        $id    = is_array($id) ? implode(',',$id) : $id;
        $fields = M($model)->getDbFields();
        if(in_array('id',$fields) && !empty($id)){
            $where = array_merge( array('id' => array('in', $id )) ,(array)$where );
        }
        $msg   = array_merge( array( 'success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>U($url) ,'ajax'=>IS_AJAX) , (array)$msg );
        if( M($model)->where($where)->save($data)!==false ) {
            $this->success($msg['success'],$msg['url'],$msg['ajax']);
        }else{
            $this->error($msg['error'],$msg['url'],$msg['ajax']);
        }
    }
	
    protected function forbid ( $model , $where = array() , $msg = array( 'success'=>'状态禁用成功！', 'error'=>'状态禁用失败！'),$url){
        $data    =  array('status' => 0);
        $this->editRow( $model , $data, $where, $msg,$url);
    }

    protected function resume (  $model , $where = array() , $msg = array( 'success'=>'状态恢复成功！', 'error'=>'状态恢复失败！'),$url){
        $data    =  array('status' => 1);
        $this->editRow(   $model , $data, $where, $msg,$url);
    }

    public function setStatus($Model=CONTROLLER_NAME){

        $id    =   I('request.id');
        $status =   I('request.status');
        $url = I('request.url');

        if(empty($id)){
            $this->error('请选择要操作的数据');
        }

        if(empty($url)){
            $url='index';
        }

        $map['id'] = array('in',$id);
        switch ($status){
            case 0  :
                $this->forbid($Model, $map, array('success'=>'禁用成功','error'=>'禁用失败'),$url);
                break;
            case 1  :
                $this->resume($Model, $map, array('success'=>'启用成功','error'=>'启用失败'),$url);
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }

    protected  function getTree($roleid = 0){
        $list = M();
        //获取1级菜单
        $tree = $list->query(" select m.id ,m.title,m.url,m.icon,p.id as checked ,m.sort  from bo_web_menus m LEFT JOIN ( select * from bo_role_privilege where roleID = $roleid ) p on m.id = p.privilegeID where m.pid= 0 and m.hide = 0 order by m.sort  ");
        $data = array();
        //获取2级菜单
        foreach ($tree as $v2){
            $id2 = $v2['id'];
            $dmenu2 = $list->query("  select m.id ,m.title,m.url,m.icon,p.id as checked , m.sort  from bo_web_menus m LEFT JOIN ( select * from bo_role_privilege where roleID = $roleid ) p on m.id = p.privilegeID where m.pid=$id2 and m.hide = 0 order by m.sort   ");
            $arr2 = array();
            //获取3级菜单
            foreach ($dmenu2 as $v3){
                $arr3 = $v3;
                $arr3['child'] = array();

                $id3 = $v3['id'];
                $dmenu3 = $list->query("  select m.id ,m.title,m.url,m.icon,p.id as checked ,m.sort from bo_web_menus m LEFT JOIN ( select * from bo_role_privilege where roleID = $roleid ) p on m.id = p.privilegeID where m.pid=$id3 and m.hide = 0 order by m.sort ");
                $arr3['child'] = $dmenu3;
                array_push($arr2,$arr3);
            }
            $v2['child'] = $arr2;
            array_push($data,$v2);
        }
        return $data;
    }

    protected function recursive($menu, $url, &$count){
        if(array_key_exists('url', $menu)){
            $tmp = explode("/", $menu['url']);
            if($tmp[0] == $url){
                $count = 1;
                return $count;
            }
        }

        foreach ($menu as $row){
            if(is_array($row)){
                 $this->recursive($row,$url,$count);
            }
        }

    }
}
