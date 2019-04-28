<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscKjData]].
 *
 * @see SscKjData
 */
class SscKjDataQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscKjData[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscKjData|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
