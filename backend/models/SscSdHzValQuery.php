<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscSdHzVal]].
 *
 * @see SscSdHzVal
 */
class SscSdHzValQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscSdHzVal[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscSdHzVal|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
