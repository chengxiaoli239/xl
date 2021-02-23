<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Ssc3numYl]].
 *
 * @see Ssc3numYl
 */
class Ssc3numYlQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Ssc3numYl[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Ssc3numYl|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
