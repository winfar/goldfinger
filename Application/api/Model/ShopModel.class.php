<?php
namespace api\Model;

use Think\Model;
use Think\Cache\Driver\RedisCache;

class ShopModel extends Model
{

    protected $md5_key = "busonline";

    /**
     * @deprecated 最新、往期揭晓列表
     * @author wenyuan
     * @date 2016-07-07
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     **/
    public function announcedList($pageindex = 1, $pagesize = 20, $pcode = '')
    {
        $orderBy = 'state asc,kaijang_time DESC,end_time DESC';
        $condition['state'] = array(array('egt',1),array('lt',3));
        $period = M('shop_period')->where($condition)->field(true)->page($pageindex, $pagesize)->order($orderBy)->select();
        //echo M()->getLastSql();
        $list = array();
        $condition['state'] = array(array('egt', 1), array('lt', 3));

        //$condition['s.category'] = array('not in', '17');
        //增加私有商品处理
        if ( isPCodeProc($pcode) ) {
            $condition['s.private'] = 1;
        } else {
            $condition['s.private'] = 0;
        }

        $period = M('shop_period')
            ->field('p.*')
            ->table('__SHOP_PERIOD__ p,__SHOP__ s')
            ->where('p.sid=s.id')
            ->where($condition)->page($pageindex, $pagesize)->order($orderBy)->select();

        //$period = M('shop_period')->where($condition)->field(true)->page($pageindex, $pagesize)->order($orderBy)->select();
        // echo M()->getLastSql();exit();

        if ( $period ) {
            foreach ( $period as $k => $v ) {
                //判断商品是pk商品还是普通摸金
                $is_common = $v['iscommon'];
                $info = M('shop')->field(true)->find($v["sid"]);
                $pkinfo = array();
                if ($is_common != 1) {
                    //获取pk商品相关信息
                    $pkinfo = M('house_manage')
                    ->table('__HOUSE_MANAGE__ house,__PKCONFIG__ pkconfig')
                    ->field('house.ispublic,house.id as houseid,house.no as room_no,pkconfig.peoplenum,pkconfig.amount')
                    ->where('house.id='.$v['house_id'].' AND house.pksetid=pkconfig.id')
                    ->find();
                }
                if($is_common == 1){//普通商品
                    $list[$k] = D('Shop')->overChange($info, $v);
                }else{//pk商品
                    //增加用到的参数
                    $v['amount'] = $pkinfo['amount'];//pk商品总价钱
                    $v['peoplenum'] = $pkinfo['peoplenum'];//pk购买人数
                    $list[$k] = D('Shop')->pkoverChange($info, $v);
                    $list[$k]['houseid'] = $pkinfo['houseid'];//房间id
                    $list[$k]['room_no'] = $pkinfo['room_no'];//房间编号
                    $list[$k]['ispublic'] = $pkinfo['ispublic'];//房间公开与否0：公开房间 1：私密房间
                    $list[$k]['price'] = $pkinfo['peoplenum'];//总需要参与人数
                }
                $list[$k]['user'] = get_user_name($v["uid"]);
                $list[$k]['avatar'] = get_user_pic_passport($v["uid"]);
                $list[$k]['count'] = M('shop_record')->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->sum('number');
                $list[$k]['iscommon'] = $is_common;
                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description']);
            }
        }
        return $list;
    }

    /**
     * @deprecated 最新、往期揭晓列表
     * @author wenyuan
     * @date 2016-07-07
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $state 1:最新揭晓(默认),2:往期揭晓
     * @param $pids 周期ids
     * @param $end_time 周期结束时间
     **/
    public function announced($pageindex = 1, $pagesize = 20, $state = 1, $pids = null, $end_time = null, $pcode = '')
    {
        // if($_SERVER['HTTP_HOST']!='passport.busonline.com'){
        //     $condition['iscommon']=2;//临时调试pk小喇叭
        // }

        $orderBy = 'state asc,kaijang_time DESC,end_time DESC';
        $pidsStr = implode(',', $pids);

        //增加私有商品处理
        // if ( isPCodeProc($pcode) ) {
        //     $condition['s.private'] = '1';
        // } else {
        //     $condition['s.private'] = '0';
        // }

        if ( $pidsStr ) {
            //$period = M('shop_period')->where(array('id' => array('in', $pidsStr), 'state' => $state))->field(true)->page($pageindex, $pagesize)->order($orderBy)->select();

            $condition['p.id'] = array('in', $pidsStr);
            $condition['state'] = $state;

            //$condition['s.category'] = array('not in', '17');

            $period = M('shop_period')
                ->field('p.*')
                ->table('__SHOP_PERIOD__ p,__SHOP__ s')
                ->where('p.sid=s.id')
                ->where($condition)
                ->page($pageindex, $pagesize)->order($orderBy)->select();

        } else {
            if ( $end_time ) {
                $condition['end_time'] = array('lt', $end_time);
            }

            $condition['state'] = $state;

            //$period = M('shop_period')->where($condition)->field(true)->page($pageindex, $pagesize)->order($orderBy)->select();

            //$condition['s.category'] = array('not in', '17');

            $period = D('shop_period')
                ->field('p.*')
                ->table('__SHOP_PERIOD__ p,__SHOP__ s')
                ->where('p.sid=s.id')
                ->where($condition)
                ->page($pageindex, $pagesize)->order($orderBy)->select();
        }

        // echo M()->getLastSql();exit();
        
        if ( $period ) {
            foreach ( $period as $k => $v ) {
                //判断商品是pk商品还是普通摸金
                $is_common = $v['iscommon'];
                $info = M('shop')->field(true)->find($v["sid"]);
                $pkinfo = array();
                if ($is_common != 1) {
                    //获取pk商品相关信息
                    $pkinfo = M('house_manage')
                    ->table('__HOUSE_MANAGE__ house,__PKCONFIG__ pkconfig')
                    ->field('house.ispublic,house.id as houseid,house.no as room_no,pkconfig.peoplenum,pkconfig.amount')
                    ->where('house.id='.$v['house_id'].' AND house.pksetid=pkconfig.id')
                    ->find();
                }
                $list[$k] = D('api/Shop')->overChange($info, $v);
                // if($is_common == 1){//普通商品
                //     $list[$k] = D('api/Shop')->overChange($info, $v);
                // }else{//pk商品
                //     //增加用到的参数
                //     $v['amount'] = $pkinfo['amount'];//pk商品总价钱
                //     $v['peoplenum'] = $pkinfo['peoplenum'];//pk购买人数
                //     $list[$k] = D('Shop')->pkoverChange($info, $v);
                //     $list[$k]['houseid'] = $pkinfo['houseid'];//房间id
                //     $list[$k]['room_no'] = $pkinfo['room_no'];//房间编号
                //     $list[$k]['ispublic'] = $pkinfo['ispublic'];//房间公开与否0：公开房间 1：私密房间
                //     $list[$k]['price'] = $pkinfo['peoplenum'];//总需要参与人数
                // }
                
                $list[$k]['user'] = get_user_name($v["uid"]);
                $list[$k]['avatar'] = get_user_pic_passport($v["uid"]);
                $list[$k]['count'] = M('shop_record')->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->sum('number');
                $list[$k]['real_amount'] = intval(($list[$k]['unit'])) * intval($list[$k]['count']);
                $list[$k]['iscommon'] = $is_common;
                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description']);
            }
        }
        if ( $pidsStr ) {
            recordLog(M()->getLastSql(), 'announced result sql');
            recordLog($list, 'announced result');
        }

        return $list;
    }
    /**
     * 最新揭晓
     * 
     * @author liuwei
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $state 1:最新揭晓(默认),2:往期揭晓
     * @param $pids 周期ids
     * @param $end_time 周期结束时间
     **/
    public function announcedNew($pageindex = 1, $pagesize = 20, $state = 1)
    {
        $orderBy = 'state asc,kaijang_time DESC,end_time DESC';

        $condition['state'] = $state;
        $condition['exchange_type'] = 0;

        $period = D('shop_period')
            ->field('p.*')
            ->table('__SHOP_PERIOD__ p,__SHOP__ s')
            ->where('p.sid=s.id')
            ->where($condition)
            ->page($pageindex, $pagesize)->order($orderBy)->select();

        // echo M()->getLastSql();exit();
        $list = array();
        if ( $period ) {
            foreach ( $period as $k => $v ) {
                $info = M('shop')->field(true)->find($v["sid"]);
                $list[$k] = $this->overChangeNew($info, $v);                
                $list[$k]['user'] = get_user_name($v["uid"]);
                $list[$k]['avatar'] = get_user_pic_passport($v["uid"]);
                $list[$k]['count'] = M('shop_record')->where("uid=" . $v["uid"] . " and pid=" . $v["id"])->sum('number');
                $list[$k]['real_amount'] = intval(($list[$k]['unit'])) * intval($list[$k]['count']);
                unset($list[$k]['content'], $list[$k]['meta_title'], $list[$k]['keywords'], $list[$k]['description']);
            }
        }
        if ( $pidsStr ) {
            recordLog(M()->getLastSql(), 'announced result sql');
            recordLog($list, 'announced result');
        }

        return $list;
    }

    /**
     * @deprecated 往期揭晓&&最新揭晓API
     * @author zhangran
     * @date 2016-07-05
     **/
    public function announcedApi($pageindex = 1, $pagesize = 20, $state = 1, $pid = null)
    {
        $data = array();
        $period = M('shop_period')->where('state=' . $state)->field(true)->page($pageindex, $pagesize)->order('state asc,kaijang_time desc')->select();
        if ( $period ) {
            foreach ( $period as $k => $v ) {
                $data[$k]["kaijang_time"] = time_format($v["kaijang_time"], "Y-m-d H:i:s");    //开奖时间
                $data[$k]["kaijang_num"] = $v['kaijang_num'];                                    //开奖号码
                $data[$k]["number"] = $v["number"];                                            //参与人数
                $data[$k]['user_name'] = get_user_name($v["uid"]);    //获奖者名称
                $data[$k]['user_uid'] = $v["uid"];                                //获奖者ID
                $data[$k]['user_pic'] = get_user_pic_passport($v["uid"]);//获奖者头像
                $data[$k]['state'] = $v["state"];                                //往期揭晓2、最新揭晓1
            }
        }
        return $data;
    }


