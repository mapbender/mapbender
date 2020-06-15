/**
 * Patched version of ol.control.ScaleLine that fixes upstream calculation issues on non-metric projections.
 * Based on OpenLayers 4 code Copyright 2005-present OpenLayers Contributors, licensed under BSD 2
 * @see https://raw.githubusercontent.com/openlayers/openlayers/master/LICENSE.md
 *
 * Calculation issues have been fixed upstream, but only after end of OpenLayers 4 maintenance.
 * @see https://github.com/openlayers/openlayers/pull/7700
 * @see https://github.com/openlayers/openlayers/pull/7908
 *
 * @param {Object} options
 * @constructor
 */

ol.control.ScaleLinePatched = (function() {
  if (ol.control.ScaleLine.prototype.createScaleBar) {
    // Openlayers 6. Base control is already behaving properly.
    // Provide ScaleLinePatched purely as an alias.
    return ol.control.ScaleLine;
  }

  function ScaleLinePatched(options) {
    ol.control.ScaleLine.call(this, options);
  }
  ScaleLinePatched.prototype = Object.create(ol.control.ScaleLine.prototype);
  ScaleLinePatched.prototype.constructor = ol.control.ScaleLinePatched;
  /**
   * Fixed updateElement_ method. Mostly Copy & paste of (quite monolithic) upstream version.
   * @private
   */
  ScaleLinePatched.prototype.updateElement_ = function() {
    var viewState = this.viewState_;

    if (!viewState) {
      if (this.renderedVisible_) {
        this.element_.style.display = 'none';
        this.renderedVisible_ = false;
      }
      return;
    }

    var center = viewState.center;
    var projection = viewState.projection;
    var units = this.getUnits();
    var pointResolutionUnits = units == ol.control.ScaleLineUnits.DEGREES ?
      ol.proj.Units.DEGREES :
      ol.proj.Units.METERS;
    var pointResolution =
        ol.proj.getPointResolution(projection, viewState.resolution, center, pointResolutionUnits);
  // Start of change vs upstream v4.6.5
    if (projection.getUnits() != ol.proj.Units.DEGREES && projection.getMetersPerUnit()
        && pointResolutionUnits == ol.proj.Units.METERS) {
  // End of change vs upstream v4.6.5
      pointResolution *= projection.getMetersPerUnit();
    }

    var nominalCount = this.minWidth_ * pointResolution;
    var suffix = '';
    if (units == ol.control.ScaleLineUnits.DEGREES) {
      var metersPerDegree = ol.proj.METERS_PER_UNIT[ol.proj.Units.DEGREES];
      if (projection.getUnits() == ol.proj.Units.DEGREES) {
        nominalCount *= metersPerDegree;
      } else {
        pointResolution /= metersPerDegree;
      }
      if (nominalCount < metersPerDegree / 60) {
        suffix = '\u2033'; // seconds
        pointResolution *= 3600;
      } else if (nominalCount < metersPerDegree) {
        suffix = '\u2032'; // minutes
        pointResolution *= 60;
      } else {
        suffix = '\u00b0'; // degrees
      }
    } else if (units == ol.control.ScaleLineUnits.IMPERIAL) {
      if (nominalCount < 0.9144) {
        suffix = 'in';
        pointResolution /= 0.0254;
      } else if (nominalCount < 1609.344) {
        suffix = 'ft';
        pointResolution /= 0.3048;
      } else {
        suffix = 'mi';
        pointResolution /= 1609.344;
      }
    } else if (units == ol.control.ScaleLineUnits.NAUTICAL) {
      pointResolution /= 1852;
      suffix = 'nm';
    } else if (units == ol.control.ScaleLineUnits.METRIC) {
      if (nominalCount < 0.001) {
        suffix = 'μm';
        pointResolution *= 1000000;
      } else if (nominalCount < 1) {
        suffix = 'mm';
        pointResolution *= 1000;
      } else if (nominalCount < 1000) {
        suffix = 'm';
      } else {
        suffix = 'km';
        pointResolution /= 1000;
      }
    } else if (units == ol.control.ScaleLineUnits.US) {
      if (nominalCount < 0.9144) {
        suffix = 'in';
        pointResolution *= 39.37;
      } else if (nominalCount < 1609.344) {
        suffix = 'ft';
        pointResolution /= 0.30480061;
      } else {
        suffix = 'mi';
        pointResolution /= 1609.3472;
      }
    } else {
      ol.asserts.assert(false, 33); // Invalid units
    }

    var i = 3 * Math.floor(
        Math.log(this.minWidth_ * pointResolution) / Math.log(10));
    var count, width;
    while (true) {
      count = ol.control.ScaleLine.LEADING_DIGITS[((i % 3) + 3) % 3] *
          Math.pow(10, Math.floor(i / 3));
      width = Math.round(count / pointResolution);
      if (isNaN(width)) {
        this.element_.style.display = 'none';
        this.renderedVisible_ = false;
        return;
      } else if (width >= this.minWidth_) {
        break;
      }
      ++i;
    }

    var html = count + ' ' + suffix;
    if (this.renderedHTML_ != html) {
      this.innerElement_.innerHTML = html;
      this.renderedHTML_ = html;
    }

    if (this.renderedWidth_ != width) {
      this.innerElement_.style.width = width + 'px';
      this.renderedWidth_ = width;
    }

    if (!this.renderedVisible_) {
      this.element_.style.display = '';
      this.renderedVisible_ = true;
    }
  };
  return ol.control.ScaleLine;
})();
