<?php

namespace backend\models\sports;

/**
 * This is the ActiveQuery class for [[SportsPlatesGames]].
 *
 * @see SportsPlatesGames
 */
class SportsPlatesGamesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SportsPlatesGames[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SportsPlatesGames|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
