<?php

namespace backend\models\sports;

/**
 * This is the ActiveQuery class for [[SportsInPlay]].
 *
 * @see SportsInPlay
 */
class SportsInPlayQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SportsInPlay[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SportsInPlay|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
