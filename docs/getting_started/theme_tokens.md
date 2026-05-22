# Theme tokens (CSS custom properties)

This page lists CSS custom properties intended for theming Mapbender UI parts from core bundles and customer bundles.

Override them in a `:root {}` block in your own stylesheet — no SCSS compilation needed.

## Main groups

### Brand / primary color

- `--primary` — primary brand color (Mapbender blue by default)
- `--primary-dark` — darker variant, derived at runtime from `--primary`

### Typography

- `--font-family`
- `--font-size`

### Text colors

- `--text-color`
- `--text-color-inactive`
- `--text-color-button-inactive`
- `--light-text-color`
- `--error-color`

### Backgrounds and borders

- `--background-color`
- `--border-color`
- `--panel-border-color`

### Buttons (primary)

- `--button-background`
- `--button-text`
- `--button-border`
- `--button-hover-background`
- `--button-hover-text`
- `--button-active-background`
- `--button-active-text`
- `--button-focus-shadow`

### Buttons (critical / danger)

- `--button-critical-background`
- `--button-critical-text`
- `--button-critical-border`
- `--button-critical-hover-background`
- `--button-critical-hover-text`

### Toolbar

- `--toolbar-background`
- `--toolbar-opacity`
- `--toolbar-text-color`
- `--toolbar-button-hover-opacity`
- `--toolbar-button-active-opacity`
- `--toolbar-button-active-background-color`

### Inputs and form controls

- `--input-background`
- `--input-foreground`
- `--input-border-color`
- `--input-focus-border-color`
- `--input-disabled-fg`
- `--slider-handle-background`
- `--slider-handle-border`
- `--slider-handle-text`

### Checkboxes and toggles

- `--checkbox-checked`
- `--checkbox-unchecked`
- `--checkbox-bg`
- `--checkbox-active-bg`

### Accordion and tab containers

- `--accordion-background`
- `--accordion-text`
- `--accordion-active-background`
- `--accordion-active-text`
- `--accordion-hover-background`
- `--tabcontainer-accordion-spacing`

### Sidepane

- `--sidepane-border-color`
- `--sidepane-text-color`
- `--sidepane-background-color`
- `--sidepane-hover-color`
- `--sidepane-button-background`
- `--sidepane-button-text`
- `--sidepane-button-hover`
- `--sidepane-button-active-text`
- `--sidepane-button-active-background`

### Popup / dialog

- `--popup-background`
- `--popup-border-color`
- `--popup-modal-width`

### Metadata dialog

- `--metadata-background`
- `--metadata-border-color`
- `--metadata-header-background`
- `--metadata-header-text`
- `--metadata-section-toggle-color`

### Layer tree

- `--layertree-indent`
- `--layertree-row-padding`
- `--layertree-checkbox-bg`
- `--layertree-checkbox-active-bg`

### Menus

- `--menu-hover-color`
- `--menu-background`
- `--menu-background-opacity`

### Spacing and radii

- `--space`
- `--radius-sm`
- `--radius-md`
- `--radius-lg`
- `--radius-circle`

## Notes for custom bundles

- Override variables in `:root {}` — no SCSS compilation required.
- All tokens have safe defaults and are backward-compatible; omitting an override leaves the Mapbender default unchanged.
- SCSS variables (`$ciColor`, `$buttonFirstColor`, etc.) remain available for compile-time use but are deprecated for theming — prefer the CSS custom properties listed above.