    //输入邀请码
    public function verificationcode($code, $tokenId, $cid)
    {
        //存入
        $redisCache = new RedisCache();
        if ( $cid == 17 ) {
            if ( isEmpty($tokenId) ) {
                returnJson('', 100, '用户未登录');
            }
            $user = isLogin($tokenId);
            if ( !$user ) {
                returnJson('', 100, '用户未登录或者登录已超时！');
            }
            //用户id
            $uid = $user['uid'];
            //验证码
            $verificationcode = $user['verificationcode'];

            $userlimit = isUserLimit($uid);

            if ( empty($code) ) {
                //判断验证码是否正确
                if ( !empty($verificationcode) ) {
                    if ( $verificationcode == '123456' ) {
                        returnJson('', 200, 'success!');
                    } else {
                        returnJson('', 401, '请输入邀请码！');
                    }
                } else {
                    returnJson('', 401, '请输入邀请码！');
                }
            } else {
                if ( $code == '123456' ) {
                    if ( $userlimit ) {
                        $time = $userlimit['time'];//错误次数
                        $limit = $userlimit['limit'];//限制时间
                        if ( $time >= 5 ) {
                            $now_time = time();
                            if ( $now_time >= $limit ) {
                                returnJson('', 402, '大侠,您输入次数太多,明日再来~');
                            } else {
                                $redisCache->set($uid, array(//存储token 并且存储相关对应用户信息
                                    'time' => 1,
                                    'limit=' => strtotime(date('Y-m-d', strtotime('+1 day'))),
                                ), 86400);
                                returnJson('', 401, '邀请码错误！');
                            }
                        } else {
                            $redisCache->set($tokenId, array(//存储token 并且存储相关对应用户信息
                                'uid' => $user['uid'],
                                'passportuid' => $user['passportuid'],
                                'username' => $user['username'],
                                'verificationcode' => $code,
                            ), 2592000);
                            returnJson('', 200, 'success!');
                        }

                    } else {
                        $redisCache->set($tokenId, array(//存储token 并且存储相关对应用户信息
                            'uid' => $user['uid'],
                            'passportuid' => $user['passportuid'],
                            'username' => $user['username'],
                            'verificationcode' => $code,
                        ), 2592000);
                        returnJson('', 200, 'success!');
                    }
                } else {
                    if ( !$userlimit ) {
                        $redisCache->set($uid, array(//存储token 并且存储相关对应用户信息
                            'time' => 1,
                            'limit=' => strtotime(date('Y-m-d', strtotime('+1 day'))),
                        ), 86400);
                        returnJson('', 401, '邀请码错误！');
                    } else {
                        $time = $userlimit['time'];//错误次数
                        $limit = $userlimit['limit'];//限制时间
                        if ( $time >= 5 ) {
                            $now_time = time();
                            if ( $now_time >= $limit ) {
                                returnJson('', 402, '大侠,您输入次数太多,明日再来~');
                            } else {
                                $redisCache->set($uid, array(//存储token 并且存储相关对应用户信息
                                    'time' => 1,
                                    'limit=' => strtotime(date('Y-m-d', strtotime('+1 day'))),
                                ), 86400);
                                returnJson('', 401, '邀请码错误！');
                            }
                        } else {
                            $redisCache->set($uid, array(//存储token 并且存储相关对应用户信息
                                'time' => $time + 1,
                                'limit=' => strtotime(date('Y-m-d', strtotime('+1 day'))),
                            ), 86400);
                            returnJson('', 401, '邀请码错误！');
                        }
                    }
                }
            }
        } else {
            returnJson('', 200, 'success!');
        }
    }


    /**
     * @deprecated 商品详情
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function detail($id, $pcode = null)
    {
        $condition = '';
        //增加私有商品处理
        // if ( isPCodeProc($pcode) ) {
        //     $condition .= ' and shop.private = 1';
        // } else {
        //     $condition .= ' and shop.private = 0';
        // }

        $info = M('shop_period')
            ->table('__SHOP__ shop,__SHOP_PERIOD__ period')
            ->field('shop.id as sid,shop.category,shop.name,shop.shopsubtitle,shop.cover_id,shop.price,shop.status,shop.content,shop.display,shop.ten,shop.videourl,period.number,period.no,period.id,period.state,period.uid,period.kaijang_time,period.kaijang_num,period.kaijiang_count,period.kaijiang_ssc,period.end_time,shop.is_full,shop.full_price,shop.shopstock')
            ->where('shop.id=period.sid and period.id=' . $id . $condition)
            ->find();

        // echo M()->getLastSql();exit();

        if ( !(is_array($info)) || 1 != $info['status'] || 1 != $info['display'] ) {
            return false;
        }

        if ( $info["state"] >= 1 ) {
            $info["kaijang_diffe"] = ($info["kaijang_time"] - NOW_TIME + 10) * 1000; //开奖倒计时时间，在开奖时间的基础上加10秒
            $info['next_pid'] = getLatestPeriodByShopId($info['sid']);
        }

        $info['winner'] = null;

        if ( $info['state'] == 2 ) {
            $winner['pid'] = $info['id'];
            $winner['uid'] = $info['uid'];
            $winner['uname'] = get_user_name($info["uid"]);
            $winner['avatar'] = get_user_pic_passport($info['uid']);
            $winner['no'] = $info['no'];
            $winner['kaijang_num'] = $info['kaijang_num'];
            $winner['kaijang_time'] = $info['kaijang_time'];
            $winner['kaijiang_count'] = $info['kaijiang_count'];
            $winner['kaijiang_ssc'] = $info['kaijiang_ssc'];
            $winner['end_time'] = $info['end_time'];

            $winner['record'] = $this->user_num($info['uid'], $info['id']);
            $winner['record_count'] = count($winner['record']);
            $info['winner'] = $winner;

            $info['count'] = M('shop_period')->field('max(no) as maxno,min(no) as minno')->where('sid=' . $info['sid'] . ' and state=2')->find();
            
        }

        $info = $this->shopChange($info);
        //获取兑换比例
        $set_item = M('exchange_set')->where('type=1')->field('number')->order('id desc')->find();
        $set_number = empty($set_item['number']) ? 1 : $set_item['number'];
        $info['set_number'] = $set_number;//兑换比例
        $info['full_price'] *= $set_number;//兑换比例
        $info['surplus_price'] = $info['restrictions']['unit']*$info['surplus']*$set_number;//包尾钻石数
        unset($info['uid'], $info['kaijang_num'], $info['kaijiang_ssc'], $info['end_time']);

        $this->addhit($info['sid']);

        return $info;
    }

    /**
     * 已揭晓商品详情API(已合并至商品详情)
     * @author zhangkang
     * @date 2016-07-05
     **/
    public function overdetail($id)
    {
        $period = M('shop_period')->where("id=" . $id)->field(true)->find();
        $info = $this->field(true)->find($period["sid"]);
        $info = D('Shop')->overChange($info, $period);
        $info['user'] = D('User')->userChange($info);
        $info['user_no'] = $this->user_num(UID, $info['pid']);
        $attend = $this->user_num($period["uid"], $period["id"]);
        $info['attend_no'] = $attend;
        $info['count'] = count($attend);

        $shared = D('User')->displays(array(1, '', 9999, $info['sid']));
        $info['shared'] = $shared;
        $record = D('Shop')->record($period['id'], 1);
        $info['record'] = $record;
        return $info;
    }

    /**
     * 获取商品列表，推荐列表
     * @date  20160719 updated
     * @param $cid 商品分类编号
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $order hits|latest|progress 最热，最新，进度
     * @param $ten 是否十元
     * @return json
     */
    public function period($cid = 0, $pageindex = 1, $pagesize = 20, $order = 'hits', $ten = 0, $pcode = null,$iscommon = 1)
    {
        if ( $cid ) {
            $condition = ' and shop.category=' . $cid;
        } else {
            //$condition = ' and shop.category not in(14,17)';
        }

        // if ( $iscommon ) {
        //     $condition .= ' and period.iscommon in ('.$iscommon.')';
        // }

        if ( $ten ) {
            $condition .= ' and shop.ten=' . $ten;
        }

        //增加私有商品处理
        // if ( isPCodeProc($pcode) ) {
        //     $condition .= ' and shop.private = 1';
        // } else {
        //     $condition .= ' and shop.private = 0';
        // }

        $defaultOrder = 'shop.hits desc,(period.number * t.unit / shop.price) desc,id desc';

        switch ( $order ) {
            case 'hits':
                $orderBy = $defaultOrder;
                break;
            case 'latest':
                $orderBy = 'period.create_time desc';
                break;
            case 'progress':
                $orderBy = '(period.number * t.unit / shop.price) desc';
                $condition .= '  and (period.number * t.unit / shop.price) > 0';
                break;
            default:
                $orderBy = $defaultOrder;
        }

        // $period = M('shop_period')
        //     ->table('__SHOP__ shop,__SHOP_PERIOD__ period,__TEN__ t')
        //     ->field('shop.id as sid,shop.name,shop.cover_id,shop.price,shop.ten,shop.category as cid,period.number,MAX(period.no) no,period.id')
        //     ->where('shop.ten=t.id and shop.id=period.sid and shop.status=1 and shop.display=1 and period.state=0 '. $condition)
        //     ->group('shop.cover_id')
        //     ->page($pageindex, $pagesize)
        //     ->order($orderBy)
        //     ->select();
        $offsize = ($pageindex-1)*$pagesize;    
        // $sql = "SELECT shop.id AS sid,shop.name,shop.cover_id,shop.price,shop.ten,shop.category AS cid,period.number,period.no NO,period.id FROM bo_shop shop,(SELECT * FROM bo_shop_period ORDER BY id DESC) period,bo_ten t WHERE ( shop.ten=t.id AND shop.id=period.sid AND shop.status=1 AND shop.display=1 AND period.state=0 ".$condition.") GROUP BY shop.id ORDER BY ".$orderBy." LIMIT ".$offsize.",".$pagesize; 
        $sql = "SELECT shop.shopstock,shop.full_price,shop.id AS sid,shop.name,shop.cover_id,shop.price,shop.ten,shop.category AS cid,period.number,period.no NO,period.id,shop.prop_type,shop.is_full FROM bo_shop shop left JOIN  (SELECT * FROM bo_shop_period ORDER BY id DESC) period ON shop.id=period.sid left join bo_ten t ON  shop.ten=t.id where  shop.status=1 AND shop.display=1 AND period.state=0 ".$condition."  GROUP BY shop.id ORDER BY (shop.hits+shop.pv+period.number/(shop.price/t.unit)*100+(SELECT COUNT(*)*10 FROM bo_shop_period WHERE state = 2 AND sid = shop.id)) desc,id desc LIMIT ".$offsize.",".$pagesize;
        $period = $this->query($sql, false); 
        // if(!isHostProduct()){
        //     exit(M()->getLastSql());
        // }
        //获取兑换比例
        $set_item = M('exchange_set')->where('type=1')->field('number')->order('id desc')->find();
        $set_number = empty($set_item['number']) ? 1 : $set_item['number'];
        if ( $period ) {
            foreach ( $period as $k => $v ) {
                //$list[]=$this->shopChange($v);
                $list[] = $this->shopChangeAPI($v);
                $list[$k]['is_full'] = $v['is_full']; 
                $list[$k]['prop_type'] = $v['prop_type'];
                $angle = ceil($list[$k]['progress']*360/100);
                if ($angle<=180) {
                    $list[$k]["left"] = $angle;
                    $list[$k]["right"] = 0;
                } else if ($angle<=360) {
                    $list[$k]["left"] = 180;
                    $list[$k]["right"] = $angle-180;
                }
                if ($v['is_full']==1) {//全价兑换
                    $list[$k]['total_price'] = $v['full_price']*$set_number;
                } else {
                    $list[$k]['total_price'] = $v['price']*$set_number;
                }
            }
        }
        return $list;
    }

