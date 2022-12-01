<?php
/**
 * Description
 *
 *
 * Datetime: 2022-04-08 17:05
 */

namespace backend\service\tools;


use common\models\QueueLog;
use common\services\BaseService;
use common\tools\Common;

class QueueService extends BaseService
{
    public function getOptions()
    {
        $options = Common::getWithCache('queue:name_options', function() {
            $options = QueueLog::find()->select('name,job_class')
                ->groupBy('name')->asArray()->all();
            return $options;
        }, 600);
        $options = json_decode($options , true);

        array_multisort(array_column($options, 'name'), SORT_DESC, $options);

        $result['name'] = $options;

        return $result;
    }

    public function getList($params)
    {
        list($page, $pageSize, $offset) = Common::getPageParams($params);
        $data = $this->getListQuery($params)
            ->offset($offset)
            ->limit($pageSize)
            ->orderBy('id desc')
//            ->createCommand()->getRawSql();print_r($data);die;
            ->asArray()->all();
        $result['count'] = $this->getListCount($params);

        foreach ($data as $k => $v) {
            $v['status_text'] = QueueLog::getStatusText($v['status']);
            $v['last_push_time'] = date('Y-m-d H:i:s', $v['last_push_time']);
            $v['complete_time'] = date('Y-m-d H:i:s', $v['complete_time']);
            $data[$k] = $v;
        }
        $result['data'] = $data;

        return $result;
    }

    private function getListQuery($params)
    {
        $query = QueueLog::find();

        if (!empty($params['job_class'])) {
            $query->andWhere(['job_class_md5'=>md5($params['job_class'])]);
        }
        if (!empty($params['id'])) {
            $query->andWhere(['id'=>$params['id']]);
        }
        if (!empty($params['status'])) {
            $query->andWhere(['status'=>$params['status']]);
        }
        if (!empty($params['business_id'])) {
            $query->andWhere(['business_id'=>$params['business_id']]);
        }
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'name', $params['name']]);
        }
        if (!empty($params['params'])) {
            $query->andWhere('MATCH (params) AGAINST (:params IN BOOLEAN MODE)', ['params'=>$params['params']]);
        }
        if (!empty($params['is_delay'])) {
            if ($params['is_delay'] == 1) {
                $query->andWhere(['>', 'delay', 0]);
            } else {
                $query->andWhere(['=', 'delay', 0]);
            }
        }

        if (!empty($params['create_time'])) {
            $dateArr = explode('~', $params['create_time']);
            $startTime = trim($dateArr[0]);
            $endTIme = trim($dateArr[1]);
            $query->andWhere(['>=', 'create_time', $startTime]);
            $query->andWhere(['<=', 'create_time', $endTIme]);
        }

        return $query;
    }

    public function getListCount($params)
    {
        $count = $this->getListQuery($params)->count();

        return intval($count);
    }

    public function rePush($params)
    {
        $queues = $params['queues'];
        foreach ($queues as $queue) {
            push_queue($queue[1], ['id'=>$queue[0]], true);
        }
    }

    public function markComplete($params)
    {
        if (empty($params)) {
            return;
        }
        $updateData = ['complete_time'=>time(), 'remark'=>'人工标记', 'status'=>QueueLog::STATUS_SUCCESS];
        QueueLog::updateAll($updateData, ['id'=>$params['queue_ids']]);
    }

    public function status($params)
    {
        $systemQueueId = $params['system_queue_id'] ?? '';
        $message = '查询不到队列状态，此查询功能上线之前的队列查询不到';
        $result = ['message'=>$message];
        if (empty($systemQueueId)) {
            return $result;
        }

        $queue = \Yii::$app->queue;
        if ($queue->isWaiting($systemQueueId)) {
            $message = '当前状态: 等待消费';
        } elseif ($queue->isReserved($systemQueueId)) {
            $message = '当前状态: 处理中';
        } elseif ($queue->isDone($systemQueueId)) {
            $message = '当前状态: 处理完成';
        }
//        $message .= "<br/>状态:" . $queue->status($systemQueueId);

        return ['message'=>$message];
    }
}