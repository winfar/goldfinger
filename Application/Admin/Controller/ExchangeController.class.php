<?php
namespace Admin\Controller;
/**
 * 兑换设置
 * @author:liuwei
 */
class ExchangeController extends WebController {
	/**
	 * 兑换设置
	 * @return html
	 */
	public function info()
	{
		$type = 1;//类型 钻石兑换金币
		$model = D('ExchangeSet');
		$info = $model->info($type);
		$money = 1;//平台金币最小系数
		if ( IS_POST ) {
			$number = I('number');//钻石兑换金币比例
			
			if ($number >= $money) {
				$result = $model->update($type);
				if ( false !== $result ) {
                	$this->success('兑换设置成功', U('info'));
	            } else {
	                $error = $model->getError();
	                $this->error(empty($error) ? '未知错误！' : $error);
	            }
			} else {
				$this->error(C("WEB_CURRENCY").'不能少于'.$money.'！');
			}
		}

		$this->assign('info', $info);
		$this->meta_title = '兑换设置';
		$this->display();
	}

}