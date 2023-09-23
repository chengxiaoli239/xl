<?php

namespace backend\models\searchs\wechat;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\wechat\RobotUser as RobotUserModel;

/**
 * RobotUser represents the model behind the search form of `backend\models\wechat\RobotUser`.
 */
class RobotUser extends RobotUserModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'status', 'wechat_status', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['wcId', 'wId', 'uuid', 'desc', 'update_at'], 'safe'],
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
        $query = RobotUserModel::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]],
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
            'user_id' => $this->user_id,
            'status' => $this->status,
            'wechat_status' => $this->wechat_status,
            'expire_time' => $this->expire_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_at' => $this->update_at,
        ]);

        $query->andFilterWhere(['like', 'wcId', $this->wcId])
            ->andFilterWhere(['like', 'wId', $this->wId])
            ->andFilterWhere(['like', 'uuid', $this->uuid])
            ->andFilterWhere(['like', 'desc', $this->desc]);

        return $dataProvider;
    }
}
