from .utils import get_url


def login(wd, name='root', password='root'):
    wd.get(get_url("/"))
    wd.find_element_by_link_text("Login").click()
    wd.find_element_by_id("username").click()
    wd.find_element_by_id("username").clear()
    wd.find_element_by_id("username").send_keys(name)
    wd.find_element_by_id("password").click()
    wd.find_element_by_id("password").clear()
    wd.find_element_by_id("password").send_keys(password)
    wd.find_element_by_css_selector("input.right.button").click()
