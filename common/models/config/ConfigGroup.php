<?php
namespace common\models\config;
use common\models\base\BaseModel;
use Yii;
/**
 * 
 */
class ConfigGroup extends BaseModel
{
    public static function tableName(): string
    {
        return 'lt_config_group';
    }

    public static function getDb(): \yii\db\Connection
    {
        return Yii::$app->db;
    }

    public static function setGroupNameList($data, $field='group_id')
    {
    	$group_ids = array_unique(array_column($data, $field));

    	if( empty($group_ids) ) {
    	    foreach ($data as $key => &$value) {
    	        $value['group_name'] = '';
    	    }
    	}

    	$list = self::find()->where([ 'id'=>$group_ids ])->asArray()->all();

    	$list = \common\tools\UtilArray::getFieldKey( $list, 'id' );

    	foreach ($data as $key => &$value) {
    	    $value['group_name'] = $list[ $value[$field] ]['name']??'';
    	}
    	return $data;
    }

}
