<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[TzSystemsAuth]].
 *
 * @see TzSystemsAuth
 */
class TzSystemsAuthQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return TzSystemsAuth[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TzSystemsAuth|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
