<?php
namespace Shop\Controller;
use Think\Controller;

class RemindController extends Controller {

	public function index()
	{
		$this->web_title = "用户信息丢失"; 
		$this->display("index");
	}
}
