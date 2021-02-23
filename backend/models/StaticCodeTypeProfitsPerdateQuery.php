<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticCodeTypeProfitsPerdate]].
 *
 * @see StaticCodeTypeProfitsPerdate
 */
class StaticCodeTypeProfitsPerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticCodeTypeProfitsPerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticCodeTypeProfitsPerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
