<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Static4dProfits]].
 *
 * @see Static4dProfits
 */
class Static4dProfitsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Static4dProfits[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Static4dProfits|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
