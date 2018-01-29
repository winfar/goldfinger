<?php
namespace Admin\Controller;
use Think\Controller;
/**
 *  商品品牌控制器
 */
class BrandController extends WebController {

	public function index(){
//        $Brand =   M('Brand')->where(array('status'=>array('gt',-1)));
//        $list   =   $this->lists($Brand);
        $Brand =M('Brand b');
        $list =   $Brand->join(' LEFT JOIN __SHOP__ s ON  b.id = s.brand_id ' )->where(array('b.status'=>array('gt',-1)))->group('b.id')->field('b.id,b.title,COUNT(s.id) as total')->select();
		$this->assign('list', $list);
		$this->meta_title = '品牌管理';
        $this->display();
	}

    public function edit($id = null){
        $Brand = D('Brand');
        if(IS_POST){
            if(false !== $Brand->update()){
                $this->success('编辑成功！');
            } else {
                $error = $Brand->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $info = $id ? $Brand->info($id) : '';
            $this->assign('list',       $info);
			$this->meta_title = '品牌修改';
            $this->display();
        }
    }
	
    public function add($pid = 0){
        $Brand = D('Brand');
        if(IS_POST){
            if(false !== $Brand->update()){
                $this->success('新增成功！');
            } else {
                $error = $Brand->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '新增专区';
            $this->display('edit');
        }
    }
}
