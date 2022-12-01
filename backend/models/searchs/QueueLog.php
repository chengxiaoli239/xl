<?php

namespace backend\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\QueueLog as QueueLogModel;

/**
 * QueueLog represents the model behind the search form of `common\models\QueueLog`.
 */
class QueueLog extends QueueLogModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'count', 'status', 'time', 'last_push_time', 'complete_time', 'delay'], 'integer'],
            [['system_queue_id', 'business_id', 'params', 'remark', 'name', 'job_class', 'job_class_md5', 'type', 'create_time', 'update_time'], 'safe'],
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
        $query = QueueLogModel::find();

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
            'count' => $this->count,
            'status' => $this->status,
            'time' => $this->time,
            'last_push_time' => $this->last_push_time,
            'complete_time' => $this->complete_time,
            'delay' => $this->delay,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'system_queue_id', $this->system_queue_id])
            ->andFilterWhere(['like', 'business_id', $this->business_id])
            ->andFilterWhere(['like', 'params', $this->params])
            ->andFilterWhere(['like', 'remark', $this->remark])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'job_class', $this->job_class])
            ->andFilterWhere(['like', 'job_class_md5', $this->job_class_md5])
            ->andFilterWhere(['like', 'type', $this->type]);

        return $dataProvider;
    }
}
