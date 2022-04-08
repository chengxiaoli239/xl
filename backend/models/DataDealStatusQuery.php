<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[DataDealStatus]].
 *
 * @see DataDealStatus
 */
class DataDealStatusQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return DataDealStatus[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DataDealStatus|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
