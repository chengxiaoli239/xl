<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscStaticVal]].
 *
 * @see SscStaticVal
 */
class SscStaticValQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscStaticVal[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscStaticVal|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
