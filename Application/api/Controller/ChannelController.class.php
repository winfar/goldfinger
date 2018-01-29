<?php
namespace api\Controller;
use Think\Controller;
class ChannelController extends BaseController
{

    /**
     *  用户绑定地推渠道
     * @author richie.hao
     * @date 20161213
     * @param $code 地推邀请码
     * @param $uid 用户id
     */
    public function bindUser4spread($code,$tokenid)
    {

        if(empty($code) || empty($tokenid)){
            returnJson('', 401, '参数错误');
        }

        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        $uid = $user['uid'];
        //检查用户是否绑定过其它渠道（用户绑定信息中是否有邀请码）
        $invitationid = M('user')->where(array('id'=>$uid))->getField('invitationid');
        if(empty($invitationid)|| $invitationid == $code){
            $channelid = M('invitation')->where(array('id'=>$code))->getField('channelid');
            if ( $channelid ) {
                $data['channelid'] = $channelid;
                $data['invitationid'] = $code;
                $count = M('user')->where(array('id'=>$uid))->setField($data);
                if($count){
                    //绑定成功
                    returnJson('', 200, '绑定成功');
                }elseif($count == 0){
                    returnJson('', 201, '您已绑定该渠道');
                }else{
                    //绑定失败
                    returnJson('', 402, '绑定失败');
                }
            }else{
                returnJson('', 404, '地推邀请码渠道不存在');
            }
        } else {
            returnJson('', 403, '您已绑定过其它渠道');
        }
    }

    public function _before_bindProfit4Channel(){
        G('begin');
        recordLog('================ START 绑定用户订单的利润归属渠道 '.'time=>'.time_format(time()).'===================',__METHOD__);
    }

    /**
     *      * 绑定利润归属渠道关系
     * @param int $rebind 强制重新绑定 0=》否  1=》强制绑定
     */
    public function bindProfit4Channel($rebind=0){
        if($rebind == 1){
            recordLog('获取shop_order 表中所有数据重新绑定',__METHOD__);
            //获取shop_order 表中所有数据重新绑定
            $data = M('shop_order')->getField('id,uid,pid');
        }else{
            recordLog('获取shop_order 表中所有channel_id_profit = 空的数据重新绑定',__METHOD__);
            //获取shop_order 表中所有channel_id_profit = 空的数据重新绑定
            $data = M('shop_order')->where(array('channel_id_profit'=>array('EXP','IS NULL')))->getField('id,uid,pid');
        }

        recordLog('待进行绑定的总数=>'.count($data),__METHOD__);
        $i = 0;
        $valid_i = 0;
        foreach ($data as $k => $v) {
            $i++;
            $channel_id_profit = D('pay')->getProfitChannel($v['uid'],$v['pid']);
            if($channel_id_profit){
                $rs = M('shop_order')->where(array('id'=>$v['id']))->setField('channel_id_profit',$channel_id_profit);
                recordLog('计数=>'.$i.' 重新绑定用户uid['.$v['uid'].']订单id['.$v['id'].']的利润归属渠道['.$channel_id_profit.'] 处理结果=>'.$rs,__METHOD__);

                if($rs) $valid_i++;
            }
        }
        $str = '完成利润归属绑定处理：计数=>'.$i.' 重新绑定用户处理结果>0 计数为=>'.$valid_i;
        recordLog($str,__METHOD__);
        echo $str;
    }

    public function _after_bindProfit4Channel(){
        G('end');
        recordLog('================ END 绑定用户订单的利润归属渠道 '.'time=>'.time_format(time()).'=================== 耗时'.G('begin','end').'s',__METHOD__);
    }

    public function updateChnannelId4Batch($limit=10000){ 
        $data = M('shop_order')->alias('o')
//            ->table('__SHOP_ORDER__ o ,__CAPITALFLOW__ c')
            ->join('LEFT JOIN __CAPITALFLOW__ c ON o.id = c.order_id')
            ->where(array('c.channel_id_profit'=>0))
            ->limit($limit)
//            ->where('o.id = c.order_id ')
            ->getField('o.id,o.channel_id_profit');

        $ids = implode(',', array_keys($data));
        $sql = "UPDATE hx_capitalflow c SET c.channel_id_profit = CASE c.order_id ";
        foreach ($data as $id => $channel_id_profit) {
            $sql .= sprintf("WHEN %d THEN %d ", $id, $channel_id_profit);
        }
        $sql .= "END WHERE c.order_id IN ($ids)";
        echo $sql;

        $result = M()->execute($sql);
         var_dump($result);
        
    }

}