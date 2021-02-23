<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscSdHzYl]].
 *
 * @see SscSdHzYl
 */
class SscSdHzYlQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscSdHzYl[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscSdHzYl|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