    public function hits($id)
    {
        $map['id'] = $id;
        $this->where($map)->setInc('hits');
    }


    /**
     * @deprecated 商品购买记录列表
     * @date 2016-07-21
     * @param $pid 周期id
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $uid 用户id
     **/
    public function record($pid, $pageindex = 1, $pagesize = 20, $uid = '')
    {
        $map = array('pid' => $pid, 'number' => array('gt', 0));

        if ( !isEmpty($uid) ) {
            array_push($map, array('uid' => $uid));
        }

        $record = M('shop_record')->where($map)->field('pid,uid,order_id,create_time,num,number')->order('id desc')->page($pageindex, $pagesize)->select();

        //->group('uid')
        //echo M()->getLastSql();exit();

        if ( $record ) {
            foreach ( $record as $k => $v ) {
                $userlist[] = D('User')->userChange($v, 'record');
            }
        }
        return $userlist;
    }
    /**
     * 往期揭晓
     *
     * @author liuwei
     * @param  [type] $pageindex [description]
     * @param  [type] $pagesize  [description]
     * @param  [type] $sid       [description]
     * @param  [type] $uid       [description]
     * @return [type]            [description]
     */
    public function history($pageindex, $pagesize, $sid, $uid)
    {
        $map = array('sid' => $sid, 'state' => array('gt', 0), 'uid' => array('gt', 0));//屏蔽PK数据

        if ( !$no ) {
            $record = M('shop_period')->where($map)->field('id,uid,number,kaijang_time,state,no,kaijang_num')->order('no desc')->page($pageindex, $pagesize)->select();
        } else {
            $where = 'no' . $no;
            if ( strstr($no, '>') ) {
                $order = 'id asc';
            } else {
                $order = 'id desc';
            }
            $record = M('shop_period')->where($map)->where($where)->field('id,uid,number,kaijang_time,state,no,kaijang_num')->order($order)->select();
        }
        $list = array();
        if ( $record ) {
            foreach ( $record as $k => $v ) {
                $list[$k] = $v;
                $total_number = M('shop_record')->where('pid='.$v['id'])->sum('number');
                $total_buy_gold = M('shop_order')->where('pid='.$v['id'])->sum('buy_gold');
                $user_total_number = M('shop_record')->where("uid=" . $v['uid'] . " and pid=" . $v["id"])->sum('number');
                $list[$k]['total_number'] = empty($total_number) ? 0 : $total_number;//参与人次
                $list[$k]['total_buy_gold'] = empty($total_buy_gold) ? 0 : $total_buy_gold;//黄金量
                $list[$k]['user_total_number'] = empty($user_total_number) ? 0 : $user_total_number;//用户参与人次
                $list[$k]['uid'] = $v['uid'];
                $list[$k]['nickname'] = get_user_name($v['uid']);
                $list[$k]["img"] = get_user_pic_passport($v['uid']);
                $list[$k]['kaijang_date'] = date("Y.m.d H:i:s",$v['kaijang_time']);
                $list[$k]['down_date'] = date("Y-m-d H:i:s",$v['kaijang_time']-C('KAIJANG_TIME')*60);
                $list[$k]['total_buy_gold'] /= 1000;
            }
        }
        return $list;
    }

    public function more($id)
    {
        $more = $this->where('id=' . $id)->field('content')->find();
        return stripslashes(htmlspecialchars_decode($more['content']));
    }
    public function user_num_one($uid, $pid, $order_id) {
        $map = array();
        $map['uid'] = $uid;
        $map['pid'] = $pid;
        if (!empty($order_id)) {
            $map['order_id'] = $order_id;
        }
        $variable = M('shop_record')->field('num')->where($map)->select();
        $num = '';
        foreach ( $variable as $key => $value ) {
            if ( $key == count($variable) - 1 ) {
                $num .= implode(',', $value);
            } else {
                $num .= implode(',', $value) . ',';
            }
        }
        $number_list = array();
        if ( $variable ) {
            $number_list = explode(',', $num);
            sort($number_list);
        }
        return $number_list;

    }
    public function user_num($uid, $pid)
    {
        // $variable = M('shop_record')->field('group_concat(num) as num')->where("uid=" . $uid . " and pid=" . $pid)->group("uid,pid")->find();
        $variable = M('shop_record')->field('num')->where("uid=" . $uid . " and pid=" . $pid)->select();
        $num = '';
        foreach ( $variable as $key => $value ) {
            if ( $key == count($variable) - 1 ) {
                $num .= implode(',', $value);
            } else {
                $num .= implode(',', $value) . ',';
            }
        }
        $number_list = array();
        if ( $variable ) {
            $number_list = explode(',', $num);
            sort($number_list);
        }
        return $number_list;

    }

    public function calculate($pid, $pageIndex = 1, $pageSize = 50)
    {
        $calculate['shop'] = M('shop_period')->where('id=' . $pid)->field('no,number,state,kaijang_num,kaijiang_count,kaijiang_ssc,kaijiang_issue')->find();
        $calculate['shop']['kaijiang_issue'] = $calculate['shop']['kaijiang_issue'] > 0 ? '20' . $calculate['shop']['kaijiang_issue'] : 0;
        if ( C('KJ_THIRD_PARTY') > 0 or $calculate['shop']['state'] == 2 ) {
            $kaijiang = M('shop_kaijiang')->table('__USER__ user,__SHOP_KAIJIANG__ kaijiang')
                ->field('kaijiang.shopid,kaijiang.create_time,user.id,user.nickname')
                ->where('kaijiang.pid=' . $pid . ' and user.id=kaijiang.uid')
                ->order('kaijiang.create_time desc')
                ->page($pageIndex, $pageSize)
                ->select();
            foreach ( $kaijiang as $k => $v ) {
                $shop = M('shop_period')->table('__SHOP__ shop,__SHOP_PERIOD__ period')->where('period.id=' . $v['shopid'] . ' and shop.id=period.sid')->field('shop.name,period.no')->find();
                $list[$k]['uid'] = $v['id'];
                $list[$k]['uname'] = get_user_name($v['id']);
                $list[$k]['create_time'] = $v['create_time'];
                $list[$k]['create_date'] = time_format(substr($v['create_time'], 0, -3), 'Y-m-d');
                $list[$k]['create_hour'] = time_format(substr($v['create_time'], 0, -3), 'H:i:s') . '.' . substr($v["create_time"], -3);
                $list[$k]['create_int'] = time_format(substr($v['create_time'], 0, -3), 'His') . substr($v["create_time"], -3);
                $list[$k]['name'] = $shop['name'];
                $list[$k]['no'] = $shop['no'];
                $list[$k]['pid'] = $v['shopid'];
                //$list[$k]['user_url'] = url_change("user/user", array("id" => $v['id'], "name" => 'user'));
                //$list[$k]['shop_url'] = url_change("shop/over", array("id" => $v['shopid'], "name" => 'shop'));
            }
            $calculate['last_recoder'] = $list;
        }
        return $calculate;
    }

    /**
     * @deprecated 商品处理API
     * @author zhangran
     * @date 2016-07-05
     **/
    public function shopChangeAPI($data)
    {
        $dealArr = array();
        $dealArr["sid"] = $data["sid"];                            //商品ID
        $dealArr["pid"] = $data["id"];                            //周期ID
        $dealArr["name"] = $data["name"];                        //商品名称
        $dealArr["cid"] = $data['cid'];                         //商品类型id

        //限制条件 
        $dealArr['restrictions'] = getRestrictions($data['ten']);

        if ( $dealArr['restrictions'] ) {
            $dealArr["price"] = $data["price"] / $dealArr['restrictions']['unit'];
        } else {
            $dealArr["price"] = $data["price"];
        }

        //$dealArr["price"] = $data["price"];	                    //商品价格，总人数
        $dealArr["number"] = $data["number"];                    //已参与人数
        $dealArr["surplus"] = $dealArr["price"] - $data["number"];    //剩余人数
        $dealArr["no"] = $data["no"];                            //期号
        $progress = $data["number"] / $dealArr["price"] * 100;//进度比例
        $dealArr['progress'] = ($progress < 1 and $progress >0) ? 1 : intval($progress);    //进度比例
        
        $dealArr["path"] = get_cover($data["cover_id"], "path") == false ? '' : get_cover($data["cover_id"], "path");    //配图
        $dealArr["ten"] = $data["ten"];
        $dealArr["restrictions_title"] = $dealArr['restrictions']['title'];
        $dealArr["restrictions_unit"] = $dealArr['restrictions']['unit'];
        $dealArr["shopstock"] = $data["shopstock"];

        return $dealArr;
    }

