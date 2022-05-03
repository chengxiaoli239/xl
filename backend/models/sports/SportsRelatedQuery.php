<?php

namespace backend\models\sports;

/**
 * This is the ActiveQuery class for [[SportsRelated]].
 *
 * @see SportsRelated
 */
class SportsRelatedQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SportsRelated[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SportsRelated|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
