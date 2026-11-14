"""Lucky5 platform TXT preview and one-shot confirmation."""

import json
import re
import uuid
from collections import Counter
from decimal import Decimal, InvalidOperation
from urllib.parse import urlparse


TASK_URL = 'lucky5-txt-import://v1'


class TxtImportError(ValueError):
    pass


def _amount(value):
    try:
        amount = Decimal(str(value))
    except (InvalidOperation, TypeError):
        raise TxtImportError('TXT 金额无效')
    if not amount.is_finite() or amount < Decimal('0.1'):
        raise TxtImportError('TXT 金额低于最低单注金额')
    return amount


_TYPE_BY_POSITIONS = {
    (0, 1): 1, (0, 2): 2, (0, 3): 3,
    (1, 2): 4, (1, 3): 5, (2, 3): 6,
    (0, 1, 2): 7, (0, 1, 3): 8,
    (0, 2, 3): 9, (1, 2, 3): 10,
    (0, 1, 2, 3): 11,
}


def _type_for_code(code, playway):
    return _TYPE_BY_POSITIONS.get(tuple(index for index, value in enumerate(code) if value != 'X'))


def _expect_task(task):
    post_data = task.get('post_data') or {}
    if task.get('bet_url') != TASK_URL or post_data.get('txt_import_v1') != 1:
        raise TxtImportError('TXT 任务格式无效')
    codes = str(post_data.get('txt_codes') or '').split(',')
    try:
        playway = int(post_data.get('txt_playway', 3))
    except (TypeError, ValueError):
        raise TxtImportError('TXT 玩法无效')
    if playway not in (1, 2, 3):
        raise TxtImportError('TXT 玩法无效')
    if not 1 <= len(codes) <= 500 or any(
            not re.fullmatch(r'[0-9X]{4}', code) or code.count('X') != 3 - playway
            for code in codes):
        raise TxtImportError('TXT 号码格式无效')
    if str(post_data.get('period_no')) != str(task.get('qihao')):
        raise TxtImportError('TXT 期号与任务不一致')
    money = _amount(post_data.get('txt_money'))
    if playway == 1 and (money < Decimal('1') or money != money.to_integral_value()):
        raise TxtImportError('二定 TXT 单注金额须为至少 1 元的整数')
    origin = (task.get('headers') or {}).get('Origin', '').rstrip('/')
    parsed = urlparse(origin)
    if (parsed.scheme != 'https' or not parsed.hostname or parsed.username or parsed.password
            or parsed.path or parsed.query or parsed.fragment):
        raise TxtImportError('TXT 盘口域名无效')
    return codes, money, origin


def _check_preview(preview, codes, money, playway):
    if not isinstance(preview, dict) or str(preview.get('Status')) != '1':
        raise TxtImportError('盘口 TXT 预览失败')
    data = preview.get('Data')
    if not isinstance(data, dict) or str(data.get('upload_type')) != '1':
        raise TxtImportError('盘口未按逐注金额解析 TXT')
    details = data.get('Details')
    if not isinstance(details, list) or len(details) != len(codes):
        raise TxtImportError('盘口 TXT 预览笔数不一致')
    expected = Counter(codes)
    bets = []
    for item in details:
        if not isinstance(item, dict):
            raise TxtImportError('盘口 TXT 预览玩法不一致')
        code = str(item.get('bet_no') or '')
        if not re.fullmatch(r'[0-9X]{4}', code) or expected[code] <= 0:
            raise TxtImportError('盘口 TXT 预览号码不一致')
        if str(item.get('dict_no_type_id')) != str(_type_for_code(code, playway)):
            raise TxtImportError('盘口 TXT 预览玩法不一致')
        if _amount(item.get('bet_money')) != money:
            raise TxtImportError('盘口 TXT 预览金额不一致')
        expected[code] -= 1
        bets.append({
            'dict_no_type_id': item['dict_no_type_id'],
            'bet_no': code,
            'bet_money': str(item['bet_money']),
        })
    if any(expected.values()) or int(data.get('TotalCount', -1)) != len(codes):
        raise TxtImportError('盘口 TXT 预览笔数不一致')
    if _amount(data.get('TotalBetMoney')) != money * len(codes):
        raise TxtImportError('盘口 TXT 预览总金额不一致')
    return bets


def execute_txt_import(session, task, headers):
    """Upload for preview, verify every bet, then submit BatchBet exactly once."""
    codes, money, origin = _expect_task(task)
    request_headers = {
        key: value for key, value in headers.items()
        if key.lower() not in ('content-length', 'content-type', 'host')
    }
    amount_text = format(money, 'f')
    payload = ' '.join(f'{code}={amount_text}' for code in codes).encode('ascii')
    preview_response = session.post(
        origin + '/Member/UpLoadBetNos',
        files={'file': ('bets.txt', payload, 'text/plain')},
        headers=request_headers,
        verify=False,
        timeout=(3, 12),
        allow_redirects=False,
    )
    if preview_response.status_code != 200:
        raise TxtImportError('盘口 TXT 预览 HTTP 状态异常')
    try:
        preview = preview_response.json()
    except ValueError:
        raise TxtImportError('盘口 TXT 预览响应不是 JSON')
    playway = int((task.get('post_data') or {}).get('txt_playway', 3))
    bets = _check_preview(preview, codes, money, playway)
    confirm_response = session.post(
        origin + '/Member/BatchBet',
        data={
            'bets': json.dumps(bets, ensure_ascii=False, separators=(',', ':')),
            'way': '103',
            'guid': str(uuid.uuid4()),
            'is_package': '0',
            'period_no': str(task['qihao']),
        },
        headers=request_headers,
        verify=False,
        timeout=(3, 12),
        allow_redirects=False,
    )
    if confirm_response.status_code != 200:
        raise TxtImportError('盘口 TXT 确认 HTTP 状态异常，结果待核对')
    try:
        result = confirm_response.json()
    except ValueError:
        raise TxtImportError('盘口 TXT 确认响应不是 JSON，结果待核对')
    if str(result.get('Status')) == '1' and str((result.get('Data') or {}).get('CompletedStatus')) != '1':
        raise TxtImportError('盘口 TXT 确认结果未完成，结果待核对')
    return result
