<?php

namespace backend\models\sports;

/**
 * This is the ActiveQuery class for [[EventsLiveDatas]].
 *
 * @see EventsLiveDatas
 */
class EventsLiveDatasQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return EventsLiveDatas[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return EventsLiveDatas|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
