<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticPeiShuCodeDateProfits]].
 *
 * @see StaticPeiShuCodeDateProfits
 */
class StaticPeiShuCodeDateProfitsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeDateProfits[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeDateProfits|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
