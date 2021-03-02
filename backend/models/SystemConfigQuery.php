<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SystemConfig]].
 *
 * @see SystemConfig
 */
class SystemConfigQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SystemConfig[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SystemConfig|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
