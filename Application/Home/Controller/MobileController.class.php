<?php
namespace Home\Controller;
use Think\Storage;
class MobileController extends WapController{
		public function __construct(){		
		parent::__construct();			
	}
    public function index(){
		
		// $url="http://test.passport.busonline.com/wapapi.php?s=/public/slider/";
        // $silder=json_decode(file_get_contents($url),TRUE);
	    // $this->assign('silder',$silder["data"]);
    	// $this->display($this->tplpath."mobile/index.html");
		
	  	$this->display($this->tplpath."mobile/static/faq.html");
    } 

	public function faq(){
	  	$this->display($this->tplpath."mobile/static/faq.html");
    }

	public function points(){
	  	$this->display($this->tplpath."mobile/static/points.html");
    }

	public function cards(){
	  	$this->display($this->tplpath."mobile/static/cards.html");
    }

	public function agreement(){
	  	$this->display($this->tplpath."mobile/static/agreement.html");
    }

	public function service(){
	  	$this->display($this->tplpath."mobile/static/service.html");
    }

	

	
}