    public function shopChange($data, $type = 'shop')
    {
        //商品ID
        $data["sid"] = $data["sid"];
        //周期ID                           
        $data["pid"] = $data["id"];
        //商品名称
        $data["name"] = $data["name"];

        //限制条件 
        $data['restrictions'] = getRestrictions($data['ten']);

        if ( $data['restrictions'] ) {
            $data["price"] = $data["price"] / $data['restrictions']['unit'];
        }

        //商品价格，总人数
        $data["price"] = $data["price"];
        //已参与人数
        $data["number"] = $data["number"];
        //剩余人数
        $data["surplus"] = $data["price"] - $data["number"];
        //期号          
        $data["no"] = $data["no"];
        //进度比例
        $data['progress'] = round($data["number"] / $data["price"] * 100, 2);
        //配图
        $map['id'] = array('in', $data["cover_id"]);
        $pictures = M('picture')->field('path')->where($map)->order('imageorder')->select();
        if (!empty($pictures)) {
            foreach ( $pictures as $key => $value ) {
                $pictures[$key] = completion_pic($value['path']); 
            }
        } else {
            $pictures[0] = completion_pic('');
        }
        

        $data["path"] = count($pictures)>0 ? $pictures : array();

        //详情
        //$data['content'] = empty($data['content']) ? '' : str_replace('/Picture', C("ONE_YUAN_URL").'/Picture',htmlspecialchars_decode($data['content']));
        $head = "<!DOCTYPE html><html><head><meta charset='utf-8' /><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=yes'><title></title><style>body, html, p, img{margin:0;padding:0;border:0;}p img{ width:100%;}</style><script>var w = $(window).width();$('p img').('width', w);</script></head><body>";
        $foot = "</body></html>";
        // $data['content'] = empty($data['content']) ? '' : $head . str_replace('/Picture', C("ONE_YUAN_IMG_URL"), htmlspecialchars_decode($data['content'])) . $foot;
        $data['content'] = empty($data['content']) ? '' : $head . htmlspecialchars_decode($data['content']) . $foot;

        //类别
        if ( $data["category"] ) {
            $data["cid"] = $data["category"];
            $data["ctitle"] = D('api/Category')->gettitle($data["category"]);
        }

        // $data["url"]=url_change($type."/index",array("id"=>$data["id"],"name"=>$type));
        // $data["moreurl"]=url_change($type."/more",array("id"=>$data["sid"],"name"=>$type));
        unset($data["cover_id"], $data["id"], $data["category"]);
        return $data;
    }
    public function overChangeNew($data, $period)
    {
        $data["sid"] = $period["sid"];
        $data["pid"] = $period["id"];
        $data['next_pid'] = getLatestPeriodByShopId($period["sid"]);

        $restriction = getRestrictions($data['ten']);
        //限制条件 
        $data['restrictions'] = $restriction;
        if ( $data['restrictions'] ) {
            $data["price"] = $data["price"] / $data['restrictions']['unit'];
        }
        $data['unit'] = $restriction['unit'];
        $data['price'] = $data["price"];
        $data["number"] = $period["number"];
        $data["surplus"] = $data["price"] - $period["number"];
        $data["cid"] = $data["category"];
        $data['ctitle'] = D('api/Category')->gettitle($data["category"]);
        $data["uid"] = $period["uid"];
        $data["no"] = $period["no"];
        $data['progress'] = floor($period["number"] / $data["price"] * 100);
        $data["state"] = $period["state"];
        // if ( UID == $period["uid"] and $period["shared"] == 1 ) {
        //     $data["shared"] = $period["shared"];
        // }
        $data["path"] = get_cover($data["cover_id"], "path");

//        if ( $period["state"] > 0 && $period["state"] < 3 ) {
//            $data["url"] = url_change("shop/over", array("id" => $period["id"], "name" => "shop"));
//        } else {
//            $data["url"] = url_change("shop/index", array("id" => $period["id"], "name" => "shop"));
//        }
//        $data["moreurl"] = url_change("shop/more", array("id" => $period["sid"], "name" => "shop"));

        //已开奖
        if ( $period["state"] == 2 ) {
            $data["express_name"] = $period["express_name"];
            $data["express_no"] = $period["express_no"];
        }
        $data["kaijang_diffe"] = $period["kaijang_time"] - time();//倒计时秒
        if ($data["kaijang_diffe"]>0) {
            $data["kaijang_diffe_data"] = gmstrftime('%M:%S', $data["kaijang_diffe"]);//倒计时分秒
            
        }
        $data["state"] = $period["kaijang_time"] > time() ? 1 :2;//开奖时间(真实开奖时间+3分钟) > 现在时间 是开奖中
        $data["kaijang_timing"] = $period["kaijang_time"];
        $data["kaijang_time"] = time_format($period["kaijang_time"], "Y.m.d H:i");
        $data["kaijang_num"] = $period["kaijang_num"];
        $data["kaijiang_count"] = $period["kaijiang_count"];
        $data["kaijiang_ssc"] = $period["kaijiang_ssc"];
        $data["end_time"] = $period["end_time"];

        unset($data["hits"], $data["display"], $data["cover_id"], $data["update_time"], $data['id'], $data['category'], $data['edit_price'], $data['buy_price'], $data['buy_url'], $data['position'], $data['auto']);
        return $data;
    }
    public function overChange($data, $period)
    {
        $data["sid"] = $period["sid"];
        $data["pid"] = $period["id"];
        $data['next_pid'] = getLatestPeriodByShopId($period["sid"]);

        $restriction = getRestrictions($data['ten']);
        //限制条件 
        $data['restrictions'] = $restriction;
        if ( $data['restrictions'] ) {
            $data["price"] = $data["price"] / $data['restrictions']['unit'];
        }
        $data['unit'] = $restriction['unit'];
        $data['price'] = $data["price"];
        $data["number"] = $period["number"];
        $data["surplus"] = $data["price"] - $period["number"];
        $data["cid"] = $data["category"];
        $data['ctitle'] = D('api/Category')->gettitle($data["category"]);
        $data["uid"] = $period["uid"];
        $data["no"] = $period["no"];
        $data['progress'] = floor($period["number"] / $data["price"] * 100);
        $data["state"] = $period["state"];
        // if ( UID == $period["uid"] and $period["shared"] == 1 ) {
        //     $data["shared"] = $period["shared"];
        // }
        $data["path"] = get_cover($data["cover_id"], "path");

//        if ( $period["state"] > 0 && $period["state"] < 3 ) {
//            $data["url"] = url_change("shop/over", array("id" => $period["id"], "name" => "shop"));
//        } else {
//            $data["url"] = url_change("shop/index", array("id" => $period["id"], "name" => "shop"));
//        }
//        $data["moreurl"] = url_change("shop/more", array("id" => $period["sid"], "name" => "shop"));

        //已开奖
        if ( $period["state"] == 2 ) {
            $data["express_name"] = $period["express_name"];
            $data["express_no"] = $period["express_no"];
        }
        $data["kaijang_diffe"] = ($period["kaijang_time"] - NOW_TIME + 10) * 1000; //开奖倒计时时间，在开奖时间的基础上加10秒
        if ($data["kaijang_diffe"]>0) {
            $data["kaijang_diffe_data"] = date('i:s',$data["kaijang_diffe"]);
            
        }
        $data["kaijang_timing"] = $period["kaijang_time"];
        $data["kaijang_time"] = time_format($period["kaijang_time"], "m-d H:i");
        $data["kaijang_num"] = $period["kaijang_num"];
        $data["kaijiang_count"] = $period["kaijiang_count"];
        $data["kaijiang_ssc"] = $period["kaijiang_ssc"];
        $data["end_time"] = $period["end_time"];

        unset($data["hits"], $data["status"], $data["display"], $data["cover_id"], $data["update_time"], $data['id'], $data['category'], $data['edit_price'], $data['buy_price'], $data['buy_url'], $data['position'], $data['auto']);
        return $data;
    }


