<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Ssc2numsVal]].
 *
 * @see Ssc2numsVal
 */
class Ssc2numsValQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Ssc2numsVal[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Ssc2numsVal|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
