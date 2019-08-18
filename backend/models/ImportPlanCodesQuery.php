<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[ImportPlanCodes]].
 *
 * @see ImportPlanCodes
 */
class ImportPlanCodesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return ImportPlanCodes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return ImportPlanCodes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