    //新增获取pkoverChange
    public function pkoverChange($data, $period)
    {
        $data["sid"] = $period["sid"];
        $data["pid"] = $period["id"];
        $data['next_pid'] = getLatestPeriodByShopId($period["sid"]);

        $restriction = getRestrictions($data['ten']);
        //限制条件 
        $data['restrictions'] = $restriction;
        if ( $data['restrictions'] ) {
            //$data["price"] = $data["price"] / $data['restrictions']['unit'];

            $data["price"] = $period["peoplenum"];
            $data["number"] = $period["number"] / ($period['amount'] / $data['restrictions']['unit'] / $period['peoplenum']);
            $data["surplus"] = $data["price"] - $data["number"];
            $data['progress'] = floor($period["number"] / $data["price"] * 100);

        } else {

            $data['price'] = $data["peoplenum"];
            $data["number"] = $period["number"] / ($period['amount'] / $period['peoplenum']);
            $data["surplus"] = $data["price"] - $data["number"];
            $data['progress'] = floor($period["number"] / $data["price"] * 100);
        }


        $data['unit'] = $restriction['unit'];
        $data["cid"] = $data["category"];
        $data['ctitle'] = D('Category')->gettitle($data["category"]);
        $data["uid"] = $period["uid"];
        $data["no"] = $period["no"];

        $data["state"] = $period["state"];
        // if ( UID == $period["uid"] and $period["shared"] == 1 ) {
        //     $data["shared"] = $period["shared"];
        // }
        $data["path"] = get_cover($data["cover_id"], "path");

//        if ( $period["state"] > 0 && $period["state"] < 3 ) {
//            $data["url"] = url_change("shop/over", array("id" => $period["id"], "name" => "shop"));
//        } else {
//            $data["url"] = url_change("shop/index", array("id" => $period["id"], "name" => "shop"));
//        }
//        $data["moreurl"] = url_change("shop/more", array("id" => $period["sid"], "name" => "shop"));

        //已开奖
        if ( $period["state"] == 2 ) {
            $data["express_name"] = $period["express_name"];
            $data["express_no"] = $period["express_no"];
        }
        $data["kaijang_diffe"] = ($period["kaijang_time"] - NOW_TIME + 10) * 1000; //开奖倒计时时间，在开奖时间的基础上加10秒
        $data["kaijang_timing"] = $period["kaijang_time"];
        $data["kaijang_time"] = time_format($period["kaijang_time"], "m-d H:i");
        $data["kaijang_num"] = $period["kaijang_num"];
        $data["kaijiang_count"] = $period["kaijiang_count"];
        $data["kaijiang_ssc"] = $period["kaijiang_ssc"];
        $data["end_time"] = $period["end_time"];

        unset($data["hits"], $data["status"], $data["display"], $data["cover_id"], $data["update_time"], $data['id'], $data['category'], $data['edit_price'], $data['buy_price'], $data['buy_url'], $data['position'], $data['auto']);
        return $data;
    }


    /*更新周期表订单状态*/
    public function updateOrderStatus($uid, $pid, $order_status)
    {

        $order = M('shop_period')->where("uid=" . $uid . " and id=" . $pid)->find();
        if ( $order ) {
            $data = array();
            $data['order_status'] = $order_status;
            if ( $order['order_status_time'] == null || empty($order['order_status_time']) ) {
                $data['order_status_time'] = time();
            } else {
                $data['order_status_time'] = $order['order_status_time'] . ',' . time();
            }

            //如果是晒单
            if ( $order_status == 103 ) {
                $data["shared"] = 0;
            }

            $rs_period = M('shop_period')->where("uid=" . $uid . " and id=" . $pid)->save($data);

            return $rs_period;
        } else {
            return false;
        }
    }


    /**
     * @deprecated 获取pk列表
     * @date  20160719 updated
     * @param $pageindex 页码
     * @param $pagesize 每页记录数
     * @param $phone 手机号搜索 默认空
     * @param $room 房间号搜索 默认空 或者手机号
     * @param $number 房间号搜索 默认0
     * @return json
     */
    public function pk($pageindex = 1, $pagesize = 20, $room = '', $number = 0)
    {
        //房间号或手机号
        if ( $room ) {
            $condition = ' and housemanage.no=' . $room;
        }
        //人数
        if ( $number) {
            $condition .= ' and pkconfig.peoplenum=' . $number;
        }
        $defaultOrder = 'housemanage.id desc';
        $list = M('shop_period')
            ->table('__SHOP__ shop,__SHOP_PERIOD__ period,__HOUSE_MANAGE__ housemanage,__PKCONFIG__ pkconfig')
            ->field('shop.id as sid,shop.ten,shop.name,shop.cover_id,period.id as pid,period.number,period.no as nid,period.create_time as p_time,housemanage.ispublic,housemanage.no,housemanage.id as houseid,housemanage.uid,housemanage.create_time,pkconfig.peoplenum,pkconfig.amount')
            ->where('shop.id=period.sid and shop.status=1 and shop.display=1 and period.state=0 AND period.iscommon=2 AND period.house_id=housemanage.id AND housemanage.isresolving=0 AND housemanage.pksetid=pkconfig.id' . $condition)
            ->page($pageindex, $pagesize)
            ->order($defaultOrder)
            ->select();

        // exit(M()->getLestSql());

        foreach ( $list as $k => $v ) {

            $restrictions = $this->getRestrictions($v['ten']);

            //$list[]=$this->shopChange($v);
            $list[$k]["path"] = get_cover($v["cover_id"], "path") == false ? '' : get_cover($v["cover_id"], "path");    //配图

            if ( $restrictions ) {
                $list[$k]["surplus"] = $v['peoplenum'] - $v['number'] / ($v['amount'] / $restrictions['unit'] / $v['peoplenum']);    //剩余人数
                $list[$k]['progress'] = round($v["number"] / ($v["amount"] / $restrictions['unit']) * 100, 2);    //进度比例
            } else {
                $list[$k]["surplus"] = $v['peoplenum'] - $v['number'] / ($v['amount'] / $v['peoplenum']);    //剩余人数
                $list[$k]['progress'] = round($v["number"] / $v["amount"] * 100, 2);    //进度比例
            }

            $list[$k]["avatar"] = "http://1.busonline.com/h5web/v-u6Jrym-zh_CN-/yymj/h5web/main/img/icon.png";
            $list[$k]["username"] = C('WEB_NAME');

            //如果是私密房间
            if($v['ispublic'] == 1){
                // $passportuid = M('user')->where('id='.$list[$k]['uid'])->getfield('passport_uid');
                // $userinfo = M('member', C('PASSPORT')['DB_PREFIX'], C('PASSPORT')['DB_NAME'])->field('username,phone,avatar')->where('uid='.$passportuid)->find();
                // if ( strpos($userinfo['avatar'], 'http') === false ) {
                //     $userinfo['avatar'] = completion_pic_passport($userinfo['avatar']);
                // }
                $userinfo = M('user')->field('nickname,username,phone')->where('id='.$list[$k]['uid'])->find();
                if($userinfo){
                    $list[$k]["avatar"] = get_user_pic_passport($list[$k]['uid']);

                    $user_name = empty($userinfo['nickname']) ? $userinfo['username'] : $userinfo['nickname'];
                    $list[$k]["username"] = isMobile($user_name) ? substr_replace($user_name, '****', 3, 4) : $user_name;
                }
                $countdown_time = (empty(C('ROOM_OPEN_TIME'))? 86400 * getPKValid():C('ROOM_OPEN_TIME'))  - (time() - $v['p_time'] ) ;
                $list[$k]['countdown_time'] =  ( is_int($countdown_time) && $countdown_time >= 0 ) ? $countdown_time * 1000 : 0; //获取3天的倒计时
            }
        }
        return $list;
    }

    public function getHouseByHouseId($houseid)
    {
        $map['h.id']=$houseid;
        $house = M('house_manage')->field('s.name,s.cover_id,h.*')->table('__HOUSE_MANAGE__ h, __SHOP__ s')->where('h.shopid=s.id')->where($map)->find();

        if($house){
            $house['qrcode_url'] = completion_pic_passport($house['qrcode_url']);

            $mappic['id'] = array('in',$house['cover_id']);
            $shop_img_path = M('picture')->where($mappic)->order('imageorder')->getField('path',1);

            $house['shop_img_path'] = completion_pic($shop_img_path);
        }

        return $house;
    }

