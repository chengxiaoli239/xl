import unittest
from unittest.mock import Mock, patch
import requests

from xy_client.services.Lucky5.betting.bet_executor import BetExecutor
from xy_client.services.tools.GlobalSession import GlobalSession


class BetExecutionTimingTest(unittest.TestCase):
    def setUp(self):
        self.executor = object.__new__(BetExecutor)
        self.executor.main_window = Mock()
        self.executor._push_bet_result = Mock()

    @patch("xy_client.services.Lucky5.betting.bet_executor.time.time", return_value=1700000002)
    def test_success_result_contains_execution_start_and_finish(self, _time):
        response = {"Status": 1}
        data = {"task_id": 123, "plan_id": 22040, "qihao": "20260918001"}

        result = self.executor._process_bet_result(
            response,
            data,
            start_time=1700000000.25,
            direct=1,
            bet_started_at=1700000000,
        )

        self.assertEqual(result, {"success": True})
        self.assertEqual(response["bet_started_at"], 1700000000)
        self.assertEqual(response["bet_finished_at"], 1700000002)
        self.executor._push_bet_result.assert_called_once_with(data, response)

    @patch("xy_client.services.Lucky5.betting.bet_executor.time.time", return_value=1700000005)
    def test_request_exception_pushes_timed_failure_result(self, _time):
        data = {"task_id": 123, "plan_id": 22040, "qihao": "20260918001"}

        self.executor._push_bet_exception_result(
            data,
            TimeoutError("request timed out"),
            start_time=1700000000.25,
            bet_started_at=1700000000,
        )

        pushed = self.executor._push_bet_result.call_args.args[1]
        self.assertEqual(pushed["task_status"], 3)
        self.assertEqual(pushed["bet_started_at"], 1700000000)
        self.assertEqual(pushed["bet_finished_at"], 1700000005)
        self.assertIn("timed out", pushed["err_msg"])

    def test_bet_retry_settings_are_bounded_and_configurable(self):
        self.executor._bet_config = Mock()
        self.executor._bet_config.get_config.side_effect = lambda key, fallback='': {
            'bet_max_attempts': '9',
            'bet_retry_delay': '7.5',
        }.get(key, fallback)

        self.assertEqual(self.executor._bet_retry_settings(), (5, 7.5))

    def test_default_bet_retry_settings_are_two_requests_and_seven_seconds(self):
        self.executor._bet_config = Mock()
        self.executor._bet_config.get_config.side_effect = ValueError('invalid config')

        self.assertEqual(self.executor._bet_retry_settings(), (2, 7.0))

    @patch('xy_client.services.Lucky5.betting.bet_executor.time.sleep')
    def test_connect_timeout_retries_once_after_configured_delay(self, sleep):
        self.executor._bet_config = Mock()
        self.executor._bet_config.get_config.side_effect = lambda key, fallback='': {
            'bet_max_attempts': '2',
            'bet_retry_delay': '8',
        }.get(key, fallback)
        session = Mock()
        response = Mock()
        session.post.side_effect = [
            requests.exceptions.ConnectTimeout('connect timeout'),
            response,
        ]

        result = self.executor._post_bet_with_retry(session, 'https://example.invalid/bet', 'amount=1', {})

        self.assertIs(result, response)
        self.assertEqual(session.post.call_count, 2)
        self.assertTrue(all(call.kwargs['allow_redirects'] is False for call in session.post.call_args_list))
        sleep.assert_called_once_with(8.0)

    @patch('xy_client.services.Lucky5.betting.bet_executor.time.sleep')
    def test_read_timeout_is_not_retried(self, sleep):
        session = Mock()
        session.post.side_effect = requests.exceptions.ReadTimeout('read timeout')

        with self.assertRaises(requests.exceptions.ReadTimeout):
            self.executor._post_bet_with_retry(session, 'https://example.invalid/bet', 'amount=1', {})

        session.post.assert_called_once()
        sleep.assert_not_called()

    @patch('xy_client.services.Lucky5.betting.bet_executor.time.sleep')
    def test_business_failure_response_is_final(self, sleep):
        session = Mock()
        response = Mock(status_code=200, text='{"Status":2}')
        session.post.return_value = response

        result = self.executor._post_bet_with_retry(session, 'https://example.invalid/bet', 'amount=1', {})

        self.assertIs(result, response)
        session.post.assert_called_once()
        sleep.assert_not_called()

    def test_bet_session_preserves_supplied_account_transport_settings(self):
        base_session = requests.Session()
        base_session.headers['X-Test-Header'] = 'account-session'
        base_session.proxies['https'] = 'http://127.0.0.1:7890'
        base_session.cookies.set('session-id', 'test-cookie', domain='example.invalid')

        bet_session = GlobalSession.get_bet_session(base_session)

        self.assertEqual(bet_session.headers['X-Test-Header'], 'account-session')
        self.assertEqual(bet_session.proxies['https'], 'http://127.0.0.1:7890')
        self.assertEqual(bet_session.cookies.get('session-id'), 'test-cookie')
        self.assertEqual(bet_session.get_adapter('https://').max_retries.total, 0)
        self.assertIsNot(bet_session.get_adapter('https://'), base_session.get_adapter('https://'))


if __name__ == "__main__":
    unittest.main()
