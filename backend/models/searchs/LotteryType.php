<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\LotteryType as LotteryTypeModel;

/**
 * LotteryType represents the model behind the search form of `backend\models\LotteryType`.
 */
class LotteryType extends LotteryTypeModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'lottery_type', 'enable', 'isDelete', 'sort', 'data_ftime', 'defaultViewGroup', 'android', 'num'], 'integer'],
            [['name', 'codeList', 'title', 'shortName', 'info', 'onGetNoed', 'typeGroupName'], 'safe'],
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
        $query = LotteryTypeModel::find();

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
            'lottery_type' => $this->lottery_type,
            'enable' => $this->enable,
            'isDelete' => $this->isDelete,
            'sort' => $this->sort,
            'data_ftime' => $this->data_ftime,
            'defaultViewGroup' => $this->defaultViewGroup,
            'android' => $this->android,
            'num' => $this->num,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'codeList', $this->codeList])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'shortName', $this->shortName])
            ->andFilterWhere(['like', 'info', $this->info])
            ->andFilterWhere(['like', 'onGetNoed', $this->onGetNoed])
            ->andFilterWhere(['like', 'typeGroupName', $this->typeGroupName]);

        return $dataProvider;
    }
}
