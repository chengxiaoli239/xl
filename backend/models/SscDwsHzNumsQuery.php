<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SscDwsHzNums]].
 *
 * @see SscDwsHzNums
 */
class SscDwsHzNumsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SscDwsHzNums[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SscDwsHzNums|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
