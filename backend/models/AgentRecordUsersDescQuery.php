<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[AgentRecordUsersDesc]].
 *
 * @see AgentRecordUsersDesc
 */
class AgentRecordUsersDescQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return AgentRecordUsersDesc[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return AgentRecordUsersDesc|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
