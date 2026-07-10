# Using custom fonts in Mapbender print
The mapbender by default uses the font Helvetica for all text content that is dynamically inserted
while printing, e.g. for the title, the scale or the current date.

This documentation describes the steps necessary to use a custom font for these purposes.

- Copy your font file into the `application/config/MapbenderPrintBundle/fonts` directory. The following font types are supported:
  - ttf (True Type)
  - otf (Open Type)
  - pfb (Postscript Font Binary)
- Open a terminal and navigate (cd) into the fonts directory
- Execute the following command. Change the path to the font file and if applicable the desired encoding (for a list of available encodings, see [fpdf documentation](https://www.fpdf.org/en/tutorial/tuto7.htm); cp1252 is for central european languages)

```bash
cd application/config/MapbenderPrintBundle/fonts
php ../../../vendor/setasign/fpdf/makefont/makefont.php <your-font-file.ttf> cp1252
 ```

- This will create a .php and .z file with the same name in the same directory
- Repeat this process for all variants of the font (regular, bold, italic and/or bolditalic are supported variants)
- Configure your font in your `parameters.yaml` file using the key `mapbender.print.fonts`: Add a child object for each font family with the font's name as the key. The value should again be an object with the font style as key and the font name (without extension) as the value. Allowed keys are `regular`, `bold`, `italic` and `bolditalic`.
- To use the font, set the parameter `mapbender.print.font` to the font's key as configured in `mapbender.print.fonts`. Apart from the custom fonts, the default fonts helvetica (alias: arial), courier and  times are available. The default font can be overwritten for individual text fields:
  - `mapbender.print.font_legend`: Legend titles
  - `mapbender.print.font_scale_bar`: Scale bar text

Example configuration: 

```yaml
# config/parameters.yaml
parameters:
  # other values ...
  mapbender.print.fonts:
      roboto:
          regular: Roboto-Light
          bold: Roboto-Medium
  mapbender.print.font: roboto
  mapbender.print.font_legend: courier
```
