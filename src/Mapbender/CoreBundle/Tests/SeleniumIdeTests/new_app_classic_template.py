# -*- coding: utf-8 -*-

from lib.user import login
from lib.logout import logout
from lib.utils import get_sreenshot_path, create_webdriver  # Changed
from subprocess import call, check_output

success = True
wd = create_webdriver()


def is_alert_present(wd):
    try:
        wd.switch_to_alert().text
        return True
    except:
        return False

try:
    print check_output(["curl", "http://localhost:8000"])
    login(wd)
    wd.find_element_by_link_text("New application").click()
    wd.find_element_by_id("application_title").send_keys("testing classic template")
    wd.find_element_by_id("application_slug").send_keys("testing_classic_template")
    wd.save_screenshot('/tmp/classic.png')
    wd.find_element_by_id("application_description").send_keys("run a test to create a new application based on the classic template")
    wd.find_element_by_css_selector("input.button").click()
    if not ("testing classic template" in wd.find_element_by_tag_name("html").text):
        raise Exception("find_element_by_tag_name failed: testing classic template")
    logout(wd)
except Exception as e:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    raise e
finally:
    if not success:
        raise Exception("Test failed.")
