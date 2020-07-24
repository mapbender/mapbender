## 3.2.0-RC2
* Fix Ruler displaying too few intermediate line segment measures
* Fix FeatureInfo only displaying single service response with `showOriginal`=false
* Fix Layertree layer title coloring regression
* Fix ScaleBar coloring in toolbar
* Fix inconsistency of toolbar button colors and hover effects vs ZoomBar in mobile template
* Fix vertically misaligned checkmarks in user / group selection list when assigning access privileges
* Fix ImageExport + Print edge-case error (viewport / min/max scale dependent) calculating label resolution params
* Fix SimpleSearch layout when used in toolbar

## 3.2.0-RC1 (Changelog WIP)
* Add configurable per-Application engine code (OpenLayers 2 or OpenLayers 6)
* Added reusable source instances; configure once, share between many applications
* Ruler: display polygon area in-place as a polygon label
* SimpleSearch: remove requirement on (current) map CRS being equal to server data CRS
* ZoomBar: add step-wise rotation and north arrow
* Redlining: add circle drawing tool
* Redlining: support labels on every type of feature
* Print: fix incompatibilities with PHP 7.4
* Wmts: fix configuration generation error on PHP 7.4

### Layouting changes
* Properly flow multiple map overlaying Elements in the same corner ("Anchor")

### Removed functionality
* ZoomBar: legacy incremental panning arrows
* ZoomBar: legacy "zoom_box" navigation workflow
* Ruler: "immediate" option (now always behaves as if immediate is on)
* ScaleBar: simultaneous dual-unit display
* Layertree "hidenottoggleable" option; superseded by instance layer settings
* "Classic" template
* Legacy (buggy) "Responsive" template
* SuggestMap element, WmcLoader / WmcList / WmcEditor elements
#### Technical removals
* Layer attribute emulation for legacy `mqlid` and `ollid` properties
* Client-side Source property `origId`


