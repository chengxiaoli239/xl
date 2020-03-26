<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[AgentUsers]].
 *
 * @see AgentUsers
 */
class AgentUsersQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return AgentUsers[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return AgentUsers|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
