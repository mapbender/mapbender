import __main__
from os import getenv, makedirs
from os.path import basename, dirname, exists

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
