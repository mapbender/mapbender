from .aux import get_url


def loginroot(wd):
    wd.get(get_url("app_test.php/"))
    wd.find_element_by_link_text("Login").click()
    wd.find_element_by_id("username").click()
    wd.find_element_by_id("username").clear()
    wd.find_element_by_id("username").send_keys("root")
    wd.find_element_by_id("password").click()
    wd.find_element_by_id("password").clear()
    wd.find_element_by_id("password").send_keys("root")
    wd.find_element_by_css_selector("input.right.button").click()
