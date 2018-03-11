<?php
/**
 * Created by PhpStorm.
 * User: win7
 * Date: 2016/7/1
 * Time: 10:35
 */
namespace api\Controller;

use Think\Controller;
class PublicController extends BaseController
{
    protected function _initialize()
    {
        parent::_initialize();
        
        vendor("JPush.JPush");
    }

    public function orderUrl($orderid){
        $url = 'http://1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/index.w?language=zh_CN&skin=#!payresult//{"orderid":"'.$orderid.'"}';
        header('Location: '.$url);
    }

    /**
     * 线上配置
     * @date    2016-07-01
     * @param
     * @return json
     */
    public function getConfig()
    {
        $config['lastest_version_ios']="1.7.80";
        $config['is_force_ios']=false;
        $config['update_content_ios']='蓝瘦，香菇，新版本更新啦，赶快体验新的惊喜吧！';

        $config['lastest_version_android']="1.7.80";
        $config['is_force_android']=false;
        $config['update_content_android']='蓝瘦，香菇，新版本更新啦，赶快体验新的惊喜吧！';

        //是否启用pk
        $config['pk_enabled']=true;

        returnJson($config,'200','success');
    }

    /**
     * 获取默认启动图片
     * @param
     * @return json
     */
    public function getDefaultDiagram()
    {
        //默认显示图片
        $map['name']='SYSTEM_DEFAULT_DIAGRAM';
        $map['display']=1;
        $rs = M('config')->where($map)->find();

        if($rs){
            $diagram['DefaultDiagramPath']=completion_pic($rs['value']);
        }

        $map1['name']='ADVERTISING_CHART';
        $map1['display']=1;
        $rs1 = M('config')->where($map1)->find();

        if($rs1){
            if(strpos($rs1['extra'],'http://') === false){
                $data1['link_type'] = 'item';
                $data1['link'] = getLatestPeriodByShopId($rs1['extra']);
            } else {
                $data1['link_type'] = 'link';
                $data1['link'] = $rs1['extra'];
            } 

            $data1['path']=completion_pic($rs1['value']);

            $diagram['AdvertisingChart']=$data1;
        }
        returnJson($diagram,'200','success');
    }

    /**
     * @deprecated 获取行政区划
     * @date    2016-07-01
     * @param
     * @return json
     */
    public function regions()
    {
        $city= json_decode(file_get_contents(CONF_PATH . 'city.json'));
        returnJson($city,'200','success');
    }

    /**
     * @deprecated 获取首页导航栏列表
     * @date    2016-07-01
     * @param
     * @return json
     */
    public function getNavigationBar()
    {
        $bar=array();
        array_push($bar,"签到","晒单","分类","常见问题");
        //echo json_encode($bar);
        returnJson($bar,'200','success');
    }

    /**
     * @deprecated 意见反馈
     * @date    2016-08-08
     * @param contacts 联系方式
     * @return content 反馈内容
     */
    public function addfeedback(){
		try{
            $result = file_get_contents('php://input');
            recordLog($result, 'addfeedback');
            $json = json_decode($result, true);

            if ( isEmpty($json['content']) ) {
                returnJson('内容不能为空！', 401, 'error');
            }

            if($this->count_feedback() > 20){
                returnJson('少侠，您今天已经提交太多反馈了，休息休息，明天再来吧！', 410, 'error');
            }

			$data['contacts'] = $json['contacts'];
			$data['content'] = $json['content'];
			$data['ip'] = getIP();
            $data['create_time'] = time();

			$result = M("feedback")->add($data);
			if($result){
            	returnJson('', 200, 'success');
			}
			else {
				returnJson($result, 402, 'error');
			}
        }catch(\Exception $e){
            returnJson($e->getMessage(), 500, 'error');
        }
	}

    private function count_feedback(){
        $year = date("Y");
		$month = date("m");
		$day = date("d");
		$start = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
		$end= mktime(23,59,59,$month,$day,$year);//当天结束时间戳
		//echo date('Ymd H:i:s',$start) .'#'.date('Ymd H:i:s',$end);

        $map['ip']=getIP();
        $map['create_time']=array('between',array($start,$end));
        $data = M("feedback")->where($map)->getField('count(*)');
        return $data;
    }

