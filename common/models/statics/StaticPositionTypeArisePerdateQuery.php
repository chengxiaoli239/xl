<?php

namespace common\models\statics;

/**
 * This is the ActiveQuery class for [[StaticPositionTypeArisePerdate]].
 *
 * @see StaticPositionTypeArisePerdate
 */
class StaticPositionTypeArisePerdateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticPositionTypeArisePerdate[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticPositionTypeArisePerdate|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
