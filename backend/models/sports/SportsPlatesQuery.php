<?php

namespace backend\models\sports;

/**
 * This is the ActiveQuery class for [[SportsPlates]].
 *
 * @see SportsPlates
 */
class SportsPlatesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SportsPlates[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SportsPlates|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
