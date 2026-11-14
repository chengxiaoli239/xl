import json
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

import requests

from xy_client.services.Lucky5.betting.txt_import import TASK_URL, TxtImportError, execute_txt_import
from xy_client.services.Lucky5.betting.bet_executor import BetExecutor


class TxtImportTest(unittest.TestCase):
    def setUp(self):
        self.task = {
            'bet_url': TASK_URL,
            'qihao': '20261107001',
            'post_data': {
                'txt_import_v1': 1,
                'txt_codes': '1234,7493',
                'txt_money': 0.1,
                'period_no': '20261107001',
            },
            'headers': {'Origin': 'https://example.invalid'},
        }
        self.headers = {
            'Origin': 'https://example.invalid',
            'Content-Type': 'application/x-www-form-urlencoded',
            'Content-Length': '99',
            'Cookie': 'test=value',
        }
        self.preview = {
            'Status': 1,
            'Data': {
                'upload_type': 1,
                'TotalCount': 2,
                'TotalBetMoney': 0.2,
                'Details': [
                    {'dict_no_type_id': 11, 'bet_no': '1234', 'bet_money': 0.1},
                    {'dict_no_type_id': 11, 'bet_no': '7493', 'bet_money': 0.1},
                ],
            },
        }

    def response(self, data):
        return Mock(status_code=200, json=Mock(return_value=data))

    def test_preview_is_checked_then_confirmed_once(self):
        session = Mock()
        session.post.side_effect = [
            self.response(self.preview),
            self.response({'Status': 1, 'Data': {'CompletedStatus': 1}}),
        ]

        result = execute_txt_import(session, self.task, self.headers)

        self.assertEqual(result['Status'], 1)
        self.assertEqual(session.post.call_count, 2)
        upload, confirm = session.post.call_args_list
        self.assertEqual(upload.args[0], 'https://example.invalid/Member/UpLoadBetNos')
        fixture = Path(__file__).parent / 'fixtures' / 'txt_import_preview.txt'
        self.assertEqual(upload.kwargs['files']['file'][1], fixture.read_bytes().strip())
        self.assertNotIn('Content-Type', upload.kwargs['headers'])
        self.assertNotIn('Content-Length', upload.kwargs['headers'])
        self.assertEqual(confirm.args[0], 'https://example.invalid/Member/BatchBet')
        self.assertEqual(confirm.kwargs['data']['way'], '103')
        self.assertEqual(confirm.kwargs['data']['period_no'], self.task['qihao'])
        self.assertEqual(len(json.loads(confirm.kwargs['data']['bets'])), 2)

    def test_wrong_preview_number_never_confirms(self):
        self.preview['Data']['Details'][1]['bet_no'] = '7494'
        session = Mock()
        session.post.return_value = self.response(self.preview)

        with self.assertRaisesRegex(TxtImportError, '号码不一致'):
            execute_txt_import(session, self.task, self.headers)

        session.post.assert_called_once()

    def test_wrong_preview_amount_never_confirms(self):
        self.preview['Data']['Details'][0]['bet_money'] = 0.2
        session = Mock()
        session.post.return_value = self.response(self.preview)

        with self.assertRaisesRegex(TxtImportError, '金额不一致'):
            execute_txt_import(session, self.task, self.headers)

        session.post.assert_called_once()

    def test_two_and_three_fixed_positions_use_platform_preview_types(self):
        cases = (
            (1, '12XX', 1),
            (1, '1X3X', 2),
            (1, '1XX4', 3),
            (1, 'X23X', 4),
            (1, 'X2X4', 5),
            (1, 'XX34', 6),
            (2, '123X', 7),
            (2, '12X4', 8),
            (2, '1X34', 9),
            (2, 'X234', 10),
        )
        for playway, code, type_id in cases:
            with self.subTest(code=code):
                amount = 1.0 if playway == 1 else 0.1
                self.task['post_data'].update(txt_codes=code, txt_playway=playway, txt_money=amount)
                preview = {
                    'Status': 1,
                    'Data': {
                        'upload_type': 1,
                        'TotalCount': 1,
                        'TotalBetMoney': amount,
                        'Details': [
                            {'dict_no_type_id': type_id, 'bet_no': code, 'bet_money': amount},
                        ],
                    },
                }
                session = Mock()
                session.post.side_effect = [
                    self.response(preview),
                    self.response({'Status': 1, 'Data': {'CompletedStatus': 1}}),
                ]

                execute_txt_import(session, self.task, self.headers)

                self.assertEqual(session.post.call_count, 2)
                self.assertEqual(session.post.call_args_list[0].kwargs['files']['file'][1],
                                 (code + '=' + str(amount)).encode('ascii'))
                bets = json.loads(session.post.call_args_list[1].kwargs['data']['bets'])
                self.assertEqual(bets[0]['dict_no_type_id'], type_id)

    def test_preview_position_type_mismatch_never_confirms(self):
        self.task['post_data'].update(txt_codes='12XX', txt_playway=1)
        self.task['post_data']['txt_money'] = 1.0
        self.preview['Data']['TotalCount'] = 1
        self.preview['Data']['TotalBetMoney'] = 1.0
        self.preview['Data']['Details'] = [
            {'dict_no_type_id': 2, 'bet_no': '12XX', 'bet_money': 1.0},
        ]
        session = Mock()
        session.post.return_value = self.response(self.preview)

        with self.assertRaisesRegex(TxtImportError, '玩法不一致'):
            execute_txt_import(session, self.task, self.headers)

        session.post.assert_called_once()

    def test_two_fixed_amount_below_minimum_never_uploads(self):
        self.task['post_data'].update(txt_codes='12XX', txt_playway=1)
        session = Mock()

        with self.assertRaisesRegex(TxtImportError, '至少 1 元'):
            execute_txt_import(session, self.task, self.headers)

        session.post.assert_not_called()

    def test_two_fixed_fractional_amount_never_uploads(self):
        self.task['post_data'].update(txt_codes='12XX', txt_playway=1, txt_money=1.1)
        session = Mock()

        with self.assertRaisesRegex(TxtImportError, '整数'):
            execute_txt_import(session, self.task, self.headers)

        session.post.assert_not_called()

    def test_confirmation_timeout_is_not_retried(self):
        session = Mock()
        session.post.side_effect = [
            self.response(self.preview),
            requests.exceptions.ReadTimeout('result unknown'),
        ]

        with self.assertRaises(requests.exceptions.ReadTimeout):
            execute_txt_import(session, self.task, self.headers)

        self.assertEqual(session.post.call_count, 2)

    def test_period_mismatch_never_calls_platform(self):
        self.task['post_data']['period_no'] = '20261107002'
        session = Mock()

        with self.assertRaisesRegex(TxtImportError, '期号'):
            execute_txt_import(session, self.task, self.headers)

        session.post.assert_not_called()

    def test_executor_dispatches_marker_without_legacy_post(self):
        executor = object.__new__(BetExecutor)
        executor.state_manager = Mock()
        executor.state_manager.get_browser_cookies.return_value = None
        executor._process_bet_result = Mock(return_value={'success': True})
        executor._wait_bet_interval = Mock()
        session = Mock()
        task = dict(self.task, plan_type='remote')

        with patch('xy_client.services.Lucky5.betting.bet_executor.execute_txt_import', return_value={'Status': 1}) as run:
            result = executor._execute_single_bet(task, 0, 1, session)

        self.assertTrue(result['success'])
        run.assert_called_once()
        session.post.assert_not_called()


if __name__ == '__main__':
    unittest.main()
