<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[WxFriends]].
 *
 * @see WxFriends
 */
class WxFriendsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return WxFriends[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return WxFriends|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
