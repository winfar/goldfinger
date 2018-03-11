<?php
namespace api\Model;

use Think\Model;

class GoldModel extends Model
{
    //用户提金
    public function draw($uid,$gold,$sid){

        if(!empty($uid) && !empty($gold) && $gold>0 && $sid>0){

            $map_user['id']=$uid;

            $user = M('user')->where($map_user)->find();

            if($user){

                $map_addr['uid']=$uid;
                $address = M('shop_address')->where($map_addr)->order('id desc')->find();

                if($address){

                    if($gold*1000 <= $user['gold_balance']){

                        $map_shop['id']=$sid;
                        $shop = M('shop')->where($map_shop)->find();

                        if($shop){

                            $map_channel['id']=$user['channelid'];
                            
                            try{
                                $gold_price = getGoldprice();//实时金价
                                // $extract_cost = M('channel')->where($map_channel)->getField('extract_cost');
                                $channel = M('channel')->where($map_channel)->find();

                                $extract_gold_persent = $channel['extract_gold_persent'];//提金百分比
                                $extract_gold_extra_expenses = $channel['extract_gold_extra_expenses'];//提金额外费用
                                $proportion = $channel['extract_gold_extra_expenses'];//兑换比例

                                // $cost = $gold * $extract_cost;
                                $cost = ($gold_price * $extract_gold_persent / 100 + $extract_gold_extra_expenses) * $proportion;

                                // $pay_json = D('api/Pay')->getCoins($uid);
                                // $pay = json_decode($pay_json, true);
                                // $balance = empty($pay['data']['amount_coin']) ? 0 : $pay['data']['amount_coin'];

                                $balance = $user['gold_coupon'];

                                if($cost > 0 && $cost <= $balance){

                                    $billid = 'GF' . think_md5(microtime(), $uid);
                                    $order_no = 'GF' . get_timestamp().rand(1000,9999);
                                    // $gold_price = getGoldprice();

                                    // $rs_cost = D('api/Pay')->cost(207, $uid, $billid, $order_no, $cost, '提金消耗', $gold);

                                    // if(!empty($rs_cost)){
                                    //     $http_result = json_decode($rs_cost,true);
                                    //     if($http_result['code']==200){

                                    //添加提金流水
                                    $data['order_id']=$order_no;
                                    $data['bill_id']=$billid;
                                    $data['uid']=$uid;
                                    $data['channel_id']=$user['channelid'];
                                    $data['sid']=$sid;
                                    $data['number']=$gold;
                                    $data['top_diamond']=$cost>0?0-$cost:$cost;
                                    $data['recharge_activity']=0;
                                    $data['gold_price']=$gold_price;//实时金价
                                    $data['order_status']=100;//100为已确认收货地址
                                    $data['order_status_time']=json_encode([$data['order_status']=>time()]);

                                    $data['contacts']=$address['nickname'];
                                    $data['phone']= trim($address['tel']);
                                    $data['email']= trim($address['email']);
                                    $data['address']= trim($address['address']);
                                    $data['create_time']=time();

                                    try{

                                        M()->startTrans();
                                        //减去虚拟币用量
                                        $rs_u_coupon = M('user')->where($map_user)->setDec('gold_coupon',$cost);
                                        //减去黄金余量
                                        $rs_u = M('user')->where($map_user)->setDec('gold_balance',$gold*1000);
                                        //添加流水
                                        $rs = M('user_cash')->add($data);

                                        $rs_msg = D('api/Message')->addUserMessage($uid,102,$rs);

                                        if($rs_u_coupon && $rs_u && $rs && $rs_msg){
                                            M()->commit();
                                            returnJson('',200,'success');
                                        }
                                        else{
                                            M()->rollback();
                                            returnJson($rs,401,'数据添加异常');
                                        }
                                    }
                                    catch(Exception $e){
                                        M()->rollback();
                                        returnJson(json_encode($e),501,'数据库异常');
                                    }
                                        // }
                                        // else{
                                        //     returnJson($rs_cost,402,'接口数据异常');
                                        // }
                                    // }
                                    // else{
                                    //     returnJson($rs_cost,403,'返回值为空');
                                    // }
                                }
                                else{
                                    returnJson(['cost'=>$cost,'balance'=>$balance],404,'余额不足');
                                }
                            }catch(Exception $e){
                                returnJson($e,500,'内部错误');
                            }
                        }
                        else{
                            returnJson($sid,411,'商品不存在');
                        }
                    }
                    else{
                        returnJson(['gold'=>$gold,'gold_balance'=>$user['gold_balance']*1000],405,'提金数量大于用户持有数量');
                    }
                }
                else{
                    returnJson($address,406,'未填写物流信息');
                }
            }
            else{
                returnJson($uid,407,'用户不存在');
            }
        }
        else{
            returnJson(['gold'=>$gold,'uid'=>$uid,'sid'=>$sid],408,'参数错误');
        }

        return $result; 
    }
    //用户提金
    public function drawcash($param = array()){
        $data = array();
        $code = 101;
        $msg = '';
        if(!empty($param['uid']) && !empty($param['goldPrice']) && !empty($param['number']) && !empty($param['total']) && !empty($param['cash']) && isset($param['cashType'])){
            $sid = $param['sid'];//商品id
            $uid = $param['uid'];//用户id
            $goldPrice = $param['goldPrice'];//实时金价
            $number = $param['number'];//提现毫克数
            $total = $param['total'];//提现总金额
            $cash = $param['cash'];//提现费用
            $cashType = $param['cashType'];//提现费用
            $map_user['u.id']=$uid;
            //判断用户是否存在
            $user = M('user')->alias('u')->where($map_user)->join('LEFT JOIN __CHANNEL__ c ON c.id=u.channelid')->field("u.*,c.extract_money,c.extract_number")->find();
            if($user){
                $max = 50000;
                //小于 最小起提克数
                if ($number<$user['extract_number']) {
                    $msg = '最小起提克数:'.$user['extract_number'].'mg';
                } elseif ($number>$user['gold_balance']*1000) {//拥有的黄金不足
                    $msg = '拥有的黄金不足';
                } elseif ($number>$max) {//大于 一次最多提取
                    $msg = '一次最多提取'.$max.'mg';
                } else {
                    //折合人民币费用
                    $buy_total = round((($goldPrice/1000)*$number)*100)/100;
                    //提金费用- 取两位小数 进一
                    $buy_cash = ceil((($goldPrice/1000)*($user['extract_money']/100)*$number)*100)/100;
                    if ($buy_total==$total and $buy_cash==$cash) {
                        if ($cashType==1 and empty($param['cashAccount'])) {
                            $msg = '支付宝帐号不能为空';
                        } elseif ($cashType==1 and empty($param['cashName'])) {
                            $msg = '支付宝姓名不能为空';
                        } else {
                            //添加提金流水
                            $extract = array();
                            $order_no = 'TX' . get_timestamp().rand(1000,9999);
                            $extract['order_no']=$order_no;
                            $extract['uid']=$uid;
                            $extract['channel_id']=$user['channelid'];
                            $extract['sid']=$sid;
                            $extract['number']=$number;
                            $extract['gold_price']=$goldPrice;
                            $extract['total_money']=$buy_total;
                            $extract['procedures_money']=$buy_cash;
                            $extract['money']=$buy_total-$buy_cash;
                            $extract['cash_type'] = $cashType;
                            $extract['percentage'] = $user['extract_money'];
                            if ($cashType==1) {
                                $extract['cash_account'] = $param['cashAccount'];
                                $extract['cash_name'] = $param['cashName'];
                            }
                            $extract['create_time']=time();

                            try{

                                M()->startTrans();
                                //减去黄金余量
                                $rs_u = M('user')->where('id='.$uid)->setDec('gold_balance',$number);
                                $rs = M('user_extract')->add($extract);

                                if($rs_u && $rs){
                                    M()->commit();
                                    $code = 200;
                                    $msg = "成功";
                                }
                                else{
                                    M()->rollback();
                                    $msg = "系统繁忙";
                                }
                            }
                            catch(Exception $e){
                                M()->rollback();
                                $msg = "数据库异常";
                            }

                        }

                    } else {
                        $msg = '金额不正确';
                    }
                }
            }
            else{
                $msg = '用户不存在';
            }
        }
        else{
            $msg = '参数错误';
            //returnJson(array() ,101,'参数错误');
        }

        returnJson($data ,$code,$msg);
    }
}