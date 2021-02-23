<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticCode4nAriseMonth]].
 *
 * @see StaticCode4nAriseMonth
 */
class StaticCode4nAriseMonthQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticCode4nAriseMonth[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticCode4nAriseMonth|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
