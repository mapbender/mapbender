# Parameters
Owsproxy evaluates the following single configuration parameter (shown value is the default):
```yaml
# http user agent for outgoing requests (string)
owsproxy.useragent: OWSProxy3
```
# Extension configuration
Owsproxy evaluates the extension configuration node `ows_proxy3_core`.
The defaults are:
```yaml
ows_proxy3_core:
    # external proxy to pass outgoing requests over (array sub-node)
    proxy:
        # hostname / ip (string or null)
        host: ~
        # port (integer or null)
        port: ~
        # connect timeout in seconds (integer or null for default)
        connecttimeout: 30
        # response timeout in seconds (integer or null for default)
        timeout: 60
        # basicauth credentials (both string or null)
        user: ~
        password: ~
        # enforce HTTPS certificate validity check (boolean)
        checkssl: true
        # hostname blacklist, bypassing external proxy (list of strings of null)
        noproxy: ~
```

Setting a non-empty `proxy`.`host` value makes Owsproxy act as a proxy client itself, passing
applicable outgoing requests over the configured `host`:`port`, optionally with basicauth credentials
`user`:`password`.

NOTE: On *nix systems it's preferrable to *not* configure any of the `proxy` values,
and instead configure external proxies via [libcurl environment variables](https://curl.haxx.se/libcurl/c/libcurl-env.html).
Make sure to set them in both your user profile (for console commands / general sanity) and
your web server serving the Mapbender / Owsproxy requests.

The entire `proxy` subnode should be regarded as a crutch for systems where the libcurl
environment cannot be set easily, or at all. Libcurl's external proxy handling is more robust and more flexible
than Owsproxy. In particular, `NO_PROXY`, in addition to Owsproxy's `noproxy` setting, also supports domain
name suffixes and IP block ranges; it also supports configuring separate external proxies per protocol (i.e. http
vs https).
