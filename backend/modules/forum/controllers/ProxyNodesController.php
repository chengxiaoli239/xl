<?php
namespace backend\modules\forum\controllers;

use backend\controllers\BaseController;
use backend\models\TzSystemsUsers;
use common\service\proxy\ProxyMihomoService;
use Yii;
use yii\web\UploadedFile;

class ProxyNodesController extends BaseController
{
    public function beforeAction($action)
    {
        // Legacy backend disables CSRF globally. Require it on this new management surface.
        Yii::$app->request->enableCsrfValidation = true;
        foreach(Yii::$app->log->targets as $target){ $target->logVars = []; }
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access'=>[
                'class'=>\yii\filters\AccessControl::className(),
                'rules'=>[['allow'=>true, 'roles'=>['@'], 'matchCallback'=>function(){
                    return (int)Yii::$app->user->id === 1 && Yii::$app->user->identity
                        && (int)Yii::$app->user->identity->status === 10;
                }]],
            ],
            'verbs'=>['class'=>\yii\filters\VerbFilter::className(), 'actions'=>[
                'preview'=>['POST'], 'import'=>['POST'], 'start'=>['POST'], 'test'=>['POST'],
            ]],
        ];
    }

    public function actionIndex($id = null)
    {
        $account = $id ? TzSystemsUsers::findOne((int)$id) : null;
        if($id && !$account){
            throw new \yii\web\NotFoundHttpException('账号不存在');
        }
        $nodes = [];
        $error = '';
        try { $nodes = ProxyMihomoService::manager('list')['nodes'] ?? []; }
        catch(\RuntimeException $e){ $error = $e->getMessage(); }
        return $this->render('index', compact('account', 'nodes', 'error'));
    }

    public function actionPreview()
    {
        return $this->runNodeAction(function(){
            $url = trim((string)Yii::$app->request->post('url', ''));
            $file = UploadedFile::getInstanceByName('yaml');
            if(($url !== '') === ($file !== null)){
                throw new \RuntimeException('请选择订阅链接或 YAML 文件其中一种方式');
            }
            if($file){
                if($file->error !== UPLOAD_ERR_OK || $file->size > 1048576 || !in_array(strtolower($file->extension), ['yaml', 'yml'], true)){
                    throw new \RuntimeException('请上传不超过 1 MB 的 YAML 文件');
                }
                $content = file_get_contents($file->tempName);
                if($content === false){ throw new \RuntimeException('文件读取失败'); }
                return ProxyMihomoService::manager('preview', ['content'=>$content]);
            }
            return ProxyMihomoService::manager('preview', ['url'=>$url]);
        });
    }

    public function actionImport()
    {
        return $this->runNodeAction(function(){
            $indexes = Yii::$app->request->post('indexes', []);
            if(!is_array($indexes) || count($indexes) > 16){
                throw new \RuntimeException('最多选择 16 个节点');
            }
            foreach($indexes as &$index){
                $index = filter_var($index, FILTER_VALIDATE_INT);
                if($index === false){ throw new \RuntimeException('节点选择无效'); }
            }
            unset($index);
            return ProxyMihomoService::manager('import', [
                'ticket'=>Yii::$app->request->post('ticket', ''), 'indexes'=>$indexes,
            ]);
        });
    }

    public function actionStart()
    {
        return $this->runNodeAction(function(){
            $port = filter_var(Yii::$app->request->post('port'), FILTER_VALIDATE_INT);
            return ProxyMihomoService::manager('start', ['port'=>$port]);
        });
    }

    private function runNodeAction(callable $action): array
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try { return ['status'=>200, 'data'=>$action()]; }
        catch(\RuntimeException $e){ return ['status'=>400, 'msg'=>$e->getMessage()]; }
    }

    public function actionTest()
    {
        return $this->runNodeAction(function(){
            $port = filter_var(Yii::$app->request->post('port'), FILTER_VALIDATE_INT);
            $ready = false;
            foreach(ProxyMihomoService::manager('list')['nodes'] ?? [] as $node){
                if($node['port'] === $port && !empty($node['running'])){ $ready = true; }
            }
            if(!$ready){ throw new \RuntimeException('请先启动节点'); }
            $ch = curl_init('https://www.cloudflare.com/cdn-cgi/trace');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>12,
                CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2]);
            ProxyMihomoService::setProxy($ch, $port);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if($status !== 200 || !is_string($body) || strlen($body) > 16384){
                throw new \RuntimeException('节点 HTTPS 出口测试失败，账号配置未改变');
            }
            preg_match('/^ip=([^\r\n]+)$/m', $body, $ip);
            preg_match('/^loc=([A-Z]{2})$/m', $body, $location);
            if(empty($ip[1]) || !filter_var($ip[1], FILTER_VALIDATE_IP)){
                throw new \RuntimeException('出口查询响应无效');
            }
            return ['ip'=>$ip[1], 'country'=>$location[1] ?? '未知'];
        });
    }
}
