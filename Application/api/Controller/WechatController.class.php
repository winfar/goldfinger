<?php

namespace api\Controller;

use Think\Controller;

class WechatController extends BaseController
{   
    protected $host = '';
    protected $callbackHost = 'http://1.busonline.com';
	protected $callbackUrl = '/h5/pkshare/index.html';// 回调地址

    protected function _initialize()
    {
        parent::_initialize();
        vendor("weixin.class_weixin_adv");

        $this->$host = $_SERVER['HTTP_HOST'];

        if(strpos($_SERVER['HTTP_HOST'],"passport.busonline.com")==0){
            $this->callbackHost = "http://1.busonline.com";
        }
        else{
            $this->callbackHost = "http://onlinetest.1.busonline.com";
        }

        $this->callbackUrl = $this->callbackHost . $this->callbackUrl;
    }

    //微信登录回调
    function oauth2($extras){
        // echo "extras:" . $extras;

		$weixin= new \class_weixin_adv();

		if (isset($_GET['code'])){
            
			$res = $weixin->get_access_token($_GET['code']);

            $res2 = $weixin->check_token($res['access_token'],$res['openid']);

            if($res2['errcode'] == 0){//access_token没过期

            }else{
                $res3 = $weixin->refresh_token($res['refresh_token']);

                if(empty($res3['errcode'])){
                    $res['access_token'] = $res3['access_token'];
                }else{
                    returnJson('', 1, '授权出错,请重新授权!');
                }
            }

			$row = $weixin->get_user_info($res['access_token'],$res['openid']); 

			if ($row['openid']) {
                $url = $this->callbackUrl."?code=200&extras=".$extras."&avatar=".urlencode($row['headimgurl'])."&nickname=".$row['nickname'];
                // echo "url:" . $url;
                header("Location: $url");

			}else{
                $url = $this->callbackUrl."?&code=401";
                header('Location: '.$url);
			}
        }else{
            $url = $this->callbackUrl."?&code=402";
            header('Location: '.$url);
        }
    }
}	