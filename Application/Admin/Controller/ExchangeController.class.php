<?php
namespace Admin\Controller;
/**
 * 兑换设置
 * @author:liuwei
 */
class ExchangeController extends WebController {
	public function index(){
		$this->rechargeconfig();
	}
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
		$this->meta_title = '比例设置';
		$this->display();
	}

	public function rechargeconfig(){

		$map = [];
		$list = $this->lists('ExchangeRecharge', $map);
        $this->assign('_list', $list);
		$this->meta_title = '充值设置';
		$this->display();
	}

	public function rechargeadd(){
		$this->meta_title = '新增充值设置';
		$this->display('rechargedetails');
	}

	public function rechargeedit($id = null){
        $exchangeRecharge = D('ExchangeRecharge');
        if(IS_POST){ //提交表单
            if(false !== $exchangeRecharge->update()){
                $this->success('编辑成功！');
            } else {
                $error = $exchangeRecharge->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
			$info = $id ? $exchangeRecharge->info($id) : '';
			// $info.extra = intval($info.extra);
            $this->assign('info', $info);
			$this->meta_title = '编辑充值设置';
            $this->display('rechargedetails');
        }
    }

}