<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Static4dProfitsPerdate]].
 *
 * @see Static4dProfitsPerdate
 */
class Static4dProfitsPerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Static4dProfitsPerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Static4dProfitsPerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
