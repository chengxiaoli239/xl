<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_code_4n_arise_month}}".
 *
 * @property int $id
 * @property string $month 月份
 * @property int $code_0145 0145
 * @property int $code_0137 0137
 * @property int $code_1256 1256
 * @property int $code_2348 2348
 * @property int $code_3567 3567
 * @property int $code_3478 3478
 * @property int $code_0678 0678
 * @property int $code_0347 0347
 * @property int $code_5689 5689
 * @property int $code_0138 0138
 * @property int $code_0189 0189
 * @property int $code_0139 0139
 * @property int $code_0125 0125
 * @property int $code_1367 1367
 * @property int $code_1348 1348
 * @property int $code_0129 0129
 * @property int $code_1378 1378
 * @property int $code_1359 1359
 * @property int $code_3589 3589
 * @property int $code_0149 0149
 * @property int $code_0478 0478
 * @property int $code_5789 5789
 * @property int $code_1238 1238
 * @property int $code_1267 1267
 * @property int $code_1234 1234
 * @property int $code_2367 2367
 * @property int $code_2569 2569
 * @property int $code_1469 1469
 * @property int $code_1269 1269
 * @property int $code_4679 4679
 * @property int $code_0258 0258
 * @property int $code_0267 0267
 * @property int $code_0369 0369
 * @property int $code_0567 0567
 * @property int $code_1568 1568
 * @property int $code_2567 2567
 * @property int $code_2457 2457
 * @property int $code_0259 0259
 * @property int $code_2356 2356
 * @property int $code_4789 4789
 * @property int $code_0148 0148
 * @property int $code_0136 0136
 * @property int $code_1678 1678
 * @property int $code_2358 2358
 * @property int $code_0569 0569
 * @property int $code_0278 0278
 * @property int $code_2478 2478
 * @property int $code_0247 0247
 * @property int $code_1379 1379
 * @property int $code_0239 0239
 * @property int $code_1136 1136
 * @property int $code_2899 2899
 * @property int $code_0448 0448
 * @property int $code_4668 4668
 * @property int $code_5889 5889
 * @property int $code_1179 1179
 * @property int $code_1159 1159
 * @property int $code_1227 1227
 * @property int $code_2247 2247
 * @property int $code_0014 0014
 * @property int $code_1168 1168
 * @property int $code_0013 0013
 * @property int $code_3559 3559
 * @property int $code_4457 4457
 * @property int $code_1366 1366
 * @property int $code_0037 0037
 * @property int $code_3346 3346
 * @property int $code_7899 7899
 * @property int $code_1889 1889
 * @property int $code_2477 2477
 * @property int $code_0466 0466
 * @property int $code_1899 1899
 * @property int $code_6889 6889
 * @property int $code_4489 4489
 * @property int $code_0499 0499
 * @property int $code_0899 0899
 * @property int $code_0477 0477
 * @property int $code_3347 3347
 * @property int $code_2344 2344
 * @property int $code_0488 0488
 * @property int $code_0229 0229
 * @property int $code_7789 7789
 * @property int $code_1124 1124
 * @property int $code_0114 0114
 * @property int $code_4456 4456
 * @property int $code_0016 0016
 * @property int $code_1149 1149
 * @property int $code_3799 3799
 * @property int $code_1499 1499
 * @property int $code_3367 3367
 * @property int $code_3499 3499
 * @property int $code_0025 0025
 * @property int $code_2447 2447
 * @property int $code_0017 0017
 * @property int $code_3348 3348
 * @property int $code_0115 0115
 * @property int $code_1228 1228
 * @property int $code_1778 1778
 * @property int $code_2388 2388
 * @property int $code_3577 3577
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8:幸运五星
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticCode4nAriseMonth extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_code_4n_arise_month}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code_0145', 'code_0137', 'code_1256', 'code_2348', 'code_3567', 'code_3478', 'code_0678', 'code_0347', 'code_5689', 'code_0138', 'code_0189', 'code_0139', 'code_0125', 'code_1367', 'code_1348', 'code_0129', 'code_1378', 'code_1359', 'code_3589', 'code_0149', 'code_0478', 'code_5789', 'code_1238', 'code_1267', 'code_1234', 'code_2367', 'code_2569', 'code_1469', 'code_1269', 'code_4679', 'code_0258', 'code_0267', 'code_0369', 'code_0567', 'code_1568', 'code_2567', 'code_2457', 'code_0259', 'code_2356', 'code_4789', 'code_0148', 'code_0136', 'code_1678', 'code_2358', 'code_0569', 'code_0278', 'code_2478', 'code_0247', 'code_1379', 'code_0239', 'code_1136', 'code_2899', 'code_0448', 'code_4668', 'code_5889', 'code_1179', 'code_1159', 'code_1227', 'code_2247', 'code_0014', 'code_1168', 'code_0013', 'code_3559', 'code_4457', 'code_1366', 'code_0037', 'code_3346', 'code_7899', 'code_1889', 'code_2477', 'code_0466', 'code_1899', 'code_6889', 'code_4489', 'code_0499', 'code_0899', 'code_0477', 'code_3347', 'code_2344', 'code_0488', 'code_0229', 'code_7789', 'code_1124', 'code_0114', 'code_4456', 'code_0016', 'code_1149', 'code_3799', 'code_1499', 'code_3367', 'code_3499', 'code_0025', 'code_2447', 'code_0017', 'code_3348', 'code_0115', 'code_1228', 'code_1778', 'code_2388', 'code_3577', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['month'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'month' => '月份',
            'code_0145' => '0145',
            'code_0137' => '0137',
            'code_1256' => '1256',
            'code_2348' => '2348',
            'code_3567' => '3567',
            'code_3478' => '3478',
            'code_0678' => '0678',
            'code_0347' => '0347',
            'code_5689' => '5689',
            'code_0138' => '0138',
            'code_0189' => '0189',
            'code_0139' => '0139',
            'code_0125' => '0125',
            'code_1367' => '1367',
            'code_1348' => '1348',
            'code_0129' => '0129',
            'code_1378' => '1378',
            'code_1359' => '1359',
            'code_3589' => '3589',
            'code_0149' => '0149',
            'code_0478' => '0478',
            'code_5789' => '5789',
            'code_1238' => '1238',
            'code_1267' => '1267',
            'code_1234' => '1234',
            'code_2367' => '2367',
            'code_2569' => '2569',
            'code_1469' => '1469',
            'code_1269' => '1269',
            'code_4679' => '4679',
            'code_0258' => '0258',
            'code_0267' => '0267',
            'code_0369' => '0369',
            'code_0567' => '0567',
            'code_1568' => '1568',
            'code_2567' => '2567',
            'code_2457' => '2457',
            'code_0259' => '0259',
            'code_2356' => '2356',
            'code_4789' => '4789',
            'code_0148' => '0148',
            'code_0136' => '0136',
            'code_1678' => '1678',
            'code_2358' => '2358',
            'code_0569' => '0569',
            'code_0278' => '0278',
            'code_2478' => '2478',
            'code_0247' => '0247',
            'code_1379' => '1379',
            'code_0239' => '0239',
            'code_1136' => '1136',
            'code_2899' => '2899',
            'code_0448' => '0448',
            'code_4668' => '4668',
            'code_5889' => '5889',
            'code_1179' => '1179',
            'code_1159' => '1159',
            'code_1227' => '1227',
            'code_2247' => '2247',
            'code_0014' => '0014',
            'code_1168' => '1168',
            'code_0013' => '0013',
            'code_3559' => '3559',
            'code_4457' => '4457',
            'code_1366' => '1366',
            'code_0037' => '0037',
            'code_3346' => '3346',
            'code_7899' => '7899',
            'code_1889' => '1889',
            'code_2477' => '2477',
            'code_0466' => '0466',
            'code_1899' => '1899',
            'code_6889' => '6889',
            'code_4489' => '4489',
            'code_0499' => '0499',
            'code_0899' => '0899',
            'code_0477' => '0477',
            'code_3347' => '3347',
            'code_2344' => '2344',
            'code_0488' => '0488',
            'code_0229' => '0229',
            'code_7789' => '7789',
            'code_1124' => '1124',
            'code_0114' => '0114',
            'code_4456' => '4456',
            'code_0016' => '0016',
            'code_1149' => '1149',
            'code_3799' => '3799',
            'code_1499' => '1499',
            'code_3367' => '3367',
            'code_3499' => '3499',
            'code_0025' => '0025',
            'code_2447' => '2447',
            'code_0017' => '0017',
            'code_3348' => '3348',
            'code_0115' => '0115',
            'code_1228' => '1228',
            'code_1778' => '1778',
            'code_2388' => '2388',
            'code_3577' => '3577',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8:幸运五星',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return StaticCode4nAriseMonthQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticCode4nAriseMonthQuery(get_called_class());
    }
}
