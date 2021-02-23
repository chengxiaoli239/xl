<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscKjData3num]].
 *
 * @see SscKjData3num
 */
class SscKjData3numQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscKjData3num[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscKjData3num|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
