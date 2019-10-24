<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[CodeTypes]].
 *
 * @see CodeTypes
 */
class CodeTypesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return CodeTypes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CodeTypes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
