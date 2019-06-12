<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticCodeTypeArisePerdate]].
 *
 * @see StaticCodeTypeArisePerdate
 */
class StaticCodeTypeArisePerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticCodeTypeArisePerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticCodeTypeArisePerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
