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
         data-preview-url="<?= Html::encode(Url::to(['/forum/number-monitor/preview'])) ?>"
         data-create-url="<?= Html::encode(Url::to(['/forum/number-monitor/create'])) ?>"
         data-toggle-url="<?= Html::encode(Url::to(['/forum/number-monitor/toggle'])) ?>"
         data-clear-url="<?= Html::encode(Url::to(['/forum/number-monitor/clear'])) ?>"
         data-csrf="<?= Html::encode($csrfToken) ?>">
    <header class="nm-heading">
        <div>
            <p class="nm-eyebrow">开奖追踪</p>
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
                        <span class="nm-muted">TXT · 空行分隔方案 · 最多 10 套，每套 10,000 组（重复号码保留）</span>
                    </div>
                    <div class="nm-import-actions">
                        <button type="button" id="nm-preview" class="nm-button nm-button-secondary" disabled>预览号码</button>
                        <button type="button" id="nm-create" class="nm-button nm-button-primary" disabled>开始监控</button>
                    </div>
                </div>
                <button type="button" class="nm-dropzone" id="nm-dropzone" aria-label="选择或拖入 TXT 文件">
                    <span class="nm-drop-icon"><i class="fa fa-file-text-o" aria-hidden="true"></i></span>
                    <span class="nm-drop-copy"><strong id="nm-file-name">拖入 TXT 文件</strong><small id="nm-file-meta">单文件最多 1 MB</small></span>
                    <span class="nm-drop-browse">选择文件</span>
                </button>
                <input id="nm-file-input" type="file" accept=".txt,text/plain" hidden>
                <div class="nm-preview" id="nm-preview-list" hidden></div>
                <div id="nm-feedback" class="nm-feedback" role="status" aria-live="polite"></div>
            </section>

            <div class="nm-board-heading">
                <div><h2>监控方案</h2><span id="nm-scheme-count" class="nm-count">0 套</span></div>
                <span id="nm-sync-state" class="nm-sync-state">实时同步中</span>
            </div>
            <div class="nm-board-viewport">
                <div class="nm-board" id="nm-board" aria-live="polite"></div>
            </div>
        </main>

        <aside class="nm-draws" aria-label="最近开奖号码">
            <div class="nm-draws-heading"><div><h2>最近开奖</h2><span>近 30 期</span></div><i class="fa fa-history" aria-hidden="true"></i></div>
            <div id="nm-draw-list" class="nm-draw-list"></div>
        </aside>
    </div>
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
</style>

