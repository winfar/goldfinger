<?php
namespace Admin\Controller;

class OperaterController extends WebController {

    public function index(){
        $this->display();
    }

    public function info(){
        $Wx = A('Wx');
        // 调用 User 模块中的方法
        $Wx->index();
    }

    public function addredenvelope(){
      $this->meta_title = '生成红包';
		  $this->display();
    }
}