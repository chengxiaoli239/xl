<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\WxMsgStatus as WxMsgStatusModel;

/**
 * WxMsgStatus represents the model behind the search form of `backend\models\WxMsgStatus`.
 */
class WxMsgStatus extends WxMsgStatusModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'msg_type', 'status', 'created_at'], 'integer'],
            [['fid', 'updated_at'], 'safe'],
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
        $query = WxMsgStatusModel::find();

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
            'msg_type' => $this->msg_type,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'fid', $this->fid]);

        return $dataProvider;
    }
}
