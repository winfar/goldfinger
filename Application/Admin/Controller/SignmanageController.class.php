<?php
/**
 * Author: zhangkang
 * Date: 2016/9/819:22
 * Description:
 */
namespace Admin\Controller;

class SignmanageController extends WebController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 积分设置保存
     */
    public function signSave()
    {
        if ( IS_GET ) {
            $id = I('id');
            $data['point'] = I('point');
            $data['gold'] = I('gold');
            $data['shop'] = I('shop');
            $data['hongbao'] = I('hongbao');

            $type = I('type');
            if ( $type == 0 ) {
                $dataconfig['begintime'] = strtotime(I('begintime'));
                $dataconfig['endtime'] = strtotime(I('endtime'));
                $dataconfig['status'] = I('status');

                $signconfig = M('SignConfig');
                $signconfig->where(array('name' => "bo_sign_activitypoint"))->save($dataconfig);

                $sign = M('SignActivitypoint');
            } else {
                $sign = M('SignBasepoint');
            }

            if ( false !== $sign->where(array('id' => $id))->save($data) ) {
                $this->success('编辑成功！');
            } else {
                $error = $sign->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }
    }

    /**
     *
     */
    public function signset()
    {
        $type = I('type');
        if ( $type == 1 ) {
            $config = D('Signmanage');
            $status = $config->getStatus('bo_sign_activitypoint');

            $sign = M('SignActivitypoint');
            $list = $this->lists($sign, array(), $order = 'id', 0, array());
        } else {
            $config = D('Signmanage');
            $status = $config->getStatus('bo_sign_basepoint');

            $sign = M('SignBasepoint');
            $list = $this->lists($sign, array(), $order = 'id', 0, array());
        }

        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('_list', $list);
        $this->meta_title = '签到配置';
        $this->display('signset');
    }

    public function addSign()
    {
        $type = I('status1');
        $signmanageid = I('signmanageid');
        $points = I('rate1');
        $golds = I('rate2');
        $shops = I('rate3');
        $hongbaos = I('rate4');
        $signsetidremoveids = I('signsetidremoveids');

        $signconfig = M('SignConfig');
        $dataconfig['status'] = I('status');
        if ( $type == 0 ) {
            $dataconfig['begintime'] = strtotime(I('begintime'));
            $dataconfig['endtime'] = strtotime(I('endtime'));

            $signconfig->where(array('name' => "bo_sign_activitypoint"))->save($dataconfig);

            $sign = M('SignActivitypoint');
        } else {
            $signconfig->where(array('name' => "bo_sign_basepoint"))->save($dataconfig);

            $sign = M('SignBasepoint');
        }

        $result = $sign->delete($signsetidremoveids);
        foreach ( $points as $key => $point ) {
            $data['id'] = $signmanageid[$key];
            $data['point'] = $point;
            $data['gold'] = $golds[$key];
            $data['shop'] = $shops[$key];
            $data['hongbao'] = $hongbaos[$key];
            if ( $data['id'] ) {
                $result = $sign->save($data);
            } else {
                if($point>0){
                    $result = $sign->add($data);
                }
            }
        }

        if ( 1 ) {
            $this->success('！编辑成功！', U('signset'));
        } else {
            $this->error('！编辑失败！', U('signset'));
        }
    }

    /**
     *
     */
    public function signRemove()
    {
        if ( IS_GET ) {
            $id = I('get.id');
            $type = I('type');
            if ( $type == 0 ) {
                $sign = M('SignActivitypoint');
            } else {
                $sign = M('SignBasepoint');
            }

            $this->remove($sign, $id);
        }
    }

    /**
     * 删除通用方法
     * @param $model 实例化的model
     * @param $id
     */
    public function remove($model, $id)
    {
        //删除该栏目信息
        $res = $model->delete($id);
        if ( $res !== false ) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 设置积分设置的状态（启用，禁用）-通用方法
     *
     */
    public function setConfigState()
    {
        if ( !empty(I('name')) ) {
            $name = I('name');
        }
        $model = M('SignConfig');

//        if(!empty(I('get.status'))){
        $status = I('get.status');
//        }

        if ( false !== $model->where(array('name' => $name))->setField('status', $status) ) {
            $this->success('编辑成功！');
        } else {
            $error = $model->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
        }
    }
}
