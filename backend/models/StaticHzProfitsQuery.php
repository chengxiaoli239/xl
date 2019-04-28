<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticHzProfits]].
 *
 * @see StaticHzProfits
 */
class StaticHzProfitsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticHzProfits[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticHzProfits|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
