<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscDwHzYl]].
 *
 * @see SscDwHzYl
 */
class SscDwHzYlQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscDwHzYl[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscDwHzYl|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
