<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[KjConfig]].
 *
 * @see KjConfig
 */
class KjConfigQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return KjConfig[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return KjConfig|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
