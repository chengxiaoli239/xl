<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_code_3n_arise_month}}".
 *
 * @property int $id
 * @property string $month 月份
 * @property int $code_137 137
 * @property int $code_256 256
 * @property int $code_126 126
 * @property int $code_238 238
 * @property int $code_078 078
 * @property int $code_013 013
 * @property int $code_589 589
 * @property int $code_019 019
 * @property int $code_138 138
 * @property int $code_014 014
 * @property int $code_478 478
 * @property int $code_267 267
 * @property int $code_125 125
 * @property int $code_567 567
 * @property int $code_012 012
 * @property int $code_139 139
 * @property int $code_469 469
 * @property int $code_034 034
 * @property int $code_018 018
 * @property int $code_278 278
 * @property int $code_056 056
 * @property int $code_569 569
 * @property int $code_025 025
 * @property int $code_029 029
 * @property int $code_356 356
 * @property int $code_015 015
 * @property int $code_347 347
 * @property int $code_348 348
 * @property int $code_134 134
 * @property int $code_145 145
 * @property int $code_258 258
 * @property int $code_036 036
 * @property int $code_123 123
 * @property int $code_148 148
 * @property int $code_789 789
 * @property int $code_378 378
 * @property int $code_067 067
 * @property int $code_156 156
 * @property int $code_178 178
 * @property int $code_037 037
 * @property int $code_069 069
 * @property int $code_167 167
 * @property int $code_248 248
 * @property int $code_236 236
 * @property int $code_289 289
 * @property int $code_039 039
 * @property int $code_578 578
 * @property int $code_678 678
 * @property int $code_023 023
 * @property int $code_158 158
 * @property int $code_017 017
 * @property int $code_046 046
 * @property int $code_038 038
 * @property int $code_129 129
 * @property int $code_024 024
 * @property int $code_247 247
 * @property int $code_456 456
 * @property int $code_136 136
 * @property int $code_568 568
 * @property int $code_169 169
 * @property int $code_026 026
 * @property int $code_027 027
 * @property int $code_058 058
 * @property int $code_269 269
 * @property int $code_089 089
 * @property int $code_149 149
 * @property int $code_259 259
 * @property int $code_689 689
 * @property int $code_367 367
 * @property int $code_128 128
 * @property int $code_127 127
 * @property int $code_135 135
 * @property int $code_028 028
 * @property int $code_357 357
 * @property int $code_146 146
 * @property int $code_048 048
 * @property int $code_059 059
 * @property int $code_147 147
 * @property int $code_168 168
 * @property int $code_234 234
 * @property int $code_467 467
 * @property int $code_358 358
 * @property int $code_189 189
 * @property int $code_268 268
 * @property int $code_468 468
 * @property int $code_679 679
 * @property int $code_045 045
 * @property int $code_179 179
 * @property int $code_245 245
 * @property int $code_279 279
 * @property int $code_235 235
 * @property int $code_257 257
 * @property int $code_079 079
 * @property int $code_489 489
 * @property int $code_047 047
 * @property int $code_359 359
 * @property int $code_124 124
 * @property int $code_068 068
 * @property int $code_239 239
 * @property int $code_035 035
 * @property int $code_246 246
 * @property int $code_458 458
 * @property int $code_479 479
 * @property int $code_057 057
 * @property int $code_579 579
 * @property int $code_016 016
 * @property int $code_157 157
 * @property int $code_457 457
 * @property int $code_237 237
 * @property int $code_249 249
 * @property int $code_389 389
 * @property int $code_159 159
 * @property int $code_049 049
 * @property int $code_369 369
 * @property int $code_379 379
 * @property int $code_349 349
 * @property int $code_346 346
 * @property int $code_345 345
 * @property int $code_368 368
 * @property int $code_459 459
 * @property int $code_899 899
 * @property int $code_499 499
 * @property int $code_099 099
 * @property int $code_448 448
 * @property int $code_114 114
 * @property int $code_122 122
 * @property int $code_399 399
 * @property int $code_116 116
 * @property int $code_334 334
 * @property int $code_299 299
 * @property int $code_044 044
 * @property int $code_889 889
 * @property int $code_001 001
 * @property int $code_668 668
 * @property int $code_166 166
 * @property int $code_007 007
 * @property int $code_778 778
 * @property int $code_003 003
 * @property int $code_244 244
 * @property int $code_004 004
 * @property int $code_229 229
 * @property int $code_288 288
 * @property int $code_477 477
 * @property int $code_337 337
 * @property int $code_119 119
 * @property int $code_225 225
 * @property int $code_447 447
 * @property int $code_355 355
 * @property int $code_377 377
 * @property int $code_688 688
 * @property int $code_022 022
 * @property int $code_266 266
 * @property int $code_799 799
 * @property int $code_228 228
 * @property int $code_115 115
 * @property int $code_779 779
 * @property int $code_188 188
 * @property int $code_199 199
 * @property int $code_223 223
 * @property int $code_366 366
 * @property int $code_002 002
 * @property int $code_055 055
 * @property int $code_336 336
 * @property int $code_445 445
 * @property int $code_033 033
 * @property int $code_117 117
 * @property int $code_227 227
 * @property int $code_557 557
 * @property int $code_118 118
 * @property int $code_339 339
 * @property int $code_344 344
 * @property int $code_446 446
 * @property int $code_113 113
 * @property int $code_005 005
 * @property int $code_224 224
 * @property int $code_255 255
 * @property int $code_699 699
 * @property int $code_006 006
 * @property int $code_011 011
 * @property int $code_088 088
 * @property int $code_277 277
 * @property int $code_388 388
 * @property int $code_788 788
 * @property int $code_449 449
 * @property int $code_466 466
 * @property int $code_133 133
 * @property int $code_556 556
 * @property int $code_112 112
 * @property int $code_488 488
 * @property int $code_455 455
 * @property int $code_667 667
 * @property int $code_335 335
 * @property int $code_233 233
 * @property int $code_577 577
 * @property int $code_009 009
 * @property int $code_559 559
 * @property int $code_008 008
 * @property int $code_077 077
 * @property int $code_599 599
 * @property int $code_066 066
 * @property int $code_338 338
 * @property int $code_588 588
 * @property int $code_669 669
 * @property int $code_677 677
 * @property int $code_177 177
 * @property int $code_566 566
 * @property int $code_226 226
 * @property int $code_144 144
 * @property int $code_558 558
 * @property int $code_155 155
 * @property int $code_000 000
 * @property int $code_222 222
 * @property int $code_444 444
 * @property int $code_999 999
 * @property int $code_555 555
 * @property int $code_777 777
 * @property int $code_666 666
 * @property int $code_888 888
 * @property int $code_333 333
 * @property int $code_111 111
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8:幸运五星
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticCode3nAriseMonth extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_code_3n_arise_month}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code_137', 'code_256', 'code_126', 'code_238', 'code_078', 'code_013', 'code_589', 'code_019', 'code_138', 'code_014', 'code_478', 'code_267', 'code_125', 'code_567', 'code_012', 'code_139', 'code_469', 'code_034', 'code_018', 'code_278', 'code_056', 'code_569', 'code_025', 'code_029', 'code_356', 'code_015', 'code_347', 'code_348', 'code_134', 'code_145', 'code_258', 'code_036', 'code_123', 'code_148', 'code_789', 'code_378', 'code_067', 'code_156', 'code_178', 'code_037', 'code_069', 'code_167', 'code_248', 'code_236', 'code_289', 'code_039', 'code_578', 'code_678', 'code_023', 'code_158', 'code_017', 'code_046', 'code_038', 'code_129', 'code_024', 'code_247', 'code_456', 'code_136', 'code_568', 'code_169', 'code_026', 'code_027', 'code_058', 'code_269', 'code_089', 'code_149', 'code_259', 'code_689', 'code_367', 'code_128', 'code_127', 'code_135', 'code_028', 'code_357', 'code_146', 'code_048', 'code_059', 'code_147', 'code_168', 'code_234', 'code_467', 'code_358', 'code_189', 'code_268', 'code_468', 'code_679', 'code_045', 'code_179', 'code_245', 'code_279', 'code_235', 'code_257', 'code_079', 'code_489', 'code_047', 'code_359', 'code_124', 'code_068', 'code_239', 'code_035', 'code_246', 'code_458', 'code_479', 'code_057', 'code_579', 'code_016', 'code_157', 'code_457', 'code_237', 'code_249', 'code_389', 'code_159', 'code_049', 'code_369', 'code_379', 'code_349', 'code_346', 'code_345', 'code_368', 'code_459', 'code_899', 'code_499', 'code_099', 'code_448', 'code_114', 'code_122', 'code_399', 'code_116', 'code_334', 'code_299', 'code_044', 'code_889', 'code_001', 'code_668', 'code_166', 'code_007', 'code_778', 'code_003', 'code_244', 'code_004', 'code_229', 'code_288', 'code_477', 'code_337', 'code_119', 'code_225', 'code_447', 'code_355', 'code_377', 'code_688', 'code_022', 'code_266', 'code_799', 'code_228', 'code_115', 'code_779', 'code_188', 'code_199', 'code_223', 'code_366', 'code_002', 'code_055', 'code_336', 'code_445', 'code_033', 'code_117', 'code_227', 'code_557', 'code_118', 'code_339', 'code_344', 'code_446', 'code_113', 'code_005', 'code_224', 'code_255', 'code_699', 'code_006', 'code_011', 'code_088', 'code_277', 'code_388', 'code_788', 'code_449', 'code_466', 'code_133', 'code_556', 'code_112', 'code_488', 'code_455', 'code_667', 'code_335', 'code_233', 'code_577', 'code_009', 'code_559', 'code_008', 'code_077', 'code_599', 'code_066', 'code_338', 'code_588', 'code_669', 'code_677', 'code_177', 'code_566', 'code_226', 'code_144', 'code_558', 'code_155', 'code_000', 'code_222', 'code_444', 'code_999', 'code_555', 'code_777', 'code_666', 'code_888', 'code_333', 'code_111', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
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
            'code_137' => '137',
            'code_256' => '256',
            'code_126' => '126',
            'code_238' => '238',
            'code_078' => '078',
            'code_013' => '013',
            'code_589' => '589',
            'code_019' => '019',
            'code_138' => '138',
            'code_014' => '014',
            'code_478' => '478',
            'code_267' => '267',
            'code_125' => '125',
            'code_567' => '567',
            'code_012' => '012',
            'code_139' => '139',
            'code_469' => '469',
            'code_034' => '034',
            'code_018' => '018',
            'code_278' => '278',
            'code_056' => '056',
            'code_569' => '569',
            'code_025' => '025',
            'code_029' => '029',
            'code_356' => '356',
            'code_015' => '015',
            'code_347' => '347',
            'code_348' => '348',
            'code_134' => '134',
            'code_145' => '145',
            'code_258' => '258',
            'code_036' => '036',
            'code_123' => '123',
            'code_148' => '148',
            'code_789' => '789',
            'code_378' => '378',
            'code_067' => '067',
            'code_156' => '156',
            'code_178' => '178',
            'code_037' => '037',
            'code_069' => '069',
            'code_167' => '167',
            'code_248' => '248',
            'code_236' => '236',
            'code_289' => '289',
            'code_039' => '039',
            'code_578' => '578',
            'code_678' => '678',
            'code_023' => '023',
            'code_158' => '158',
            'code_017' => '017',
            'code_046' => '046',
            'code_038' => '038',
            'code_129' => '129',
            'code_024' => '024',
            'code_247' => '247',
            'code_456' => '456',
            'code_136' => '136',
            'code_568' => '568',
            'code_169' => '169',
            'code_026' => '026',
            'code_027' => '027',
            'code_058' => '058',
            'code_269' => '269',
            'code_089' => '089',
            'code_149' => '149',
            'code_259' => '259',
            'code_689' => '689',
            'code_367' => '367',
            'code_128' => '128',
            'code_127' => '127',
            'code_135' => '135',
            'code_028' => '028',
            'code_357' => '357',
            'code_146' => '146',
            'code_048' => '048',
            'code_059' => '059',
            'code_147' => '147',
            'code_168' => '168',
            'code_234' => '234',
            'code_467' => '467',
            'code_358' => '358',
            'code_189' => '189',
            'code_268' => '268',
            'code_468' => '468',
            'code_679' => '679',
            'code_045' => '045',
            'code_179' => '179',
            'code_245' => '245',
            'code_279' => '279',
            'code_235' => '235',
            'code_257' => '257',
            'code_079' => '079',
            'code_489' => '489',
            'code_047' => '047',
            'code_359' => '359',
            'code_124' => '124',
            'code_068' => '068',
            'code_239' => '239',
            'code_035' => '035',
            'code_246' => '246',
            'code_458' => '458',
            'code_479' => '479',
            'code_057' => '057',
            'code_579' => '579',
            'code_016' => '016',
            'code_157' => '157',
            'code_457' => '457',
            'code_237' => '237',
            'code_249' => '249',
            'code_389' => '389',
            'code_159' => '159',
            'code_049' => '049',
            'code_369' => '369',
            'code_379' => '379',
            'code_349' => '349',
            'code_346' => '346',
            'code_345' => '345',
            'code_368' => '368',
            'code_459' => '459',
            'code_899' => '899',
            'code_499' => '499',
            'code_099' => '099',
            'code_448' => '448',
            'code_114' => '114',
            'code_122' => '122',
            'code_399' => '399',
            'code_116' => '116',
            'code_334' => '334',
            'code_299' => '299',
            'code_044' => '044',
            'code_889' => '889',
            'code_001' => '001',
            'code_668' => '668',
            'code_166' => '166',
            'code_007' => '007',
            'code_778' => '778',
            'code_003' => '003',
            'code_244' => '244',
            'code_004' => '004',
            'code_229' => '229',
            'code_288' => '288',
            'code_477' => '477',
            'code_337' => '337',
            'code_119' => '119',
            'code_225' => '225',
            'code_447' => '447',
            'code_355' => '355',
            'code_377' => '377',
            'code_688' => '688',
            'code_022' => '022',
            'code_266' => '266',
            'code_799' => '799',
            'code_228' => '228',
            'code_115' => '115',
            'code_779' => '779',
            'code_188' => '188',
            'code_199' => '199',
            'code_223' => '223',
            'code_366' => '366',
            'code_002' => '002',
            'code_055' => '055',
            'code_336' => '336',
            'code_445' => '445',
            'code_033' => '033',
            'code_117' => '117',
            'code_227' => '227',
            'code_557' => '557',
            'code_118' => '118',
            'code_339' => '339',
            'code_344' => '344',
            'code_446' => '446',
            'code_113' => '113',
            'code_005' => '005',
            'code_224' => '224',
            'code_255' => '255',
            'code_699' => '699',
            'code_006' => '006',
            'code_011' => '011',
            'code_088' => '088',
            'code_277' => '277',
            'code_388' => '388',
            'code_788' => '788',
            'code_449' => '449',
            'code_466' => '466',
            'code_133' => '133',
            'code_556' => '556',
            'code_112' => '112',
            'code_488' => '488',
            'code_455' => '455',
            'code_667' => '667',
            'code_335' => '335',
            'code_233' => '233',
            'code_577' => '577',
            'code_009' => '009',
            'code_559' => '559',
            'code_008' => '008',
            'code_077' => '077',
            'code_599' => '599',
            'code_066' => '066',
            'code_338' => '338',
            'code_588' => '588',
            'code_669' => '669',
            'code_677' => '677',
            'code_177' => '177',
            'code_566' => '566',
            'code_226' => '226',
            'code_144' => '144',
            'code_558' => '558',
            'code_155' => '155',
            'code_000' => '000',
            'code_222' => '222',
            'code_444' => '444',
            'code_999' => '999',
            'code_555' => '555',
            'code_777' => '777',
            'code_666' => '666',
            'code_888' => '888',
            'code_333' => '333',
            'code_111' => '111',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc 7:北京快乐8 8:幸运五星',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return StaticCode3nAriseMonthQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticCode3nAriseMonthQuery(get_called_class());
    }
}
