# -*- coding: utf-8 -*-
from selenium.webdriver.phantomjs.webdriver import WebDriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from subprocess import call

from lib.user import login
from lib.logout import logout
from lib.utils import get_sreenshot_path, create_webdriver  # Changed

success = True
wd = create_webdriver()


def is_alert_present(wd):
    try:
        wd.switch_to_alert().text
        return True
    except:
        return False

try:
    login(wd)
    wd.find_element_by_css_selector("h1.contentTitle").click()
    wd.find_element_by_link_text("New user").click()
    wd.find_element_by_id("user_username").click()
    wd.find_element_by_id("user_username").clear()
    wd.find_element_by_id("user_username").send_keys("test")
    wd.find_element_by_id("user_email").click()
    wd.find_element_by_id("user_email").clear()
    wd.find_element_by_id("user_email").send_keys("testing@example.com")
    wd.find_element_by_id("user_password_first").click()
    wd.find_element_by_id("user_password_first").clear()
    wd.find_element_by_id("user_password_first").send_keys("test1234")
    wd.find_element_by_id("user_password_second").click()
    wd.find_element_by_id("user_password_second").clear()
    wd.find_element_by_id("user_password_second").send_keys("test1234")
    wd.find_element_by_css_selector("input.button").click()
    wd.find_element_by_css_selector("span.iconRemove.iconSmall").click()
    wd.find_element_by_link_text("Delete").click()
    logout(wd)
except Exception as e:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    raise e
finally:
    if not success:
        raise Exception("Test failed.")
