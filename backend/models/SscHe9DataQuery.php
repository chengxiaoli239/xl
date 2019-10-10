<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscHe9Data]].
 *
 * @see SscHe9Data
 */
class SscHe9DataQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscHe9Data[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscHe9Data|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
