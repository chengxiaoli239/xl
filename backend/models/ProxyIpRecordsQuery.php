<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[ProxyIpRecords]].
 *
 * @see ProxyIpRecords
 */
class ProxyIpRecordsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return ProxyIpRecords[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return ProxyIpRecords|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
