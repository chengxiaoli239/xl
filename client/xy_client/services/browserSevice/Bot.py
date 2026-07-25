import os

from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService


class Bot:

    def __init__(self, driver_path='../geckodriver.exe'):
        self.options = None
        self.driverPath = driver_path
        self.browser = self.createDriver()

    def setOptions(self):
        self.options.set_preference("devtools.debugger.remote-enabled", True)
        self.options.set_preference("devtools.debugger.prompt-connection", False)

    def createDriver(self):
        # 设置FirefoxOptions
        self.options = webdriver.FirefoxOptions()
        self.options.binary_location = r'C:\Program Files\Mozilla Firefox\firefox.exe'

        # 将geckodriver添加到系统PATH中
        os.environ['PATH'] = f"{os.environ['PATH']};{os.path.dirname(self.driver_path)}"
        self.browser = webdriver.Firefox(options=self.options)

        # 设置geckodriver的路径
        self.options.add_argument(f"webdriver.gecko.driver={self.driver_path}")

        print('||||||||||||||************************正在打开浏览器************************||||||||||||||')
        # 设置geckodriver的路径
        geckodriver_path = os.path.join(os.getcwd(), 'geckodriver.exe')
        print('geckodriver_path', geckodriver_path)

        return webdriver.Firefox(options=self.options)

    def getBot(self):
        return self.browser


from selenium import webdriver


class Browser:
    def __init__(self, browser_type, executable_path=None):
        self.browser_type = browser_type
        self.executable_path = executable_path
        self.driver = None

    def initialize_driver(self):
        if self.browser_type == 'chrome':
            if not self.executable_path or not os.path.isfile(self.executable_path):
                raise FileNotFoundError(
                    '必须配置本地chromedriver.exe；自动下载已禁用'
                )
            self.driver = webdriver.Chrome(
                service=ChromeService(executable_path=self.executable_path)
            )
        elif self.browser_type == 'firefox':
            #self.driver = webdriver.Firefox()
            geckodriver_path = os.path.join(os.getcwd(), 'geckodriver.exe')
            print('geckodriver_path', geckodriver_path)

            # 将geckodriver添加到系统PATH中
            os.environ['PATH'] = f"{os.environ['PATH']};{os.path.dirname(geckodriver_path)}"

            # 设置FirefoxOptions
            options = webdriver.FirefoxOptions()
            options.binary_location = r'C:\Program Files\Mozilla Firefox\firefox.exe'
            # 设置geckodriver的路径
            options.add_argument(f"webdriver.gecko.driver={geckodriver_path}")
            self.driver = webdriver.Firefox(options=options)

        else:
            raise ValueError("Unsupported browser type")

    def open_url(self, url):
        if not self.driver:
            self.initialize_driver()
        self.driver.get(url)

    def close_browser(self):
        if self.driver:
            self.driver.quit()

## 使用示例
## 假设你已经下载了对应浏览器的驱动，并知道其路径
# chrome_driver_path = "/path/to/chromedriver"
# firefox_driver_path = "/path/to/geckodriver"
#
## 启动Chrome浏览器
# chrome_browser = Browser('chrome', executable_path=chrome_driver_path)
# chrome_browser.open_url('https://www.google.com')
## ... 执行其他操作 ...
# chrome_browser.close_browser()

## 启动Firefox浏览器
# firefox_browser = Browser('firefox', executable_path=firefox_driver_path)
# firefox_browser.open_url('https://www.mozilla.org')
## ... 执行其他操作 ...
# firefox_browser.close_browser()
