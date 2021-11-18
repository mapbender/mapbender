# OWS Proxy
Proxy-aware http client for Mapbender.

## Features
* Forwards client requests via Mapbender server to support displaying intranet-only resources (Wms images etc)
* Hash-based request spoofing  protection; only forward requests to target URLs signed / allowed by Mapbender server
* Configurable proxy client; can reach resources only accessible via another proxy server 

![Sequence diagram](http://plantuml.com/plantuml/proxy?src=https://raw.githubusercontent.com/mapbender/mapbender/staging/3.3/src/OwsProxy3/communication.puml)

# Configuration

## Parameters
Owsproxy evaluates the following single configuration parameter (shown value is the default):
```yaml
# http user agent for outgoing requests (string)
owsproxy.useragent: OWSProxy3
```

## Extension configuration

The configuration is done in `app/config/config.yml` at `ows_proxy3_core` section.

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
        # proxy client exclusion list (list of strings or null)
        # Use host names or ip addresses
        noproxy: ~
```

Setting a non-empty `proxy`.`host` value makes Owsproxy act as a proxy client itself, passing
applicable outgoing requests over the configured `host`:`port`, optionally with basicauth credentials
`user`:`password`.

A null `proxy`.`host` setting disables proxy client functionality (client request forwarding is
retained).

## Note for *nix servers
On *nix systems it's preferrable to configure external proxies via [libcurl environment variables](https://curl.haxx.se/libcurl/c/libcurl-env.html),
and leaving Owsproxy's `host` setting empty.
Always make sure to provide environment variables in in both your user profile (for console commands / general sanity) and
to the web server serving the Mapbender / Owsproxy requests.

Owsproxy's own proxy client functionality should be regarded as a workaround
for systems where the libcurl environment cannot be set easily, or at all.

Libcurl's external proxy handling is more robust and more flexible
than Owsproxy. In particular, `NO_PROXY`, in addition to Owsproxy's `noproxy` setting, also supports domain
name suffixes and IP block ranges; it also supports configuring separate external proxies per protocol (i.e. http
vs https). Owsproxy's own implementation can only only exlude based on full domain names and full ip addresses.
