<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[ProxyIpSuppliers]].
 *
 * @see ProxyIpSuppliers
 */
class ProxyIpSuppliersQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return ProxyIpSuppliers[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return ProxyIpSuppliers|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
