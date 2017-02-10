import __main__
from selenium.webdriver.phantomjs.webdriver import WebDriver
from os import getenv, makedirs
from os.path import basename, dirname, exists
from subprocess import call

def get_url(path):
    """Build full URL to test server based on path and env vars"""
    return 'http://%(host)s:%(port)s/%(path)s' % {
        'host': getenv('TEST_WEB_SERVER_HOST', '127.0.0.1'),
        'port': getenv('TEST_WEB_SERVER_PORT', 8000),
        'path': path
    }


def get_sreenshot_path(suffix):
    path = '%(base)s/%(test)s_%(suffix)s.png' % {
        'base': getenv('TEST_SCREENSHOT_PATH', '/tmp'),
        'test': basename(__main__.__file__).strip(".py"),
        'suffix': suffix
    }
    if not exists(dirname(path)):
        makedirs(dirname(path))
    return path

def create_webdriver():
    wd = WebDriver('/home/travis/build/mapbender/mapbender-starter/application/bin/phantomjs')

    wd.set_window_size(1400,1000)
    wd.implicitly_wait(300)
    return wd