## dev-release/3.0.7 @ a050b64d8
- Fix mbmapclick event data (FeatureInfo, CoordinatesUtility etc) on mobile ([PR#1243](https://github.com/mapbender/mapbender/pull/1243))
- Restore keyboard arrow keys map panning
- Fix compiler discovery of newly added Yaml applications
- Fix error when deleting Wmts source with instances
- Fix error when using a new Wmts instance without explicitly saving its instance form first

## v3.0.8.6-RC1
- [Regression] Fix FeatureInfo print with "showOriginal" option
- [Regression] Fix missing ScaleSelector visual update when zooming map by non-ScaleSelector methods
- Fix Wms source reload errors when the Wms added a new group layer ([#1234](https://github.com/mapbender/mapbender/issues/1234), [PR#1238](https://github.com/mapbender/mapbender/pull/1238))
- Fix bad ordering of Wms instance layers in form if layer ordering priority is not fully initialized ([#1236](https://github.com/mapbender/mapbender/issues/1236), [PR#1239](https://github.com/mapbender/mapbender/pull/1239))
- Fix random WMS source layer reordering on reload
- Fix random WMS instance layer reordering on source reload
- Fix new WMS group layers only appearing after reloading the source twice
- Fix POI coordinate rounding
- Fix excessive Ruler output with type `line` and `immediate` enabled
- Fix SearchRouter Symfony 3.4 incompatibility with common configs
- Fix encoding errors in Gps error messages
- Fix translations of login errors
- Fix multiple-emission of icon CSS rules depending on Application content
- Fix Symfony 3.4 debug-mode backend errors when working with Applications containing unknown Element classes
- Fix conflicts of legacy custom dropdown widget with Bootstrap JavaScript
- Fix login issues when embedding Mapbender in a frame
- Fix toolbar button centering when showing only icon but no label
- Fix untranslated popup headings in Mobile template
- [PrintClient]: Fully reinitialize selection rectangle scale and center on each activation
- [PrintClient]: Replace manual `type` configuration with auto-detection
- [PrintClient]: remove comment fields from default configuration (not printable with shipping default templates)
- [GpsPosition]: Fix defaults for tooltip and icon options
- [GpsPosition]: remove "refreshinterval" option (unused since v3.0.5.3)
- [Backend] Fix form change discard confirmation when going "back" to Element type list from a modified Element form
- [Backend] Fix double-display of collection adding icon when editing empty Element permissions set
- [Backend] Add translation for form change discard confirmation when leaving a modified Element form
- [Backend] Avoid form resubmit confirmation when refreshing source instance form after saving
- Update "map-click" demo element
- Misc fixes to jQueryUI standard / theme css compatibility
- Removed ineffective legacy configuration fields ZoomBar `position`, PrintClient `type`
- [Scripting] add `mapbender:wms:add` command to load a new WMS source
- [Scripting] add `mapbender:wms:show` command to inspect layer structure in a persistent WMS source
- [Scripting] add `mapbender:wms:parse:url` and `mapbender:wms:parse:file` commands to inspect layer structure in a GetCapabilities document
- [Scripting] add `mapbender:wms:reload:url` and `mapbender:wms:parse:file` to update a persistent WMS source with a new GetCapabilities document
- [Framework] Client-side Popup now supports opening without any buttons
- [Framework] custom dropdown widget visual-only update can now be triggered with `dropdown.changevisual` custom event

## v3.0.8.5
### Regression fixes
- Fix broken layerset order saving in Map administration
- Fix missing WMS data when querying a layer with name "0" (broken in v3.0.8.2)
- Fix PHP strict warning when editing / creating a LayerTree Element
### Other functional fixes
- Disable sqlite foreign keys when running doctrine:schema:update command for safety
- Fix unreliable / broken initial map srs configurations depending on database response order
- Fix PostgreSQL 10 incompatibilities when running `doctrine:schema:update` et al
- Fix invalid relative urls in cached css when switching base url (e.g. url with "app.php" vs without script name)
- Fix invalid relative urls in generated application css when running Mapbender in a "subdirectory url" (see UPGRADING.md for potential conflicts with old workarounds)
- Fix broken map scales configuration if loaded config contains non-contiguous array
- Fix twig 2.x incompatibility in TwigConstraintValidator (applied HTML Element content field); clean up various twig deprecations
- Fix nonfunctional CSS minification in `prod` environment ([PR#1219](https://github.com/mapbender/mapbender/pull/1219))
- Add missing grants check for instance enable toggle / instance reordering actions (requires `EDIT` on containing Application)
- Resolve misc form type, service configuration and other incompatibilities with Symfony 3
- [FeatureInfo] fix validation of HTML documents where every tag has attributes
- [PrintClient] fix missing data if form is submitted by pressing Enter key
- [PrintClient] prevent form submit in sidepane if selection rectangle is inactive
- [PrintClient] Fix selection rectangle recentering on change of scale dropdown or rotation field
- [Backend] Fix internal server error when submitting PrintClient configuration form with invalid values
- [Backend] Fix invalid application export data format for WMS legend and metadata url sub-objects
- [Backend] Fix errors on import of previously broken application export formats
- [Backend] SourceInstance opacity field: reduce step to default 1 to prevent HTML5 form validation failures
- [Backend] Maintain backend element form confirmation on close behaviour after submitting once with validation errors
- [Backend] Fix login form submit url if login form is triggered through non-login url (e.g. editing Element after session expiry / logging out in a different tab)
- [Backend] remove control over ineffective _global_ Element grants (never checked; grants on concrete Elements in concrete Applications remain effective)
- [Framework] Fix missing .dropdownValue visual update on "changed" event
- [Framework] Fix missing .dropdownValue visual update when value changes on form reset ([#1214](https://github.com/mapbender/mapbender/issues/1214))
- [Framework] Fix progressive slowdown caused by repeated reinitialization of tab container / accordion widgets
### New / extended functionality
- Support dynamic vendor specifics value substitutions with arbitrary prefix / postfix strings
- Show dependent applications and instances in source view (as a new "Applications" tab)
- Show affected applications and instances in source deletion confirmation popup
- When cloning DB applications, also clone access control rules
- Automatically assign owner permission for current user on cloned application ([PR#1207](https://github.com/mapbender/mapbender/pull/1207))
- Allow privileged users to access non-published Yaml-based applications
- Support viewing Yaml-based applications with `published: false` for users with appropriate privileges (root user, global Application view grant, or passing Yaml-Application-specific role check)
- Support accessing non-published Yaml-based application in clone and export cli commands
- [Framework] Support direct message key and wildcard key prefixes as Element / Template translation requirement inputs ([PR#1208](https://github.com/mapbender/mapbender/pull/1208))
- [Framework] add engine-agnostic `mbmapclick` event
### Visual fixes and changes - frontend
- Fix translations of login errors ([PR#1206](https://github.com/mapbender/mapbender/pull/1206))
- [FeatureInfo] fix inconsistent popup behaviour when displaying a mix of html and plain text responses
- [FeatureInfo] resolve popup behaviour variations between `onlyValid` on or off
- [FeatureInfo] activate first loaded tab / accordion pane only
- [FeatureInfo] detect empty server response even with `onlyValid` option off
- [ZoomBar] replace history icons with more appropriate double-arrows (also forward-compatible with Fontawesome 5)
- [ZoomBar] fix horizontal alignment of zoom level icons
### Visual fixes and changes - login and backend
- Sort sources primarily by title in source index view and in layerset assignment list
- Fix display of wide-format custom logos in backend sidepane and login areas
- Fix encoding errors of backend headings containing HTML-escapable characters
- Fix untranslated "Back" button in backend source views
- Change misleading "active" labeling in Application Security tab to "public access" (this is _not_ a functional change, only a text update)
- Increase form field contrast for better placeholder readability
- Split instance editing `<h1>` to improve presentation of instances with very long titles
- Supply validation error messages (line + snippet) for yaml-type form fields
- Disable undesirable close on outside click / mouse drag in misc backend modal popups (e.g. Layerset title editing)
- Replace custom backend message boxes with standard Boostrap `.alert`
- Remember last active tab also in Source item view (previously only in application editing)
- Replace some custom backend-only icon constructs with forward-compatible FontAwesome markup
- Improve wording consistency of common backend button / interaction labels (save vs update, delete, back etc)
- Consistently use plural forms for all top-level menu items
- Remove confusing WMTS instance form fields for unpersisted values
- Remove form fields related to inactive, unimplemented WMTS featureinfo
- Remove inconsequential Source Instance attribute `visible` and related form fields; instance visibility is always determined by the root layer's `selected` settting
- Remove inconsequential Map Element configuration field `units` (units are auto-determined by CRS)
- Remove unused tooltip Element configuration (ZoomBar, ScaleDisplay, ScaleBar, Overview, FeatureInfo, CoordinatesDisplay, Legend, Sketch)
- Remove schema validation status display icons from backend Source listing (schema validation has been disabled since 3.0.8)
### Package dependency changes
*NOTE*: see [UPGRADING.md](./UPGRADING.md) for guidance on all package dependency changes
- Dropped legacy joii library dependency
- Replaced `eslider/sasscb` dependency with two new dependencies ([PR#1219](https://github.com/mapbender/mapbender/pull/1219))
- Add missing `sensio/generator-bundle` dependency declaration (required by `mapbender:generate:element` command)
- Moved owsproxy dependency back to stable / tagged version releases
### Other changes
- Capitalize all field labels generated through form theme
- [FeatureInfo] remove `type` configuration option only relevant for destkop vs mobile templates; auto-detect instead
- [CSS] `.linkButton` and all `<a>` elements now inherit font color by default
- [CSS] `.icon*` no longer has a universal margin-right; only when applied on links and `.toolBarItem`
- [CSS] Allow default-styled lists via .list-default class, document Bootstrap conflicts
- [CSS] switch to root-relative units for all header elements and font-size classes
- [CSS] `table` elements in popups and sidepanes now have full width by default, globally
- [CSS] [Potential break] Resolve conflicts with Bootstrap checkbox markup. Elements with class `.checkbox` are no longer globally hidden. See UPGRADING.md for guidance.
- [CSS] allow natural text flow in div.contentTitle
- [CSS] extract new SCSS variables `$inputForegroudColor` (previously identical to `$secondColor`) and `$inputBackgroundColor` (previously hard-coded to full white)
- Add `translation:get` command (optional MapbenderIntrospectionBundle, inactive by default)
- Add `mapbender:inspect:translations` command to scan for invalid repeats and identity translations (optional MapbenderIntrospectionBundle, inactive by default)
- Add `mapbender:inspect:translations:twigs` command to scan for JavaScript-side translation aliasing (optional MapbenderIntrospectionBundle, inactive by default)
- Removed `mapbender:generate:template` command; never worked in any release, all the way back to 3.0.0.0

## v3.0.8.4
- Support secured WmsLoader sources in modern browsers
- Fix button misalignment in vis-ui popups (e.g. Digitizer)
- Skip ACL-based grants check when passing `roles` grant in Yaml-defined application
- Fix inconsistent grants checks (application list vs application view) on Yaml-defined applications
- Fix basic auth secured services via WmsLoader ([#1201](https://github.com/mapbender/mapbender/issues/1201))
- Fix SearchRouter visibility in mobile template ([#1200](https://github.com/mapbender/mapbender/issues/1200))
- Fix PrintClient legend_default_behaviour option ([#1203](https://github.com/mapbender/mapbender/issues/1203))
- Suppress PrintClient rotation handle if rotation is configured as disabled
- Suppress "Sources" menu item for users with no source view grant
- Enable foreign keys on Sqlite platform (required for [FOM user / group ACE deletion fix](https://github.com/mapbender/fom/releases/tag/v3.2.8) to work)
- Fix syntax error in 'map click' generated element ([PR#1185](https://github.com/mapbender/mapbender/pull/1158))
- Fix reusable source layer matching in YAML-to-db-import to work with nested-transaction capable RDBMS
- Readd basic support for ancient (Mapbender 3.0.4), now invalid, mb_core_source tables with empty `type` column
- Align WMTS form label translations with WMS translations
- Update default Mapbender logo ([#1156](https://github.com/mapbender/mapbender/issues/1156), [PR#1202](https://github.com/mapbender/mapbender/pull/1202))

## v3.0.8.3
- [Regression fix] newly created source instances are again added to the top of the target layerset
- DimensionsHandler configuration form no longer offers selection of disabled instances ([#1166](https://github.com/mapbender/mapbender/issues/1166))

## v3.0.8.2
- Restore support for app/Resources drop-in overrides for .js, .json.twig and .css application resources
- Fix various application import errors on exports created with older versions
- Fix application screenshot display in edit view
- Fix dropdown choice display for choice values containing backslashes or quotes (e.g. Application template chooser)
- Fix missing favicon in login / password reset areas
- Fix sizing and missing localization of layerset, group and user delete confirmation popups
- Fix "toggle all" visuals in instance form ([#1169](https://github.com/mapbender/mapbender/issues/1169))
- Fix broken "toggle all" interaction in Element ACL assignment popup header
- Fix application publish / unpublish interaction
- Fix JavaScript extraction of url components `.port` and `.host` in Internet Explorer ([#1187](https://github.com/mapbender/mapbender/issues/1187), [PR#1190](https://github.com/mapbender/mapbender/pull/1190), [PR#1191](https://github.com/mapbender/mapbender/pull/1190))
- Fix errors in ImageExport / Print attempting to serialize undefined feature style
- Adjust Layertree CSS for Internet Explorer
- Only allow Application ACL editing for Application owner and users with global ACL editing rights
- Provide reasonable default region properties for new application
- Allow exiting user / group selector when trying to add a new Element permission
- Improve HTML and asset response performance for complex YAML-defined applications
- Enable HTML response caching for YAML-defined applications
- Improve export file size and import / export / copying performance
- Add mapbender:source:rewrite:host CLI command (update matching source urls without reeavaluating capabilities)
- Add mapbender:application:export CLI command
- Add mapbender:application:import CLI command
- Add mapbender:application:clone CLI command
- Support cloning of YAML-defined applications into the database (CLI + backend)
- When cloning applications, also copy uploads directory (including screenshot)
- Add separate memory limit configuration parameter `mapbender.print.memory_limit` for direct print jobs
- Add spearate download base path / base url parameter `mapbender.print.queue.load_path` to support forwarding PDFs from
  externally installed "dedicated print queue servers"
- Add mapbender:print:queue:gcfiles command to remove dangling local files
- Support suppressing menu items for backend areas based on route prefixes. Add route prefixes to `mapbender.manager.menu.route_prefix_blacklist` param (list of strings; use app/console debug:route to see all available routes in correct format)
- [Print templating] recognize text field named `user_name`, automatically insert name of submitting user
- [Backend] form for newly created DimensionsHandler is immediately functional
- [Backend] form for newly created BaseSourceSwitcher is immediately functional
- [Backend] no longer close Element form popup on outside click / selection drag
- [Backend] When adding a new Element requiring a map target to an Application, preselect the map Element automatically
- [Backend] Suppress broken link to source refresh for non-refreshable (i.e. WMTS + TMS) sources in sources index
- [Backend] Translate source creation and source reloading error messages
- [Backend] Redirect to edit view instead of index when creating new application
- [Framework] form theme now supports grouped choices in dropdowns (nested `<optgroup>` tags)
- [Framework] form theme can now properly render basic radio button groups (`choice` types with `expanded` option)

## v3.0.8.1
- Add configurable site links to login box and backend ([PR#1158](https://github.com/mapbender/mapbender/pull/1158))
- SearchRouter: add full reprojection support and misc smaller improvements / config simplifcations ([PR#1159](https://github.com/mapbender/mapbender/pull/1159/files))
- Enable DimensionsHandler element by default
- Fix Dimension parameter handling for non-tunneled sources
- Apply Dimension parameters in GetFeatureInfo requests
- Fix vertical misalignment of CoordinatesDisplay Element in bottom toolbar
- Fix Overview initialization with `maximized` set to false
- Fix relative ordering of multiple sources assigned to Overview layerset (same behaviour as main map; [#1161](https://github.com/mapbender/mapbender/issues/1161))
- Extend marker support in export / print to vector features with svg `externalGraphic` style rule ([PR#1163](https://github.com/mapbender/mapbender/pull/1163))
- Add `mapbender:user:create` console command for scripted creation of local users
- Support importing YAML application with missing local uploads directory into DB ([#1157](https://github.com/mapbender/mapbender/issues/1157))
- Print WMS tiling: support axis-separate configuration of max GetMap and tile buffer dimensions; bump default GetMap limit from 4096 to 8192
- Print: respect configured `mapbender.print.template_dir` also when opening PDF (previously only for ODG)
- [Backend] Fix text overflow over icons in Application list with very long Application titles
- [Backend] Remove validation status icons from source selection popup
- [Debug] log console error when print template fetching fails ([PR#1153](https://github.com/mapbender/mapbender/pull/1153))
- [Documentation] Add PrintBundle/CONFIGURATION.md

## v3.0.8
  - [Beta 4 regression] Fix Print client dialog-type deactivation via click on controlling button
  - [Beta 4 regression] Fix Redlining toolset loss after sidepane deactivate / activate cycle
  - [Beta regression] Fix layertree sublayer reordering under certain source layer nesting conditions
  - [Beta regression] Restore function of Application Layersets list filter
  - [Beta regression] Fix Layertree sometimes updating the wrong FeatureInfo checkbox
  - Fix out-of-box overflow in application list with very long application descriptions
  - Denoise popup and sidepane backgrounds

## v3.0.8-beta4
  - [Beta 3 regression] Fix Print selection dragging not influencing print extent
  - [Beta 2 regression] Fix Export / Print handling of source instances with `proxy` setting
  - [Beta regression] Fix layer menu positioning in a non-thematic LayerTree ([Issue#1124](https://github.com/mapbender/mapbender/issues/1124), [PR#1142](https://github.com/mapbender/mapbender/pull/1142))
  - [Beta regression] Restore Legend option `showGroupedLayerTitle`, migrate historically misspelled option names automatically ([Issue#1127](https://github.com/mapbender/mapbender/issues/1127), [PR#1143](https://github.com/mapbender/mapbender/pull/1143))
  - [Beta 2 regression] Fix IE11 positioning issues for zoombar icons, mobile toolbar icons and LayerTree rows
  - [Beta regression] Fix SimpleSearch / SearchRouter zoom-to-feature behavior ([PR#1146](https://github.com/mapbender/mapbender/pull/1146))
  - Print: Fix black bar artifacts on `northArrow` image at rotations near 90 or 270 degrees
  - Print: Fix black opaque black backgrounds on transparent Wms tile images
  - Support nested source definitions in YAML applications ([PR#1125](https://github.com/mapbender/mapbender/pull/1125))
  - Layers in YAML-defined sources are now initially selected by default if YAML-only `visible` setting is omitted (previously: deselected)
  - Fix LayerTree menu metadata for WMS layers with empty names
  - Fix LayerTree menu metadata for WMS child layers without an own bounding box
  - Disable LayerTree menu metadata entry for WMTS / TMS sources (metadata rendering not implemented for these source types)
  - Fix Overview map support for source instances with `proxy` setting
  - Fix Overview map support for source instances with `tiled` setting
  - Overview map now respects per-layer `selected` setting on assigned WMS instances
  - Fix ZoomBar configuration value handling discrepancies for `stepByPixel` and `stepSize` options in YAML apps vs DB apps
  - Improve Digitizer feature Print support by always sending close event first before destrying popup ([PR#1128](https://github.com/mapbender/mapbender/pull/1128))
  - Fix nested tab container conflicts / conflicts with non-unique tab ids
  - Fix multi-argument `visiblelayers` URL param handling ([Issue#1082](https://github.com/mapbender/mapbender/issues/1082), [PR#1140](https://github.com/mapbender/mapbender/pull/1140))
  - Fix WMTS matrix resolution calculations for non-metric initial projections
  - Restore missing sub-layer toggle icons in backend source layer view
  - Restore missing frontend icon highlight effects in Redlining, Wmc Elements and LayerTree inline menu
  - Redlining: allow direct tool deactivation via second click on activation button ([PR#1147](https://github.com/mapbender/mapbender/pull/1147))
  - GpsButton: remove unsafe `zoomToAccuracy` option
  - Previously detected default version is now applied when reloading a WMS from an origin url with omitted `VERSION=...` parameter ([PR#1150](https://github.com/mapbender/mapbender/pull/1150))
  - Avoid lengthy WMS validation when loading a new WMS source ([PR#1151](https://github.com/mapbender/mapbender/pull/1151))
  - WMS GetFeatureInfo FEATURE_COUNT default is reduced from 1000 to 100; this value is now configurable in the FeatureInfo Element as `maxCount` ([Issue#1099](https://github.com/mapbender/mapbender/issues/1099), [PR#1152](https://github.com/mapbender/mapbender/pull/1152))

## v3.0.8-beta3
  - [Beta regression] Restore Wms Loader function
  - [Beta regression] Fix error on SimpleSearch centering
  - [Beta regression] Fix behavior of LayerTree opacity menu when reducing opacity to zero
  - [Beta regression] Fix PrintClient selection broken in Firefox
  - [Beta regression] Fix ZoomBar missing highlight on activated zoom box icon
  - Update `mbPrintClient.printDigitizerFeature` API ([PR#1123](https://github.com/mapbender/mapbender/pull/1123), [Digitizer PR#69](https://github.com/mapbender/mapbender-digitizer/pull/69))
  - Fix broken display of Buttons with 'Coordinates (FontAwesome)' icon assigned
  - Fix missing gap in ScaleDisplay
  - Fix PrintClient errors when switching template while selection rectangle is disabled (sidepane / element mode)

## v3.0.8-beta2
  - [Beta regression] Fix button group misalginment in Application "Layersets" tab
  - [Beta regression] Fix layout breakage in "Vendorspecifics" area of WMS instance backend
  - [Beta regression] Fix layout breakage in "Reload source" and "Add new source" sections
  - [Beta regression] Fix mobile pane always displaying several currently disabled Elements in demo mobile Application
  - [Beta regression] Fix layertree menu initialization for WMS sources where no layer has any configured bounding boxes
  - [Beta regression] Fix layertree menu positioning
  - [Beta regression] Re-invert legend ordering in print to match frontend Legend / LayerTree order
  - Fix Dimensions values not getting applied while layer is invisible ([PR#1114](https://github.com/mapbender/mapbender/pull/1114))
  - Fix map max extent breaking down on repeated SRS switches
  - Wms instance layer titles can now automatically follow source layer title changes on source reloads ([PR#1115](https://github.com/mapbender/mapbender/pull/1115))
  - Fix broken URL parameters generated by WmcHandler state saving / restoring ([59c41476c](https://github.com/mapbender/mapbender/commit/59c41476c3a4203c40085c9608f4178d038c6f8a))
  - Fix Wmc Editor Screenshot upload errors
  - Sources in YAML-defined Applications can now be configured to start expanded and / or not be expandable, analogous to `toggle` and `allowToggle` root layer options for DB applications ([PR#1113](https://github.com/mapbender/mapbender/pull/1113))
  - Print: Rotation can now be controlled by dragging the corner of the print area ([PR#1121](https://github.com/mapbender/mapbender/pull/1121))
  - Print: fix rendering of multi-line dynamically populated text regions
  - Print: fix errors extracting user specific job values for custom LDAP user objects without a `getId()` method
  - FeatureInfo: fix visual dialog overflow for wide response HTML formats with `showOriginal` option off
  - FeatureInfo: fix erratic double scrollbars appearing / disappearing when resizing dialog with `showOriginal` option on
  - LayerTree: "zoom to layer" now supports WMS 1.3.0 `<BoundingBox>` axis order specification quirks
  - LayerTree: Support horizontal growth and auto-ellipse long title texts purely with CSS; this obsoletes the `titlemaxlength` option, which has been removed
  - LayerTree menu: (partial) usability fix for menu when shown for source currently in loading state
  - LayerTree: fix conflicts between layer menus opened simultaneously by multiple LayerTree elements
  - Add experimental, partial WMTS / TMS source support. Disabled by default. See [PR#1116](https://github.com/mapbender/mapbender/pull/1116) for instructions and known limitations
  - Annex responsibility for general backend layout and certain JavaScript widgets from FOM. See [PR#1120](https://github.com/mapbender/mapbender/pull/1120) for potential BC impact on highly customized installations.
  - Increase reverse-proxy setup compatibility also for owsproxy urls generated for source instances with `proxy` option checked
  - Revert HTMLElement to pure markup rendering functionality ([PR#1122](https://github.com/mapbender/mapbender/pull/1122))
  - Button: fix ~random font family changes for label depending on chosen `icon` option
  - Button: fix display sizing of Button with no icon
  - Button: fix responsive toolbar folding of buttons with a label but no icon (leave the label visible)
  - Replace toolbar / navigation element opacity with much more benign rgba colors (via sass fade-out)
  - [Backend] Fix application edit form screenshot area layout breaking on deletion
  - [Backend] Fix application screenshot image rescaling to maintain aspect ratio and never crop
  - [Backend] removed noisy background
  - [Framework] `false` is now a viable return type from Element::getWidgetName, and indicates a "static" element with no client-side script
  - Misc obsolete asset removals (see UPGRADING.md)
  - Misc multiple-id DOM fixes

## v3.0.8-beta1
  - [Regression fix] restore function of optional `wms_id` application url parameter ([PR#1084](https://github.com/mapbender/mapbender/pull/1084))
    - Sources added via `wms_id` parameter now support metadata loading via LayerTree menu
  - [Regression fix] restore function of optional `visiblelayers` application url parameter on root layers ([PR#1083](https://github.com/mapbender/mapbender/pull/1083) collateral)
  - [Regression fix] restore non-root limited adminstrator's ability to reload a Source ([PR#1093](https://github.com/mapbender/mapbender/pull/1093))
  - Fix element-order dependent script initialization error in mobile template
  - Fix incomplete cached application assets for applications with protected elements ([PR#1052](https://github.com/mapbender/mapbender/pull/1052))
  - Fix Ruler measurement errors when switching between geodesic and non-geodesic CRS at runtime ([PR#1069](https://github.com/mapbender/mapbender/pull/1069))
  - Fixed dynamic (layertree) source reordering errors with many layers
  - Fix erratic LayerTree / Legend states after ZoomBar "zoom to full extent" interaction ([PR#1074](https://github.com/mapbender/mapbender/pull/1074))
  - Fix erratic LayerTree / Legend state updates on first map interaction after submitting a print job ([PR#1077](https://github.com/mapbender/mapbender/pull/1077))
  - Fix erratic LayerTree layer "ghosting" on certain map interactions ([PR#1074](https://github.com/mapbender/mapbender/pull/1074))
  - Typo fixes in WmsCapabilitiesParser130, thanks jef-n ([PR#1046](https://github.com/mapbender/mapbender/pull/1046))
  - Extend / update Italian locale translations ([PR #1062](https://github.com/mapbender/mapbender/pull/1062), [PR#1078](https://github.com/mapbender/mapbender/pull/1078))
  - Various Button fixes:
    - Buttons with invalid targets can longer break other buttons with the same `group` setting
    - Fix exception when rendering a Button Element with completely undefined `click` option ([PR#1076](https://github.com/mapbender/mapbender/pull/1076))
    - `activate` / `deactivate` options are no longer mandatory and can safely be left empty ([Issue#1050](https://github.com/mapbender/mapbender/issues/1050), [PR#1095](https://github.com/mapbender/mapbender/pull/1095))
    - Automatic Button highlighting restored ([PR#1095](https://github.com/mapbender/mapbender/pull/1095))
    - Buttons already start highlighted if their target has been configured with an `autoOpen` or similar option ([PR#1095](https://github.com/mapbender/mapbender/pull/1095))
    - Improved / restored compatibility for non-controlling Button element children ([PR#1096](https://github.com/mapbender/mapbender/pull/1096))
    - Fix broken Button vs POI interaction ([Issue#549](https://github.com/mapbender/mapbender/issues/549))
    - Improved support for multiple Buttons controlling the same target Element
  - Partial forward-compatibility with font-awesome 5 ([PR #1065](https://github.com/mapbender/mapbender/pull/1065))
  - Restore layer enabled / FeautureInfo checkbox state synchronization across multiple LayerTree Elements ([PR#1074](https://github.com/mapbender/mapbender/pull/1074))
  - WmsLoader: make `autoOpen` option work
  - WmsLoader: Fix newly added service starting out with a deselected root layer ([PR#1045](https://github.com/mapbender/mapbender/pull/1045))
  - WmsLoader: Make behaviour of 'declarative' links with default `mb-wms-merge` setting repeatable ([PR#1083](https://github.com/mapbender/mapbender/pull/1083))
  - WmsLoader: Enable activation of specific layers via `mb-wms-layer` on 'declarative' links even if root or group layers have empty names ([PR#1083](https://github.com/mapbender/mapbender/pull/1083))
  - Improve compatibility with certain reverse-proxy setups ([PR#1061](https://github.com/mapbender/mapbender/pull/1061), [PR#1075](https://github.com/mapbender/mapbender/pull/1075))
  - Enable scrolling of FeatureInfo response in Mobile Template ([PR#1057](https://github.com/mapbender/mapbender/pull/1057))
  - Print / ImageExport:
    - Add WMS GetMap size limits, use tiling to stitch larger images ([PR#1073](https://github.com/mapbender/mapbender/pull/1073))
    - Generate label and other symbol sizing parameters understood by Mapserver, QGis server and Geoserver ([735626322](https://github.com/mapbender/mapbender/commit/73562632261819d79b9a9c0c264caeb33f34f4bf#diff-c72724b3690b61d792254dd26a7ca9cbR222))
    - Optimize handling performance of WMS layers with manually reduced opacity
    - Synchronize layer and legend visibility at any scale with client-side behavior ([PR#1077](https://github.com/mapbender/mapbender/pull/1077))
    - Support exporting / printing icons on "marker layers" ([PR#1108](https://github.com/mapbender/mapbender/pull/1108))
    - Fix wrong (grey instead of white) color of GpsButton area display circle, if one makes it into an export or print
    - Extend label reproduction to all types of features, improve reproduction accuracy ([PR#1111](https://github.com/mapbender/mapbender/pull/1111))
  - Print:
    - Selection rectangle position and scale are now restored, if still on screen, when closing / reopening the dialog ([PR#1011](https://github.com/mapbender/mapbender/pull/1101))
    - Add optional queue mode, decoupling job execution from web server request ([PR#1070](https://github.com/mapbender/mapbender/pull/1070))
      - NOTE: Print queue display styling [inherits from .mapbender-element-result-table](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/PrintBundle/Resources/public/element/printclient.scss#L28), which means
        any custom css styles you may have already applied to Digitizer result tables should automatically transfer to the print queue visual.
    - Significantly reduce memory requirements for larger printouts
    - Suppress redundant group layer legend images and legend images for deactivated layers ([Issue #611](https://github.com/mapbender/mapbender/issues/611) [PR#1053](https://github.com/mapbender/mapbender/pull/1053))
    - Improved reproduction of patterned and / or semi-transparent and / or very thick lines ([PR#1080](https://github.com/mapbender/mapbender/pull/1080))
    - Fixed reproduction of 'donut'-style polygon cutouts ([PR#1080](https://github.com/mapbender/mapbender/pull/1080))
    - Very large legend images will now be scaled to fit ([PR#1112](https://github.com/mapbender/mapbender/pull/1112))
    - Very long legend titles now render as multiline text ([PR#1112](https://github.com/mapbender/mapbender/pull/1112))
  - Fix inconsistent legend image behaviors between `proxy` source instance setting on and off
  - Fix Legend Element display ordering certain layer nestings
  - Fix Legend Element `autoOpen` option
  - Fix broken Legend Element handling of WMS sources with only a root layer
  - Limit Legend Element image sizes to available width
  - Fix redundant double WMS request on first LayerTree off / on cycle on a source ([Issue #715](https://github.com/mapbender/mapbender/issues/715), [PR#1074](https://github.com/mapbender/mapbender/pull/1074))
  - Fix Redlining in sidepane never deactivating its drawing tools once activated ([Issue #992](https://github.com/mapbender/mapbender/issues/992), [PR#1088](https://github.com/mapbender/mapbender/pull/1088))
  - Add support for Redlining `deactivate_on_close` also when in placed sidepane
  - Fix dangling Redlining edit-mode vertices when deleting currently edited feature ([Issue #1040](https://github.com/mapbender/mapbender/issues/1040), [PR#1106](https://github.com/mapbender/mapbender/pull/1106))
  - Redlining now reacts appropriately to runtime SRS switching ([PR#1107](https://github.com/mapbender/mapbender/pull/1107))
  - Fixed SearchRouter feature highlighting after zoom ([Issue #1072](https://github.com/mapbender/mapbender/issues/1072), [PR#1103](https://github.com/mapbender/mapbender/pull/1103))
  - Fixed POI initialization with non-default SRS ([Issue #458](https://github.com/mapbender/mapbender/issues/458), [PR#1109](https://github.com/mapbender/mapbender/pull/1109))
  - Fixed handling of DimensionsHandler backend form ([PR#1049](https://github.com/mapbender/mapbender/pull/1049))
  - Dimension submenu in LayerTree now remember its last state when closing / reopening
  - Fixed behaviour of Digitizer and similar Elements in `Buttons`-style sidepane ([PR#1097](https://github.com/mapbender/mapbender/pull/1097))
  - [Vendorspecifics] Fix inconsistent generated params for `user` and `group` type vendorspecifics hidden vs non-hidden
  - [Vendorspecifics] Unused / ineffective form fields have been removed ([PR#1047](https://github.com/mapbender/mapbender/pull/1047))
  - [Backend] Fix Application import from pretty-printed JSON input
  - [Backend] Fix broken display of long layerset titles in Map configuration form ([Issue#1085](https://github.com/mapbender/mapbender/issues/1085), [PR#1098](https://github.com/mapbender/mapbender/pull/1098))
  - [Backend] Provide better scope information (layerset name, element region) in source instance assignment and element creation popups
  - [Backend] Use localized strings in Application delete confirmation popup (previously hard-coded to English)
  - [Backend] Fix excessive height of Application delete confirmation popup, align with other confirmation dialogs
  - [Backend] Close potential script injection angle on certain popup subtitles
  - [Backend] More consistent styling between 'Layouts' and 'Layerset' Application sections
  - [Backend] Fix 'Layerset' Application section overflows when displaying very high source / instance ids
  - [Debug] Application routes assets/js and assets/css will now produce information markers at the beginning of each merged input file in `app_dev` mode
  - Add new `mapbender:config:check` console command
  - Misc deprecation cleanups for Symfony 3+ compatibility

## v3.0.7.7
  - [Regression fix] Restore either-or privilege checking behaviour for access to instance tunnel and metadata actions ([341bf11](https://github.com/mapbender/mapbender/commit/341bf117812173b3d9e211be8d5498750d73bf2d))
  - [Regression fix] Fix erratic behaviour when dynamically reodering sources ([09050ee](https://github.com/mapbender/mapbender/commit/09050eeecd81bd6a003e62c3a0d54f1de8a03cbb))
  - [Regression fix] Remove 'required' asterisk from non-required fields configured into print client form ([PR#1036](https://github.com/mapbender/mapbender/pull/1036))
  - [Regression fix] Remove impossible grants check preventing non-root users from editing a source instance ([e1919a0](https://github.com/mapbender/mapbender/commit/e1919a012addb523fc21d50deab6f23bd64bc520))
  - [Regression fix] Restore function of `Classic` template
  - FeatureInfo: extend support for "declarative" WmsLoader links to "show original" iframe rendering mode ([PR#1042](https://github.com/mapbender/mapbender/pull/1042))
  - FeatureInfo: respect configured info format on Layerset; fix text/plain response handling
  - FeatureInfo: support direct requests; bypass owsproxy unless required to get around [CORB](https://chromium.googlesource.com/chromium/src/+/master/services/network/cross_origin_read_blocking_explainer.md#What-kinds-of-requests-are-CORB_eligible)
  - Print: Fix broken overview for non-metric current map units ([c77fc88](https://github.com/mapbender/mapbender/commit/c77fc881af8290e5246f3804bc84948adcdd9e6f))
  - Print: Fix zoom level and center for overview when Overview Element is set to 'fixed' ([PR#1032](https://github.com/mapbender/mapbender/pull/1032))
  - Print: Fix non-updating print region when reopening PrintClient dialog after zooming the map ([PR#1038](https://github.com/mapbender/mapbender/pull/1038) collateral)
  - Print / ImageExport: pre-filter null geometries, avoiding followup server errors ([PR#1038](https://github.com/mapbender/mapbender/pull/1038) collateral)
  - PrintClient frontend form: allow control of ordering of "optional fields" marked as required versus the rest of the generated form ([PR#1043](https://github.com/mapbender/mapbender/pull/1043), followup to v3.0.7.5 form ordering changes)
  - Modify proxy URL signing to no longer check URL path (compatibility for WMTS / UTFGrid and similarly structured URLs)
  - [Framework] Client ElementRegistry now based on promises ([PR#1041](https://github.com/mapbender/mapbender/pull/1041))
  - [Framework] Add [Mapbender.Util.SourceTree](https://github.com/mapbender/mapbender/blob/656fae688b2b292687c628f10cb521663abdcf30/src/Mapbender/CoreBundle/Resources/public/mapbender-model/sourcetree-util.js) static method collection to unify layerset / source traversal
  - Support Elements requiring (uncommpiled) CSS assets in all the same ways as for JS assets ([PR#1020](https://github.com/mapbender/mapbender/pull/1020))
  - Geosource: add missing change event when toggling layer ([6ea27e1](https://github.com/mapbender/mapbender/commit/6ea27e11136c7c243ee6c25d7fde21525a214bc0))
  - Layertree: misc rendering / logic cleanups
  - WmsLoader now calculates its own source and layer ids for dynamically added sources
  - Map initialization cleanups
    - Scales cast to numbers server-side ([ab16ada](https://github.com/mapbender/mapbender/commit/ab16ada6a1d5967fe2f950d73f01b22132acf45f))
    - Resolved widget options self-destruct in initialization ([136e4ab](https://github.com/mapbender/mapbender/commit/136e4ab66f2b0799c2ce8b5058ad143e690cfb7f))
    - Calculation / prioritization of initial center + scale from a) map default, b) POI, c) explicit URL parameters passed to application now performed server side
  - Extensive Print + ImageExport cleanups
    - Refactored and simplified server-side rotation and transformation handling ([PR#1031](https://github.com/mapbender/mapbender/pull/1031))
    - Improved Element PHP customizability by separating methods for job data preprocessing and current user lookup ([PR#1037](https://github.com/mapbender/mapbender/pull/1037))
    - Improved ImageExport and PrintClient JS customizability by breaking up monolithic job data collection + sumbission into multiple smaller methods ([PR#1038](https://github.com/mapbender/mapbender/pull/1038))


## v3.0.7.6
  - Fix button group behavior of Legend Element ([PR#1034](https://github.com/mapbender/mapbender/pull/1034))
  - Fix broken 'queryable' state of source added via WmsLoader ([91e7d4e](https://github.com/mapbender/mapbender/commit/91e7d4e29dcd9bf4096df3bdd7d6714be7ba360b))
  - Integrate GPS and POI Elements ([PR#985](https://github.com/mapbender/mapbender/pull/985), [PR#1015](https://github.com/mapbender/mapbender/pull/1015))
  - More robust sizing of backend Element editing modal popups ([PR#1035](https://github.com/mapbender/mapbender/pull/1035))

## v3.0.7.5
  - [Security] Remove obsolete TranslationController (potential XSS vector)
  - [Security] Fix SecurityContext compatiblity with framework auth listeners ([PR#1021](https://github.com/mapbender/mapbender/pull/1021))
  - [Regression fix] Restore support for Wms services advertising only a root layer ([0192e0c](https://github.com/mapbender/mapbender/commit/0192e0c135af44c5c7ff55a718069d2dc3a646d1))
  - Fix layer order reversals depending on Element population and order ([PR#1025](https://github.com/mapbender/mapbender/pull/1025))
  - Fix Redlining hang after edit mode ([PR#1027](https://github.com/mapbender/mapbender/pull/1027))
  - Print: skip Wms layers where the service response can't be fetched or is invalid.
    Log a warning and continue printing the remaining layers normally ([PR#987](https://github.com/mapbender/mapbender/pull/987), [PR#1013](https://github.com/mapbender/mapbender/pull/1013))
  - Print: move extra fields marked as required to top of form to avoid confusion ([d0630fa](https://github.com/mapbender/mapbender/commit/d0630fa208a9f116894fc446d003aa26b5194233))
  - Fix service loading error on DNS / routing error in Xml validation
  - Fix invalid markup in about_dialog.html.twig
  - Fix Button interactions with dialog-type Elements ([PR#1019](https://github.com/mapbender/mapbender/pull/1019))
  - Fix Redlining functionality on second activation in 'dialog' mode ([Issue #995](https://github.com/mapbender/mapbender/issues/955))
  - Fix POI opening additional dialogs on every button click
  - Silence untranslated and redundant map load error announcement from legend element  ([059673e](https://github.com/mapbender/mapbender/commit/059673e94ed5285b378e4f708fbdef4a9ae136d4))
  - [Frontend] suppress unneeded scroll bars on popup dialogs ([PR#1022](https://github.com/mapbender/mapbender/pull/1022))
  - [Backend] popup sizing changes, add CSS-level customizability ([PR#1022](https://github.com/mapbender/mapbender/pull/1022))
  - [Backend] reformulate non-framework conformant security and response interactions ([PR#1028](https://github.com/mapbender/mapbender/pull/1028))
  - [Backend] Add new form types for source instances, source instance layers ([ee0099e](https://github.com/mapbender/mapbender/commit/ee0099e1d49bfdbe916fa83f5121e3150418a612))
  - [Framework] Extend runtime extension of SRS definitions with preliminiary support for proj4js 2.x
  - [Framework] Provide global boolean Javascript value `Mapbender.configuration.application.debug` to check for `app_dev` environment
  - [Framework] Pre-calculate internal layer attributes `id` and `origId` and source attribute `origId` server-side
  - [Framework] New optional widget [mbCheckbox](https://github.com/mapbender/mapbender/blob/eca5cd66296f539945802c4f5d048c4adbabb739/src/Mapbender/CoreBundle/Resources/public/widgets/mapbender.checkbox.js) as a replacement for FOM's `initCheckbox`
  - [Framework] Move Mapbender version knowledge from Mapbender Starter into Mapbender ([PR#1012](https://github.com/mapbender/mapbender/pull/1012))
  - [Database] add delete cascade to foreign keys referencing Application or Source,
    allowing such objects to be deleted on the database (non-Doctrine) level
  - [Console debugging] Check / provide appropriate message if Element widget constructor or widget namespace do not exist
  - [Console debugging] Show original stack trace of widget initialization error instead of new stack trace truncated to Mapbender.setup
  - [Deprecation] [pre-mark 3.0.8 removals](https://github.com/mapbender/mapbender/commit/c45b0d00dd17d7e3d62dd1acf2106ff81c4fce8d):
    * Element::listAsset
    * HTMLElement::isAssoc
    * HTMLElement::prepareItems
    * vis-ui.js support in HTMLElement Javascript
  - [Removed] unused asset `mapbender.application.json.js`
  - [Removed] processing of `app/config/mapbender.yml` ([deprecated since 2016](https://github.com/mapbender/mapbender-starter/commit/f8de52fd0d49d26ea0faf07babd2a093a5d5458a))
  - [Removed] broken `readyCallback` handling in multiple Element scripts ([PR#1029](https://github.com/mapbender/mapbender/pull/1029))
  - [Misc] Mapbender can now run with zero yaml applications and with the `app/config/applications`directory removed
  - [Misc] merge Github issue templates from master
  - [Misc] non-functional type annotation fixes

## v3.0.7.4
  - [Security] Fix potential XSS vector in applicationAssetsAction
  - [Regression fix] restore compatibility with Internet Explorer 11
  - [Regression fix] Apply WmsLoader image format / info format settings
  - Revert keyword column type back to varchar to work around issues on Oracle.
    Pathologically long Wms keywords will now be silently truncated to 255 characters.
  - Fix delete cascade error when deleting a Wms Source on PostgreSQL
  - Skip empty layer names when collecting feature info QUERY_LAYERS value (pull #1010)
  - Work around Doctrine optimizations preventing correct
    updating of the layer order setting on PostgreSQL
  - Clean up dummy translations ("__mb...", "[Placeholder]") from es, it and ru locales, those will
    now use the texts from the fallback locale (most likely English)

## v3.0.7.3
  - "target" selection in elements shows only appropriate other elements (regression fix)
  - WMS metadata now renders email addresses and links as clickable links (PR#837)
  - Update Turkish translation (PR#944)

## v3.0.7.2
  - Fix dynamic layertree reordering vs reversed layer order (PR#852)
  - Change default prefix for printouts to mapbender (Issue #855)
  - Add cookieconsent code for mapbender (PR#835)
  - Fix PHP5 incompatibility in Migrations (PR#851)
  - Restore WmsLoader "splitLayers" functionality (PR#848)
  - Disable non-functional Meta data display for dynamically added Wms (PR#845)
  - Raise maximum feature count for feature info to 1000 (PR#849)
  - Fix saving layer order on Postgres (PR#846)
  - Restore application of preconfigured image format / feature info format in WmsLoader (PR#841)
  - Fix foreign key violation error when deleting data source on Postgres (PR#840)

## v3.0.7.1
  - Revert inclusion of "only valid" checkbox in WmsLoader
  - Bypass (potentially very long) WmsLoader DTD / XSD validation of GetCapabilities document
  - Print: fixed font size handling for coordinates display
  - Fix application screenshot upload handling

## v.3.0.7.0
  - Support reversible layer order per WMS source instance (new dropdown application backend section "Layersets")
  - Support WMS keywords > 255 characters; needs app/console doctrine:schema:update for running installations
  - Extend WmsLoader WMS service compatibility, now matches backend
  - Update WmsLoader example URL to https
  - Skip undefined element classes in Yaml applications, log a warning instead of crashing
  - Fix unbounded growth in "authority" on repeated export / reimport / cloning of applications (#777)
  - Various fixes to displaying and handling min / max scale definition from sublayers vs root layers (see pull #787)
  - Add Doctrine migration framework and command-line support (pull #762)
  - Fix strict SCSS warnings when compiling with ruby-sass (closes issue #761)
  - Fix possible URL signing spoof with input URLs missing query parameters (internal issue #8375)
  - Replace usort => array_multisort to skip around PHP bug #50688 when sorting Element names (MB3 issue #586)
  - Fix http 500 when rendering meta data for a service with undefined contact information
  - Merge pull request #760 from mapbender/fix/unittest-preconditions
  - Merge pull request #747 from mapbender/fix/metadata-serialization-746
  - Merge pull request #743 from mapbender/fix/element-inheritance-639-noconfig
  - Changed Opacity for zoombar and toolbar to get a unique button color

## v3.0.6.4
  - Validate Element forms in backend
  - Extend WmsLoader WMS service compatibility, now matches backend
  - Fix error displaying Wms metadata if no contact information available
  - Element selector (when adding to Application) is now filtered (Pull #766)
  - Fix displaying scale value in scale selector #657
  - Fix GetLegendGraphic tunnel

## v3.0.6.3
  - Fix WMS with Scale fails to load #584 - see commit message #2783540 for more information
  - Fix possible URL signing spoof with input URLs missing query parameters (internal issue #8375)

## v3.0.6.2
  - Fix create legend URL
  - Merge pull request #572 from mapbender/fix/wrong-scaleHint-in-sublayers
  - Fix layer instance administration form sizes Closes: #559
  - Merge pull request #545 from mapbender/hotfix/imagepathCommand-530
  - Merge pull request #553 from mapbender/hotfix/featureinfo-print-trans-button
  - Add output for better UX
  - Revert commit d11dd2fd1bde139225a388ddb6d125cb24562260
  - Merge pull request #570 from mapbender/fix/ruler-unmatching-value-app-backend
  - Reverse to old getScaleRecursive-function in WmsLayerSource because of regression bug. Now correct scale and scale hint for sublayer are set
  - Change default value for immediate messurment to null and add check if value is set
  - Merge pull request #563 from mapbender/hotfix/epsg-code-list
  - added EPSG:4839 and EPSG:5243 to the list
  - changed trans variable for print button mb.core.featureinfo.popup.btn.print
  - changed trans variable for print button mb.core.featureinfo.error.noresult
  - Fix FeatureInfo print translations
  - Fix initialize search router Closes: #543
  - Added Command to update old imagepath of map element / Fix OpenLayers2 image path #530
  - Merge pull request #551 from mapbender/fix/search-router-autoclose-after-click
  - remove 'move' check on click event
  - Add spaces behind foreach and if to satisfy code quality standards
  - Remove unused element generator code. Add documentation
  - Added reverse axis default for EPSG:31466

## v3.0.6.1
  - PrintService/ImageExport: Accept all kinds of image/jpeg and image/gif from wms
  - Refactor print scale bugfix
  - fix a css problem with checkboxes by moving it out of screen some more
  - improved position of removebutton
  - Print Scale Bugfix
  - Update Date in Changelog.md
  - Set deprecated command advice for next release version
  - Fix and add application by render elements
  - Add WmcEditor Default Parameters for width and height
  - Fix parse dimension data
  - Fix vendor specific parameter close button position
  - Add missed VendorSpecific origextentextent property
  - Fix save MetadataUrl as doctine array type
  - Fix WmsLayerSource modificators
  - Fix save Style, VendorSpecific and WmsLayerSource entities

## v3.0.6.0
  - Fix transalate element titles by import Closes: https://github.com/mapbender/mapbender-starter/issues/46
  - Add WmsInstanceLayer to fix import applications
  - Fix import YAML application BaseSourceSwitcher element configuration
  - Add WmsInstanceLayerRepository to fix import
  - Remove repository class  WmsInstanceLayerRepository as annotation
  - Fix import demo applications and import base source switcher configuration
  - Fix and refactor YAML application importing https://github.com/mapbender/mapbender-starter/issues/45
  - Improve core annotations
  - Fix display error message text https://github.com/mapbender/mapbender-starter/issues/45
  - Fix print element administration translation https://github.com/mapbender/mapbender-starter/issues/46
  - Fix WMS doctrine entities
  - Fix getting print settings from ODG file Closes: https://github.com/mapbender/mapbender-starter/issues/44
  - Fix getting new application entity by slug from database Closes: https://github.com/mapbender/mapbender-starter/issues/43
  - Fix composer open-sans vendor typo
  - Change open sans font library vendor to wheregroup
  - Change JOII javascript library vendor to wheregroup
  - Change compass-mixins library vendor to wheregroup
  - Change codemirror library vendor to wheregroup
  - Fix getting setting minimal and maximal scale hint by WmsInstanceLayer, WmsLayerSource, WmsInstanceLayerEntityHandler and MinMax
  - Improve WMS annotations
  - Improve EntityHandler annotation
  - Revert encoding credentials by RepositoryController
  - Fix gettint scale hint by WmsLayerSource
  - Encode WMS credintials
  - Fix using application edit/create title translation
  - Merge pull request #511 from mapbender/fix/translations_manager
  - restructured translations for de, en and fr in edit/create new application
  - Fix application manager edit translation link
  - Merge feature/symfony-upgrade-2.8
  - Add logging if element isn't registered
  - Skip elements this not registred instead of break whole application
  - Merge pull request #506 from mapbender/hotfix/popup-text-selection
  - Reenable default text selection in elements (popups...) nested inside OL map
  - Merge pull request #489 from mapbender/feature/searchrouter-buttonposition
  - Merge pull request #505 from mapbender/hotfix/translation-manager-typos
  - Fix tabs german translation
  - Merge pull request #500 from mapbender/hotfix/translation-core-typos-1
  - Remove search word from global translation name spache
  - Merge pull request #496 from mapbender/hotfix/translation-manager-typos
  - Deactivate WMS only valid option by default
  - Fix WmsLayerSource getScaleRecursive method and add annotations
  - Merge pull request #499 from mapbender/hotfix/translation-core-typos
  - Merge pull request #503 from mapbender/hotfix/printclient-adddynamictext-utfdecode
  - Improve copy right element configurability (release/3.0.5 merge)
  - Fix frontend.html.twig and index.html.twig templates using JOII lib
  - Add utf8_decode to handle öüä for dynamic textes
  - Remove deprecated joii.min.js library
  - Describe and fix WMS entity documenations
  - Fix core element documentation
  - Describe SourceMetadata method types
  - Fix MapbenderBundle code description
  - Describe and fix InstanceConfiguration
  - Describe and refactor EntityHandler
  - Describe Element::getElementForm
  - Describe Mapbender core bundle class
  - Fix and refactor Application component
  - Fix recover vendor specifics and buffer settings my WmsInstanceConfigurationOptions
  - Fix and describe WMC entity
  - Fix WmcParser code description
  - Clean up MapbenderPrintBundle desciprion
  - Describe MapbenderMobileBundle
  - Refactor and descript mobile template
  - Clean up manager bundle component code descriptions
  - Clean up drupal and KML bundles code description
  - Clean up mapbender core bundle code description
  - Clean up core data transformers and add code documentation
  - Optimize RequestListener imports
  - Clean up core entities and add code documentation
  - Revert Template render method fix
  - Fix using authorization_checker as service in RepositoryController
  - Clean up core components and add code documentation
  - Refactor and mark core generating commands as deprecated
  - Refactor asset classes
  - Mark CustomDoctrineParamConverter as deprecaed and optimize imports
  - Fix utils annotations and documentation
  - Fix actions.html.twig template create application translation
  - Upgrade security context from 2.3 to 2.8
  - Merge remote-tracking branch 'composer/feature/symfony2.7' into feature/symfony-upgrade-2.8
  - Fix map query image patch
  - Fix Printcl typo
  - Fixed typos and merged structure with en version
  - Fixed typos in core bundle translation see #498
  - Fixed typos, merged with en version, changed some translation
  - Fix options variable link in copyright element
  - Add copyright element width and height configuration options
  - Add fullscreen template region "align" and "closed" properties
  - Fix only valid russian translation
  - Merge pull request #490 from mapbender/hotfix/wms-keyword-unlimit
  - Fixed keyword character limitation
  - SearchRouter in Sidepane move buttons ok/reset under form
  - Set mapbender/mapquery version to 1.x
  - Add require libraries to composer definition
  - Remove html5shiv.3.7.2.min.js, placeholders.3.0.2.min.js, respond.1.4.2.min.js and javascript.util.js
  - Remove IE 6-10 template support
  - Add "eslider/sasscb" and "components/underscore" libraries
  - Merge pull request #486 from mapbender/feature/printClient-legendpage-image
  - Fix legendpage_image and logo on legend page #476 #477
  - Merge pull request #482 from mapbender/hotfix/basesourceswitcher-initialization
  - Merge printclient: legend on first page, legend logo #462
  - Fix load core application by MapbenderYamlCompilerPass
  - Refactor Mapbender Yaml Compiler Pass
  - Merge pull request #483 from mapbender/feature/print-transparent-background
  - Fix SecurityContext annotations
  - Fix getting blank gif by overview.scss
  - Fix component codemirror source path
  - Rewrite components/codemirror paths
  - Remove git modules definition file
  - Remove mapquery as submodule
  - Remove components/codemirror
  - Fix OpenLayer2 MapQuery edition library binding
  - Add composer definition
  - Merge pull request #484 from mapbender/hotfix/scaledisplay
  - Remove calculate scale
  - Move LDAP component to Mapbender/Ldap Bundle as composer 'mapbender/ldap' module
  - Remove unnecessary overlay from mobile SCSS
  - Remove abstract typed class definition from mapbender.geosource.js
  - Merge pull request #473 from mapbender/hotfix/view-permission-for-instance-creating
  - Fix set paramaters by create meta data object
  - Fix incomplete configuration update when first BSS entry is a multi-item submenu
  - Fix getting permission for creating objects
  - Merge pull request #481 from mapbender/hotfix/print_png8_png24
  - Fix Print and Imageexport with content-type image/png8 and image/png mode=24bit
  - Apply BaseSourceSwitcher initial state top-down to Application config
  - Machinery for top-down Application configuration updates through Elements
  - Fix Print templates with transparent background windows fix
  - Merge pull request #474 from mapbender/hotfix/fix-duplicate-loads
  - Merge pull request #475 from mapbender/feature/print-transparent-background
  - Fix duplicate loads of WMS
  - Fix allow saving of instances with VIEW right on sources
  - Fix print pdf templates with transparent background part2
  - Fix print pdf templates with transparent background
  - Fix error legendpage_image on every legend page
  - Merge pull request #471 from mapbender/hotfix/map-default-dpi-value
  - Merge pull request #472 from mapbender/hotfix/print-legend-owsproxy
  - Add host for a legend url
  - Revert change of standard dpi value to 90.714 again because it's more conformant to the wms specification
  - Merge pull request #470 from mapbender/hotfix/print-styles-param
  - Fix printing on some scales
  - Update dimension handler source code
  - Add application template security block
  - Merge pull request #469 from mapbender/hotfix/print-style
  - Fix printservice: point style fix
  - Fix printservice: fix feature style in case of strokewidth = 0
  - Fix printservice style
  - Define logo block in full screen template
  - Fix showing feature iframe informations as tab
  - Fix print second opened feature data
  - Fix showing feature informations as tabs
  - Fix showing feature informations in mobile apps
  - Fix print legendpage_image on second page
  - Revert feature info styling
  - Show line ruler measure reverse (first measure on top)
  - Merge pull request #466 from LazerTiberius/feature/immediate-ruler-measurement
  - Deselect base source by creating WMS Instance
  - Fix displaying feature info iframe content and draw container border
  - Fix applciation copy permissions check by not root user
  - Fix feature info reopen if active
  - Merge remote-tracking branch 'origin/hotfix/wms-via-tunnel' into release/3.0.5
  - Improve login input height style
  - Make login, register, forgot password and restore password screens responsive
  - Disallow select map, overview and buttons as text
  - Fix print getting geometry style defaults
  - Merge print and digitizer service binding
  - Set FF accordion overflow-x: hidden
  - Add SecurityContext anonymouse ID
  - Fix set anonymous user by SecurityContext
  - Merge remote-tracking branch 'remotes/origin/feature/added-anonymous-userconst' into release/3.0.5
  - Print additional data from digitizer feature
  - Remove popup tab item space for last and first children
  - Fix form generate dialog close button position
  - Remove application temlate name from application list
  - Fix manage  application administration link
  - Fix screen shot image tag
  - Merge remote-tracking branch 'remotes/github/release/3.0.5' into upgrade/3.0.5
  - Workaround adding doctrine where conditions (PHP7)
  - Fix Userid setting
  - Add anonymous user constant for security.
  - Add possibility to update the shown distance/area after mouse move instead of click
  - Add immediate checkbox to admin form
  - Add generic way to render form -  Form will be iterated, So all form entries will be rendered , not only the defined. This way is more DRY
  - Move YAML edit plugin to edit.html.twig template
  - Add code-mirror.yaml.plugin init textarea as YAML editor
  - Add code mirror YAML highlighting plugin
  - Add HTML element the ability to set custom css files
  - Add full screen template container info manager
  - Refactor HTMLElement
  - Add missed entity property for render element template
  - Improve show errors by SymfonyAjaxManager
  - PrintClient: fixes to generate legendpage image with group
  - Optimize printservice, imageexport for secured services
  - Fix printclient: legend on first page, legend logo
  - Fix pring for tunnel connections
  - Merge remote-tracking branch 'origin/release/3.0.5' into release/3.0.5
  - Check if url for tunnel esists
  - Fix call legends, featureInfo via tunnel for secured services
  - Add SymfonyAjaxManager to ManagerTemplate
  - Fix wms proxy vs. tunnel
  - Add popup styles to manager template
  - Fix ui dialog close button displaying
  - Fix administration input color
  - Intergrate bootstrap and refactor/fix administration SCSS files
  - Fix and refactor login and manager template
  - Fix and refactor UploadScreenshot component
  - Merge pull request #459 from mapbender/hotfix/changelog
  - Merge pull request #460 from mapbender/hotfix/default-titlesize512
  - Change tilesize default to 512

