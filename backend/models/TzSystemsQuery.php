<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[TzSystems]].
 *
 * @see TzSystems
 */
class TzSystemsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return TzSystems[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TzSystems|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
