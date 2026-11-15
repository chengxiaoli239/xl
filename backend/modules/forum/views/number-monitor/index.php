<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $lotteryType int */
/* @var $lotteryTypes array */
/* @var $dashboard array */
/* @var $csrfToken string */

$this->title = '号码监控';
$stateJson = Json::htmlEncode($dashboard);
?>
<section class="number-monitor wrapper site-min-height"
         id="number-monitor"
         data-lottery-type="<?= (int)$lotteryType ?>"
         data-data-url="<?= Html::encode(Url::to(['/forum/number-monitor/data'])) ?>"
         data-scheme-url="<?= Html::encode(Url::to(['/forum/number-monitor/scheme'])) ?>"
         data-history-url="<?= Html::encode(Url::to(['/forum/number-monitor/history'])) ?>"
         data-preview-url="<?= Html::encode(Url::to(['/forum/number-monitor/preview'])) ?>"
         data-create-url="<?= Html::encode(Url::to(['/forum/number-monitor/create'])) ?>"
         data-toggle-url="<?= Html::encode(Url::to(['/forum/number-monitor/toggle'])) ?>"
         data-clear-url="<?= Html::encode(Url::to(['/forum/number-monitor/clear'])) ?>"
         data-csrf="<?= Html::encode($csrfToken) ?>">
    <header class="nm-heading">
        <div>
            <h1>号码监控</h1>
            <div class="nm-live-line"><span class="nm-live-dot"></span><span id="nm-refresh-label">同步最新开奖</span><span class="nm-separator">·</span><span id="nm-current-issue">等待开奖</span></div>
        </div>
        <label class="nm-lottery-select">
            <span>彩种</span>
            <select id="nm-lottery-type" aria-label="选择彩种">
                <?php foreach ($lotteryTypes as $type => $label): ?>
                    <option value="<?= (int)$type ?>" <?= (int)$type === (int)$lotteryType ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </header>

    <div class="nm-workspace">
        <main class="nm-main">
            <section class="nm-import-panel" aria-label="导入号码方案">
                <div class="nm-import-head">
                    <div>
                        <h2>添加号码文件</h2>
                        <span class="nm-muted">TXT · 空行分隔 · 最多 15 套，每套 10,000 组（保留重复号码）</span>
                    </div>
                    <div class="nm-mobile-draws-action">
                        <button type="button" id="nm-mobile-draws-toggle" aria-label="查看最近开奖号" aria-controls="nm-draws-panel" aria-expanded="false">
                            <strong id="nm-floating-draw-code">----</strong><small>开奖</small>
                        </button>
                    </div>
                    <div class="nm-import-actions">
                        <button type="button" id="nm-preview" class="nm-button nm-button-secondary" disabled>预览号码</button>
                        <button type="button" id="nm-create" class="nm-button nm-button-primary" disabled>开始监控</button>
                    </div>
                </div>
                <button type="button" class="nm-dropzone" id="nm-dropzone" aria-label="选择或拖入 TXT 文件">
                    <span class="nm-drop-icon"><i class="fa fa-file-text-o" aria-hidden="true"></i></span>
                    <span class="nm-drop-copy"><strong id="nm-file-name">拖入 TXT 文件</strong><small id="nm-file-meta">单文件最多 2 MB</small></span>
                    <span class="nm-drop-browse">选择文件</span>
                </button>
                <input id="nm-file-input" type="file" accept=".txt,text/plain" hidden>
                <div class="nm-preview" id="nm-preview-list" hidden></div>
                <div id="nm-feedback" class="nm-feedback" role="status" aria-live="polite"></div>
            </section>

            <div class="nm-board-heading">
                <div><h2>监控方案</h2><span id="nm-scheme-count" class="nm-count">0 套</span><span id="nm-monitor-period" class="nm-monitor-period">最新期号 --</span></div>
                <span id="nm-sync-state" class="nm-sync-state">实时同步中</span>
            </div>
            <div class="nm-board-viewport">
                <div class="nm-board" id="nm-board" aria-live="polite"></div>
            </div>
        </main>

        <aside class="nm-draws" id="nm-draws-panel" aria-label="最近开奖号码">
            <div class="nm-draws-heading"><div><h2>最近开奖</h2><span>近 30 期</span></div><i class="fa fa-history" aria-hidden="true"></i><button type="button" id="nm-mobile-draws-close" aria-label="关闭最近开奖"><i class="fa fa-times" aria-hidden="true"></i></button></div>
            <div id="nm-draw-list" class="nm-draw-list"></div>
        </aside>
    </div>
    <div class="nm-draws-backdrop" id="nm-draws-backdrop" hidden></div>
    <div class="nm-scheme-dialog" id="nm-scheme-dialog" hidden>
        <div class="nm-scheme-backdrop" data-close-scheme="1"></div>
        <section class="nm-scheme-panel" role="dialog" aria-modal="true" aria-labelledby="nm-scheme-title" tabindex="-1">
            <header class="nm-scheme-dialog-head">
                <div><h2 id="nm-scheme-title">方案号码</h2><p id="nm-scheme-summary"></p></div>
                <button type="button" id="nm-scheme-close" aria-label="关闭号码列表"><i class="fa fa-times" aria-hidden="true"></i></button>
            </header>
            <div class="nm-scheme-patterns" id="nm-scheme-patterns" aria-live="polite"></div>
            <footer class="nm-scheme-dialog-foot">
                <button type="button" id="nm-scheme-prev">上一页</button>
                <span id="nm-scheme-page"></span>
                <button type="button" id="nm-scheme-next">下一页</button>
            </footer>
        </section>
    </div>
</section>

