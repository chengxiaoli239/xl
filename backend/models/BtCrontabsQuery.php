<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[BtCrontabs]].
 *
 * @see BtCrontabs
 */
class BtCrontabsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return BtCrontabs[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return BtCrontabs|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
