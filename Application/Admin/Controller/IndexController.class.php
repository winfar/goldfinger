<?php
namespace Admin\Controller;
use Think\Controller;

class IndexController extends WebController {
    public function index(){
		$this->display();
    }

    public function welcome(){
        $this->display();
    }
}