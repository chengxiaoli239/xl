<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\StaticCode4nAriseMonth as StaticCode4nAriseMonthModel;

/**
 * StaticCode4nAriseMonth represents the model behind the search form of `backend\models\StaticCode4nAriseMonth`.
 */
class StaticCode4nAriseMonth extends StaticCode4nAriseMonthModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'code_0145', 'code_0137', 'code_1256', 'code_2348', 'code_3567', 'code_3478', 'code_0678', 'code_0347', 'code_5689', 'code_0138', 'code_0189', 'code_0139', 'code_0125', 'code_1367', 'code_1348', 'code_0129', 'code_1378', 'code_1359', 'code_3589', 'code_0149', 'code_0478', 'code_5789', 'code_1238', 'code_1267', 'code_1234', 'code_2367', 'code_2569', 'code_1469', 'code_1269', 'code_4679', 'code_0258', 'code_0267', 'code_0369', 'code_0567', 'code_1568', 'code_2567', 'code_2457', 'code_0259', 'code_2356', 'code_4789', 'code_0148', 'code_0136', 'code_1678', 'code_2358', 'code_0569', 'code_0278', 'code_2478', 'code_0247', 'code_1379', 'code_0239', 'code_1136', 'code_2899', 'code_0448', 'code_4668', 'code_5889', 'code_1179', 'code_1159', 'code_1227', 'code_2247', 'code_0014', 'code_1168', 'code_0013', 'code_3559', 'code_4457', 'code_1366', 'code_0037', 'code_3346', 'code_7899', 'code_1889', 'code_2477', 'code_0466', 'code_1899', 'code_6889', 'code_4489', 'code_0499', 'code_0899', 'code_0477', 'code_3347', 'code_2344', 'code_0488', 'code_0229', 'code_7789', 'code_1124', 'code_0114', 'code_4456', 'code_0016', 'code_1149', 'code_3799', 'code_1499', 'code_3367', 'code_3499', 'code_0025', 'code_2447', 'code_0017', 'code_3348', 'code_0115', 'code_1228', 'code_1778', 'code_2388', 'code_3577', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
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
        $query = StaticCode4nAriseMonthModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            'code_0145' => $this->code_0145,
            'code_0137' => $this->code_0137,
            'code_1256' => $this->code_1256,
            'code_2348' => $this->code_2348,
            'code_3567' => $this->code_3567,
            'code_3478' => $this->code_3478,
            'code_0678' => $this->code_0678,
            'code_0347' => $this->code_0347,
            'code_5689' => $this->code_5689,
            'code_0138' => $this->code_0138,
            'code_0189' => $this->code_0189,
            'code_0139' => $this->code_0139,
            'code_0125' => $this->code_0125,
            'code_1367' => $this->code_1367,
            'code_1348' => $this->code_1348,
            'code_0129' => $this->code_0129,
            'code_1378' => $this->code_1378,
            'code_1359' => $this->code_1359,
            'code_3589' => $this->code_3589,
            'code_0149' => $this->code_0149,
            'code_0478' => $this->code_0478,
            'code_5789' => $this->code_5789,
            'code_1238' => $this->code_1238,
            'code_1267' => $this->code_1267,
            'code_1234' => $this->code_1234,
            'code_2367' => $this->code_2367,
            'code_2569' => $this->code_2569,
            'code_1469' => $this->code_1469,
            'code_1269' => $this->code_1269,
            'code_4679' => $this->code_4679,
            'code_0258' => $this->code_0258,
            'code_0267' => $this->code_0267,
            'code_0369' => $this->code_0369,
            'code_0567' => $this->code_0567,
            'code_1568' => $this->code_1568,
            'code_2567' => $this->code_2567,
            'code_2457' => $this->code_2457,
            'code_0259' => $this->code_0259,
            'code_2356' => $this->code_2356,
            'code_4789' => $this->code_4789,
            'code_0148' => $this->code_0148,
            'code_0136' => $this->code_0136,
            'code_1678' => $this->code_1678,
            'code_2358' => $this->code_2358,
            'code_0569' => $this->code_0569,
            'code_0278' => $this->code_0278,
            'code_2478' => $this->code_2478,
            'code_0247' => $this->code_0247,
            'code_1379' => $this->code_1379,
            'code_0239' => $this->code_0239,
            'code_1136' => $this->code_1136,
            'code_2899' => $this->code_2899,
            'code_0448' => $this->code_0448,
            'code_4668' => $this->code_4668,
            'code_5889' => $this->code_5889,
            'code_1179' => $this->code_1179,
            'code_1159' => $this->code_1159,
            'code_1227' => $this->code_1227,
            'code_2247' => $this->code_2247,
            'code_0014' => $this->code_0014,
            'code_1168' => $this->code_1168,
            'code_0013' => $this->code_0013,
            'code_3559' => $this->code_3559,
            'code_4457' => $this->code_4457,
            'code_1366' => $this->code_1366,
            'code_0037' => $this->code_0037,
            'code_3346' => $this->code_3346,
            'code_7899' => $this->code_7899,
            'code_1889' => $this->code_1889,
            'code_2477' => $this->code_2477,
            'code_0466' => $this->code_0466,
            'code_1899' => $this->code_1899,
            'code_6889' => $this->code_6889,
            'code_4489' => $this->code_4489,
            'code_0499' => $this->code_0499,
            'code_0899' => $this->code_0899,
            'code_0477' => $this->code_0477,
            'code_3347' => $this->code_3347,
            'code_2344' => $this->code_2344,
            'code_0488' => $this->code_0488,
            'code_0229' => $this->code_0229,
            'code_7789' => $this->code_7789,
            'code_1124' => $this->code_1124,
            'code_0114' => $this->code_0114,
            'code_4456' => $this->code_4456,
            'code_0016' => $this->code_0016,
            'code_1149' => $this->code_1149,
            'code_3799' => $this->code_3799,
            'code_1499' => $this->code_1499,
            'code_3367' => $this->code_3367,
            'code_3499' => $this->code_3499,
            'code_0025' => $this->code_0025,
            'code_2447' => $this->code_2447,
            'code_0017' => $this->code_0017,
            'code_3348' => $this->code_3348,
            'code_0115' => $this->code_0115,
            'code_1228' => $this->code_1228,
            'code_1778' => $this->code_1778,
            'code_2388' => $this->code_2388,
            'code_3577' => $this->code_3577,
            'lottery_type' => $this->lottery_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'month', $this->month]);

        return $dataProvider;
    }
}
