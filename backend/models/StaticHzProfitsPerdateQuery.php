<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticHzProfitsPerdate]].
 *
 * @see StaticHzProfitsPerdate
 */
class StaticHzProfitsPerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticHzProfitsPerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticHzProfitsPerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
