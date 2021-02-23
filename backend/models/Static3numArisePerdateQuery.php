<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[Static3numArisePerdate]].
 *
 * @see Static3numArisePerdate
 */
class Static3numArisePerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Static3numArisePerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Static3numArisePerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
