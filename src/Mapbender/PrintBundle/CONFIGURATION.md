Container parameters of note in the PrintBundle. Any of the values named below can be modified
via parameters.yml. Use the names exactly as given (dots _do_ _not_ imply array nesting).
# WMS tiling
## GetMap limits
This is for WMS services that stop returning images if the requested pixel dimensions get too large.

`mapbender.imaageexport.renderer.wms.max_getmap_size` (default 8192) sets the largest possible `WIDTH=`
and `HEIGHT=` parameter values for WMS requests generated from printing and ImageExport.

`WIDTH=` and `HEIGHT=` parameters can also be limited separately. Use `mapbender.imaageexport.renderer.wms.max_getmap_size.x`
for the `WIDTH=` limit, and `mapbender.imaageexport.renderer.wms.max_getmap_size.y` for the `HEIGHT=` limit.  

## Tile buffer
This is to essentially throw away a ring of pixels around every requested WMS tile, to counteract
"smart" label placement mechanisms in WMS services.  
Our general recommendation for WMS services you can fully control yourself is to configure them
for fully deterministic label placement which may only depend on scale and potentially font sizing
options. Any amount of discarded pixels is an efficiency loss. With reliably placed labels, you
can avoid this loss by turning the tile buffer down to zero pixels.

`mapbender.imaageexport.renderer.wms.tile_buffer` (default 512) sets the amount of pixels to throw
away on both x and y axes.  
For typical western left-to-right text, it may be convenient to control buffering separately per
axis. Use `mapbender.imaageexport.renderer.wms.tile_buffer.x` for the horizontal buffer amount and
`mapbender.imaageexport.renderer.wms.tile_buffer.y` for vertical.

# Directories
ImageExport and print require temporary files. These are by default stored in the system temp directory
autodetected via [`sys_get_temp_dir()`](https://www.php.net/manual/en/function.sys-get-temp-dir.php).
This directory may not be accessible or detection may fail on certain configurations.

Use `mapbender.imageexport.temp_dir` (string; default `null` for auto-detection) to set a valid,
writable temp directory instead.

Use `mapbender.imageexport.resource_dir` (string; default `%kernel.root_dir%/Resources/MapbenderPrintBundle`)
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

## Memory limit
The main motivation for queued print is to support more resource-intensive jobs with long execution
times. These jobs may also require a lot of memory to finish.

Use `mapbender.print.queue.memory_limit` (string; default 1G) to increase the maximum allowed memory
specifically during print job execution. Any value syntax valid in a php.ini (e.g. `512M`, `2G`, `2048M` etc)
should work here.
