<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[UserSysPlans]].
 *
 * @see UserSysPlans
 */
class UserSysPlansQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return UserSysPlans[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserSysPlans|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
