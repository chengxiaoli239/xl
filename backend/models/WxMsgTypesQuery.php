<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[WxMsgTypes]].
 *
 * @see WxMsgTypes
 */
class WxMsgTypesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return WxMsgTypes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return WxMsgTypes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
