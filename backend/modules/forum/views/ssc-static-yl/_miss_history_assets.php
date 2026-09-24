<?php

$this->registerCss(<<<CSS
.miss-history-guide {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0 0 12px;
    padding: 6px 10px;
    color: #6b7280;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 12px;
}
.miss-history-guide__latest {
    color: #b42318;
    font-weight: 700;
}
.miss-history {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    min-width: 180px;
    line-height: 1.4;
}
.miss-history__item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 30px;
    min-height: 26px;
    padding: 3px 7px;
    color: #4b5563;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 5px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.miss-history__item--latest {
    gap: 5px;
    color: #9b1c1c;
    background: #fff1f0;
    border-color: #ff9c95;
    box-shadow: 0 0 0 2px rgba(255, 77, 79, .08);
}
.miss-history__latest-label {
    padding: 1px 4px;
    color: #fff;
    background: #e53935;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.4;
}
.miss-history__value {
    font-size: 14px;
}
@media (max-width: 767px) {
    .miss-history { min-width: 240px; gap: 4px; }
    .miss-history__item { min-width: 28px; padding: 3px 6px; }
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
    var html = '<div class="miss-history">'
        + '<span class="miss-history__item miss-history__item--latest">'
        + '<small class="miss-history__latest-label">最新</small>'
        + '<strong class="miss-history__value">' + escapeHtml(current) + '</strong></span>';
    $.each(values, function (_, value) {
        html += '<span class="miss-history__item">' + escapeHtml(value) + '</span>';
    });
    return html + '</div>';
};
JS
);
?>
<div class="miss-history-guide">
    <span class="miss-history-guide__latest">红色“最新”</span>
    <span>为本期最新遗漏；从左到右依次为最新 → 更早</span>
</div>
