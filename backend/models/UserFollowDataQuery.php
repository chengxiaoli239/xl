<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[UserFollowData]].
 *
 * @see UserFollowData
 */
class UserFollowDataQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return UserFollowData[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserFollowData|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
