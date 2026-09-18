import unittest
from unittest.mock import Mock, patch

from xy_client.services.Lucky5.betting.bet_executor import BetExecutor


class BetExecutionTimingTest(unittest.TestCase):
    def setUp(self):
        self.executor = object.__new__(BetExecutor)
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


if __name__ == "__main__":
    unittest.main()
