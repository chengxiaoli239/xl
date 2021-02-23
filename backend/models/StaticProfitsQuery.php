<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticProfits]].
 *
 * @see StaticProfits
 */
class StaticProfitsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticProfits[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticProfits|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
