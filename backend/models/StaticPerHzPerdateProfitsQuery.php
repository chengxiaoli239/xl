<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticPerHzPerdateProfits]].
 *
 * @see StaticPerHzPerdateProfits
 */
class StaticPerHzPerdateProfitsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticPerHzPerdateProfits[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticPerHzPerdateProfits|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
