<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticCode3nAriseMonth]].
 *
 * @see StaticCode3nAriseMonth
 */
class StaticCode3nAriseMonthQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticCode3nAriseMonth[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticCode3nAriseMonth|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
