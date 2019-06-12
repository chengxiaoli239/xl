<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticHzArisePerdate]].
 *
 * @see StaticHzArisePerdate
 */
class StaticHzArisePerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticHzArisePerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticHzArisePerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
