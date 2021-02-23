<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscDsStatic]].
 *
 * @see SscDsStatic
 */
class SscDsStaticQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscDsStatic[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscDsStatic|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
