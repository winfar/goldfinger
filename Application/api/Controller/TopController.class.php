<?php

namespace api\Controller;

use Think\Controller;

class TopController extends BaseController
{
	/**
	 * 达人榜列表
	 * @param  integer $pageindex [description]
	 * @param  integer $pagesize  [description]
	 * @param  integer $tokenid   [description]
	 * @param  integer $type      [description]
	 * @return [type]             [description]
	 */
	public function toplist($pageindex = 1, $pagesize = 20, $tokenid = 0, $type = 1)
	{
		//如果存在tokenid
		$uid = 0;
		if (!empty($tokenid)) {
			$user = isLogin($tokenid);
            if ( !$user ) {
                returnJson('', 100, '请登录！');
            }
            $uid = $user['uid'];
		}
		//用户自己的排行
		$user_list = D('Top')->user_list($uid, $type);
		//100排行
		$list = D('Top')->top_list($pageindex, $pagesize, $type);
		$data = array();
		$data['info'] = $user_list;
		$data['list'] = $list;
		return returnJson($data, 200, 'success');
		
	}
}	