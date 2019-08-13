<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[AdminLog]].
 *
 * @see AdminLog
 */
class AdminLogQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return AdminLog[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return AdminLog|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
