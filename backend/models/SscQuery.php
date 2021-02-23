<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscKjDataDs]].
 *
 * @see SscKjDataDs
 */
class SscQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscKjDataDs[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscKjDataDs|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
