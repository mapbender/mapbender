## Extension configuration
The `fom_manager` extension configuration accepts a single sub-entry `route_prefix` (string scalar).

`route_prefix` is the URL prefix for most Mapbender backend pages and interactions.

The default is `/manager`

Example:
```yaml
fom_manager:
  route_prefix: /admin
```
