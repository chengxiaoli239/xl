<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[LotteryDataDealStatus]].
 *
 * @see LotteryDataDealStatus
 */
class LotteryDataDealStatusQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return LotteryDataDealStatus[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return LotteryDataDealStatus|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
