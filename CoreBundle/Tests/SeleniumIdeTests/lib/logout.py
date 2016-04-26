def logout(wd):
    wd.find_element_by_id("accountOpen").click()
    wd.find_element_by_link_text("Logout").click()
    if not ("Login" in wd.find_element_by_tag_name("html").text):
        success = False
        print("verifyTextPresent failed")
