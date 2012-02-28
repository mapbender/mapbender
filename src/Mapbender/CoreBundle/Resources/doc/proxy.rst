Proxy Service Configuration
===========================

The proxy service ``mapbender.proxy`` can be used whenever you need to proxy
a Request object and will return a Response object. The service is implemented
using PHP cUrl extension and can be configured to use a - ehm - proxy for it's
requests.

The URL is determined by checking the ``url`` parameter of the request object.
If none is given, a RuntimeException will occur. Any other parameter given to
the request object will be passed into the proxy request.

To use the service from the client, use the ``mapbender_core_proxy_open`` route
which will only check that an session has been started before (by checking the
``proxyAllowed`` attribute of the session).

The proxy configuration can be set in the ``config.yml`` in the
``mapbender_core`` section. To proxy trough a host called ``proxyhost`` on port
``8080`` with a user ``proxyuser`` and password ``proxypasswd`` the following
configuration would be used:

::
mapbender_core:
    proxy:
        host: proxyhost
        port: 8080
        user: proxyuser
        password: proxypasswd

Anything but the ``proxy.host`` setting is optional. To not use a proxy for the
``mapbender.proxy`` service, just omit the proxy configuration in the
``mapbender_core`` section of your ``config.yml``.

The noproxy Option
------------------

Additionally to the options described above, a ``noproxy`` option can be given
in the form of an array of hostnames and/or IP addresses which then will never
be routed trough the configured proxy. An example::

mapbender_core:
    proxy:
        host: proxyhost
        port: 8080
        user: proxyuser
        password: proxypasswd
        noproxy:
            - myinternalhost
            - 10.10.1.4

