<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscDwHzStatic]].
 *
 * @see SscDwHzStatic
 */
class SscDwHzStaticQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscDwHzStatic[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscDwHzStatic|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
