<?php
/**
 * Author: zhangkang
 * Date: 2016/9/110:15
 * Description:
 */

namespace Admin\Model;

use Think\Model;

class NotificationModel extends Model
{
    protected $app_key = '1332f6f2c55665e4010fae13';// '06c61e785e5745ff9c52c751';
    protected $master_secret = '22bef63563b88d158c3d861e';//'c6dad8a64ebfe1d19c8726ce';
    public $alert = '1元摸金';

    public function _initialize()
    {
        parent::_initialize();
        vendor("JPush.JPush");
    }


    /**
     * 推送
     * @param array $regIdArr regId数组
     * @param string $title 推送标题
     * @param string $content 推送内容
     * @param bool|true $productionEnv 是否生产环境
     * @param $extras 扩展信息
     */
    public function pushNotification($platform='ios',$regIdArr=array(), $title='',$content='',$productionEnv=true,$extras,$debug=false){

        if($debug==false){
            //非正式站不发送推送
            if(!isHostProduct()){
                return false;
            }
        }
        
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);
        switch ($platform) {
            case 'ios':
                // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
                $result = $client->push()
                    ->setPlatform(array($platform))
                    ->addRegistrationId($regIdArr)
                    //->setNotificationAlert($this->alert)
                    ->addIosNotification($content, 'sound', \JPush::DISABLE_BADGE, true, 'iOS category', $extras)//alert,sound,badge,content-available,category,extras
                    ->setMessage($content, $title, 'type', $extras)
                    ->setOptions(100000, 3600, null, $productionEnv)
                    ->send();
                break;
            case 'android':
                $result = $client->push()
                    ->setPlatform(array($platform))
                    ->addRegistrationId($regIdArr)
                    //->setNotificationAlert($this->alert)
                    ->addAndroidNotification($content, $title, 1, $extras)//alert,title,builder_id,extras
                    ->setMessage($content, $title, 'type', $extras)
                    ->setOptions(100000, 3600, null, false)
                    ->send();
                break;
            default:
                $result=false;
                break;
        }

        return $result;
    }

    public function JpushAll($title,$content,$extras,$debug=false)
    {
        /**
         * 该示例主要为JPush Push API的调用示例
         * HTTP API文档:http://docs.jpush.io/server/rest_api_v3_push/
         * PHP API文档:https://github.com/jpush/jpush-api-php-client/blob/master/doc/api.md#push-api--构建推送pushpayload
         */

        if($debug==false){
            //非正式站不发送推送
            if(!isHostProduct()){
                return false;
            }
        }

        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);
        // 简单推送示例
        $result = $client->push()
            ->setPlatform('all')
            ->addAllAudience()
            //->setNotificationAlert($this->alert)
            ->addAndroidNotification($content, $title, 1, $extras)//alert,title,builder_id,extras
            ->addIosNotification($content, 'sound', \JPush::DISABLE_BADGE, true, 'iOS category', $extras)//alert,sound,badge,content-available,category,extras
            ->setMessage($content, $title, 'type', $extras)
            ->setOptions(100000, 3600, null, true)
            ->send();
        return json_encode($result);
    }

    public function  JpushIos()
    {
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('ios'))
            ->addAlias('alias1')
            ->addTag(array('tag1', 'tag2'))
            ->setNotificationAlert($this->alert)
            ->addIosNotification("Hi, iOS notification", 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', array("key1" => "value1", "key2" => "value2"))
            ->setMessage("请升级app", '提示', 'type', array("key1" => "value1", "key2" => "value2"))
            ->setOptions(100000, 3600, null, true)
            ->send();

        echo 'Result=' . json_encode($result);
    }

    public function JpushAndroid()
    {
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);
        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('android'))
            ->addAlias('alias1')
            ->addTag(array('tag1', 'tag2'))
            ->setNotificationAlert($this->alert)
            ->addAndroidNotification('Hi, android notification', 'notification title', 1, array("key1" => "value1", "key2" => "value2"))
            ->setMessage("msg content", 'msg title', 'type', array("key1" => "value1", "key2" => "value2"))
            ->setOptions(100000, 3600, null, false)
            ->send();

        echo 'Result=' . json_encode($result);
    }

    public function JpushAppoint()
    {
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->addAlias('alias1')
            ->addTag(array('tag1', 'tag2'))
            ->setNotificationAlert($this->alert)
            ->setMessage("msg content", 'msg title', 'type', array("key1" => "value1", "key2" => "value2"))
            ->setOptions(100000, 3600, null, false)
            ->send();

        echo 'Result=' . json_encode($result);
    }

    public function JpushAndroidAll($title,$content,$extras)
    {
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('android'))
            //->addAlias('alias1')
            //->addTag(array('tag1', 'tag2'))
            //->setNotificationAlert($this->alert)
            ->addAndroidNotification($content, $title, 1, $extras)//alert,title,builder_id,extras
            ->setMessage($content, $title, 'type', $extras)
            ->setOptions(100000, 3600, null, false)
            ->send();

        return json_encode($result);
    }

    public function JpushIosAll($title,$content,$extras)
    {
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('ios'))
            //->addAlias('alias1')
            //->addTag(array('tag1', 'tag2'))
            //->setNotificationAlert($this->alert)
            ->addIosNotification($content, 'sound', \JPush::DISABLE_BADGE, true, 'iOS category', $extras)//alert,sound,badge,content-available,category,extras
            ->setMessage($content, $title, 'type', $extras)
            ->setOptions(100000, 3600, null, true)
            ->send();

        return json_encode($result);
    }

    public function addMsg($data)
    {
        $data['status'] = 1;

        $message = M('Message');
        if (empty($data['id'])) {
            $count = $message->add($data);
        } else {
            $count = $message->save($data);
        }
        
       
        return $count;
    }
}