Container parameters of note in the PrintBundle. Any of the values named below can be modified
via parameters.yaml. Use the names exactly as given (dots _do_ _not_ imply array nesting).
# WMS tiling
## GetMap limits
This is for WMS services that stop returning images if the requested pixel dimensions get too large.

`mapbender.imageexport.renderer.wms.max_getmap_size` (default 8192) sets the largest possible `WIDTH=`
and `HEIGHT=` parameter values for WMS requests generated from printing and ImageExport.

`WIDTH=` and `HEIGHT=` parameters can also be limited separately. Use `mapbender.imageexport.renderer.wms.max_getmap_size.x`
for the `WIDTH=` limit, and `mapbender.imageexport.renderer.wms.max_getmap_size.y` for the `HEIGHT=` limit.  

## Tile buffer
This is to essentially throw away a ring of pixels around every requested WMS tile, to counteract
"smart" label placement mechanisms in WMS services.  
Our general recommendation for WMS services you can fully control yourself is to configure them
for fully deterministic label placement which may only depend on scale and potentially font sizing
options. Any amount of discarded pixels is an efficiency loss. With reliably placed labels, you
can avoid this loss by turning the tile buffer down to zero pixels.

`mapbender.imageexport.renderer.wms.tile_buffer` (default 512) sets the amount of pixels to throw
away on both x and y axes.  
For typical western left-to-right text, it may be convenient to control buffering separately per
axis. Use `mapbender.imageexport.renderer.wms.tile_buffer.x` for the horizontal buffer amount and
`mapbender.imageexport.renderer.wms.tile_buffer.y` for vertical.

# Directories
ImageExport and print require temporary files. These are by default stored in the system temp directory
autodetected via [`sys_get_temp_dir()`](https://www.php.net/manual/en/function.sys-get-temp-dir.php).
This directory may not be accessible or detection may fail on certain configurations.

Use `mapbender.imageexport.temp_dir` (string; default `null` for auto-detection) to set a valid,
writable temp directory instead.

Use `mapbender.imageexport.resource_dir` (string; default `%kernel.project_dir%/config/MapbenderPrintBundle`)
to control where ImageExport and print look for fonts, "dynamic_image" resources and certain built-in
images like the north arrow.

Use `mapbender.print.template_dir` (string; default `<configured resource dir>/templates`) to control where
ODG templates are loaded from.

# Queued print
Queued print is disabled by default because it requires some external integration setup (cron jobs
or similar; see [PR#1070](https://github.com/mapbender/mapbender/pull/1070)).
Set parameter `mapbender.print.queueable` to `true` to enable queued printing.
This adds a new `renderMode` configuration field to the PrintClient Element backend form, where queued
operation can now be selected.

For YAML-defined applications, the allowable values for `renderMode` are either `direct` or `queued`.

## Storage path
Use `mapbender.print.queue.storage_dir` (string; default <webroot>/prints) to control where
PDFs generated from queued print jobs are stored.

## Separate load_path
To support file forwarding from a "print queue server" installed separately from the browser-facing Mapbender
installation, the path where PDFs are loaded from can be configured separately with the parameter
`mapbender.print.queue.load_path` (string; default same as `mapbender.print.queue.storage_dir`).

Unlike the storage_dir parameter, load_path allows urls.

One example use case for urls is to keep the separate "print queue servers" storage_dir accessible under its
web root, and set an appropriate http url into the load_path of the browser-facing frontend
Mapbender install.

Do note that in any case, a separately installed "print queue server" _must_ share the default
database with the browser-facing frontend Mapbender installation.

# Memory limit
Print job execution may require more memory than generally available to PHP to
finish. Mapbender can attempt to increase the PHP memory limit at runtime via
`ini_set` before a print job starts.

The main motivation for queued print is to support more resource-intensive jobs with
long execution times. These jobs tend to require even more memory than direct print
jobs, so the appliedmemory limit is configurable separately.

Use `mapbender.print.memory_limit` (string or null; default null) for the memory limit
used in direct print job execution. The default `null` means no attempt to touch the memory
limit. The memory limit set via php.ini or SAPI configuration will apply.

In addition to null, any value syntax valid in a php.ini (e.g. `512M`, `2G`, `2048M` etc) should work here.
Use `-1` for unlimited memory consumption.

Use `mapbender.print.queue.memory_limit` (string; default 1G) to increase the maximum allowed memory
specifically during execution of queued print jobs. This parameter _does_ _not_
support a null value.
