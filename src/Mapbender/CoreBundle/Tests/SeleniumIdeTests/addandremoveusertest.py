# -*- coding: utf-8 -*-
from selenium.webdriver.phantomjs.webdriver import WebDriver
from selenium.webdriver.common.action_chains import ActionChains
import time
from includes.loginroot import loginroot 

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
    loginroot(wd)
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
    wd.save_screenshot("./test.png")
finally:
    wd.save_screenshot("./error.png")
    wd.quit()
    if not success:
        raise Exception("Test failed.")
