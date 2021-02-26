<?php

namespace backend\models\wx;

/**
 * This is the ActiveQuery class for [[WebotConfigs]].
 *
 * @see WebotConfigs
 */
class WebotConfigsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return WebotConfigs[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return WebotConfigs|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
