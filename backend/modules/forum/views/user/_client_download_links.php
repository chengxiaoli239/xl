<?php

use yii\helpers\Html;

$clientDownloadCompact = $clientDownloadCompact ?? false;
$buttonClass = $clientDownloadCompact ? 'btn btn-primary btn-xs' : 'btn btn-primary';
$secondaryClass = $clientDownloadCompact ? 'btn btn-default btn-xs' : 'btn btn-default';
$userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
$isMac = (bool)preg_match('/Macintosh|Mac OS X/i', $userAgent);
$windowsUrl = trim((string)getenv('LUCKY5_CLIENT_WINDOWS_ZIP_URL'));
$windowsLabel = $windowsUrl !== '' ? '下载 Windows 客户端 (ZIP)' : '下载 Windows 客户端 (EXE)';
$hasWindows = $windowsUrl !== '' || trim((string)getenv('LUCKY5_CLIENT_DOWNLOAD_URL')) !== '';
$hasMac = trim((string)getenv('LUCKY5_CLIENT_MAC_ZIP_URL')) !== '';
?>
<?php if ($isMac): ?>
    <?php if ($hasMac): ?>
        <?= Html::a('下载 macOS 客户端 (ZIP)', ['/forum/user/download-client', 'platform' => 'macos'], [
            'class' => $buttonClass,
            'title' => '下载 macOS 客户端',
        ]) ?>
    <?php else: ?>
        <?= Html::tag('span', 'macOS 客户端暂未发布', [
            'class' => $secondaryClass . ' disabled',
            'title' => 'macOS 客户端尚未完成公开发布',
            'aria-disabled' => 'true',
        ]) ?>
    <?php endif; ?>
    <?php if ($hasWindows): ?>
        <?= Html::a($windowsLabel . '（备用）', ['/forum/user/download-client', 'platform' => 'windows'], [
            'class' => $secondaryClass,
            'title' => '下载 Windows 客户端备用版本',
        ]) ?>
    <?php endif; ?>
<?php else: ?>
    <?php if ($hasWindows): ?>
        <?= Html::a($windowsLabel, ['/forum/user/download-client', 'platform' => 'windows'], [
            'class' => $buttonClass,
            'title' => '下载 Windows 客户端',
        ]) ?>
    <?php endif; ?>
    <?php if ($hasMac): ?>
        <?= Html::a('下载 macOS 客户端 (ZIP)', ['/forum/user/download-client', 'platform' => 'macos'], [
            'class' => $secondaryClass,
            'title' => '下载 macOS 客户端',
        ]) ?>
    <?php endif; ?>
<?php endif; ?>