<script>
(function () {
    var root = document.getElementById('number-monitor');
    var lotteryType = Number(root.dataset.lotteryType);
    var csrf = root.dataset.csrf;
    var state = <?= $stateJson ?>;
    var selectedFile = null;
    var previewed = false;
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
    var openedSchemePage = 1;
    var schemeRequest = 0;
    var previousFocus = null;

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
        document.body.classList.remove('nm-dialog-open');
        if (previousFocus && document.contains(previousFocus)) previousFocus.focus();
    }

    function loadSchemePage(page) {
        if (!openedSchemeId || page < 1 || page > 50) return;
        var requestId = ++schemeRequest;
        var list = document.getElementById('nm-scheme-patterns');
        list.innerHTML = '<p class="nm-scheme-message">正在读取号码…</p>';
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
            openedSchemePage = Number(scheme.page);
            document.getElementById('nm-scheme-title').textContent = scheme.title;
            document.getElementById('nm-scheme-summary').textContent = scheme.pattern_count + ' 组号码 · 重复号码以 × 次数展示';
            document.getElementById('nm-scheme-page').textContent = '第 ' + openedSchemePage + ' 页';
            list.innerHTML = scheme.patterns.map(function (entry) {
                return '<span class="nm-pattern"><span>' + escapeHtml(entry.pattern) + '</span>' +
                    (Number(entry.occurrence_count) > 1 ? '<span class="nm-pattern-count">×' + Number(entry.occurrence_count) + '</span>' : '') + '</span>';
            }).join('') || '<p class="nm-scheme-message">本页没有号码</p>';
            document.getElementById('nm-scheme-prev').disabled = openedSchemePage <= 1;
            document.getElementById('nm-scheme-next').disabled = !scheme.has_more;
        }).catch(function (error) {
            if (requestId !== schemeRequest || schemeDialog.hidden) return;
            list.innerHTML = '<p class="nm-scheme-message">' + escapeHtml(error.message) + '</p>';
        });
    }

    function openScheme(id, trigger) {
        openedSchemeId = Number(id);
        openedSchemePage = 1;
        previousFocus = trigger;
        schemeDialog.hidden = false;
        document.body.classList.add('nm-dialog-open');
        schemeDialog.querySelector('.nm-scheme-panel').focus();
        loadSchemePage(1);
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
        document.getElementById('nm-file-meta').textContent = selectedFile ? Math.ceil(selectedFile.size / 1024) + ' KB' : '单文件最多 1 MB';
        previewButton.disabled = !selectedFile || busy;
        createButton.disabled = !selectedFile || !previewed || busy;
    }

    function chooseFile(file) {
        if (!file || busy) return;
        selectedFile = file;
        previewed = false;
        document.getElementById('nm-preview-list').hidden = true;
        document.getElementById('nm-preview-list').innerHTML = '';
        setFeedback('', '');
        updateFileLabel();
    }

    function renderPreview(schemes) {
        var preview = document.getElementById('nm-preview-list');
        preview.innerHTML = schemes.map(function (scheme) {
            var sample = scheme.patterns.slice(0, 8).join(' ');
            if (Number(scheme.pattern_count) > 8) sample += ' …';
            return '<div class="nm-preview-row"><span><strong>' + escapeHtml(scheme.title) + '</strong><br>' + escapeHtml(sample) + '</span><em>' + escapeHtml(scheme.pattern_count) + ' 组</em></div>';
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
        document.getElementById('nm-current-issue').textContent = state.draws && state.draws.length ? '最新 ' + state.draws[0].qihao : '等待开奖';
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
                var cards = schemes.map(function (scheme) {
                    var paused = Number(file.status) !== 1;
                    var status = paused ? 'none' : Number(scheme.current_status) === 1 ? 'hit' : Number(scheme.current_status) === 2 ? 'miss' : 'none';
                    var label = paused ? '已暂停' : status === 'hit' ? '中 +' + scheme.streak_count : status === 'miss' ? '错 +' + scheme.streak_count : '待开奖';
                    var meta = scheme.last_qihao ? '最近更新 ' + scheme.last_qihao : scheme.pattern_count + ' 组号码';
                    return '<button type="button" class="nm-scheme" data-action="view" data-id="' + Number(scheme.id) + '" aria-label="查看' + escapeHtml(scheme.title) + '号码"><div class="nm-scheme-head"><strong>' + escapeHtml(scheme.title) + '</strong><span class="nm-streak ' + status + '">' + escapeHtml(label) + '</span></div><div class="nm-scheme-sample">' + escapeHtml(scheme.sample || '') + '</div><div class="nm-scheme-meta"><span>' + escapeHtml(meta) + '</span><span class="nm-scheme-open-hint">查看 <i class="fa fa-angle-right" aria-hidden="true"></i></span></div></button>';
                }).join('');
                return '<section class="nm-file-column ' + (Number(file.status) === 1 ? '' : 'paused') + '" data-file-id="' + Number(file.id) + '">' +
                    '<header class="nm-file-top"><div class="nm-file-title"><span class="nm-file-mark"><i class="fa fa-file-text-o" aria-hidden="true"></i></span><div><strong title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</strong><small>' + schemes.length + ' 套方案</small></div></div>' +
                    '<div class="nm-file-actions"><button type="button" data-action="toggle" data-id="' + Number(file.id) + '" data-active="' + (Number(file.status) === 1 ? '0' : '1') + '">' + (Number(file.status) === 1 ? '暂停' : '继续') + '</button><button type="button" data-action="clear" data-id="' + Number(file.id) + '">清除</button></div></header>' +
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
            return;
        }
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
        request(root.dataset.previewUrl, formForFile()).then(function (body) {
            renderPreview(body.schemes);
            setFeedback('识别到 ' + body.schemes.length + ' 套方案', 'success');
        }).catch(function (error) { previewed = false; setFeedback(error.message, 'error'); })
            .finally(function () { busy = false; updateFileLabel(); });
    });

    createButton.addEventListener('click', function () {
        if (!selectedFile || !previewed || busy) return;
        busy = true; updateFileLabel(); setFeedback('', '');
        request(root.dataset.createUrl, formForFile()).then(function () {
            selectedFile = null; previewed = false; input.value = '';
            document.getElementById('nm-preview-list').hidden = true;
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
    document.getElementById('nm-scheme-prev').addEventListener('click', function () { loadSchemePage(openedSchemePage - 1); });
    document.getElementById('nm-scheme-next').addEventListener('click', function () { loadSchemePage(openedSchemePage + 1); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !schemeDialog.hidden) closeScheme();
    });

    document.getElementById('nm-lottery-type').addEventListener('change', function (event) {
        window.location.href = window.location.pathname + '?lottery_type=' + encodeURIComponent(event.target.value);
    });
    document.addEventListener('visibilitychange', function () { scheduleNextPoll(true); });

    renderBoard();
    scheduleNextPoll(true);
})();
</script>
