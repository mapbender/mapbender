def loginroot(wd):
    wd.get("http://localhost/data/mapbender-starter/application/web/app_dev.php/")
    wd.find_element_by_link_text("Login").click()
    wd.find_element_by_id("username").click()
    wd.find_element_by_id("username").clear()
    wd.find_element_by_id("username").send_keys("root")
    wd.find_element_by_id("password").click()
    wd.find_element_by_id("password").clear()
    wd.find_element_by_id("password").send_keys("root")
    wd.find_element_by_css_selector("input.right.button").click()

