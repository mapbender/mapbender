# Changelog

* **v3.0.2.0** -
  - Signer for OwsProxy added
  - Properties for regions added
  - Sketch feature (circle) added
  - Update layertree changed
  - Funktion Model.changeLayerState added
  - LoadWms load declarative WMS added
  - Dispatcher for declarative Sources added
  - Dropdown lists are now scrollable
  - Micro designfixes
  - Search router design added
  - New button icons for wmc editor and loader added
  - console.* stubs
  - Proxy security: Only pass correctly signed URLs
  - Allow for multiple application YAML files

## Release History

* **v3.0.1.1** - 2013-09-26
  - The development controller app_dev.php is limited to localhost again

* **v3.0.1.0** - 2013-09-12
  - Fixed visibility toggle for elements and layers
  - Hide sidepane if empty
  - Parameter/Service 'mapbender.proxy' removed
  - Parameter 'mapbender.uploads_dir' added
  - Application's directory added
  - Added wgs84 print
  - Added printclient parameter file_prefix
  - Added default action for elements
  - Splited `frontend/components.js` into `sidepane.js` and `tabcontainer.js`
  - Remove unused images references
  - New popup architecture
  - Add application dublication
  - Prepare `collection.js` for dynamic element properties (full support in next versions)
  - Fix some micro css bugs
  - Map scale bugs fixes
  - Move checkbox script into `checkbox.js`
  - Merge checkbox frontend and backend script
  - Move dropdown script into `dropdown.js`
  - Merge dropdown frontend and backend script
  - Fix some dropdown bugs
  - Fix some layertree css bugs
  - Fix some popup css bugs
  - Micro design fixes
  - Remove unused jQuery-UI CSS
  - Add more translation wraps
  - Add `widget_attribute_class` macro for forms
  - Element position moved from `mapbender_theme.scss` to `fullscreen.scss`
  - Add new frontend template - Fullscreen alternative
  - Frontend jQuery upgrade to 1.9.1/1.10.2 (jQuery UI)

* **v3.0.0.2** - 2013-07-19
  - Removed incorrect feature info function `create`
  - Set overlay `position` to `fixed`
  - PrintClient Admintype added
  - PrintClient Configuration Parameter changed
  - Instance view - order of `on` and `allow` changed
  - Disable WMCBundle - Available in the next versions
  - Parameter unitPrefix added to ScaleDisplay
  - normalize.css compressed
  - Popup decrease `max-height`
  - Forgot, Register success and error messages designed
  - Restructured login, forgot and success templates
  - Elements overview is sorted by asc
  - Wmsloader popup - set fix `width`
  - Design added to ScaleDisplay
  - Fixed manager logo positioning
  - Fixed design of print client and map forms
  - Fixed double *delete* label at layers and elements
  - Fixed html and body `height`
  - Fixed Firefox font bug
  - Fixed printclient tooltip bug
  - Fixed ScaleDisplay position bug
  - Added POI (0...n) and BBOX URL parameter handling
  - Fixed ACL creation during user creation (#52)
  - Fixed ACL creation during group creation (#53)
  - Enhanced ACL creation during service creation (#54)
  - Honor published attribute on YAML-defined applications (#42)

* **v3.0.0.1** - 2013-06-07

* **v3.0.0.0** - 2013-05-31
  - First version
