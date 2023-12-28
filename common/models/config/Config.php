<?php
namespace common\models\config;
use common\models\base\BaseModel;
use Yii;
/**
 * 
 */
class Config extends BaseModel
{

	const TYPE_STRING = 1;
	const TYPE_ARRAY_JSON = 2;

	/**
	 * 状态：已禁用
	 */
	const STATUS_N = 1;
	/**
	 * 已启用
	 */
	const STATUS_Y = 2;

	public static array $s = [
		'type' => [
			 self::TYPE_STRING=>'字符串',
			 self::TYPE_ARRAY_JSON=>'数组|JSON',
		],
		'status' => [
			self::STATUS_Y=>'已启用',
			self::STATUS_N=>'已禁用'
		]
	];

    public static function tableName(): string
    {
        return 'lt_config';
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }

    public static function getConfig($key='goods_list_coupon_tag',$type=1){
        return self::find()->where(['key'=>$key,'type'=>$type,'status'=>2])->asArray()->one();
    }
    
}
