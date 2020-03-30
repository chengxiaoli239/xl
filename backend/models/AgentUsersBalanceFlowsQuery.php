<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[AgentUsersBalanceFlows]].
 *
 * @see AgentUsersBalanceFlows
 */
class AgentUsersBalanceFlowsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return AgentUsersBalanceFlows[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return AgentUsersBalanceFlows|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