<style>
.number-monitor{--nm-ink:#192b37;--nm-muted:#73838a;--nm-line:#dce4e6;--nm-paper:#fff;--nm-ground:#f2f6f5;--nm-teal:#168982;--nm-hit:#17835b;--nm-miss:#bb3838;color:var(--nm-ink);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif;padding:22px 20px 36px;background:var(--nm-ground);min-height:calc(100vh - 74px)}
.number-monitor *{box-sizing:border-box;letter-spacing:0}
.nm-heading,.nm-heading>div,.nm-live-line,.nm-lottery-select,.nm-import-head,.nm-import-actions,.nm-dropzone,.nm-board-heading,.nm-board-heading>div,.nm-draws-heading,.nm-draws-heading>div,.nm-file-actions,.nm-file-title,.nm-scheme-head{display:flex;align-items:center}
.nm-heading{justify-content:space-between;gap:20px;margin:0 0 18px}.nm-heading>div{display:block}.nm-eyebrow{margin:0 0 5px;color:var(--nm-teal);font-size:11px;font-weight:700;text-transform:uppercase}.nm-heading h1{margin:0;color:var(--nm-ink);font-size:25px;font-weight:700;line-height:1.25}.nm-live-line{gap:8px;margin-top:8px;color:var(--nm-muted);font-size:12px}.nm-live-dot{width:8px;height:8px;border-radius:50%;background:#2c9b76;box-shadow:0 0 0 3px rgba(44,155,118,.12)}.nm-separator{color:#a5b0b4}.nm-lottery-select{gap:10px;color:var(--nm-muted);font-size:12px}.nm-lottery-select select{min-width:136px;height:36px;border:1px solid var(--nm-line);border-radius:6px;background:var(--nm-paper);color:var(--nm-ink);padding:0 30px 0 10px;font-size:13px}
.nm-workspace{display:grid;grid-template-columns:minmax(0,1fr) 270px;align-items:start;gap:16px}.nm-main{min-width:0}.nm-import-panel,.nm-draws{border:1px solid var(--nm-line);border-radius:8px;background:var(--nm-paper)}.nm-import-panel{padding:16px}.nm-import-head{justify-content:space-between;gap:16px;margin-bottom:12px}.nm-import-head h2,.nm-board-heading h2,.nm-draws-heading h2{margin:0;font-size:15px;font-weight:700;color:var(--nm-ink)}.nm-muted,.nm-draws-heading span{color:var(--nm-muted);font-size:11px}.nm-import-head .nm-muted{display:block;margin-top:4px}.nm-import-actions{gap:8px}.nm-button{height:34px;border:1px solid transparent;border-radius:5px;padding:0 13px;font-size:12px;font-weight:650;cursor:pointer;transition:background .15s,border-color .15s}.nm-button:disabled{cursor:not-allowed;opacity:.45}.nm-button-primary{background:var(--nm-teal);color:#fff}.nm-button-primary:hover:not(:disabled){background:#11736e}.nm-button-secondary{border-color:var(--nm-line);background:#fff;color:var(--nm-ink)}.nm-button-secondary:hover:not(:disabled){background:#f5f8f8}
.nm-dropzone{width:100%;min-height:62px;gap:12px;border:1px dashed #b9cbca;border-radius:6px;background:#f8fbfa;padding:9px 12px;text-align:left;cursor:pointer}.nm-dropzone.dragging{border-color:var(--nm-teal);background:#edf8f6}.nm-drop-icon{display:grid;flex:0 0 38px;width:38px;height:38px;place-items:center;border-radius:6px;background:#e7f3f1;color:var(--nm-teal);font-size:16px}.nm-drop-copy{display:block;min-width:0;flex:1}.nm-drop-copy strong,.nm-drop-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.nm-drop-copy strong{font-size:12px;color:var(--nm-ink)}.nm-drop-copy small{margin-top:4px;color:var(--nm-muted);font-size:11px}.nm-drop-browse{border-left:1px solid var(--nm-line);padding:5px 3px 5px 12px;color:var(--nm-teal);font-size:12px;font-weight:650;white-space:nowrap}.nm-preview{max-height:180px;overflow:auto;margin-top:10px;border-top:1px solid var(--nm-line);padding-top:8px}.nm-preview-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:7px 2px;border-bottom:1px solid #edf1f2;font-size:11px}.nm-preview-row strong{font-weight:650}.nm-preview-row span{overflow:hidden;color:var(--nm-muted);text-overflow:ellipsis;white-space:nowrap}.nm-preview-row em{color:var(--nm-teal);font-style:normal;white-space:nowrap}.nm-feedback{min-height:0;color:var(--nm-muted);font-size:11px}.nm-feedback:not(:empty){padding-top:8px}.nm-feedback.error{color:#b43636}.nm-feedback.success{color:#167a61}
.nm-board-heading{justify-content:space-between;margin:20px 2px 10px}.nm-board-heading>div{gap:9px}.nm-count{color:var(--nm-muted);font-size:11px}.nm-sync-state{color:var(--nm-muted);font-size:11px}.nm-sync-state.error{color:#b43636}.nm-board-viewport{overflow-x:auto;padding-bottom:10px;scrollbar-color:#bdcbcd transparent;scrollbar-width:thin}.nm-board{display:flex;align-items:stretch;gap:12px;min-height:300px}.nm-empty{display:grid;width:100%;min-height:240px;place-items:center;border:1px dashed #cbd6d7;border-radius:8px;color:var(--nm-muted);font-size:12px}.nm-empty strong{display:block;margin-bottom:6px;color:#50616a;font-size:13px}.nm-file-column{flex:0 0 252px;width:252px;min-height:292px;overflow:hidden;border:1px solid var(--nm-line);border-radius:8px;background:var(--nm-paper)}.nm-file-top{display:flex;align-items:flex-start;justify-content:space-between;gap:9px;padding:12px;border-bottom:1px solid var(--nm-line);background:#fafcfc}.nm-file-title{min-width:0;gap:8px}.nm-file-mark{display:grid;flex:0 0 28px;width:28px;height:28px;place-items:center;border-radius:5px;background:#e8f3f1;color:var(--nm-teal);font-size:12px}.nm-file-title strong{display:block;max-width:138px;overflow:hidden;color:var(--nm-ink);font-size:12px;text-overflow:ellipsis;white-space:nowrap}.nm-file-title small{display:block;margin-top:3px;color:var(--nm-muted);font-size:10px}.nm-file-actions{gap:5px}.nm-file-actions button{height:27px;border:1px solid var(--nm-line);border-radius:4px;background:white;color:#45555d;padding:0 7px;font-size:10px;cursor:pointer}.nm-file-actions button:hover{border-color:#9eaeaf;background:#f5f8f8}.nm-file-actions button[data-action="clear"]{color:#a43434}.nm-file-paused{padding:5px 12px;border-bottom:1px solid var(--nm-line);background:#f5f6f6;color:var(--nm-muted);font-size:10px}.nm-scheme-list{display:grid;align-content:start;gap:8px;padding:9px}.nm-scheme{border:1px solid #e6ebec;border-radius:6px;background:#fff;padding:9px}.nm-scheme-head{justify-content:space-between;gap:7px}.nm-scheme-head strong{overflow:hidden;color:var(--nm-ink);font-size:11px;font-weight:650;text-overflow:ellipsis;white-space:nowrap}.nm-streak{flex:0 0 auto;min-width:66px;padding:5px 7px;border-radius:4px;color:#fff;text-align:center;font-size:11px;font-weight:700;line-height:1.1}.nm-streak.hit{background:var(--nm-hit)}.nm-streak.miss{background:var(--nm-miss)}.nm-streak.none{background:#e9eff0;color:#617179}.nm-scheme-sample{overflow:hidden;margin-top:7px;color:var(--nm-muted);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:10px;text-overflow:ellipsis;white-space:nowrap}.nm-scheme-meta{margin-top:5px;color:#89969a;font-size:10px}.nm-file-column.paused .nm-scheme{opacity:.66}
.nm-draws{position:sticky;top:10px;overflow:hidden}.nm-draws-heading{justify-content:space-between;padding:14px;border-bottom:1px solid var(--nm-line)}.nm-draws-heading span{display:block;margin-top:4px}.nm-draws-heading>i{color:#829297;font-size:15px}.nm-draw-list{max-height:calc(100vh - 240px);overflow-y:auto}.nm-draw-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;padding:9px 14px;border-bottom:1px solid #edf1f2}.nm-draw-row:last-child{border-bottom:0}.nm-draw-row.latest{background:#f0f7f6}.nm-draw-issue{display:block;color:var(--nm-ink);font-size:11px;font-weight:650}.nm-draw-time{display:block;margin-top:3px;color:#91a0a3;font-size:10px}.nm-draw-code{color:#8f3434;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:15px;font-weight:750;letter-spacing:1px!important}.nm-draw-empty{padding:28px 12px;color:var(--nm-muted);font-size:11px;text-align:center}
@media(max-width:1050px){.nm-workspace{grid-template-columns:minmax(0,1fr) 230px;gap:12px}.nm-file-column{flex-basis:230px;width:230px}}
@media(max-width:760px){.number-monitor{padding:16px 12px 28px}.nm-heading{align-items:flex-start}.nm-heading h1{font-size:21px}.nm-lottery-select{align-items:flex-start;flex-direction:column;gap:4px}.nm-lottery-select select{min-width:116px}.nm-workspace{grid-template-columns:minmax(0,1fr)}.nm-import-panel{padding:12px}.nm-import-head{align-items:flex-start;flex-direction:column}.nm-import-actions{width:100%}.nm-import-actions .nm-button{flex:1}.nm-draws{position:static}.nm-draw-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:270px}.nm-draw-row{padding:8px 10px}.nm-file-column{flex-basis:232px;width:232px}.nm-board{min-height:250px}}
.nm-sync-state.pending{color:#a77a1e}
.number-monitor{--nm-ink:#142038;--nm-muted:#728198;--nm-line:#dce7f0;--nm-teal:#315efb;background:radial-gradient(ellipse at 4% 0%,#dceef8 0,transparent 44%),radial-gradient(ellipse at 95% 8%,#e9eaf8 0,transparent 42%),#f2f7fb;border-radius:18px}
.nm-heading{padding:20px 22px;border:1px solid var(--nm-line);border-radius:16px;background:linear-gradient(120deg,#fff,#f4f8fc);box-shadow:0 12px 32px rgba(54,91,128,.06)}
.nm-import-panel,.nm-draws,.nm-file-column{border-radius:14px;box-shadow:0 10px 28px rgba(54,91,128,.05)}
.nm-file-top{display:grid;grid-template-columns:minmax(0,1fr);gap:10px}.nm-file-title strong{max-width:185px}.nm-file-actions{justify-content:flex-end}.nm-file-actions button{min-width:55px;border-radius:7px}
.nm-dropzone{border-radius:10px;background:#f8fbff}.nm-button{border-radius:8px}.nm-scheme{border-radius:9px}.nm-streak{border-radius:7px}
.number-monitor button:focus-visible,.number-monitor select:focus-visible{outline:2px solid var(--nm-teal);outline-offset:2px}
@media(max-width:760px){.number-monitor{border-radius:0}.nm-heading{padding:16px}.nm-file-title strong{max-width:170px}}
@media(prefers-reduced-motion:reduce){.nm-button{transition:none}}
#nm-file-input{display:none!important}
.nm-draw-row{gap:6px;padding:9px 12px}
.nm-draw-issue{overflow:hidden;font-size:13px;text-overflow:ellipsis;white-space:nowrap}
.nm-draw-code{font-size:17px;letter-spacing:.14em!important;padding-left:.14em}
@media(max-width:760px){.nm-draw-row{gap:5px;padding:8px}.nm-draw-issue{font-size:12px}.nm-draw-code{font-size:16px;letter-spacing:.1em!important;padding-left:.1em}}
@media(max-width:760px){.nm-live-line{flex-wrap:wrap;gap:4px 6px}.nm-live-line>span{flex:0 0 auto;white-space:nowrap}}
.nm-scheme{display:block;width:100%;text-align:left;font:inherit;cursor:pointer}.nm-scheme:hover{border-color:var(--nm-teal);box-shadow:0 4px 12px rgba(54,91,128,.1)}.nm-scheme:focus-visible{outline:2px solid var(--nm-teal);outline-offset:2px}.nm-scheme-meta{display:flex;justify-content:space-between;gap:6px}.nm-scheme-open-hint{flex:none;color:var(--nm-teal)}
.nm-scheme-dialog{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;padding:16px}.nm-scheme-dialog[hidden]{display:none}.nm-scheme-backdrop{position:absolute;inset:0;background:rgba(12,28,45,.62)}.nm-scheme-panel{position:relative;display:flex;flex-direction:column;width:min(680px,100%);max-height:min(80vh,760px);overflow:hidden;border:1px solid var(--nm-line);border-radius:16px;background:var(--nm-paper);color:var(--nm-ink);box-shadow:0 22px 64px rgba(12,28,45,.2)}.nm-scheme-dialog-head,.nm-scheme-dialog-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 18px}.nm-scheme-dialog-head{border-bottom:1px solid var(--nm-line)}.nm-scheme-dialog-head h2{margin:0;font-size:18px;color:var(--nm-ink)}.nm-scheme-dialog-head p{margin:5px 0 0;color:var(--nm-muted);font-size:12px}.nm-scheme-dialog-head button,.nm-scheme-dialog-foot button{border:1px solid var(--nm-line);border-radius:8px;background:var(--nm-paper);color:var(--nm-ink);cursor:pointer}.nm-scheme-dialog-head button{width:34px;height:34px}.nm-scheme-dialog-foot{border-top:1px solid var(--nm-line);font-size:12px;color:var(--nm-muted)}.nm-scheme-dialog-foot button{min-height:36px;padding:0 12px}.nm-scheme-dialog-foot button:disabled{opacity:.45;cursor:not-allowed}.nm-scheme-patterns{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));align-content:start;gap:8px;min-height:120px;overflow-y:auto;padding:16px 18px}.nm-scheme-patterns .nm-pattern{display:flex;justify-content:space-between;gap:5px;border:1px solid var(--nm-line);border-radius:7px;background:var(--nm-ground);padding:7px 9px;font:600 13px ui-monospace,SFMono-Regular,Menlo,monospace}.nm-pattern-count{color:var(--nm-muted);font-size:11px}.nm-scheme-patterns .nm-scheme-message{grid-column:1/-1;color:var(--nm-muted);font-size:13px}
@media(max-width:760px){.nm-scheme-panel{max-height:85vh}.nm-scheme-patterns{grid-template-columns:repeat(auto-fill,minmax(86px,1fr));padding:12px}.nm-scheme-dialog-head,.nm-scheme-dialog-foot{padding:12px}}
.nm-dialog-open{overflow:hidden}
.number-monitor{box-sizing:border-box}
@media(max-width:760px){.nm-heading{flex-direction:column;align-items:stretch}.nm-lottery-select{flex-direction:row;align-items:center}.nm-import-head{align-items:stretch}}
.nm-preview-row{width:100%;border:0;border-bottom:1px solid var(--nm-line);background:transparent;color:var(--nm-ink);text-align:left;cursor:pointer}
.nm-preview-row:hover,.nm-preview-row:focus-visible{background:var(--nm-ground)}
.nm-preview-row em{align-self:center}
.nm-scheme-panel{width:min(800px,100%);max-height:min(84vh,800px)}
.nm-scheme-patterns{grid-template-columns:repeat(auto-fill,minmax(78px,1fr));gap:6px;padding:12px 14px}
.nm-scheme-patterns .nm-pattern{min-width:0;padding:6px 7px;font-size:12px}
.nm-scheme-head strong{min-width:0;flex:1}
.nm-scheme-sample{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;max-height:3em;line-height:1.5;overflow:hidden;white-space:normal;overflow-wrap:anywhere;text-overflow:ellipsis}
.nm-scheme-meta>span:first-child{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nm-import-head>.nm-mobile-draws-action,#nm-mobile-draws-close{display:none}
@media(max-width:760px){
    .nm-import-head{display:grid;grid-template-columns:minmax(0,1fr) 62px;align-items:start;gap:10px}
    .nm-import-head>.nm-mobile-draws-action{display:block;grid-column:2;grid-row:1;justify-self:end}
    .nm-import-actions{grid-column:1/-1}
    .nm-mobile-draws-action button,#nm-mobile-draws-close{display:grid;place-content:center;gap:2px;width:62px;height:62px;border:1px solid #cbd9ef;border-radius:50%;background:#fff;color:#315efb;box-shadow:0 8px 24px rgba(32,61,105,.2);cursor:pointer}
    .nm-mobile-draws-action button strong{font:700 14px ui-monospace,SFMono-Regular,Menlo,monospace;line-height:1}
    .nm-mobile-draws-action button small{font-size:9px;line-height:1}
    .nm-file-column{flex:0 0 min(300px,calc(100vw - 40px));width:min(300px,calc(100vw - 40px))}
    .nm-scheme-list{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px;padding:7px}
    .nm-scheme{min-width:0;padding:7px}
    .nm-scheme-head{gap:4px;align-items:flex-start}
    .nm-scheme-title{min-width:0;font-size:10px}
    .nm-streak-button{min-width:0;padding:4px 5px;font-size:10px}
    .nm-scheme-sample{font-size:9px}
    .nm-scheme-meta{font-size:9px}
    .nm-scheme-patterns{grid-template-columns:repeat(auto-fill,minmax(68px,1fr));gap:5px;padding:10px}
    .nm-scheme-patterns .nm-pattern{padding:6px;font-size:11px}
    .nm-draws{display:none}
    .number-monitor.nm-mobile-draws-open .nm-draws{position:fixed;top:50%;left:50%;z-index:2001;display:block;width:min(420px,calc(100vw - 28px));max-height:85vh;max-height:85dvh;transform:translate(-50%,-50%);box-shadow:0 22px 64px rgba(12,28,45,.22)}
    .nm-draws-heading>i{display:none}
    .nm-draws-backdrop:not([hidden]){position:fixed;inset:0;z-index:2000;background:rgba(12,28,45,.62)}
    .nm-draw-list{display:block;max-height:70vh;max-height:70dvh;overflow-y:auto}
    .nm-draw-row{padding:10px 14px}
}
.nm-scheme-panel{display:grid;grid-template-rows:auto minmax(0,1fr) auto;height:min(84vh,800px);max-height:calc(100vh - 32px)}
.nm-scheme-patterns{min-height:0}
.nm-scheme-patterns.nm-history-list{display:block}
.nm-history-row{display:grid;grid-template-columns:max-content max-content max-content;justify-content:start;align-items:center;gap:5px;min-height:42px;border-bottom:1px solid var(--nm-line);padding:6px 2px}
.nm-history-issue{overflow:hidden;color:var(--nm-ink);font-size:14px;font-weight:600;text-overflow:ellipsis;white-space:nowrap}
.nm-history-code{color:var(--nm-muted);font:600 14px ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.08em!important}
.nm-history-status{min-width:48px;border-radius:5px;padding:4px 6px;text-align:center;font-size:13px;font-weight:700}
.nm-history-status.hit{background:#e5f5ee;color:#15774e}
.nm-history-status.miss{background:#fdecec;color:#ad3434}
.nm-history-status.none{background:#eef1f4;color:#687581}
.nm-scheme-panel .nm-scheme-dialog-foot{min-height:62px}
.nm-scheme-title,.nm-scheme-view,.nm-streak-button{border:0;background:transparent;color:inherit;cursor:pointer;font:inherit}
.nm-scheme-title{min-width:0;flex:1;padding:0;text-align:left;color:var(--nm-ink);font-size:11px;font-weight:650}
.nm-streak-button{flex:none;padding:0}
.nm-streak-button .nm-streak{display:block;min-width:62px}
.nm-scheme-view{display:block;width:100%;min-width:0;padding:0;text-align:left}
.nm-scheme-title:focus-visible,.nm-scheme-view:focus-visible,.nm-streak-button:focus-visible{outline:2px solid var(--nm-teal);outline-offset:2px}
.nm-history-row:last-child{border-bottom:0}
@media(max-width:760px){
    .nm-file-column{flex-basis:150px;width:150px;min-height:0}
    .nm-file-top{gap:7px;padding:8px}
    .nm-file-title{gap:6px}
    .nm-file-title strong{max-width:100px}
    .nm-file-actions{gap:4px}
    .nm-file-actions button{min-width:0;height:25px;padding:0 6px;font-size:9px}
    .nm-scheme-list{grid-template-columns:minmax(0,1fr);gap:5px;padding:6px}
    .nm-scheme{padding:6px}
    .nm-scheme-head{gap:3px}
    .nm-scheme-title{font-size:10px}
    .nm-streak-button .nm-streak{min-width:50px;font-size:10px}
    .nm-scheme-sample{margin-top:5px;font-size:9px}
    .nm-scheme-meta{margin-top:4px;font-size:9px}
}
@media(max-width:760px){.nm-scheme-panel{height:min(84dvh,800px);max-height:calc(100dvh - 32px)}}
.number-monitor{padding:10px 12px 18px}
.nm-heading{gap:8px;margin-bottom:8px;padding:8px 12px;border-radius:8px}
.nm-heading h1{font-size:20px}
.nm-live-line{gap:5px;margin-top:2px;font-size:11px}
.nm-lottery-select select{min-width:110px;height:30px}
.nm-workspace{gap:10px}
.nm-import-panel{padding:8px 10px;border-radius:8px}
.nm-import-head{gap:8px;margin-bottom:5px}
.nm-import-head .nm-muted{margin-top:2px;font-size:10px}
.nm-import-actions{gap:6px}
.nm-button{height:30px;padding:0 9px}
.nm-dropzone{min-height:38px;gap:8px;padding:4px 8px}
.nm-drop-icon{flex-basis:25px;width:25px;height:25px;font-size:12px}
.nm-drop-copy small{margin-top:1px;font-size:10px}
.nm-drop-browse{font-size:11px}
.nm-preview{max-height:160px;margin-top:6px;padding-top:4px}
.nm-preview-row{padding:5px 2px;font-size:12px}
.nm-board-heading{margin:9px 1px 5px}
.nm-board-viewport{padding-bottom:4px}
.nm-board{width:100%;gap:5px;min-height:0}
.nm-file-column{flex:0 0 max(98px,calc(10% - 4.5px));width:max(98px,calc(10% - 4.5px));min-height:0;border-radius:6px}
.nm-file-top{display:grid;grid-template-columns:minmax(0,1fr);gap:2px;padding:4px}
.nm-file-title{min-width:0;gap:0}
.nm-file-mark{display:none}
.nm-file-title>div{display:flex;align-items:center;gap:3px;min-width:0;width:100%}
.nm-file-title strong{flex:1;min-width:0;max-width:none;font-size:12px}
.nm-file-title small{flex:none;margin:0;font-size:9px;white-space:nowrap}
.nm-file-actions{justify-content:flex-end;gap:3px}
.nm-file-actions button{display:grid;place-items:center;min-width:24px;width:24px;height:22px;padding:0;border-radius:4px;font-size:11px}
.nm-file-action-label{display:none}
.nm-file-paused{padding:3px 4px;font-size:10px}
.nm-scheme-list{display:block;padding:2px 3px}
.nm-scheme{display:flex;align-items:center;justify-content:space-between;gap:1px;min-width:0;min-height:25px;padding:2px 0;border:0;border-bottom:1px solid var(--nm-line);border-radius:0;box-shadow:none}
.nm-scheme:last-child{border-bottom:0}
.nm-scheme:hover{border-color:var(--nm-line);background:var(--nm-ground);box-shadow:none}
.nm-scheme-view{flex:1;width:auto;min-width:0;overflow:hidden;margin:0;padding:2px 0;text-overflow:clip;white-space:nowrap;font-size:12px;font-weight:650;font-variant-numeric:tabular-nums;line-height:18px}
.nm-streak-button{flex:none;padding:0}
.nm-streak-button .nm-streak{display:inline-flex;align-items:center;justify-content:center;gap:1px;min-width:25px;padding:2px 1px;border-radius:4px;font-size:11px;line-height:1.1;white-space:nowrap}
.nm-streak .fa{font-size:10px}
.nm-file-column.paused .nm-scheme{opacity:.72}
.nm-monitor-period{color:var(--nm-muted);font-size:10px;white-space:nowrap}
@media(max-width:1600px){
    .nm-workspace{display:block}
    .nm-import-head>.nm-mobile-draws-action{display:block;margin-left:auto}
    .nm-mobile-draws-action button{display:grid;place-content:center;gap:1px;width:42px;height:42px;border:1px solid #cbd9ef;border-radius:50%;background:#fff;color:#315efb;box-shadow:0 4px 12px rgba(32,61,105,.14);cursor:pointer}
    .nm-mobile-draws-action button strong{font:700 12px ui-monospace,SFMono-Regular,Menlo,monospace;line-height:1}
    .nm-mobile-draws-action button small{font-size:9px;line-height:1}
    .nm-draws{display:none}
    .number-monitor.nm-mobile-draws-open .nm-draws{position:fixed;top:50%;left:50%;z-index:2001;display:block;width:min(420px,calc(100vw - 28px));max-height:85dvh;transform:translate(-50%,-50%);box-shadow:0 22px 64px rgba(12,28,45,.22)}
    .nm-draws-heading>i{display:none}
    #nm-mobile-draws-close{display:grid;place-items:center;width:30px;height:30px;border:1px solid var(--nm-line);border-radius:5px;background:#fff;color:var(--nm-ink);cursor:pointer}
    .nm-draws-backdrop:not([hidden]){position:fixed;inset:0;z-index:2000;background:rgba(12,28,45,.62)}
    .nm-draw-list{display:block;max-height:70dvh;overflow-y:auto}
}
@media(max-width:760px){
    .number-monitor{padding:8px 8px 16px;border-radius:0}
    .nm-heading{flex-direction:row;align-items:center;padding:8px}
    .nm-heading h1{font-size:18px}
    .nm-live-line{flex-wrap:wrap;font-size:10px}
    .nm-lottery-select{flex-direction:row;align-items:center}
    .nm-lottery-select span{display:none}
    .nm-lottery-select select{min-width:92px;max-width:100px;height:30px;font-size:11px}
    .nm-import-panel{padding:7px}
    .nm-import-head{display:grid;grid-template-columns:minmax(0,1fr) 46px;gap:5px;align-items:start}
    .nm-import-head>.nm-mobile-draws-action{grid-column:2;grid-row:1}
    .nm-import-actions{grid-column:1/-1;width:100%}
    .nm-board-heading,.nm-board-heading>div{flex-wrap:wrap;gap:5px}
    .nm-file-column{flex:0 0 106px;width:106px}
    .nm-file-title strong{font-size:11px}
    .nm-scheme-view{font-size:11px}
}
@media(min-width:1800px){
    .nm-file-title strong,.nm-scheme-view{font-size:13px}
    .nm-streak-button .nm-streak{font-size:12px}
    .nm-scheme{min-height:29px}
}
</style>

<script>
(function () {
    var root = document.getElementById('number-monitor');
    var lotteryType = Number(root.dataset.lotteryType);
    var csrf = root.dataset.csrf;
    var state = <?= $stateJson ?>;
    var selectedFile = null;
    var previewed = false;
    var previewSchemes = [];
    var busy = false;
    var loadingData = false;
    var pollTimer = null;
    var pollDenied = false;
    var pollCycleMs = 5 * 60 * 1000;
    var pollStartMs = 20 * 1000;
    var pollEndMs = 90 * 1000;
    var clockOffsetMs = Number(state.server_now) ? Number(state.server_now) * 1000 - Date.now() : 0;
    var activeCycle = Math.floor((Date.now() + clockOffsetMs) / pollCycleMs);
    var cycleStartDrawId = Number(state.latest_draw_id || 0);
    var settledCycle = -1;
    var input = document.getElementById('nm-file-input');
    var dropzone = document.getElementById('nm-dropzone');
    var previewButton = document.getElementById('nm-preview');
    var createButton = document.getElementById('nm-create');
    var feedback = document.getElementById('nm-feedback');
    var schemeDialog = document.getElementById('nm-scheme-dialog');
    var openedSchemeId = 0;
    var openedPreviewScheme = null;
    var openedPreviewEntries = [];
    var openedSchemePage = 1;
    var schemeRequest = 0;
    var dialogMode = 'patterns';
    var schemeHasMore = false;
    var historyCursors = [0];
    var historyHasMore = false;
    var previousFocus = null;
    var previewPageSize = 400;
    var drawsToggle = document.getElementById('nm-mobile-draws-toggle');
    var drawsBackdrop = document.getElementById('nm-draws-backdrop');
    var drawsPreviousFocus = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char];
        });
    }

    function request(url, formData) {
        return fetch(url, {method:'POST', body:formData, credentials:'same-origin', headers:{'X-CSRF-Token':csrf}})
            .then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok || !body.success) throw new Error(body.message || '请求未完成');
                    return body;
                });
            });
    }

    function closeScheme() {
        schemeRequest++;
        schemeDialog.hidden = true;
        schemeDialog.querySelector('.nm-scheme-panel').classList.remove('loading');
        document.body.classList.remove('nm-dialog-open');
        if (previousFocus && document.contains(previousFocus)) previousFocus.focus();
    }

    function renderSchemePage(scheme) {
        var list = document.getElementById('nm-scheme-patterns');
        dialogMode = 'patterns';
        list.classList.remove('nm-history-list');
        list.setAttribute('aria-busy', 'false');
        schemeDialog.querySelector('.nm-scheme-panel').classList.remove('loading');
        openedSchemePage = Number(scheme.page);
        schemeHasMore = Boolean(scheme.has_more);
        document.getElementById('nm-scheme-title').textContent = scheme.title;
        document.getElementById('nm-scheme-summary').textContent = scheme.pattern_count + ' 组号码 · 重复号码以 × 次数展示';
        document.getElementById('nm-scheme-page').textContent = '第 ' + openedSchemePage + ' 页';
        list.innerHTML = scheme.patterns.map(function (entry) {
            return '<span class="nm-pattern"><span>' + escapeHtml(entry.pattern) + '</span>' +
                (Number(entry.occurrence_count) > 1 ? '<span class="nm-pattern-count">×' + Number(entry.occurrence_count) + '</span>' : '') + '</span>';
        }).join('') || '<p class="nm-scheme-message">本页没有号码</p>';
        list.scrollTop = 0;
        document.getElementById('nm-scheme-prev').disabled = openedSchemePage <= 1;
        document.getElementById('nm-scheme-next').disabled = !schemeHasMore;
    }

    function renderHistoryPage(history, page) {
        var list = document.getElementById('nm-scheme-patterns');
        dialogMode = 'history';
        openedSchemePage = page;
        historyHasMore = Boolean(history.has_more);
        if (historyHasMore) historyCursors[page] = Number(history.next_before_id);
        list.classList.add('nm-history-list');
        list.setAttribute('aria-busy', 'false');
        schemeDialog.querySelector('.nm-scheme-panel').classList.remove('loading');
        document.getElementById('nm-scheme-title').textContent = history.title + ' · 监控历史';
        document.getElementById('nm-scheme-summary').textContent = '本次开始监控后的记录，最新在前';
        document.getElementById('nm-scheme-page').textContent = '第 ' + page + ' 页';
        list.innerHTML = history.records.map(function (record) {
            var status = Number(record.status) === 1 ? 'hit' : Number(record.status) === 2 ? 'miss' : 'none';
            var label = status === 'hit' ? '中' : status === 'miss' ? '错' : '无结果';
            return '<div class="nm-history-row"><span class="nm-history-issue">' + escapeHtml(record.qihao) + '</span>' +
                '<strong class="nm-history-code">' + escapeHtml(record.code || '----') + '</strong>' +
                '<span class="nm-history-status ' + status + '">' + label + '</span></div>';
        }).join('') || '<p class="nm-scheme-message">方案开始监控后暂无开奖记录</p>';
        list.scrollTop = 0;
        document.getElementById('nm-scheme-prev').disabled = page <= 1;
        document.getElementById('nm-scheme-next').disabled = !historyHasMore;
    }

    function loadHistoryPage(page) {
        if (page < 1 || page > historyCursors.length || !openedSchemeId || typeof historyCursors[page - 1] === 'undefined') return;
        var requestId = ++schemeRequest;
        var panel = schemeDialog.querySelector('.nm-scheme-panel');
        var list = document.getElementById('nm-scheme-patterns');
        panel.classList.add('loading');
        list.setAttribute('aria-busy', 'true');
        document.getElementById('nm-scheme-summary').textContent = '正在读取第 ' + page + ' 页历史…';
        document.getElementById('nm-scheme-prev').disabled = true;
        document.getElementById('nm-scheme-next').disabled = true;
        var url = root.dataset.historyUrl + '?id=' + openedSchemeId + '&before_id=' + historyCursors[page - 1];
        fetch(url, {credentials:'same-origin', headers:{'Accept':'application/json'}}).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || !body.success) throw new Error(body.message || '历史记录读取失败');
                return body.history;
            });
        }).then(function (history) {
            if (requestId !== schemeRequest || schemeDialog.hidden) return;
            renderHistoryPage(history, page);
        }).catch(function (error) {
            if (requestId !== schemeRequest || schemeDialog.hidden) return;
            document.getElementById('nm-scheme-summary').textContent = error.message;
            list.setAttribute('aria-busy', 'false');
            panel.classList.remove('loading');
            document.getElementById('nm-scheme-prev').disabled = openedSchemePage <= 1;
            document.getElementById('nm-scheme-next').disabled = !historyHasMore;
        });
    }

    function loadSchemePage(page) {
        if (page < 1 || page > 50) return;
        if (openedPreviewScheme) {
            var start = (page - 1) * previewPageSize;
            if (start >= openedPreviewEntries.length) return;
            renderSchemePage({
                title: openedPreviewScheme.title,
                pattern_count: openedPreviewScheme.pattern_count,
                page: page,
                has_more: start + previewPageSize < openedPreviewEntries.length,
                patterns: openedPreviewEntries.slice(start, start + previewPageSize)
            });
            return;
        }
        if (!openedSchemeId) return;
        var requestId = ++schemeRequest;
        var list = document.getElementById('nm-scheme-patterns');
        schemeDialog.querySelector('.nm-scheme-panel').classList.add('loading');
        list.setAttribute('aria-busy', 'true');
        document.getElementById('nm-scheme-summary').textContent = '正在读取第 ' + page + ' 页号码…';
        document.getElementById('nm-scheme-prev').disabled = true;
        document.getElementById('nm-scheme-next').disabled = true;
        var url = root.dataset.schemeUrl + '?id=' + openedSchemeId + '&page=' + page;
        fetch(url, {credentials:'same-origin', headers:{'Accept':'application/json'}}).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || !body.success) throw new Error(body.message || '号码读取失败');
                return body.scheme;
            });
        }).then(function (scheme) {
            if (requestId !== schemeRequest || schemeDialog.hidden) return;
            renderSchemePage(scheme);
        }).catch(function (error) {
            if (requestId !== schemeRequest || schemeDialog.hidden) return;
            document.getElementById('nm-scheme-summary').textContent = error.message;
            list.setAttribute('aria-busy', 'false');
            schemeDialog.querySelector('.nm-scheme-panel').classList.remove('loading');
            document.getElementById('nm-scheme-prev').disabled = openedSchemePage <= 1;
            document.getElementById('nm-scheme-next').disabled = !schemeHasMore;
        });
    }

    function openScheme(id, trigger) {
        openedPreviewScheme = null;
        openedPreviewEntries = [];
        openedSchemeId = Number(id);
        dialogMode = 'patterns';
        openedSchemePage = 1;
        schemeHasMore = false;
        historyCursors = [0];
        previousFocus = trigger;
        schemeDialog.hidden = false;
        document.body.classList.add('nm-dialog-open');
        document.getElementById('nm-scheme-title').textContent = '方案号码';
        document.getElementById('nm-scheme-patterns').classList.remove('nm-history-list');
        document.getElementById('nm-scheme-patterns').innerHTML = '';
        schemeDialog.querySelector('.nm-scheme-panel').focus();
        loadSchemePage(1);
    }

    function openHistory(id, trigger) {
        openedPreviewScheme = null;
        openedPreviewEntries = [];
        openedSchemeId = Number(id);
        dialogMode = 'history';
        openedSchemePage = 1;
        historyCursors = [0];
        historyHasMore = false;
        previousFocus = trigger;
        schemeDialog.hidden = false;
        document.body.classList.add('nm-dialog-open');
        document.getElementById('nm-scheme-title').textContent = '监控历史';
        document.getElementById('nm-scheme-summary').textContent = '正在读取历史记录…';
        document.getElementById('nm-scheme-patterns').classList.add('nm-history-list');
        document.getElementById('nm-scheme-patterns').innerHTML = '';
        schemeDialog.querySelector('.nm-scheme-panel').focus();
        loadHistoryPage(1);
    }

    function openPreviewScheme(index, trigger) {
        var scheme = previewSchemes[index];
        if (!scheme) return;
        openedSchemeId = 0;
        dialogMode = 'patterns';
        openedPreviewScheme = scheme;
        openedPreviewEntries = [];
        var occurrences = Object.create(null);
        scheme.patterns.forEach(function (pattern) {
            if (occurrences[pattern]) {
                occurrences[pattern].occurrence_count++;
            } else {
                var entry = {pattern:pattern, occurrence_count:1};
                occurrences[pattern] = entry;
                openedPreviewEntries.push(entry);
            }
        });
        openedSchemePage = 1;
        previousFocus = trigger;
        schemeDialog.hidden = false;
        document.body.classList.add('nm-dialog-open');
        schemeDialog.querySelector('.nm-scheme-panel').focus();
        loadSchemePage(1);
    }

    function closeDraws() {
        if (!root.classList.contains('nm-mobile-draws-open')) return;
        root.classList.remove('nm-mobile-draws-open');
        drawsBackdrop.hidden = true;
        drawsToggle.setAttribute('aria-expanded', 'false');
        document.getElementById('nm-draws-panel').removeAttribute('role');
        document.getElementById('nm-draws-panel').removeAttribute('aria-modal');
        document.body.classList.remove('nm-dialog-open');
        if (drawsPreviousFocus && document.contains(drawsPreviousFocus)) drawsPreviousFocus.focus();
    }

    function openDraws() {
        if (window.innerWidth > 1600) return;
        drawsPreviousFocus = document.activeElement;
        root.classList.add('nm-mobile-draws-open');
        drawsBackdrop.hidden = false;
        drawsToggle.setAttribute('aria-expanded', 'true');
        document.getElementById('nm-draws-panel').setAttribute('role', 'dialog');
        document.getElementById('nm-draws-panel').setAttribute('aria-modal', 'true');
        document.body.classList.add('nm-dialog-open');
        document.getElementById('nm-mobile-draws-close').focus();
    }

    function setFeedback(message, kind) {
        feedback.textContent = message || '';
        feedback.className = 'nm-feedback' + (kind ? ' ' + kind : '');
    }

    function formForFile() {
        var form = new FormData();
        form.append('file', selectedFile);
        form.append('lottery_type', String(lotteryType));
        return form;
    }

    function updateFileLabel() {
        document.getElementById('nm-file-name').textContent = selectedFile ? selectedFile.name : '拖入 TXT 文件';
        document.getElementById('nm-file-meta').textContent = selectedFile ? Math.ceil(selectedFile.size / 1024) + ' KB' : '单文件最多 2 MB';
        previewButton.disabled = !selectedFile || busy;
        createButton.disabled = !selectedFile || !previewed || busy;
    }

    function chooseFile(file) {
        if (!file || busy) return;
        selectedFile = file;
        previewed = false;
        previewSchemes = [];
        document.getElementById('nm-preview-list').hidden = true;
        document.getElementById('nm-preview-list').innerHTML = '';
        setFeedback('', '');
        updateFileLabel();
    }

    function renderPreview(schemes) {
        var preview = document.getElementById('nm-preview-list');
        previewSchemes = schemes;
        preview.innerHTML = schemes.map(function (scheme, index) {
            return '<button type="button" class="nm-preview-row" data-preview-index="' + index + '" aria-label="查看' + escapeHtml(scheme.title) + '的全部号码"><span><strong>' + escapeHtml(scheme.title) + '</strong></span><em>' + escapeHtml(scheme.pattern_count) + ' 组 · 查看</em></button>';
        }).join('');
        preview.hidden = false;
        previewed = true;
        updateFileLabel();
    }

    function renderBoard() {
        var board = document.getElementById('nm-board');
        var scrollLeft = board.parentNode.scrollLeft;
        var files = state.files || [];
        var schemeTotal = 0;
        files.forEach(function (file) { schemeTotal += (file.schemes || []).length; });
        document.getElementById('nm-scheme-count').textContent = schemeTotal + ' 套';
        var latestIssue = state.draws && state.draws.length ? String(state.draws[0].qihao || '') : '';
        document.getElementById('nm-current-issue').textContent = latestIssue ? '最新 ' + latestIssue : '等待开奖';
        document.getElementById('nm-monitor-period').textContent = latestIssue ? '最新期号 ' + latestIssue : '最新期号 --';
        document.getElementById('nm-refresh-label').textContent = state.updated_at ? '已同步 ' + new Date(state.updated_at * 1000).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'}) :
            (state.draws && state.draws.length ? '最新开奖已同步' : '等待开奖数据');
        var hasActiveFile = files.some(function (file) { return Number(file.status) === 1 && (file.schemes || []).length > 0; });
        var isProcessed = !hasActiveFile || Number(state.processed_draw_id || 0) >= Number(state.latest_draw_id || 0);
        var syncState = document.getElementById('nm-sync-state');
        syncState.textContent = isProcessed ? (hasActiveFile ? '已处理至最新期' : '实时同步中') : '正在处理开奖';
        syncState.classList.toggle('error', false);
        syncState.classList.toggle('pending', !isProcessed);

        if (!files.length) {
            board.innerHTML = '<div class="nm-empty"><div><strong>还没有监控文件</strong>导入 TXT 后，方案会显示在这里</div></div>';
        } else {
            board.innerHTML = files.map(function (file) {
                var schemes = file.schemes || [];
                var cards = schemes.map(function (scheme, schemeIndex) {
                    var paused = Number(file.status) !== 1;
                    var status = paused ? 'none' : Number(scheme.current_status) === 1 ? 'hit' : Number(scheme.current_status) === 2 ? 'miss' : 'none';
                    var icon = status === 'hit' ? 'fa-check' : status === 'miss' ? 'fa-times' : paused ? 'fa-pause' : 'fa-minus';
                    var label = paused ? '已暂停' : status === 'hit' ? '连中 ' + scheme.streak_count + ' 期' : status === 'miss' ? '连错 ' + scheme.streak_count + ' 期' : '等待本期开奖';
                    var streak = status === 'hit' || status === 'miss' ? '+' + Number(scheme.streak_count || 0) : '';
                    var number = Number(scheme.line_no || schemeIndex + 1);
                    return '<div class="nm-scheme"><button type="button" class="nm-scheme-view" data-action="view" data-id="' + Number(scheme.id) + '" aria-label="查看第' + number + '套，' + escapeHtml(scheme.title) + '，' + Number(scheme.pattern_count || 0) + '组号码" title="' + escapeHtml(scheme.title) + ' · 查看号码">' + number + '.' + Number(scheme.pattern_count || 0) + '组</button><button type="button" class="nm-streak-button" data-action="history" data-id="' + Number(scheme.id) + '" aria-label="' + escapeHtml(label + '，查看逐期监控历史') + '" title="' + escapeHtml(label) + ' · 查看历史"><span class="nm-streak ' + status + '"><i class="fa ' + icon + '" aria-hidden="true"></i>' + streak + '</span></button></div>';
                }).join('');
                return '<section class="nm-file-column ' + (Number(file.status) === 1 ? '' : 'paused') + '" data-file-id="' + Number(file.id) + '">' +
                    '<header class="nm-file-top"><div class="nm-file-title"><span class="nm-file-mark"><i class="fa fa-file-text-o" aria-hidden="true"></i></span><div><strong title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</strong><small>' + schemes.length + ' 套方案</small></div></div>' +
                    '<div class="nm-file-actions"><button type="button" data-action="toggle" data-id="' + Number(file.id) + '" data-active="' + (Number(file.status) === 1 ? '0' : '1') + '" aria-label="' + (Number(file.status) === 1 ? '暂停' : '继续') + '监控文件 ' + escapeHtml(file.name) + '" title="' + (Number(file.status) === 1 ? '暂停' : '继续') + '"><i class="fa ' + (Number(file.status) === 1 ? 'fa-pause' : 'fa-play') + '" aria-hidden="true"></i><span class="nm-file-action-label">' + (Number(file.status) === 1 ? '暂停' : '继续') + '</span></button><button type="button" data-action="clear" data-id="' + Number(file.id) + '" aria-label="清除监控文件 ' + escapeHtml(file.name) + '" title="清除"><i class="fa fa-trash-o" aria-hidden="true"></i><span class="nm-file-action-label">清除</span></button></div></header>' +
                    (Number(file.status) === 1 ? '' : '<div class="nm-file-paused">已暂停监控</div>') +
                    '<div class="nm-scheme-list">' + cards + '</div></section>';
            }).join('');
        }
        board.parentNode.scrollLeft = scrollLeft;
        renderDraws();
    }

    function renderDraws() {
        var draws = state.draws || [];
        var list = document.getElementById('nm-draw-list');
        if (!draws.length) {
            list.innerHTML = '<div class="nm-draw-empty">暂无开奖数据</div>';
            document.getElementById('nm-floating-draw-code').textContent = '----';
            return;
        }
        var latestCode = String(draws[0].kj_code || '').replace(/\D/g, '');
        document.getElementById('nm-floating-draw-code').textContent = latestCode || '----';
        drawsToggle.setAttribute('aria-label', '查看最近开奖号，最新期号 ' + String(draws[0].qihao || ''));
        list.innerHTML = draws.map(function (draw, index) {
            var code = String(draw.kj_code || '').replace(/\D/g, '');
            return '<div class="nm-draw-row ' + (index === 0 ? 'latest' : '') + '"><span class="nm-draw-issue">' + escapeHtml(draw.qihao) + '</span><strong class="nm-draw-code">' + escapeHtml(code || '----') + '</strong></div>';
        }).join('');
    }

    function serverNowMs() { return Date.now() + clockOffsetMs; }

    function syncPollCycle() {
        var cycle = Math.floor(serverNowMs() / pollCycleMs);
        if (cycle !== activeCycle) {
            activeCycle = cycle;
            cycleStartDrawId = Number(state.latest_draw_id || 0);
            settledCycle = -1;
        }
    }

    function loadData() {
        if (loadingData) return Promise.resolve();
        window.clearTimeout(pollTimer);
        pollTimer = null;
        loadingData = true;
        syncPollCycle();
        var url = root.dataset.dataUrl + '?lottery_type=' + lotteryType;
        return fetch(url, {credentials:'same-origin', headers:{'Accept':'application/json'}}).then(function (response) {
            if (response.status === 401 || response.status === 403) {
                var accessError = new Error('监控数据访问被拒绝');
                accessError.status = response.status;
                throw accessError;
            }
            return response.json().then(function (body) {
                if (!response.ok || !body.success) {
                    var error = new Error(body.message || '同步失败');
                    error.status = response.status;
                    throw error;
                }
                state = body.dashboard;
                if (Number(state.server_now)) clockOffsetMs = Number(state.server_now) * 1000 - Date.now();
                renderBoard();
                var files = state.files || [];
                var hasActiveFile = files.some(function (file) { return Number(file.status) === 1; });
                if (Number(state.latest_draw_id || 0) > cycleStartDrawId &&
                    (!hasActiveFile || Number(state.processed_draw_id || 0) >= Number(state.latest_draw_id || 0))) {
                    settledCycle = activeCycle;
                }
            });
        }).catch(function (error) {
            var message = error.status === 401 ? '登录状态已失效，请重新登录' :
                (error.status === 403 ? '当前账号没有号码监控数据权限' : '同步暂不可用');
            document.getElementById('nm-sync-state').textContent = message;
            document.getElementById('nm-sync-state').classList.add('error');
            if (error.status === 401 || error.status === 403) pollDenied = true;
        }).then(function () {
            loadingData = false;
            scheduleNextPoll(false);
        });
    }

    function scheduleNextPoll(immediate) {
        window.clearTimeout(pollTimer);
        pollTimer = null;
        if (document.hidden || pollDenied) return;
        syncPollCycle();
        var phase = serverNowMs() % pollCycleMs;
        var delay;
        if (phase < pollStartMs) {
            delay = pollStartMs - phase;
        } else if (phase < pollEndMs - 1000 && settledCycle !== activeCycle) {
            delay = immediate ? 0 : Math.min(5000, pollEndMs - phase - 1000);
        } else {
            delay = pollCycleMs - phase + pollStartMs;
        }
        pollTimer = window.setTimeout(loadData, Math.max(1000, Math.ceil(delay)));
    }

    dropzone.addEventListener('click', function () { if (!busy) input.click(); });
    input.addEventListener('change', function () { chooseFile(input.files && input.files[0]); });
    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) { event.preventDefault(); dropzone.classList.add('dragging'); });
    });
    ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove('dragging');
            if (eventName === 'drop' && event.dataTransfer.files.length) chooseFile(event.dataTransfer.files[0]);
        });
    });

    previewButton.addEventListener('click', function () {
        if (!selectedFile || busy) return;
        busy = true; updateFileLabel(); setFeedback('', '');
        previewed = false; previewSchemes = [];
        document.getElementById('nm-preview-list').hidden = true;
        request(root.dataset.previewUrl, formForFile()).then(function (body) {
            renderPreview(body.schemes);
            setFeedback('识别到 ' + body.schemes.length + ' 套方案', 'success');
        }).catch(function (error) { previewed = false; setFeedback(error.message, 'error'); })
            .finally(function () { busy = false; updateFileLabel(); });
    });
    document.getElementById('nm-preview-list').addEventListener('click', function (event) {
        var button = event.target.closest('button[data-preview-index]');
        if (button) openPreviewScheme(Number(button.dataset.previewIndex), button);
    });

    createButton.addEventListener('click', function () {
        if (!selectedFile || !previewed || busy) return;
        busy = true; updateFileLabel(); setFeedback('', '');
        request(root.dataset.createUrl, formForFile()).then(function () {
            selectedFile = null; previewed = false; previewSchemes = []; input.value = '';
            document.getElementById('nm-preview-list').hidden = true;
            document.getElementById('nm-preview-list').innerHTML = '';
            setFeedback('号码文件已加入监控', 'success');
            return loadData();
        }).catch(function (error) { setFeedback(error.message, 'error'); })
            .finally(function () { busy = false; updateFileLabel(); });
    });

    document.getElementById('nm-board').addEventListener('click', function (event) {
        var button = event.target.closest('button[data-action]');
        if (!button) return;
        var action = button.dataset.action;
        if (action === 'view') { openScheme(button.dataset.id, button); return; }
        if (action === 'history') { openHistory(button.dataset.id, button); return; }
        var form = new FormData();
        form.append('id', button.dataset.id);
        if (action === 'toggle') {
            form.append('active', button.dataset.active);
            request(root.dataset.toggleUrl, form).then(loadData).catch(function (error) { setFeedback(error.message, 'error'); });
            return;
        }
        var file = (state.files || []).find(function (item) { return Number(item.id) === Number(button.dataset.id); });
        if (!file || !window.confirm('清除「' + file.name + '」及其中的全部监控方案？')) return;
        request(root.dataset.clearUrl, form).then(loadData).catch(function (error) { setFeedback(error.message, 'error'); });
    });
    document.getElementById('nm-scheme-close').addEventListener('click', closeScheme);
    schemeDialog.addEventListener('click', function (event) {
        if (event.target.dataset.closeScheme) closeScheme();
    });
    document.getElementById('nm-scheme-prev').addEventListener('click', function () {
        if (dialogMode === 'history') loadHistoryPage(openedSchemePage - 1);
        else loadSchemePage(openedSchemePage - 1);
    });
    document.getElementById('nm-scheme-next').addEventListener('click', function () {
        if (dialogMode === 'history') loadHistoryPage(openedSchemePage + 1);
        else loadSchemePage(openedSchemePage + 1);
    });
    drawsToggle.addEventListener('click', openDraws);
    document.getElementById('nm-mobile-draws-close').addEventListener('click', closeDraws);
    drawsBackdrop.addEventListener('click', closeDraws);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !schemeDialog.hidden) closeScheme();
        else if (event.key === 'Escape') closeDraws();
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 1600) closeDraws();
    });

    document.getElementById('nm-lottery-type').addEventListener('change', function (event) {
        window.location.href = window.location.pathname + '?lottery_type=' + encodeURIComponent(event.target.value);
    });
    document.addEventListener('visibilitychange', function () { scheduleNextPoll(true); });

    renderBoard();
    scheduleNextPoll(true);
})();
</script>
