import os
import unittest

os.environ.setdefault("QT_QPA_PLATFORM", "offscreen")

from PyQt5 import QtWidgets

from xy_client.services.ui.LuckyClientOppSite import Ui_MainWindow
from xy_client.services.ui.client_theme import apply_main_window_theme


class PreviewWindow(QtWidgets.QMainWindow, Ui_MainWindow):
    def query_bet_details(self):
        pass


class ClientThemeTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.app = QtWidgets.QApplication.instance() or QtWidgets.QApplication([])

    def test_account_tabs_and_query_controls_fit(self):
        window = PreviewWindow()
        try:
            window.setupUi(window)
            apply_main_window_theme(window)
            window.show()
            tabs = window.findChild(QtWidgets.QTabWidget, "accountTabs")

            self.assertEqual([tabs.tabText(i) for i in range(tabs.count())],
                             ["用户", "代理", "查询"])
            self.assertIs(tabs.widget(0), window.UserInfo)
            self.assertIs(tabs.widget(1), window.ProxyInfo)
            self.assertIs(tabs.widget(2), window.QueryGroup)

            tabs.setCurrentIndex(2)
            self.app.processEvents()
            self.assertFalse(window.period_val.geometry().intersects(
                window.category_label.geometry()))
            self.assertFalse(window.category_combo.geometry().intersects(
                window.query_btn.geometry()))
        finally:
            window.close()


if __name__ == "__main__":
    unittest.main()
