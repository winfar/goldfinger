<?php
namespace Redirect\Controller;
use Think\Controller;
class UrlController extends Controller {
    
    //private $baseUrl = "http://1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w?#!";
    //private $burl = "/h5web/v-u6Jrym-zh_CN-/yymj/h5web/";
    private $burl = "/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w?#!";
    private $host = '';

    public function _initialize()
    {
        $this->host = $_SERVER['HTTP_HOST'];

        if($_SERVER['HTTP_HOST']=='local.1.busonline.com' || $_SERVER['HTTP_HOST']=='test.1.busonline.com'){
            $this->host = 'onlinetest.1.busonline.com';
        }

        $this->burl = "http://".$this->host.$this->burl;
    }

    public function index(){
         //$this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>[ 您现在访问的是Redirect模块的Index控制器 ]</div><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
        //    echo 123;
    }

    public function guide(){
        $url = $this->burl . 'guide//{}';
        header('location:'.$url);
    }

    public function details($pid){
        $url = $this->burl . 'detail//({"pid":"'.$pid.'"}#!userinfoContent/!/parameterContent)';
        header('location:'.$url);
    }

    public function orderResult($orderid){
        $url = $this->burl . 'payresult//{"orderid":"'.$orderid.'"}';
        header('Location: '.$url);  
    }

    public function pkshare($houseid,$uid){
        //$url = $this->burl . 'pkhouse//{"houseid":"'.$houseid.'","uid":"'.$uid.'"}';
        $url = "/h5/pkshare/index.html?houseid=" . $houseid . "&uid=" .$uid;
        header('Location: '.$url);
    }
}