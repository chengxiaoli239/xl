<?php

namespace backend\models;

/**
 * This is the ActiveQuery class for [[SysCustomizedFilters]].
 *
 * @see SysCustomizedFilters
 */
class SysCustomizedFiltersQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return SysCustomizedFilters[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return SysCustomizedFilters|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
