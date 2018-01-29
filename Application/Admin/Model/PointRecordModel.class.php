<?php
/**
 * 积分记录
 * @author liuwei
 * @date   2016-10-17 15:46:56
 */
namespace Admin\Model;
use Think\Model;

class PointRecordModel extends Model {
	/**
     * 获取明细详情
     * @param  [int] $id 积分详情id
     * @author liuwei
     * @return array
     */
    public function type_info($id)
    {
    	$item = $this->alias('p')
        ->join('LEFT JOIN __USER__ u ON p.user_id = u.id ')
        ->where('p.id='.$id)
        ->field('p.id,p.point,p.type_id,p.remark,p.create_time,u.username,u.nickname,u.passport_uid,u.phone,u.total_point')
        ->find();
        if (!empty($item)) {
            $item['type_name'] = get_pointtype($item['type_id']);
            if (empty($item['phone'])) {

                //算出其他登录方式
                $passport_uid = $item['passport_uid'];
                $file_contents = file_get_contents("http://passport.busonline.com/wapapi.php?s=/Usertest/loginWay&uid=$passport_uid");
                $value = (json_decode($file_contents, true));
                $loginWay = $value['data'];

                switch ($loginWay) {
                    case 100:
                        $item['loginWay'] = "用户名";
                        break;
                    case 101:
                        $item['loginWay'] = "手机号";
                        break;
                    case 102:
                        $item['loginWay'] = "邮箱";
                        break;
                    case 201:
                        $item['loginWay'] = "微信";
                        break;
                    case 202:
                        $item['loginWay'] = "qq";
                        break;
                    case 203:
                        $item['loginWay'] = "微博";
                        break;
                    case 204:
                        $item['loginWay'] = "百度";
                        break;
                    case 205:
                        $item['loginWay'] = "淘宝";
                        break;
                    case 206:
                        $item['loginWay'] = "163";
                        break;
                    case 207:
                        $item['loginWay'] = "搜狐";
                        break;
                    case 301:
                        $item['loginWay'] = "谷歌";
                        break;
                    case 302:
                        $item['loginWay'] = "twitter";
                        break;
                    case 303:
                        $item['loginWay'] = "facebook";
                        break;
                    case 304:
                        $item['loginWay'] = "instagram";
                        break;
                    case 401:
                        $item['loginWay'] = "游客";
                        break;
                    default:
                        $item['loginWay'] = "其他登录方式";
                }
            } else {
                $item['loginWay'] = "手机号";
            }
        }
        return $item;
    }
}