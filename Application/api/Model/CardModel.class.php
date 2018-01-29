<?php
namespace api\Model;
use Think\Model;

/**
     * 卡密模型
 */
class CardModel extends Model{
    
    public function getCardList(){

    }

    public function getCard($id){

        $map['id'] = $id;
        $card = $this -> field(true) -> where($map) -> find();

        return $card;
    }

    /**
     * @param $type 卡密类别编号
     * @return mixed 返回1条最早未激活的卡片对象
     */
    public function getCardByType($type){

        $map['type'] = $type;
        $map['status'] = 0;
        $card = $this -> field(true) -> where($map) -> order('id') -> find();

        return $card;
    }

    /**
     * @param $id 卡密id
     * @param int $status 卡密激活状态
     * @return bool
     */
    public function setCardStatus($id,$status=0){
        $map['id'] = $id;
        $card = $this -> where($map) -> setField('status',$status);
        if($card){
            return true;
        }
        else {
            return false;
        }
    }
}