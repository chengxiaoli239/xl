<?php

$this->registerCss(<<<CSS
.miss-history {
    white-space: nowrap;
    line-height: 1.6;
    font-variant-numeric: tabular-nums;
}
.miss-history__latest {
    color: #d93025;
    font-weight: 700;
}
@media (max-width: 767px) {
    .miss-history { white-space: normal; word-break: break-all; }
}
CSS
);

$this->registerJs(<<<JS
window.renderMissHistory = function (current, records) {
    var escapeHtml = function (value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    };
    var values = String(records || '').replace(/^-+|-+$/g, '').split('-').filter(function (value) {
        return value !== '';
    });
    var html = '<span class="miss-history">'
        + '<strong class="miss-history__latest" title="最新遗漏">' + escapeHtml(current) + '</strong>';
    $.each(values, function (_, value) {
        html += '-' + escapeHtml(value);
    });
    return html + '</span>';
};
JS
);
?>
