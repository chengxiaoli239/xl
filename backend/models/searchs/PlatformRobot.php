<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\open\PlatformRobot as PlatformRobotModel;

/**
 * PlatformRobot represents the model behind the search form of `backend\models\open\PlatformRobot`.
 */
class PlatformRobot extends PlatformRobotModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'platform_robot_id', 'platform_id', 'user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['name', 'token', 'remark', 'update_at'], 'safe'],
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
        $query = PlatformRobotModel::find();

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
            'platform_robot_id' => $this->platform_robot_id,
            'platform_id' => $this->platform_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'update_at' => $this->update_at,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'token', $this->token])
            ->andFilterWhere(['like', 'remark', $this->remark]);

        return $dataProvider;
    }
}