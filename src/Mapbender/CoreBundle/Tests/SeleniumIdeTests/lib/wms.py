from .aux import get_url
from selenium.webdriver.support.wait import WebDriverWait

def addwms(wd, url='http://osm-demo.wheregroup.com/service?REQUEST=GetCapabilities'):
    wd.find_element_by_link_text("Add source").click()
    wd.find_element_by_id("wmssource_originUrl").send_keys(url)
    wd.find_element_by_name("load").click()
    if not ("Your WMS has been created" in wd.find_element_by_tag_name("html").text):
        raise Exception("verifyTextPresent failed:\n" + wd.find_element_by_tag_name("html").text)

def is_stale(elm):
    try:
        elm.is_displayed()
        return False
    except:
        return True

def check_has_class(elm, name):
    return name in elm.get_attribute('class')

def deletewms(wd):
    if not ("Sources" in wd.find_element_by_tag_name("html").text):
        raise Exception("verifyTextPresent failed: Sources")
    wd.find_element_by_link_text("Sources").click()
    if not (len(wd.find_elements_by_css_selector("span.iconRemove.iconBig")) != 0):
        raise Exception("verifyTextPresent failed: span.iconRemove.iconBig")
    wd.find_element_by_css_selector("span.iconRemove.iconBig").click()
    elm = wd.find_element_by_class_name('ajaxWaiting')
    if (elm.is_displayed()):
        WebDriverWait(wd, 10).until(lambda d: not check_has_class(elm, 'ajaxWaiting'))
    elm = wd.find_element_by_link_text("Delete")
    elm.click()
    WebDriverWait(wd, 10).until(lambda d: is_stale(elm))
    with open('/tmp/wh', 'w') as f:
        f.write(wd.find_element_by_class_name('flashBox').text)
    if not ("Your WMS has been deleted" in wd.find_element_by_class_name("flashBox").text):
        raise Exception("verifyTextPresent failed: Your WMS has been deleted")
