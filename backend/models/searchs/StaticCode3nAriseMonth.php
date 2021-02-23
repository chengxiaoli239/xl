<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticCode3nAriseMonth as StaticCode3nAriseMonthModel;

/**
 * StaticCode3nAriseMonth represents the model behind the search form of `backend\models\StaticCode3nAriseMonth`.
 */
class StaticCode3nAriseMonth extends StaticCode3nAriseMonthModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'code_137', 'code_256', 'code_126', 'code_238', 'code_078', 'code_013', 'code_589', 'code_019', 'code_138', 'code_014', 'code_478', 'code_267', 'code_125', 'code_567', 'code_012', 'code_139', 'code_469', 'code_034', 'code_018', 'code_278', 'code_056', 'code_569', 'code_025', 'code_029', 'code_356', 'code_015', 'code_347', 'code_348', 'code_134', 'code_145', 'code_258', 'code_036', 'code_123', 'code_148', 'code_789', 'code_378', 'code_067', 'code_156', 'code_178', 'code_037', 'code_069', 'code_167', 'code_248', 'code_236', 'code_289', 'code_039', 'code_578', 'code_678', 'code_023', 'code_158', 'code_017', 'code_046', 'code_038', 'code_129', 'code_024', 'code_247', 'code_456', 'code_136', 'code_568', 'code_169', 'code_026', 'code_027', 'code_058', 'code_269', 'code_089', 'code_149', 'code_259', 'code_689', 'code_367', 'code_128', 'code_127', 'code_135', 'code_028', 'code_357', 'code_146', 'code_048', 'code_059', 'code_147', 'code_168', 'code_234', 'code_467', 'code_358', 'code_189', 'code_268', 'code_468', 'code_679', 'code_045', 'code_179', 'code_245', 'code_279', 'code_235', 'code_257', 'code_079', 'code_489', 'code_047', 'code_359', 'code_124', 'code_068', 'code_239', 'code_035', 'code_246', 'code_458', 'code_479', 'code_057', 'code_579', 'code_016', 'code_157', 'code_457', 'code_237', 'code_249', 'code_389', 'code_159', 'code_049', 'code_369', 'code_379', 'code_349', 'code_346', 'code_345', 'code_368', 'code_459', 'code_899', 'code_499', 'code_099', 'code_448', 'code_114', 'code_122', 'code_399', 'code_116', 'code_334', 'code_299', 'code_044', 'code_889', 'code_001', 'code_668', 'code_166', 'code_007', 'code_778', 'code_003', 'code_244', 'code_004', 'code_229', 'code_288', 'code_477', 'code_337', 'code_119', 'code_225', 'code_447', 'code_355', 'code_377', 'code_688', 'code_022', 'code_266', 'code_799', 'code_228', 'code_115', 'code_779', 'code_188', 'code_199', 'code_223', 'code_366', 'code_002', 'code_055', 'code_336', 'code_445', 'code_033', 'code_117', 'code_227', 'code_557', 'code_118', 'code_339', 'code_344', 'code_446', 'code_113', 'code_005', 'code_224', 'code_255', 'code_699', 'code_006', 'code_011', 'code_088', 'code_277', 'code_388', 'code_788', 'code_449', 'code_466', 'code_133', 'code_556', 'code_112', 'code_488', 'code_455', 'code_667', 'code_335', 'code_233', 'code_577', 'code_009', 'code_559', 'code_008', 'code_077', 'code_599', 'code_066', 'code_338', 'code_588', 'code_669', 'code_677', 'code_177', 'code_566', 'code_226', 'code_144', 'code_558', 'code_155', 'code_000', 'code_222', 'code_444', 'code_999', 'code_555', 'code_777', 'code_666', 'code_888', 'code_333', 'code_111', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['month', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = StaticCode3nAriseMonthModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'code_137' => $this->code_137,
            'code_256' => $this->code_256,
            'code_126' => $this->code_126,
            'code_238' => $this->code_238,
            'code_078' => $this->code_078,
            'code_013' => $this->code_013,
            'code_589' => $this->code_589,
            'code_019' => $this->code_019,
            'code_138' => $this->code_138,
            'code_014' => $this->code_014,
            'code_478' => $this->code_478,
            'code_267' => $this->code_267,
            'code_125' => $this->code_125,
            'code_567' => $this->code_567,
            'code_012' => $this->code_012,
            'code_139' => $this->code_139,
            'code_469' => $this->code_469,
            'code_034' => $this->code_034,
            'code_018' => $this->code_018,
            'code_278' => $this->code_278,
            'code_056' => $this->code_056,
            'code_569' => $this->code_569,
            'code_025' => $this->code_025,
            'code_029' => $this->code_029,
            'code_356' => $this->code_356,
            'code_015' => $this->code_015,
            'code_347' => $this->code_347,
            'code_348' => $this->code_348,
            'code_134' => $this->code_134,
            'code_145' => $this->code_145,
            'code_258' => $this->code_258,
            'code_036' => $this->code_036,
            'code_123' => $this->code_123,
            'code_148' => $this->code_148,
            'code_789' => $this->code_789,
            'code_378' => $this->code_378,
            'code_067' => $this->code_067,
            'code_156' => $this->code_156,
            'code_178' => $this->code_178,
            'code_037' => $this->code_037,
            'code_069' => $this->code_069,
            'code_167' => $this->code_167,
            'code_248' => $this->code_248,
            'code_236' => $this->code_236,
            'code_289' => $this->code_289,
            'code_039' => $this->code_039,
            'code_578' => $this->code_578,
            'code_678' => $this->code_678,
            'code_023' => $this->code_023,
            'code_158' => $this->code_158,
            'code_017' => $this->code_017,
            'code_046' => $this->code_046,
            'code_038' => $this->code_038,
            'code_129' => $this->code_129,
            'code_024' => $this->code_024,
            'code_247' => $this->code_247,
            'code_456' => $this->code_456,
            'code_136' => $this->code_136,
            'code_568' => $this->code_568,
            'code_169' => $this->code_169,
            'code_026' => $this->code_026,
            'code_027' => $this->code_027,
            'code_058' => $this->code_058,
            'code_269' => $this->code_269,
            'code_089' => $this->code_089,
            'code_149' => $this->code_149,
            'code_259' => $this->code_259,
            'code_689' => $this->code_689,
            'code_367' => $this->code_367,
            'code_128' => $this->code_128,
            'code_127' => $this->code_127,
            'code_135' => $this->code_135,
            'code_028' => $this->code_028,
            'code_357' => $this->code_357,
            'code_146' => $this->code_146,
            'code_048' => $this->code_048,
            'code_059' => $this->code_059,
            'code_147' => $this->code_147,
            'code_168' => $this->code_168,
            'code_234' => $this->code_234,
            'code_467' => $this->code_467,
            'code_358' => $this->code_358,
            'code_189' => $this->code_189,
            'code_268' => $this->code_268,
            'code_468' => $this->code_468,
            'code_679' => $this->code_679,
            'code_045' => $this->code_045,
            'code_179' => $this->code_179,
            'code_245' => $this->code_245,
            'code_279' => $this->code_279,
            'code_235' => $this->code_235,
            'code_257' => $this->code_257,
            'code_079' => $this->code_079,
            'code_489' => $this->code_489,
            'code_047' => $this->code_047,
            'code_359' => $this->code_359,
            'code_124' => $this->code_124,
            'code_068' => $this->code_068,
            'code_239' => $this->code_239,
            'code_035' => $this->code_035,
            'code_246' => $this->code_246,
            'code_458' => $this->code_458,
            'code_479' => $this->code_479,
            'code_057' => $this->code_057,
            'code_579' => $this->code_579,
            'code_016' => $this->code_016,
            'code_157' => $this->code_157,
            'code_457' => $this->code_457,
            'code_237' => $this->code_237,
            'code_249' => $this->code_249,
            'code_389' => $this->code_389,
            'code_159' => $this->code_159,
            'code_049' => $this->code_049,
            'code_369' => $this->code_369,
            'code_379' => $this->code_379,
            'code_349' => $this->code_349,
            'code_346' => $this->code_346,
            'code_345' => $this->code_345,
            'code_368' => $this->code_368,
            'code_459' => $this->code_459,
            'code_899' => $this->code_899,
            'code_499' => $this->code_499,
            'code_099' => $this->code_099,
            'code_448' => $this->code_448,
            'code_114' => $this->code_114,
            'code_122' => $this->code_122,
            'code_399' => $this->code_399,
            'code_116' => $this->code_116,
            'code_334' => $this->code_334,
            'code_299' => $this->code_299,
            'code_044' => $this->code_044,
            'code_889' => $this->code_889,
            'code_001' => $this->code_001,
            'code_668' => $this->code_668,
            'code_166' => $this->code_166,
            'code_007' => $this->code_007,
            'code_778' => $this->code_778,
            'code_003' => $this->code_003,
            'code_244' => $this->code_244,
            'code_004' => $this->code_004,
            'code_229' => $this->code_229,
            'code_288' => $this->code_288,
            'code_477' => $this->code_477,
            'code_337' => $this->code_337,
            'code_119' => $this->code_119,
            'code_225' => $this->code_225,
            'code_447' => $this->code_447,
            'code_355' => $this->code_355,
            'code_377' => $this->code_377,
            'code_688' => $this->code_688,
            'code_022' => $this->code_022,
            'code_266' => $this->code_266,
            'code_799' => $this->code_799,
            'code_228' => $this->code_228,
            'code_115' => $this->code_115,
            'code_779' => $this->code_779,
            'code_188' => $this->code_188,
            'code_199' => $this->code_199,
            'code_223' => $this->code_223,
            'code_366' => $this->code_366,
            'code_002' => $this->code_002,
            'code_055' => $this->code_055,
            'code_336' => $this->code_336,
            'code_445' => $this->code_445,
            'code_033' => $this->code_033,
            'code_117' => $this->code_117,
            'code_227' => $this->code_227,
            'code_557' => $this->code_557,
            'code_118' => $this->code_118,
            'code_339' => $this->code_339,
            'code_344' => $this->code_344,
            'code_446' => $this->code_446,
            'code_113' => $this->code_113,
            'code_005' => $this->code_005,
            'code_224' => $this->code_224,
            'code_255' => $this->code_255,
            'code_699' => $this->code_699,
            'code_006' => $this->code_006,
            'code_011' => $this->code_011,
            'code_088' => $this->code_088,
            'code_277' => $this->code_277,
            'code_388' => $this->code_388,
            'code_788' => $this->code_788,
            'code_449' => $this->code_449,
            'code_466' => $this->code_466,
            'code_133' => $this->code_133,
            'code_556' => $this->code_556,
            'code_112' => $this->code_112,
            'code_488' => $this->code_488,
            'code_455' => $this->code_455,
            'code_667' => $this->code_667,
            'code_335' => $this->code_335,
            'code_233' => $this->code_233,
            'code_577' => $this->code_577,
            'code_009' => $this->code_009,
            'code_559' => $this->code_559,
            'code_008' => $this->code_008,
            'code_077' => $this->code_077,
            'code_599' => $this->code_599,
            'code_066' => $this->code_066,
            'code_338' => $this->code_338,
            'code_588' => $this->code_588,
            'code_669' => $this->code_669,
            'code_677' => $this->code_677,
            'code_177' => $this->code_177,
            'code_566' => $this->code_566,
            'code_226' => $this->code_226,
            'code_144' => $this->code_144,
            'code_558' => $this->code_558,
            'code_155' => $this->code_155,
            'code_000' => $this->code_000,
            'code_222' => $this->code_222,
            'code_444' => $this->code_444,
            'code_999' => $this->code_999,
            'code_555' => $this->code_555,
            'code_777' => $this->code_777,
            'code_666' => $this->code_666,
            'code_888' => $this->code_888,
            'code_333' => $this->code_333,
            'code_111' => $this->code_111,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'month', $this->month]);

        return $dataProvider;
    }
}
