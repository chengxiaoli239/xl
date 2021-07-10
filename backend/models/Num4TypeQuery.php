<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Num4Type]].
 *
 * @see Num4Type
 */
class Num4TypeQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Num4Type[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Num4Type|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
