<?php

namespace backend\models\thirdD;

use Yii;

/**
 * This is the model class for table "{{%local_to_site_method}}".
 *
 * @property int $id
 * @property int $method_id 玩法id
 * @property int $site_method_id 玩法id
 * @property string $name 玩法名称
 * @property string $money 本金
 * @property string $bouns 奖金
 * @property string $ratio 比率:奖金除于本金
 * @property string $desc 描述
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class LocalToSiteMethodBackend extends \common\models\thirdD\LocalToSiteMethod
{

}