    //pk详情页面
    public function newPkInfo($tokenid, $houseid,$pid='')
    {
//        $this->entryPKRoom($tokenid, $houseid);

        $user = isLogin($tokenid);
        
        $condition = empty($pid)? ' AND housemanage.periodid = period.id ': ' AND period.id= '.$pid;
        $info = M('house_manage')
            ->table('__SHOP__ shop,__SHOP_PERIOD__ period,__HOUSE_MANAGE__ housemanage,__PKCONFIG__ pkconfig')
            ->field('shop.id as sid,shop.ten,shop.content,shop.name,shop.status,shop.cover_id,shop.display,housemanage.no houseno,housemanage.id as houseid,housemanage.create_time,housemanage.ispublic,pkconfig.peoplenum,pkconfig.amount,housemanage.uid as ownerid,housemanage.pksetid as pkid,housemanage.periodid as pid,housemanage.invitecode,housemanage.qrcode_url, period.number,period.no,period.state,period.uid,period.kaijang_time,period.kaijang_num,period.create_time as p_time')
            ->where('housemanage.id=' . $houseid . ' AND housemanage.pksetid=pkconfig.id AND housemanage.shopid=shop.id '.$condition)
            ->find();


        $validFlag = D('HouseManage')->isValidRoom($houseid);
        if(!$validFlag){
//            returnJson('', 404, '房间已解散或商品已下架！');
            $info['dissolved'] = 1;
        }

        if ( $info ) {
            //期号
            $periodid = empty($pid)?$info['pid'] : $info['pid'] = $pid ;
            //获取参与人数及相关信息
            $user_buy = M('shop_record')
                ->table('__SHOP_RECORD__ record,__USER__ user')
                ->field('record.number,record.uid,record.create_time,record.num,user.nickname,user.headimgurl')
                ->where('record.pid=' . $periodid . ' AND record.uid=user.id')
                ->order('record.create_time desc')
                ->select();

            foreach ( $user_buy as $key => $value ) {
                // $avatar = $value['headimgurl'];
                // if ( strpos($avatar, 'http') === false ) {
                //     $user_buy[$key]['headimgurl'] = completion_pic_passport($avatar);
                // }

                //add by wenyuan 2016-11-21
                $user_buy[$key]['headimgurl'] = get_user_pic_passport($value['uid']);
                $user_buy[$key]['nickname'] = isMobile($user_buy[$key]['nickname']) ? substr_replace($user_buy[$key]['nickname'], '****', 3, 4) : $user_buy[$key]['nickname'];

                //add by richie 2016-11-16 摸金号转数组
                $arr_num  = explode(',',$value['num']);
                foreach ($arr_num as &$v) {
                    $v = intval($v);
                    if($info['state'] == 2 && $v == $info['kaijang_num'] ){
                        $user_buy[$key]['winner'] = 1; //中奖用户
                    }
                }
                $user_buy[$key]['num'] = $arr_num ;
            }

            $restrictions = $this->getRestrictions($info['ten']);
            $info['restrictions']=$restrictions;

            if ( $restrictions ) {
                //剩余人数
                $info['surplus'] = $info['peoplenum'] - $info['number'] / ($info['amount'] / $restrictions['unit'] / $info['peoplenum']);
                //进度比例
                $info['progress'] = round($info["number"] / ($info["amount"] / $restrictions['unit']) * 100, 2);
                //每人单次购买次数
                $info['buynum'] = $info['amount'] / $restrictions['unit'] / $info['peoplenum'];

                //购买金额
                $info['price'] = $info['amount'] / $info['peoplenum'];
            } else {
                //剩余人数
                $info['surplus'] = $info['peoplenum'] - $info['number'] / ($info['amount'] / $info['peoplenum']);
                //进度比例
                $info['progress'] = round($info["number"] / $info["amount"] * 100, 2);
                //每人单次购买次数
                $info['buynum'] = $info['amount'] / $info['peoplenum'];

                //购买金额
                $info['price'] = $info['amount'] / $info['peoplenum'];
            }

            //商品图片
            $info["path"] = get_cover($info["cover_id"], "path") == false ? '' : get_cover($info["cover_id"], "path");

            //商品图片
            $map['id'] = array('in', $info["cover_id"]);
            $pictures = M('picture')->field('path')->where($map)->select();
            foreach ( $pictures as $key => $value ) {
                $pictures[$key] = completion_pic($value['path']);
            }
            $info["path_array"] = empty($pictures)?array() : $pictures;

            //商品详情
            $head = "<!DOCTYPE html><html><head><meta charset='utf-8' /><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=yes'><title></title><style>body, html, p, img{margin:0;padding:0;border:0;}p img{ width:100%;}</style><script>var w = $(window).width();$('p img').('width', w);</script></head><body>";
            $foot = "</body></html>";
            $info['content'] = empty($info['content']) ? '' : $head . str_replace('/Picture', C("ONE_YUAN_IMG_URL"), htmlspecialchars_decode($info['content'])) . $foot;
            
            //参与用户
            $info['participation'] = empty($user_buy)?array() : $user_buy;

            if( empty($info['invitecode']) ){ $info['invitecode'] = ''; }
            //不存在图片数据的，需要生成二维码图片
            if(empty($info['qrcode_url'])){
                //生成json数据
                $data['houseid'] =intval($houseid) ;
                $data['houseno'] =intval($info['houseno']) ;
                $json = json_encode($data);
                $qrcode_url = genQRcode($json);
                $info['qrcode_url'] = $qrcode_url;
                //保存二维码图片地址
                M('HouseManage')->where("id=" . $houseid)->setField('qrcode_url',$qrcode_url);
            }

            $info['qrcode_url'] = completion_pic($info['qrcode_url']);

            if ( $info["state"] >= 1 ) {
                $info["kaijang_diffe"] = ($info["kaijang_time"] - NOW_TIME + 10) * 1000; //开奖倒计时时间，在开奖时间的基础上加10秒
            }

            $countdown_time = (empty(C('ROOM_OPEN_TIME'))? 86400 * getPKValid():C('ROOM_OPEN_TIME'))  - (time() - $info['p_time'] ) ; 
            $info['countdown_time'] =  ( is_int($countdown_time) && $countdown_time >= 0 ) ? $countdown_time * 1000 : 0; //获取3天的倒计时

            //判断当前用户是否购买
            $if_buy = M('shop_record')->where('pid=' . $info['pid'] . ' AND uid=' . $user['uid'])->find();
            if ( $if_buy ) {
                $info['if_buy'] = 1;
            } else {
                $info['if_buy'] = 0;
            }
            return $info;
        } else {
            returnJson('', 404, '房间id错误');
        }

    }


    //pk商品选择
    public function selectPk($pageindex = 1, $pagesize = 20, $number = 0,$tokenid)
    {

        $user = isLogin($tokenid);
        $uid = $user['uid'] ;
        recordLog($user['uid'] . ':' . $user['passportuid'] . ':' . $user['username'], "userinfo");
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

//        //筛选已经选过的商品,（仅有效商品）
//        $have_selectpk = M()->table('__HOUSE_MANAGE__ m , __SHOP__ s')->where(array('m.uid'=>$user['uid'],'s.status'=>1 ,'s.display'=>1,'m.isresolving'=>1,'m.ispublic'=>1 ))->where('m.shopid = s.id ')->distinct(true)->field('pksetid')->select();
//        if($have_selectpk){
//            foreach($have_selectpk as $k=>$v){
//                $selectpk[] = $v['pksetid'];
//            }
//            $have_select = implode(",",$selectpk);
//            $condition = ' and pkconfig.id not in('.$have_select.')';
//        }else{
//            $condition ='';
//        }
//
//        //人数
//        if ( $number ) {
//            if ( $number == 0 ) {
//            } else {
//                $condition .= ' and pkconfig.peoplenum=' . $number;
//            }
//        }
//        $list = M('pkconfig')
//            ->table('__SHOP__ shop,__PKCONFIG__ pkconfig')
//            ->field('shop.id as sid,shop.name,shop.content,shop.cover_id,pkconfig.peoplenum,pkconfig.id as pkid,pkconfig.inventory,pkconfig.amount')
//            ->where('pkconfig.shopid=shop.id AND pkconfig.inventory > 0 AND shop.status=1 AND shop.display=1 AND shop.pkset > 1 ' . $condition)
//            ->order('pkconfig.peoplenum,pkconfig.create_time desc')
//            ->page($pageindex, $pagesize)
//            ->select();

        /******************************
         * Start
         ******************************/
        $lst_shop =  $this->getShopList(2,2,$number,$uid);
//        if($lst_shop){   //所有商品的列表
            $map['s.id '] = array('in',implode(',',$lst_shop));
//        }
//            ->where('pkconfig.shopid=shop.id AND pkconfig.inventory > 0 AND shop.status=1 AND shop.display=1 AND shop.pkset > 1 ' . $condition)

//        $map['c.inventory'] = array('GT',0);
//        $map['c.status'] = 1;
//        $map['c.display'] = 1;
//        $list = M('pkconfig')
//            ->table('__SHOP__ shop,__PKCONFIG__ pkconfig,_HOUSE_MANAGE__ m')
//            ->field('shop.id as sid,shop.name,shop.content,shop.cover_id,pkconfig.peoplenum,pkconfig.id as pkid,pkconfig.inventory,pkconfig.amount')
//            ->where('pkconfig.shopid=shop.id AND pkconfig.inventory > 0 AND shop.status=1 AND shop.display=1 AND shop.pkset > 1 ' . $condition)
//            ->order('pkconfig.peoplenum,pkconfig.create_time desc')
//            ->page($pageindex, $pagesize)
//            ->select();

//        if(!empty($uid)){
//            $map['m.uid'] = $uid;
//        }
        if($number > 0){
            $map['c.peoplenum'] = $number;
        }

        $list = M()->table('__SHOP__ s , __PKCONFIG__ c ')
            ->field('s.id as sid,s.name,s.content,s.cover_id,c.peoplenum,c.id as pkid,c.inventory,c.amount')
            ->where($map)
            ->where('s.id = c.shopid' )
            ->order('c.peoplenum,c.create_time desc')
            ->page($pageindex, $pagesize)
            ->select();

        /******************************
         * END
         ******************************/

        foreach ( $list as $k => $v ) {
            //每人单次购买次数
            $list[$k]['buynum'] = $v['amount'] / $v['peoplenum'];
            $list[$k]["path"] = get_cover($v["cover_id"], "path") == false ? '' : get_cover($v["cover_id"], "path");    //配图

            //商品图片
            $pictures = M('picture')->field('path')->where(array('id'=>array('in', $v["cover_id"])))->select();
            foreach ( $pictures as $key => $value ) {
                $pictures[$key] = completion_pic($value['path']);
            }
            $list[$k]["path_array"] = empty($pictures)?array():$pictures;

            //商品详情
            $head = "<!DOCTYPE html><html><head><meta charset='utf-8' /><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=yes'><title></title><style>body, html, p, img{margin:0;padding:0;border:0;}p img{ width:100%;}</style><script>var w = $(window).width();$('p img').('width', w);</script></head><body>";
            $foot = "</body></html>";
            $list[$k]['content'] = empty($list[$k]['content']) ? '' : $head . str_replace('/Picture', C("ONE_YUAN_IMG_URL"), htmlspecialchars_decode($list[$k]['content'])) . $foot;

        }
        return $list;
    }


    //创建私人房间 并且开启新的一期
    public function createPrivacyPk($param = array())
    {
        $user = isLogin($param['tokenid']);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }
        //用户id
        $uid = $user['uid'];
        //pkid
        $pkid = $param['pkid'];
        //邀请码
        $invitecode = $param['invitecode'];

        $time_start = strtotime(date('Y-m-d'));;
        $time_end = strtotime(date('Y-m-d', strtotime('+1 day')));

        //同一用户一天内不能创建超过5个房间
        $today_count = M('house_manage')->distinct(true)->field('pksetid')->where('ispublic=1 AND uid=' . $uid . ' AND create_time >=' . $time_start . ' AND create_time <= ' . $time_end)->select();

        $today_total_count=5;
        if(!isHostProduct()){
            $today_total_count=100;
        }

