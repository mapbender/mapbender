# -*- coding: utf-8 -*-
from selenium.webdriver.phantomjs.webdriver import WebDriver
from selenium.webdriver.common.action_chains import ActionChains
import time

from lib import get_url, get_sreenshot_path  # Changed

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
    wd.get(get_url('app_test.php/'))  # Changed
    wd.find_element_by_link_text("Login").click()
    wd.find_element_by_id("username").click()
    wd.find_element_by_id("username").clear()
    wd.find_element_by_id("username").send_keys("root")
    wd.find_element_by_id("password").click()
    wd.find_element_by_id("password").clear()
    wd.find_element_by_id("password").send_keys("root")
    wd.find_element_by_css_selector("input.right.button").click()
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
    wd.save_screenshot(get_sreenshot_path('success'))  # Changed
except Exception as e:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    wd.save_screenshot('error.png')
    wd.quit()
    raise e
finally:
    wd.quit()
    if not success:
        raise Exception("Test failed.")
