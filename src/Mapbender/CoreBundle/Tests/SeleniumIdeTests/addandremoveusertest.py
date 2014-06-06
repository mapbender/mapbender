# -*- coding: utf-8 -*-
from selenium.webdriver.phantomjs.webdriver import WebDriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from lib.user import login
from lib.logout import logout
from lib.aux import get_sreenshot_path  # Changed

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
    wd.save_screenshot(get_sreenshot_path('test'))  # Changed
    WebDriverWait(wd, 10).until(
        EC.presence_of_element_located((By.LINK_TEXT, "Delete"))
    )
    if not ("Confirm delete" in wd.find_element_by_tag_name("html").text):
        success = False
        print("verifyTextPresent failed")
    wd.find_element_by_link_text("Delete").click()
    logout(wd)
    wd.save_screenshot(get_sreenshot_path('success'))  # Changed
except Exception as e:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    wd.quit()
    raise e
finally:
    wd.quit()
    if not success:
        raise Exception("Test failed.")
