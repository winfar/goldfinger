<?php
namespace api\Controller;
use Think\Cache\Driver\RedisCache;
use Think\Controller;

class ShopController extends BaseController
{

    /**
     * @deprecated 地址状态
     * @author zhangkang
     * @date 2016-7-21
     **/
    public function addOrderAddress()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'addOrderAddress');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            return returnJson('', 1, '您还未登录！');
        }
        if ( isEmpty($json['contacts']) ) {
            return returnJson('', 1, '联系人不能为空！');
        }
        if ( isEmpty($json['phone']) ) {
            return returnJson('', 1, '联系电话不能为空！');
        }
        if ( isEmpty($json['address']) ) {
            return returnJson('', 1, '详细不能为空！');
        }
        if ( isEmpty($json['pid']) ) {
            return returnJson('', 1, '期ID不能为空！');
        }

        $userAddress = D('User')->addOrderAddress($json);
    }

    /**
     * @deprecated 更新状态
     * @author zhangkang
     * @date 2016-7-21
     **/
    public function  updateOrderStatus()
    {
        $result = file_get_contents('php://input');
        recordLog($result, 'updateOrderStatus');
        $json = json_decode($result, true);

        if ( isEmpty($json['tokenid']) ) {
            return returnJson('', 1, '您还未登录！');
        }
        if ( isEmpty($json['pid']) ) {
            return returnJson('', 1, '期ID不能为空！');
        }
        if ( isEmpty($json['order_status']) ) {
            return returnJson('', 1, '状态不能为空！');
        }

        $userAddress = D('User')->updateOrderStatus($json);
    }

    /**
     * @deprecated 最新、往期揭晓列表
     * @author wenyuan
     * @date 2016-07-07
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     **/
    public function announcedList($pageindex = 1, $pagesize = 20  ,$pcode = '')
    {
        //$pageindex = 1, $pagesize = 20
        try
        {
            // $pageindex = empty($params['pageindex']) ? 0 : $params['pageindex'];
            // $pagesize = empty($params['pagesize']) ? 0 : $params['pagesize'];

            // if(is_numeric($pageindex) && is_numeric($pagesize)){
            //     if($pageindex < 1){
            //         $pageindex=1;
            //     }

            //     if($pagesize < 1){
            //         $pagesize=20;
            //     }
            // }
            // else {
            //     returnJson('', 401, '参数类型错误');
            // }

            $announced = D('Shop')->announcedList($pageindex, $pagesize, $pcode);
            returnJson($announced, '200', 'success');
        } catch(\Exception $e){
            returnJson('', 500, $e->getMessage());
        }
    }

    /**
     * @deprecated 最新、往期揭晓列表
     * @author wenyuan
     * @date 2016-07-07
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $type 1:最新揭晓(默认),2:往期揭晓
     * @param $pids 周期id数组
     **/
    public function announced()
    {
        //$pageindex = 1, $pagesize = 20, $type = 1, $pids = array()
        try
        {
            $jsonInput = file_get_contents('php://input');
            //$jsonInput = '{"pageindex":1,"pagesize":20,"type":2,"pids":[15,17]}';
            recordLog($jsonInput, 'announced');
            $params = json_decode($jsonInput, true);

            //var_dump($params);exit();

            $pageindex = empty($params['pageindex']) ? 0 : $params['pageindex'];
            $pagesize = empty($params['pagesize']) ? 0 : $params['pagesize'];
            $pcode = $params['pcode'];

            if(is_numeric($pageindex) && is_numeric($pagesize) && is_numeric($params['type'])){
                if($pageindex < 1){
                    $pageindex=1;
                }

                if($pagesize < 1){
                    $pagesize=20;
                }
            }
            else {
                returnJson('', 401, '参数类型错误');
            }

            if ( $params['type'] <= 0 ) {
                returnJson('', 402, 'state必须大于0');
            }

            $announced = D('Shop')->announced($pageindex, $pagesize, $params['type'], $params['pids'], $params['end_time'] ,$pcode);
            returnJson($announced, '200', 'success');

        } catch(\Exception $e){
            returnJson('', 500, $e->getMessage());
        }
    }

    /**
     * @deprecated 按商品id获取往期揭晓列表
     * @author wenyuan
     * @date 2016-07-026
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $type 1:最新揭晓(默认),2:往期揭晓
     * @param $pids 周期id数组
     **/
    public function history($pageindex=1,$pagesize=20,$sid,$no=null){
		try
        {
            if(!$sid){
                returnJson('', 401, '商品id不能为空');
            }

            if(is_numeric($pageindex) && is_numeric($pagesize) && is_numeric($sid))
            {
                $history=D("Shop")->history($pageindex,$pagesize,$sid,$no);
                returnJson($history, 200, 'success');
            }
            else {
                returnJson('', 402, '参数类型错误');
            }

        } catch(\Exception $e){
            returnJson('', '500', $e->getMessage());
        }
	}

    /**
     * @deprecated 商品分类接口
     * @author zhangran
     * @date 2016-07-04
     **/
    public function category()
    {
        $categoryList = D('Category')->getTree();
        $data = array();
        if ( $categoryList ) {
            foreach ( $categoryList as $k => $v ) {
                $data[$k]['id'] = $v['id'];        //分类ID
                $data[$k]['title'] = $v['title'];    //分类标题
                $data[$k]['icon'] = completion_pic($v['iconpath']);    //图片路径
            }
        }

        $all = array('id' => '0', 'title' => '全部商品', 'icon' => completion_pic('/Picture/ProductCategory/100@2x.png'));

        array_unshift($data, $all);

        returnJson($data);
    }

    /**
     * @deprecated 商品列表，首页推荐列表
     * @author wenyuan
     * @date 2016-07-10
     * @param $cid 商品类别编号（默认0，全部商品）
     * @param $pageindex 页码(默认第1页)
     * @param $pagesize 每页记录数(默认20)
     * @param $order 排序方式，hits:最热(默认),<br/>latest:最新,<br/>progress:进度
     * @param $ten 是否十元专区商品，默认(0:否)
     **/
    public function period($cid = 0, $pageindex = 1, $pagesize = 20, $order = 'hits', $ten = 0 ,$pcode = '')
    {
        // if($_SERVER['HTTP_HOST']=='onlinetest.passport.busonline.com'){
        //     $opts = array(
        //     'http'=>array(
        //         'method'=>"GET",
        //         'header'=>"APPVERSION:475d514697e0aee1d56c24a1332a3cd8"
        //     )
        //     );

        //     $context = stream_context_create($opts);


        //     $url = "http://passport.busonline.com/api.php?s=/shop/period/&cid=".$cid."&pageindex=".$pageindex."&pagesize=".$pagesize."&order=".$order."&ten=0";
        //     //return file_get_contents($url);
        //     $content = file_get_contents($url,false,$context);
        //     header('Content-Type:application/json; charset=utf-8');
    
        //     exit($content);
        // }
        // else{
        //     $shop = D('Shop')->period($cid, $pageindex, $pagesize, $order, $ten);
        //     returnJson($shop, 200, 'success');
        // }

        $shop = D('Shop')->period($cid, $pageindex, $pagesize, $order, $ten ,$pcode);
        returnJson($shop, 200, 'success');
    }



    //输入验证码接口
    public function verificationcode($code='',$tokenid,$cid){
        
        D('shop')->verificationcode($code,$tokenid,$cid);

    }


    /**
     * @deprecated 商品最后50条记录
     * @author wenyuan
     * @date 2016-07-25
     * @param $pid 商品周期编号
     **/
    public function lastPurchaseRecords($pid){
		$lastRecorder = D("Shop")->calculate($pid);
        returnJson($lastRecorder, 200, 'success');
	}

    /**
     * @deprecated 夺宝分类接口
     * @author <zhangran></zhangran>
     * @date 2016-07-05
     **/
    public function indiana()
    {
        //	测试cid分类ID、type(hits人气、news最新、progress进度)--order(1asc 2desc)
        //	$request_s = '{"cid":"9","type":"hits","order":"2","page":"1"}';
        $request_s = file_get_contents("php://input");
        if ( empty($request_s) ) {
            returnJson('', 201, '数据不能为空');
        }

        //记录LOG
        recordLog($request_s, "夺宝分类request");
        $request = json_decode($request_s, true);
        if ( !empty($request['type']) ) {
            if ( $request['order'] == 1 ) {
                $orderstr = "asc";
            } else {
                $orderstr = "desc";
            }
            if ( $request['type'] == "hits" ) {            //人气
                $order = "shop.hits|" . $orderstr;
            } elseif ( $request['type'] == "news" ) {    //最新
                $order = "id|" . $orderstr;
            } elseif ( $request['type'] == "progress" ) {//进度
                $order = "	period.number/shop.price*100|" . $orderstr;
            }
        } else {
            $order = "shop.hits|desc";    //默认
        }

        $shop = D('Shop')->period($request['cid'], $request['page'], 20, $order, 0);
        returnJson($shop, 200, 'success');
    }

    /**
     * @deprecated 未揭晓商品详情API
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function detail($id,$pcode='')
    {
        if ( isEmpty($id) ) {
            return returnJson('', 401, '商品ID不能为空');
        }
        $info = D("Shop")->detail($id,$pcode);
        if ( $info == false ) {
            return returnJson('', 404, '商品已下架！');
        }

        return returnJson($info, 200, 'success');
    }

    /**
     * @deprecated 商品购买记录列表
     * @author wenyuan
     * @date 2016-07-20
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $state 1:最新揭晓(默认),2:往期揭晓
     * @param $pid 周期id
     **/
    public function RecordList($pageindex = 1, $pagesize = 20, $pid = '', $uid = '')
    {
        try{

            if (isEmpty($pid)) {
                return returnJson('', 401, '商品周期ID不能为空');
            }

            $record = D('Shop')->record($pid, $pageindex, $pagesize, $uid);

            if ( !$record ) {
                $record = array();
            }

            returnJson($record, '200', 'success');
            
        } catch(\Exception $e){
            returnJson('', '500', $e->getMessage());
        }
    }

    /**
     * @deprecated 获取商品晒单列表
     * @author wenyuan
     * @date 2016-07-19
     * @param $sid 商品编号shopid，必须
     * @param $pageindex 页码(默认第1页)
     * @param $pagesize 每页记录数(默认20)
     * @param $tokenid 如果是数字就查询其他人的晒单记录
     * @param $type 1人气 2 时间
     **/
    public function SharedList($sid = 0, $pageindex = 1, $pagesize = 20, $tokenid = 0, $type = 2)
    {
        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的晒单记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }

        try{
            $shared_list = D('User')->shopdisplays($pageindex, $map['uid'], $pagesize, $sid, $type);

            if ( !$shared_list ) {
                $shared_list = array();
            }

            returnJson($shared_list, 200, 'success');

        } catch(\Exception $e){
            returnJson('', 500, $e->getMessage());
        }
    }

    /**
     * @deprecated 获取商品晒单列表合并pk
     * @author gengguanyi
     * @date 2016-10-17
     * @param $sid 商品编号shopid，必须
     * @param $pageindex 页码(默认第1页)
     * @param $pagesize 每页记录数(默认20)
     * @param $tokenid 如果是数字就查询其他人的晒单记录
     **/
     public function SharedListNew($sid = 0, $pageindex = 1, $pagesize = 20, $tokenid = 0)
    {
        $map = array();
        if ( is_numeric($tokenid) ) {//如果是数字就查询其他人的晒单记录
            $map['uid'] = $tokenid;
        } else {
            $user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $map['uid'] = $user['uid'];
        }

        try{
            $shared_list = D('User')->shopdisplaysnew($pageindex, $map['uid'], $pagesize, $sid);

            if ( !$shared_list ) {
                $shared_list = array();
            }

            returnJson($shared_list, 200, 'success');

        } catch(\Exception $e){
            returnJson('', 500, $e->getMessage());
        }
    }



    /**
     * @deprecated 已揭晓商品详情API
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function overdetail($id)
    {
        if ( isEmpty($id) ) {
            return returnJson('', 401, '商品ID不能为空');
        }
        $info = D("Shop")->overdetail($id);

        return returnJson($info, 200, 'success');
    }
}