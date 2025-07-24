#!/usr/bin/node
// requires the env variables NODE_PATH=$(npm root -g) and MB_VT_PRINT_CONFIG

const puppeteer = require('puppeteer');
const fs = require('fs');

const headless = true; // change to false for debugging

/**
 * @typedef {Object} PrintConfig
 * @property {number[]} bbox - Bounding box coordinates as an array of numbers.
 * @property {number} width - viewport width in pixels.
 * @property {number} height - viewport height in pixels.
 * @property {string} [referer] - Optional referer string for HTTP headers.
 * @property {string} dpi - Resolution in DPI (dots per inch).
 * @property {string} [scaleCorrection] - Optional multiplier for the scale, e.g. '1.5' for 150% scale.
 * @property {string} styleUrl - URL to the Mapbox style JSON.
 * @property {number} [timeout] - Optional timeout value in milliseconds.
 */

/** @type {PrintConfig} */
const printConfig = JSON.parse(process.env.MB_VT_PRINT_CONFIG || '{}');
const timeout = (printConfig.timeout || 120) * 1000;
const scale = printConfig.dpi / 100 * printConfig.scaleCorrection; // Convert DPI to scale factor, having 100 DPI as the base scale

const html = `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>OpenLayers Vector Tile Map</title>
  <style>
    html, body, #map {
      margin: 0;
      padding: 0;
      width: ${Math.round(printConfig.width / scale)}px;
      height: ${Math.round(printConfig.height / scale)}px;
      overflow: hidden;
    }
  </style>
</head>
<body>
  <div id="map"></div>
</body>
</html>
`;

(async () => {

    const olJs = fs.readFileSync('vendor/mapbender/openlayers6-es5/dist/ol.js', 'utf8');

    const browser = await puppeteer.launch({
        defaultViewport: {width: Math.round(printConfig.width / scale), height: Math.round(printConfig.height / scale), deviceScaleFactor: scale},
        headless: headless,
    });
    const page = await browser.newPage();
    if (printConfig.referer) {
        await page.setExtraHTTPHeaders({ referer: printConfig.referer})
    }

    await page.setContent(html, {waitUntil: 'networkidle0'});
    await page.addScriptTag({content: olJs});

    await page.evaluate((printConfig, timeout) => {
        const map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.MapboxVector({
                    styleUrl: printConfig.styleUrl,
                }),
            ],
            controls: [],
            view: new ol.View({
                extent: printConfig.bbox,
                projection: 'EPSG:3857',
            })
        });
        map.getView().fit(printConfig.bbox, {size: map.getSize()});

        window.tilesLoading = 0;
        window.tilesLoaded = false;

        map.getLayers().forEach(layer => {
            if (layer instanceof ol.layer.VectorTile) {
                const source = layer.getSource();
                source.on('tileloadstart', () => {
                    window.tilesLoading++;
                });
                source.on('tileloadend', () => {
                    window.tilesLoading--;
                    if (window.tilesLoading === 0) {
                        window.tilesLoaded = true;
                    }
                });
                source.on('tileloaderror', () => {
                    window.tilesLoading--;
                    if (window.tilesLoading === 0) {
                        window.tilesLoaded = true;
                    }
                });
            }
        });


        // Fallback: set status after a delay
        setTimeout(() => {
            window.tilesLoaded = true;
        }, timeout - 500);
    }, printConfig, timeout);

    // Wait for rendering (either event or timeout)
    await page.waitForFunction('window.tilesLoaded === true', {timeout: timeout});

    const screenshot = await page.screenshot({type: 'png', omitBackground: true, encoding: 'base64'});
    if (headless) await browser.close();
    console.log(screenshot.toString());
})();
