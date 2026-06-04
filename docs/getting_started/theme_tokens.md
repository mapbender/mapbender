# Theme tokens (CSS custom properties)

This page lists all CSS custom properties defined in
`sass/libs/_css_custom_properties.scss`. Override them in a `:root {}` block in
your own stylesheet — no SCSS compilation needed.

See [css_custom_properties.md](../elements/css_custom_properties.md) for the
full SCSS variable → CSS property mapping.

## Token groups

### Typography

- `--font-family`
- `--font-size`

### Brand

- `--primary` — primary brand color
- `--primary-dark` — darker variant, derived at runtime from `--primary`

### Text

- `--text-color`
- `--text-color-inactive`
- `--text-color-button-inactive`
- `--light-text-color`
- `--error-color`
- `--focus-color`

### Layout and spacing

- `--space`
- `--popup-modal-width`
- `--radius-sm`
- `--radius-md`
- `--radius-lg`
- `--radius-circle`

### Backgrounds and borders

- `--background-color`
- `--border-color`
- `--panel-border-color`

### Checkboxes and toggles

- `--checkbox-checked`
- `--checkbox-unchecked`
- `--checkbox-bg`
- `--checkbox-active-bg`

### Menus

- `--menu-hover-color`
- `--menu-background`
- `--menu-background-opacity`

### Toolbar

- `--toolbar-background`
- `--toolbar-opacity`
- `--toolbar-text-color`
- `--toolbar-button-hover-opacity`
- `--toolbar-button-active-opacity`
- `--toolbar-button-active-background-color`
- `--toolbar-button-active-background-hover-color`

### Buttons (primary)

- `--button-font-size`
- `--button-background`
- `--button-text`
- `--button-border`
- `--button-hover-background`
- `--button-hover-text`
- `--button-active-background`
- `--button-active-text`
- `--button-focus-shadow`

### Buttons (white / outlined)

- `--button-white-foreground`
- `--button-white-background`
- `--button-white-background-hover`

### Buttons (critical / danger)

- `--button-critical-background`
- `--button-critical-text`
- `--button-critical-border`
- `--button-critical-hover-background`
- `--button-critical-hover-text`

### Accordion and tab containers

- `--accordion-font-size`
- `--accordion-background`
- `--accordion-text`
- `--accordion-active-background`
- `--accordion-active-text`
- `--accordion-hover-background`
- `--tabcontainer-accordion-spacing`

### Sidepane buttons

- `--sidepane-button-background`
- `--sidepane-button-text`
- `--sidepane-button-border`
- `--sidepane-button-color`
- `--sidepane-button-hover`
- `--sidepane-button-active-text`
- `--sidepane-button-active-background`

### Sidepane

- `--sidepane-border-color`
- `--sidepane-text-color`
- `--sidepane-inactive-text-color`
- `--sidepane-background-color`
- `--sidepane-hover-color`
- `--sidepane-active-background-color`
- `--sidepane-collapsed-width`
- `--sidepane-padding-left`
- `--sidepane-padding-right`
- `--sidepane-padding-y`

### Inputs and form controls

- `--input-background`
- `--input-foreground`
- `--input-border-color`
- `--input-focus-border-color`
- `--input-disabled-fg`
- `--slider-handle-background`
- `--slider-handle-border`
- `--slider-handle-text`

### Popup / dialog

- `--popup-background`
- `--popup-border-color`
- `--popup-modal-width`

### Metadata dialog

- `--metadata-background`
- `--metadata-border-color`
- `--metadata-heading-color`
- `--metadata-section-toggle-color`
- `--metadata-header-background`
- `--metadata-header-text`

### Layer tree

- `--layertree-indent`
- `--layertree-row-padding`
- `--layertree-checkbox-bg`
- `--layertree-checkbox-active-bg`
