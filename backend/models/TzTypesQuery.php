<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[TzTypes]].
 *
 * @see TzTypes
 */
class TzTypesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return TzTypes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TzTypes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
