<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscDsYl]].
 *
 * @see SscDsYl
 */
class SscDsYlQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscDsYl[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscDsYl|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
