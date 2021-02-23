<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[UserCustomPlans]].
 *
 * @see UserCustomPlans
 */
class UserCustomPlansQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return UserCustomPlans[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserCustomPlans|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
