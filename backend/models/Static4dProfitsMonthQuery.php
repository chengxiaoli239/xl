<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Static4dProfitsMonth]].
 *
 * @see Static4dProfitsMonth
 */
class Static4dProfitsMonthQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Static4dProfitsMonth[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Static4dProfitsMonth|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
