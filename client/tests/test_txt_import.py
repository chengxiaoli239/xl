import json
import unittest
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
        self.assertEqual(upload.kwargs['files']['file'][1], b'1234=0.1 7493=0.1')
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
