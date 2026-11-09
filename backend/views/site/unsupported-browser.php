<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>请在系统浏览器中打开</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f4f7fb; color: #172235; font: 16px/1.65 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        main { width: min(100%, 440px); padding: 32px 26px; border-radius: 18px; background: #fff; box-shadow: 0 18px 50px rgba(26, 43, 70, .08); text-align: center; }
        .icon { margin: 0 auto 18px; width: 56px; height: 56px; border-radius: 16px; background: #eaf2ff; color: #2563eb; font-size: 31px; line-height: 56px; }
        h1 { margin: 0 0 12px; font-size: 23px; line-height: 1.4; }
        p { margin: 0 0 18px; color: #526176; }
        .steps { margin: 0 0 24px; padding: 16px 18px; border-radius: 12px; background: #f5f8fd; text-align: left; color: #34445a; }
        button { width: 100%; min-height: 48px; border: 0; border-radius: 10px; background: #2563eb; color: white; font: inherit; font-weight: 600; cursor: pointer; }
        button:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
        #link { width: 100%; margin-top: 12px; padding: 10px; border: 1px solid #dce4ee; border-radius: 8px; background: #fff; color: #526176; font-size: 13px; }
        #feedback { min-height: 25px; margin: 9px 0 0; color: #2563eb; font-size: 14px; }
    </style>
</head>
<body>
<main>
    <div class="icon" aria-hidden="true">↗</div>
    <h1>请在系统浏览器中打开</h1>
    <p>当前页面不支持在微信或 QQ 浏览器内访问。请使用 Safari、Chrome 或手机自带浏览器打开，登录后即可继续使用。</p>
    <div class="steps">点右上角「···」，选择「在浏览器中打开」；如果没有这个选项，请复制下方链接，再粘贴到系统浏览器地址栏。</div>
    <button type="button" id="copy">复制当前链接</button>
    <input id="link" type="text" readonly aria-label="当前页面链接">
    <p id="feedback" role="status" aria-live="polite"></p>
</main>
<script>
(function () {
    var link = document.getElementById('link');
    var feedback = document.getElementById('feedback');
    link.value = window.location.href;
    document.getElementById('copy').addEventListener('click', function () {
        link.select();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link.value).then(function () {
                feedback.textContent = '链接已复制，请在系统浏览器中打开';
            }).catch(function () {
                feedback.textContent = '请长按链接复制，再到系统浏览器中打开';
            });
        } else {
            feedback.textContent = document.execCommand('copy')
                ? '链接已复制，请在系统浏览器中打开'
                : '请长按链接复制，再到系统浏览器中打开';
        }
    });
})();
</script>
</body>
</html>
