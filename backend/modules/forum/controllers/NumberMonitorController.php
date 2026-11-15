<?php

namespace backend\modules\forum\controllers;

use backend\controllers\BaseController;
use backend\service\NumberMonitorService;
use common\service\lottery\CommonLotteryService;
use common\service\numberMonitor\NumberSchemeParser;
use Yii;
use yii\helpers\StringHelper;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class NumberMonitorController extends BaseController
{
    public function allowAction()
    {
        return ['index', 'data', 'scheme', 'history', 'preview', 'create', 'create-folder-scheme', 'toggle', 'clear', 'delete-scheme'];
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'data' => ['GET'],
                    'scheme' => ['GET'],
                    'history' => ['GET'],
                    'preview' => ['POST'],
                    'create' => ['POST'],
                    'create-folder-scheme' => ['POST'],
                    'toggle' => ['POST'],
                    'clear' => ['POST'],
                    'delete-scheme' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest || !$this->_user_id) {
            if ($action->id === 'data') {
                return true;
            }
            throw new ForbiddenHttpException('请先登录');
        }
        return parent::beforeAction($action);
    }

    public function actionIndex($lottery_type = CommonLotteryService::LOTTERY_TYPE_LUCKY5)
    {
        $lotteryType = (int)$lottery_type;
        if (!NumberMonitorService::supportedLotteryType($lotteryType)) {
            $lotteryType = CommonLotteryService::LOTTERY_TYPE_LUCKY5;
        }
        return $this->render('index', [
            'lotteryType' => $lotteryType,
            'lotteryTypes' => NumberMonitorService::lotteryTypeOptions(),
            'dashboard' => NumberMonitorService::dashboardData((int)$this->_user_id, $lotteryType),
            'csrfToken' => Yii::$app->request->csrfToken,
        ]);
    }

    public function actionData($lottery_type = CommonLotteryService::LOTTERY_TYPE_LUCKY5)
    {
        if (Yii::$app->user->isGuest || !$this->_user_id) {
            return $this->jsonResponse(['success' => false, 'message' => '登录状态已失效，请重新登录'], 401);
        }
        $lotteryType = (int)$lottery_type;
        if (!NumberMonitorService::supportedLotteryType($lotteryType)) {
            return $this->jsonResponse(['success' => false, 'message' => '彩种参数无效'], 400);
        }
        return $this->jsonResponse([
            'success' => true,
            'dashboard' => NumberMonitorService::dashboardData((int)$this->_user_id, $lotteryType),
        ]);
    }

    public function actionScheme($id = 0, $page = 1, $page_size = 400)
    {
        try {
            return $this->jsonResponse([
                'success' => true,
                'scheme' => NumberMonitorService::schemePatterns((int)$this->_user_id, (int)$id, (int)$page, (int)$page_size),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionHistory($id = 0, $before_id = 0)
    {
        try {
            return $this->jsonResponse([
                'success' => true,
                'history' => NumberMonitorService::schemeHistory(
                    (int)$this->_user_id,
                    (int)$id,
                    (int)$before_id
                ),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionPreview()
    {
        try {
            $content = $this->getUploadedContent();
            $summaryOnly = (string)Yii::$app->request->post('summary_only', '') === '1';
            $schemes = array_map(function ($scheme) use ($summaryOnly) {
                $result = [
                    'title' => $scheme['title'],
                    'pattern_count' => $scheme['pattern_count'],
                    'fixed_count' => $scheme['fixed_count'],
                ];
                if (!$summaryOnly) {
                    $result['patterns'] = $scheme['patterns'];
                }
                return $result;
            }, NumberSchemeParser::parse($content));
            return $this->jsonResponse(['success' => true, 'schemes' => $schemes]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionCreate()
    {
        $lotteryType = (int)Yii::$app->request->post('lottery_type', 0);
        try {
            $content = $this->getUploadedContent();
            $schemes = NumberSchemeParser::parse($content);
            $file = UploadedFile::getInstanceByName('file');
            $result = NumberMonitorService::createFile(
                (int)$this->_user_id,
                $lotteryType,
                $file ? $file->name : '号码方案.txt',
                $schemes
            );
            return $this->jsonResponse(['success' => true, 'file' => $result]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionCreateFolderScheme()
    {
        try {
            $content = $this->getUploadedContent();
            $schemes = NumberSchemeParser::parse($content);
            $file = UploadedFile::getInstanceByName('file');
            $title = trim(pathinfo($file->name, PATHINFO_FILENAME));
            foreach ($schemes as $index => &$scheme) {
                $suffix = count($schemes) > 1 ? ' · ' . ($index + 1) : '';
                $scheme['title'] = StringHelper::truncate(($title ?: '号码方案') . $suffix . ' · ' . $scheme['fixed_count'] . ' 定', 70);
            }
            unset($scheme);
            $result = NumberMonitorService::createFile(
                (int)$this->_user_id,
                (int)Yii::$app->request->post('lottery_type', 0),
                (string)Yii::$app->request->post('folder_name', '文件夹导入'),
                $schemes,
                true
            );
            return $this->jsonResponse(['success' => true, 'file' => $result]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionToggle()
    {
        try {
            $fileId = (int)Yii::$app->request->post('id', 0);
            $active = (string)Yii::$app->request->post('active', '') === '1';
            NumberMonitorService::setFileStatus((int)$this->_user_id, $fileId, $active);
            return $this->jsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionClear()
    {
        try {
            NumberMonitorService::deleteFile((int)$this->_user_id, (int)Yii::$app->request->post('id', 0));
            return $this->jsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function actionDeleteScheme()
    {
        try {
            NumberMonitorService::deleteScheme((int)$this->_user_id, (int)Yii::$app->request->post('id', 0));
            return $this->jsonResponse(['success' => true]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function getUploadedContent(): string
    {
        $file = UploadedFile::getInstanceByName('file');
        if (!$file || $file->error !== UPLOAD_ERR_OK || strtolower($file->extension) !== 'txt') {
            throw new \InvalidArgumentException('请选择有效的 TXT 文件');
        }
        if ($file->size > NumberSchemeParser::MAX_FILE_BYTES) {
            throw new \InvalidArgumentException('TXT 文件不能超过 2 MB');
        }
        $content = file_get_contents($file->tempName);
        if (!is_string($content)) {
            throw new \InvalidArgumentException('无法读取 TXT 文件');
        }
        return $content;
    }

    private function jsonResponse(array $data, int $status = 200): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->statusCode = $status;
        return $data;
    }

    private function errorResponse(\Throwable $exception): array
    {
        if ($exception instanceof \InvalidArgumentException) {
            return $this->jsonResponse(['success' => false, 'message' => $exception->getMessage()], 422);
        }
        Yii::error($exception, 'number-monitor');
        return $this->jsonResponse(['success' => false, 'message' => '操作失败，请稍后重试'], 500);
    }
}
