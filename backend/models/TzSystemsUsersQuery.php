<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[TzSystemsUsers]].
 *
 * @see TzSystemsUsers
 */
class TzSystemsUsersQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return TzSystemsUsers[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TzSystemsUsers|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