        if ( count($today_count) >= $today_total_count ) {
            returnJson('', 402, '一天内不能超过5个房间');
        } else {
            $rs_pk = D('Pkconfig')->where(array('id'=>$pkid))->field('shopid,peoplenum')->find();
            $canCreate = D('HouseManage')->canCreateRoom($uid,$rs_pk['shopid'],$pkid);
            if(!$canCreate){
                returnJson('', 402, '此商品已创建pk房间');
            }


            $result = M('pkconfig')
                ->table('__SHOP__ shop,__PKCONFIG__ pkconfig')
                ->field('shop.id as sid,shop.name as name,shop.periodnumber,pkconfig.id as pkid,pkconfig.inventory,pkconfig.amount,pkconfig.peoplenum')
                ->where('pkconfig.id=' . $pkid . ' AND shop.id = pkconfig.shopid')
                ->find();

            if ( $result ) {
                if ( $result['inventory'] > 0 && $result['periodnumber'] > 0 ) {//判断pk商品库存与夺宝期数
                    //商品id
                    $shopid = $result['sid'];
                    $shopname = $result['name'];
                    //pk商品价格
                    $amount = $result['amount'];
                    $data = D('shop')->shopinfo($shopid, 'price,ten,status,periodnumber,shopstock,pkset,iscreatehouse');

                    //获取商品属于几元专区
                    $restrictions = $this->getRestrictions($data['ten']);

                    //获取最新一期号码
                    $no = M('shop_period')->where('sid=' . $shopid)->max('no');
                    //判断夺宝期数是否达到或者是否有库存
                    if ( $data['periodnumber'] > 0 && $data['shopstock'] > 0 ) {
                        $period['sid'] = $shopid;
                        $period['create_time'] = NOW_TIME;
                        $period['state'] = 0;
                        $tempno = $no ? $no + 1 : 100001;
                        $period['no'] = $tempno;

                        if ( $data['status'] > 0 ) {
                            if ( $data['pkset'] == 1 ) {//普通摸金商品不属于pk
                                returnJson('', 402, '商品已下架!');
                            } else {//可以pk
                                $model = M();
                                $model->startTrans();//事务处理开始

                                if ( $restrictions ) {
                                    $period['jiang_num'] = jiang_num($result['amount'] / $restrictions['unit'] - 1);
                                } else {
                                    $period['jiang_num'] = jiang_num($data['amount'] - 1);
                                }
                                $period['iscommon'] = 2;//属于pk
                                $periodid = M('shop_period')->data($period)->add();
                                $house_manage = array();
                                $house_manage['ispublic'] = 1;
                                $house_manage['invitecode'] = $invitecode;//邀请码
                                $house_manage['isresolving'] = 0;//是否解散0：否 1：是
                                $house_manage['create_time'] = NOW_TIME;
                                $house_manage['shopid'] = $shopid;
                                $house_manage['pksetid'] = $pkid;
                                $house_manage['periodid'] = $periodid;
                                $house_manage['uid'] = $uid;
                                $house_manage['begin_time'] = NOW_TIME;
                                //获取最大房间号
                                $houseno = M('house_manage')->field('max(no) maxno')->find();
                                if ( $houseno['maxno'] ) {
                                    $house_manage['no'] = $houseno['maxno'] + 1;
                                } else {
                                    $house_manage['no'] = 100001;
                                }

                                $houseid = M('house_manage')->add($house_manage);

                                if ( !empty($periodid) && !empty($houseid) ) {
                                    M('shop_period')->where("id=".$periodid)->save(array('house_id' => $houseid));
                                    $model->commit();//成功

                                    //pk商品库存减少1个
                                    M('pkconfig')->where('id=' . $pkid)->setDec('inventory');

                                    //添加房间进入记录
                                    $house_user = array();
                                    $house_user['houseno'] = $house_manage['no'];
                                    $house_user['userid'] = $uid;
                                    $house_user['create_time'] = time();
                                    M('house_user')->add($house_user);
                                    $resultDate = array("room_no" => $house_manage['no'],"houseid"=>$houseid);

                                    //发送创建房间记录
                                    D('Notification')->push4CreateRoom($uid,$periodid,$shopname,$houseid, $invitecode);
                                    returnJson($resultDate, 200, 'success！');
                                } else {
                                    $model->rollback();
                                    returnJson('房间创建失败!', 500, 'error');
                                }

                            }

                        } else {
                            returnJson('', 402, '商品已下架!');
                        }

                    } else {
                        returnJson('', 402, '商品已下架!');
                    }
                } else {
                    returnJson('', 402, '商品已下架!');
                }

            } else {//pk商品不存在
                returnJson('', 402, 'pk商品不存在！');
            }


        }
    }


    //公开房间再次参与
    public function pkAgain($tokenid, $pkid)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        //获取pk商品最新期号
        $result = M('house_manage')->field('max(periodid) maxno')->where('pksetid=' . $pkid .' and ispublic=0')->find();
        if ( $result ) {
            $maxPeriodid = $result['maxno'];
            //判断当前期是否进行中
            $if_ing = M('shop_period')->field('state')->where('id =' . $maxPeriodid)->find();

            if ( $if_ing['state'] == 0 ) {//正确进行pk
                $houseid = M('house_manage')->field('id')->where('periodid=' . $maxPeriodid)->find();
                $resultDate = array("houseid" => $houseid['id']);
                returnJson($resultDate, 200, 'success！');
            } else {
                returnJson('', 402, 'pk房间已结束！');
            }

        } else {
            returnJson('', 402, 'pk房间已结束！');
        }
    }


    //私密房间再次参与
    public function privacyPkAgain($tokenid, $pkid, $ownerid)
    {
        $user = isLogin($tokenid);
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        //房主id
        $ownerid = $ownerid;
        //获取pk商品最新期号
        $result = M('house_manage')->field('max(periodid) periodid,shopid,uid,invitecode,no,id')->where('pksetid=' . $pkid . ' AND uid =' . $ownerid)->find();
        if ( $result ) {
            $maxPeriodid = $result['periodid'];
            $shopid = $result['shopid'];
            //判断当前期是否进行中
            $if_ing = M('shop_period')->field('state')->where('id =' . $maxPeriodid)->find();
            if ( $if_ing['state'] == 0 ) {//正确进行pk
                $houseid = M('house_manage')->field('id')->where('periodid=' . $maxPeriodid)->find();
                $resultDate = array("houseid" => $houseid['id']);
                returnJson($resultDate, 200, 'success！');
            } else {//创建私密房间新的一期
                $shopinfo = M('shop')->where('id=' . $shopid)->field('ten,periodnumber')->find();
                if ( $shopinfo['periodnumber'] > 0 ) {

                    //获取商品属于几元专区
                    $restrictions = $this->getRestrictions($shopinfo['ten']);

                    //获取pk商品库存判断
                    $pkinfo = M('pkconfig')->where('id=' . $pkid)->field('id,peoplenum,amount,inventory')->find();

                    if ( $pkinfo['inventory'] > 0 ) {
                        $model = M();
                        $model->startTrans();//事务处理开始

                        //获取最新一期号码
                        $no = M('shop_period')->where('sid=' . $shopid)->max('no');
                        $period['sid'] = $shopid;
                        $period['create_time'] = NOW_TIME;
                        $period['state'] = 0;
                        $tempno = $no ? $no + 1 : 100001;
                        $period['no'] = $tempno;
                        $period['iscommon'] = 2;//属于pk
                        $period['house_id'] = $result['id'];//房间id

                        if ( $restrictions ) {
                            $period['jiang_num'] = jiang_num($pkinfo['amount'] / $restrictions['unit'] - 1);
                        } else {
                            $period['jiang_num'] = jiang_num($pkinfo['amount'] - 1);
                        }

                        $periodid = M('shop_period')->data($period)->add();
                        $house_manage = array();
                        $house_manage['ispublic'] = 1;
                        $house_manage['invitecode'] = $result['invitecode'];//邀请码
                        $house_manage['isresolving'] = 0;//是否解散0：否 1：是
                        $house_manage['create_time'] = NOW_TIME;
                        $house_manage['shopid'] = $result['shopid'];
                        $house_manage['pksetid'] = $pkid;
                        $house_manage['periodid'] = $periodid;
                        $house_manage['uid'] = $result['uid'];
                        $house_manage['begin_time'] = NOW_TIME;
                        $house_manage['no'] = $result['no'];

                        $houseid = M('house_manage')->add($house_manage);

                        if ( !empty($periodid) && !empty($houseid) ) {
                            M('shop_period')->where("id=".$periodid)->save(array('house_id' => $houseid));
                            $model->commit();//成功

                            //pk商品库存减少1个
                            M('pkconfig')->where('id=' . $pkid)->setInc('inventory');
                            $resultDate = array("houseid" => $houseid);
                            returnJson($resultDate, 200, 'success！');
                        } else {
                            $model->rollback();
                            returnJson('pk房间创建失败!', 500, 'error');
                        }

                    } else {
                        returnJson('', 402, '商品已无库存！');
                    }


                } else {
                    returnJson('', 402, 'pk房间已结束！');
                }

            }

        } else {
            returnJson('', 402, 'pk房间已结束！');
        }


    }


    /**
     * 获取某商品期的限制
     * @param intger $id 限制条件id，对应数据库ten表内容
     */
    public function getRestrictions($id)
    {
        $restrictions = M('ten')->field('id,title,unit,restrictions,restrictions_num')->where(array('id' => $id, 'status' => 1))->find();
        return $restrictions;
    }


    //获取商品详情
    public function shopinfo($id, $field = true)
    {
        $map = array();
        if ( is_numeric($id) ) {
            $map['id'] = $id;
        }
        $info = $this->field($field)->where($map)->find();
        return $info;
    }

    /**
     * 进入房间
     * @param $tokenid
     * @param $houseid
     * @param $invitecode
     */
    public function entryPKRoom($tokenid, $houseid, $invitecode=''){
        $user = isLogin($tokenid);
        recordLog($user['uid'] . ':' . $user['passportuid'] . ':' . $user['username'], "userinfo");
        if ( !$user ) {
            returnJson('', 100, '请登录！');
        }

        $validFlag = D('HouseManage')->isValidRoom($houseid);
        if(!$validFlag){
            returnJson('', 404, '房间已解散或商品已下架！');
        }

        //判断用户是否进入过房间
        $houseinfo = M('house_manage')->where('id =' . $houseid)->find();
        if ( $houseinfo ) {
            //判断是公共房间还是私密房间
            if ( $houseinfo['ispublic'] != 0 ) {
                //私密房间
                $if_enter = M('house_user')->where('houseno=' . $houseinfo['no'] . ' AND userid=' . $user['uid'])->find();
                if ( !$if_enter ) {
                    if ( isEmpty($invitecode) ) {
                        returnJson('', 401, '私密房间首次进入需要邀请码');
                    } else {
                        $res = M('house_manage')->where('id=' . $houseid . ' AND invitecode=' . $invitecode)->find();
                        if ( !$res ) {
                            returnJson('', 403, '邀请码不对');
                        } else {
                            //增加用户进入房间记录
                            $house_user = array();
                            $house_user['houseno'] = $houseinfo['no'];
                            $house_user['userid'] = $user['uid'];
                            $house_user['create_time'] = time();
                            M('house_user')->add($house_user);
                        }
                    }
                }
            }
        } else {
            returnJson('', 404, '房间id错误');
        }
    }

    /**
     * 是否为有效商品
     * 有效商品（正常展示，状态为上架）
     * @param $shopid
     * @return bool
     */
    public function isShopValid($shopid){
        $map['id'] = $shopid;
        $map['status'] = 1;
        $map['display'] = 1;
        $rs = $this->where($map)->count();
        if($rs){
            return true;
        }
        return false;
    }



    /**
     * //获取有效商品列表
     * @param int $area_type  1=> 普通商品（默认） 2=>PK专区
     * @param int  $room_type PK专区 是否公开房间 0 => 所有房间 1 =>公共房间 （默认） 2=>私有房间
     * @param string $uid
     * @param int $number 场次数 ，默认为0即为所有场次（例如 2人场）
     * @return mixed
     */
