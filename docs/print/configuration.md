# Print Configuration

This table lists configuration options that can be set up in your parameters.yaml to customize the pdf print component.

| Parameter                                    | Default Value | Description                                                                                                                            |
|----------------------------------------------|---------------|----------------------------------------------------------------------------------------------------------------------------------------|
| mapbender.print.font                         | helvetica     | key of the default font. Either [key of custom font](custom-fonts.md) or one of the defaults: helvetica (alias: arial), courier, times |
| mapbender.print.fonts                        | null          | Custom fonts, see [documentation for details](custom-fonts.md)                                                                         |
| mapbender.print.font_scale_bar               | null          | key of font to be used for the scale bar, falls back to default font if not defined                                                    |
| mapbender.print.font_legend                  | null          | key of font to be used for legend headings, falls back to default font if not defined                                                  | 
| mapbender.print.font_geojson                 | null          | key of font to be used for labels in geojson layers (e.g. sketch), falls back to default font if not defined                           | 
| mapbender.print.font_size                    | 10            | default font size, e.g. for legend labels. in pt                                                                                       |
| mapbender.print.font_size_scale_bar          | 8             | font size for scale bar, in pt                                                                                                         |
| mapbender.print.font_style_scale_bar         | null          | font style for scale bar, combination of B (bold), I (italic) or U (underline)                                                         |
| mapbender.print.scale_bar_height             | 2             | height of the scale bar itself, in px                                                                                                  |
| mapbender.print.legend_margin_x              | 5             | horizontal margin between page edge and legend and between legends, in mm                                                              |
| mapbender.print.legend_margin_y              | 10            | vertical margin between legends, in mm                                                                                                 |
| mapbender.print.legend_margin_y_page         | 5             | vertical margin between the page edge and the legends, in mm                                                                           |
| mapbender.print.legend_margin_title_to_image | 0             | vertical margin between legend titles and the legend image, in mm                                                                      |


[↑ Back to top](#print-configuration)

[← Back to README](../README.md)
