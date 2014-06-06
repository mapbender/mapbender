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
    wd.find_element_by_link_text("Add source").click()
    wd.find_element_by_id("wmssource_originUrl").send_keys("http://osm-demo.wheregroup.com/service?REQUEST=GetCapabilities")
    wd.find_element_by_name("load").click()
    if not ("WMS OpenStreetMap (OSM) Demo WhereGroup" in wd.find_element_by_tag_name("html").text):
        success = False
        print("verifyTextPresent failed")
    wd.save_screenshot(get_sreenshot_path('addwms'))  # Changed
    logout(wd)
except Exception as e:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    wd.quit()
    raise e
finally:
    wd.quit()
    if not success:
        raise Exception("Test failed.")
