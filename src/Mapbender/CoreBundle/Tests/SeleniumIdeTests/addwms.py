# -*- coding: utf-8 -*-
from selenium.webdriver.firefox.webdriver import WebDriver
#from selenium.webdriver.phantomjs.webdriver import WebDriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from lib.user import login
from lib.logout import logout
from lib.aux import get_sreenshot_path  # Changed
from lib.wms import addwms

success = True
wd = WebDriver()
wd.implicitly_wait(60)


def is_alert_present(wd):
    try:
        wd.switch_to_alert().text
        return True
    except:
        return False

try:
    login(wd)
    addwms(wd)
    logout(wd)
except Exception as e:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    wd.quit()
    raise e
finally:
    wd.quit()
    if not success:
        raise Exception("Test failed.")
