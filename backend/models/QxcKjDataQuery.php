<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[QxcKjData]].
 *
 * @see QxcKjData
 */
class QxcKjDataQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return QxcKjData[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return QxcKjData|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