    /**
     * @deprecated 获取首页轮播图
     * @date    2016-07-01
     * @param
     * @return json
     */
    public function slider($type = 101)
    {
        $header = getHttpHeader();
        $clienttype = $header['CLIENTTYPE'];

        $order = 's.app_order,s.create_time desc';

        switch ($clienttype){
            case 1 :
            case 2:
                $order = 's.app_order,s.create_time desc';
                break;
            case 3:
                $order = 's.h5_order,s.create_time desc';
                break;
            default:
                break;
        }

        $condition = "s.cover_id = p.id and s.status=1 and s.publish<>0 and s.h5_ismajor=0 and ( s.start_time <= '".time()  ."' and '".time()."' <= s.end_time )";
        $data = M('slider')->table('__SLIDER__ s,__PICTURE__ p')
            -> field(array('s.title','s.link','s.create_time','p.path','s.start_time','s.end_time','s.publish','s.h5_order','s.app_order','s.h5_ismajor'))
            -> where($condition)
            -> order($order)
            -> limit(10)
            -> select();

        // exit(M()->getLastSql());
        
        $cnt = 0;
        foreach ($data as $key => $value) {
            $publish =  parse_slider_publish($value['publish']);
            $h_publish = '';
            switch ($clienttype){
                case 1 :
                    break;
                case 2:
                    break;
                case 3:
                    $h_publish = 'H5';
                    break;
                default:
                    $h_publish = '';
                    break;
            }

            if(strpos($publish,'H5') === false && $h_publish == 'H5'){
                //终端为H5, 发布不包含APP（安卓，ios）平台的处理方式，跳过不显示
                continue;
            }elseif(strpos($publish,'APP') === false && $h_publish == ''){
                //终端为APP（安卓，ios）平台，发布包含H5的处理方案，跳过不显示
                continue;
            }

            $list[$cnt]['title'] = $value['title'];
            $list[$cnt]['link'] = $value['link'];

            if( strtolower($value['link']) == 'pk'){
                $list[$cnt]['link_type'] = 'pk';
            }
            else if(strpos($value['link'],'http') == 0){
                $list[$cnt]['link_type'] = 'link';
            } else {
                $list[$cnt]['link_type'] = 'item';
                $list[$cnt]['link'] = getLatestPeriodByShopId($list[$cnt]['link']);
            }
                        
            $list[$cnt]['publish'] =  parse_slider_publish($value['publish']);

            $list[$cnt]['app_order'] = $value['app_order'];
            $list[$cnt]['h5_order'] =  $value['h5_order'];
            $list[$cnt]['h5_ismajor'] = $value['h5_ismajor'];
            $list[$cnt]['start_time'] = $value['start_time'];
            $list[$cnt]['end_time'] = $value['end_time'];
            $list[$cnt]['create_time'] = $value['create_time'];
            $list[$cnt]['path'] = completion_pic($value['path']);

            $cnt++;
        }

        returnJson(count($list)>0?$list:array(),200,'success');
    }

    /**
     * 获取置顶图片信息
     */
    public function stick(){
        $header = getHttpHeader();
        $clienttype = $header['CLIENTTYPE'];

        if($clienttype<=2){
            //TODO APP 端包含ios，android
            returnJson(null,'200','success');
        }else{
            //H5端
            //获取h5置顶图
            $condition = "s.cover_id = p.id and s.status=1 and s.h5_ismajor = 1 and ( s.start_time <= '".time()  ."' and '".time()."' <= s.end_time )";
            $data = M('slider')->table('__SLIDER__ s,__PICTURE__ p')
                -> field(array('s.title','s.link','s.create_time','p.path'))
                -> where($condition)
                -> order('s.create_time desc')
                -> find();

            $data['title']; //标题
            $id = $data['link'];   //链接

            if(strpos($value['link'],'http://') == 0 || strpos($value['link'],'https://') == 0){
                $data['link_type'] = 'link';
            } else {
                $data['link_type'] = 'item';
                $data['link'] = getLatestPeriodByShopId($data['link']);
            }

            $data['path'] = completion_pic($data['path']);

            $where = array('sid' => $id, 'state' => 0);
            $pid = M('shop_period')->where($where)->field('id')->find(); 
            
            //获取商品id获取最新周期详情
            $Shop = D('Shop');
            $detail = $Shop->detail($pid['id']);
            $data['price'] = $detail['price'];
            $data['surplus'] = $detail['surplus'];
            returnJson($data,'200','success');
        }
    }

    public function test(){
        $dataArray= file_get_contents(MODULE_PATH . 'Common/city.json');
        $data=json_decode($dataArray,TRUE)['citylist'];
        $count_data=count($data);
        $aa=array();
        header('Content-Type:application/json; charset=utf-8');
        for ($i = 0; $i < $count_data; $i++)
        {
            array_push($aa,$data[$i]['p']);
        }
        echo json_encode($aa);
    }

    /*
    * 获取晒单列表  
    */
    private function Shared($sid){
        $shared_list = D('User')->displays(1, '', 9999, $sid);
        returnJson($shared_list,'200','success');
    }
    // /**
    //  * @deprecated 中奖发送验证短信与邮件 id 中奖表的ID
    //  * */
    public function winningSendCode($id)
    {
        try{
            header('Content-Type:application/json; charset=utf-8');
            $period = M('shop_period')->where("id=".$id)->field('sid,uid,no')->find();
            $shopName=M('shop')->where("id=".$period['sid'])->getField('name');
            $shopName='【（第'.$period['no'].'期）'.$shopName.'】';
            $uid = M('user')->where("id=".$period['uid'])->getField('passport_uid');
            D('Notification')->sendPhoneAndEmail($uid,$shopName);
            exit;
        }catch(\Exception $e){
            
        }
    }

    /**
     * 增加虚拟币记录
     * @activity_type 1=大转盘 2=牛气冲天 3=系统赠送
     *
     */
    public function addPrizeRecord($uid,$activity_type,$num,$d_recharge,$d_active,$sn){
        $rs = D('Admin/GcouponRecord')->addRecord($uid,$activity_type,$num,$d_recharge,$d_active,$sn);
        $rs2 = M('user')->where(array('id'=>$uid))->setInc('gold_coupon',$num);
        if($rs && $rs2){
            returnJson('',200,'success');
        }else{
            returnJson('',301,'fail');
        }
    }

    /**
     * 系统增加虚拟币记录
     * @activity_type 1=大转盘 2=牛气冲天 3=系统赠送
     *
     */
    public function addGC4Admin($uid,$num,$d_recharge,$d_active,$sn){
        return $this->addPrizeRecord($uid,3,$num,$d_recharge,$d_active,$sn);
    }
}