//    public function getShopList($shopid,$valid_type=1,  $pid='',$area_type=1,$ispublic=1,$uid='',$number=0){
    public function getShopList($area_type=1,$room_type=0,$number=0,$uid=''){
        //有效商品
        // display = 1 & status = 1
        $map['s.display'] = 1 ;
        $map['s.status'] = 1 ;
        $map['s.pkset'] = $area_type ; //普通商品区域

        if($area_type == 1 ){
            $map['s.shopstock'] = array('GT',0) ;   //库存大于零
            $map['s.pkset'] = array('IN','1,3') ;   //普通商品
            //普通摸金
            $rs_shop = M('shop s ')->where( $map)->field('s.id')->select();
            return array_column($rs_shop,'id');
        }elseif ($area_type == 2 ){

            //PK 专区
            $map['s.pkset'] = array('IN','2,3') ;   //PK商品

            if($room_type == 0){
                //所有房间的商品 应该返回期号信息
//                //所有场次通用库存
//                $map['s.shopstock'] = array('GT',0) ;   //库存大于零
//                //公共房间商品
//                $map['s.iscreatehouse'] = 1 ;   //公共房间商品
//                $rs_shop = M('shop s ')->where( $map)->field('s.id')->select();
//                return array_column($rs_shop,'id');
            }
            elseif($room_type == 1){
                //所有场次通用库存
                $map['s.shopstock'] = array('GT',0) ;   //库存大于零
                //公共房间商品
                $map['s.iscreatehouse'] = 1 ;   //公共房间商品
                $rs_shop = M('shop s ')->where( $map)->field('s.id')->select();
                return array_column($rs_shop,'id');
            }elseif ($room_type == 2)
            {
                if($number > 0 ){ $map['c.peoplenum'] = $number; }

                //按场次检查库存
                $map['c.inventory'] = array('GT',0) ;   //库存大于零
//                $map['m.isresolving'] = 0 ;            //房间未解散状态的
//                $map['m.ispublic'] = 1 ;                 //私密房间类型

                //私有房间商品 返回按场次分的有效商品
                $fields = array('c.peoplenum','c.shopid','c.id');
//                if( !empty($uid)  ){
//                    $map['m.uid'] = $uid;
//                    $fields[] = 'm.uid';
//                }

                //获取正在进行中的房间商品，包含用户(若没有用户则不进行筛选)
                if(!empty($uid)){
                    $_shops = M()->table('__HOUSE_MANAGE__ m , __PKCONFIG__ c ')->distinct(true)->where(array('m.ispublic'=>1,'m.isresolving'=>0,'c.peoplenum'=>$number,'m.uid'=>$uid))->where('m.pksetid = c.id')->field('m.shopid')->select();
                    $_shops = array_column($_shops,'shopid');
                }
                if(!empty($_shops)){
                    $map['c.shopid'] = array('NOT IN',$_shops);
                }
//              $rs_shop = M()->table('__SHOP__ s ,__PKCONFIG__ c ')->where( $map)->field('c.peoplenum,c.shopid,c.inventory')->order('c.peoplenum')->select();
//                $rs_shop = M()->table('__HOUSE_MANAGE__ m')
//                    ->join(array('LEFT JOIN  __SHOP__ s ON m.shopid = s.id ','LEFT JOIN  __PKCONFIG__ c ON m.pksetid = c.id '))
//                    ->where($map)->field($fields)->order('c.peoplenum')
//                    ->group('c.peoplenum,s.id')
//                    ->select();

                $rs_shop = M()->table('__SHOP__ s,__PKCONFIG__ c')
//                    ->join(array('LEFT JOIN  __SHOP__ s ON m.shopid = s.id ','LEFT JOIN  __PKCONFIG__ c ON m.pksetid = c.id '))
                    ->where($map)
                    ->where(' s.id = c.shopid ')
                    ->field($fields)->order('c.peoplenum')
                    ->group('c.peoplenum,c.shopid ')
                    ->select();

                return array_column($rs_shop,'shopid');
            }
        }
    }

    /**
     * 获取场次列表
     * 0 = 全部 （默认），1 = 商品选择的场次列表 ，2=pk列表的场次列表
     * @param int $filtrate
     * @param string $tokenid
     * @return array
     */
    public function numberList($filtrate = 0,$tokenid =''){
        $uid = isLogin($tokenid);

        $rs_all = M('Pkconfig')
            ->field('peoplenum')
            ->order('peoplenum ASC')
            ->group('peoplenum')
            ->select();

        if($filtrate == 0){
         return $rs_all;
        }
//        elseif($filtrate == 1){
//            return $rs_all;
//        }

        $list = array();
        foreach ($rs_all as $k => $v ){
//            0 = 全部 （默认），1 = 商品选择的场次列表 ，2=pk列表的场次列表
            switch ($filtrate){
                case 1:
                    //普通  商品选择的场次列表
                    //pk选择列表的场次列表
//                    $data = $this->getShopList($area_type=2,$room_type=0,$v['peoplenum']);
                    $data =  D('HouseManage')->getPKRoomList($v['peoplenum']);
                    if($data){    $list[] = $v;   }
                    break;
                case 2:
                    //pk选择列表的场次列表
                    $data = $this->getShopList($area_type=2,$room_type=2,$v['peoplenum'],$uid);
                    if($data){    $list[] = $v;   }
                    break;
            }
        }
        return $list;
    }

    /**
     * 生成订单信息
     * @param $pid
     * @param $shopname
     * @param $amount
     */
    public function generateorder($pid,$shopname,$amount){
        //生成订单号
        $mch_tradeno = uuid();
        $data = array(
            'pid' => $pid,
            'shopname' => urldecode($shopname) . "",
            "price" => $amount,//支付金额 改为购买次数
            "md5key" => think_md5($mch_tradeno, $this->md5_key),
            'mch_tradeno' => $mch_tradeno,
        );
        return $data;
    }

    /**
     * 钻石商城获取商品的有效库存
     * @param $sid
     */
        public function getInventory4do($sid,$valid=1){
            $p_map['sid'] = $sid;
            $p_map['state'] = 0 ;
            $count = D('shop_period')->where($p_map)->count();
            $rs = $this->where(array('id'=>$sid))->getField('shopstock');
            //商品库存在开新一期时就已经扣除库存
//            if($count > 0 ){
//                return $rs - $count;
//            }
            return $rs;
        }
    /**
     * 获取进行中最新一期id
     * @param  integer $sid [description]
     * @return [type]       [description]
     */
    public function getnewperiod($sid=0)
    {
        $id = M('shop')->where('status = 1 and id='.$sid)->getField('id');//商品id
        $period_id = 0;
        if (!empty($id)) {
            $period_id = D('shop_period')->where('state=0 and sid='.$id)->order('id desc')->getField('id');
        }
        return $period_id;
    } 

    /**
     * 商品热度加一
     * @param  integer $sid [description]
     * @return [type]       [description]
     */
    public function addhit($id)
    {
        $map['id'] = $id;
        $this->where($map)->setInc('pv');
    }  
    /**
     * banner 图片
     * @return [type] [description]
     */
    public function slider()
    {
        $time = time();
        $where = array();
        $where['publish'] = 2;
        $where['display'] = 1;
        $where['status'] = 1;
        $where['end_time'] = array('egt',$time+86400);
        $list = M('slider')->where($where)->field('id,link,cover_id')->order('h5_order asc,id desc')->select(); 
        //echo M()->getLastSql();exit;
        $data = array();   
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $data[$key] = get_cover($value['cover_id']);
                $data[$key]['id'] = $value['id'];
                $link = $value['link'];//链接
                if (is_numeric($link)) {
                    $data[$key]['link'] = U('index/detail').'/shopid/'.$link;
                } elseif (strpos($link,"http://") === 0){
                    $data[$key]['link'] =$link;
                } else {
                    $data[$key]['link']= C("WEB_URL").$link;
                } 
                
            }
        }
        return $data;
    }


    /**
     * 检查下一期是否正常
     *
     * @param $shopstock  库存数
     * @param $next_periodnumber  新一期期号
     * @param $max_periodnumber  最大期数
     * @return bool
     */
    public function validNextShop($pid,$shopstock,$next_periodnumber,$max_periodnumber,$status){
        //库存数检查
        $_shopstock = $shopstock;
        $_next_periodnumber = $next_periodnumber;
        $_max_periodnumber = $max_periodnumber;
        $_status = $status;
//        $info['shopstock'] //商品库存  //商品期数检查
        if($_shopstock < 1 || $_status < 1 || $_next_periodnumber > $_max_periodnumber){
            return false;
        }
        return true;
    }
}
