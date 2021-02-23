<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Ssc2numsYl]].
 *
 * @see Ssc2numsYl
 */
class Ssc2numsYlQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Ssc2numsYl[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Ssc2numsYl|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
