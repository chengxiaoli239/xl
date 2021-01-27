<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[StaticPeiShuCodeTrueFalse]].
 *
 * @see StaticPeiShuCodeTrueFalse
 */
class StaticPeiShuCodeTrueFalseQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeTrueFalse[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return StaticPeiShuCodeTrueFalse|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
