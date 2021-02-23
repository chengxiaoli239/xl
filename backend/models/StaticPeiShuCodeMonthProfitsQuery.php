<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticPeiShuCodeMonthProfits]].
 *
 * @see StaticPeiShuCodeMonthProfits
 */
class StaticPeiShuCodeMonthProfitsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeMonthProfits[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeMonthProfits|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
