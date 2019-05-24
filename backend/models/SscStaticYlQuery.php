<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscStaticYl]].
 *
 * @see SscStaticYl
 */
class SscStaticYlQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscStaticYl[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscStaticYl|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
