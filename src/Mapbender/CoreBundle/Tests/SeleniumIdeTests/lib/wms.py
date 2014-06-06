from .aux import get_url


def addwms(wd, url='http://osm-demo.wheregroup.com/service?REQUEST=GetCapabilities'):
    wd.find_element_by_link_text("Add source").click()
    wd.find_element_by_id("wmssource_originUrl").send_keys(url)
    wd.find_element_by_name("load").click()
    if not ("Your WMS has been created" in wd.find_element_by_tag_name("html").text):
        success = False
        print("verifyTextPresent failed")
