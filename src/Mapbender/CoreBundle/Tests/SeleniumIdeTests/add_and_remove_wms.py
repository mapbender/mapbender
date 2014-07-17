# -*- coding: utf-8 -*-

from lib.user import login
from lib.logout import logout
from lib.aux import get_sreenshot_path, create_webdriver  # Changed
from lib.wms import addwms
from lib.wms import deletewms

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
    addwms(wd)
    deletewms(wd)
    logout(wd)
except:  # Changed ff
    wd.save_screenshot(get_sreenshot_path('error'))
    wd.quit()
    raise
finally:
    wd.quit()
    if not success:
        raise Exception("Test failed.")
