# Migration: SCSS variables → CSS custom properties

This document describes how to adapt a customer bundle's theming code
to the CSS custom property system introduced in Mapbender 5.x.
The same principles apply to any bundle that overrides Mapbender's visual appearance.

> **Note**: SCSS variables (`$ciColor`, `$buttonFirstColor`, etc.) are now deprecated
> for theming purposes. However, they will **not break** — existing bundles continue
> to work without changes. Migration to CSS custom properties is recommended for future
> maintainability and runtime responsiveness, but not mandatory.

---

## 1. How the build works

Mapbender compiles all CSS for an application in a single SCSS pass.
All files are concatenated into one string before compilation.
The fixed order is:

```
1. getSassVariablesAssets() files   ← SCSS variable overrides
2. app custom CSS variables
3. theme entry (fullscreen/mobile/mapbender3.scss)
     └─ @import "libs/css_custom_properties"   ← emits :root {} from SCSS vars
     └─ @import element and module SCSS
```

`_css_custom_properties.scss` uses `#{$varName}` interpolation, so it always
reads the final values of SCSS variables — including any customer overrides from step 1.

---

## 2. SCSS variable overrides (no change required)

Override SCSS variables by appending a file via `getSassVariablesAssets()`.
This continues to work without modification.

You may add zero, one, or multiple files. The file name and path are chosen by you.

### Example

```php
// Template class
public function getSassVariablesAssets(Application $application)
{
    $assets = parent::getSassVariablesAssets($application);
    $assets[] = '@YourBundle/Resources/public/sass/theme_variables.scss';
    return $assets;
}
```

```scss
// theme_variables.scss (any name is fine)
$ciColor: #e84d00;
$buttonFirstColor: $ciColor;
```

The values are compiled into `:root { --primary: #e84d00; … }` automatically.

---

## 3. File-level variable declarations (action may be required)

Element and module SCSS files used to redeclare scoped fallback variables at
the top. All of these have been removed.

### Before

```scss
// _button.scss (develop branch)
$buttonTextColor: #fff !default;
$buttonBorderColor: #333 !default;
```

### After

```scss
// _button.scss (refactored) — no local declarations; CSS vars used directly
.button { color: var(--button-text); border-color: var(--button-border); }
```

**If your bundle set these variables expecting them to be picked up by element
files**, move the overrides to your SCSS variables file (via `getSassVariablesAssets()`)
or switch to the corresponding CSS custom property.

**If a variable you were setting does not appear in `_variables.scss`**, it was
file-level only and is now gone entirely. Setting it has no effect and produces
no compile error. Use the CSS custom property from the mapping table below instead.

---

## 4. SCSS functions → CSS functions (action required for runtime theming)

SCSS functions (`darken()`, `mix()`, `lighten()`) cannot operate on CSS custom
properties. Expressions using them still compile, but produce a fixed value that
does not react to runtime CSS variable overrides.

### Before

```scss
.my-highlight {
    background-color: darken($ciColor, 10%);
}
```

### After

```scss
.my-highlight {
    background-color: color-mix(in srgb, var(--primary), black 10%);
}
```

| SCSS | CSS equivalent |
|---|---|
| `darken($var, N%)` | `color-mix(in srgb, var(--token), black N%)` |
| `lighten($var, N%)` | `color-mix(in srgb, var(--token), white N%)` |
| `mix($a, $b, 80%)` | `color-mix(in srgb, var(--a), var(--b) 20%)` |
| `$space / 2` | `calc(var(--space) / 2)` |

---

## 5. Runtime CSS override (new recommended approach)

Override CSS custom properties in a plain CSS file — no SCSS compilation required.

```css
/* customer-bundle/public/css/theme.css */
:root {
    --primary: #e84d00;
    --button-background: #e84d00;
    --toolbar-background: #f5f0ee;
}
```

A `:root {}` block appearing later in the cascade overrides the compiled values
from `_css_custom_properties.scss`.

---

## 6. Full token reference

Properties marked with a SCSS variable are derived from that variable at compile
time. Properties without one have hardcoded or derived defaults and can only be
overridden at runtime via `:root {}`.

### Typography

| CSS custom property | SCSS variable |
|---|---|
| `--font-family` | `$fontFamily` |
| `--font-size` | `$fontSize` |

### Brand

| CSS custom property | SCSS variable |
|---|---|
| `--primary` | `$ciColor` |
| `--primary-dark` | *(derived: `color-mix(--primary, black 20%)`)* |

### Text

| CSS custom property | SCSS variable |
|---|---|
| `--text-color` | `$textColor` |
| `--text-color-inactive` | *(hardcoded)* |
| `--text-color-button-inactive` | *(hardcoded)* |
| `--light-text-color` | `$lightFontColor` |
| `--error-color` | `$errorColor` |
| `--focus-color` | *(derived: `var(--primary)`)* |

### Layout and spacing

| CSS custom property | SCSS variable |
|---|---|
| `--space` | `$space` |
| `--popup-modal-width` | `$popupModalWidth` |
| `--radius-sm` | *(hardcoded)* |
| `--radius-md` | *(hardcoded)* |
| `--radius-lg` | *(hardcoded)* |
| `--radius-circle` | *(hardcoded)* |

### Backgrounds and borders

| CSS custom property | SCSS variable |
|---|---|
| `--background-color` | `$backgroundColor` |
| `--border-color` | *(hardcoded)* |
| `--panel-border-color` | `$panelBorderColor` |

### Checkboxes and toggles

| CSS custom property | SCSS variable |
|---|---|
| `--checkbox-checked` | *(derived: `var(--primary)`)* |
| `--checkbox-unchecked` | *(hardcoded)* |
| `--checkbox-bg` | *(hardcoded)* |
| `--checkbox-active-bg` | *(hardcoded)* |

### Menus

| CSS custom property | SCSS variable |
|---|---|
| `--menu-hover-color` | *(hardcoded)* |
| `--menu-background` | `$menuBackgroundColor` |
| `--menu-background-opacity` | `$menuBackgroundOpacity` |

### Toolbar

| CSS custom property | SCSS variable |
|---|---|
| `--toolbar-background` | `$toolBarBackgroundColor` |
| `--toolbar-opacity` | `$toolBarOpacity` |
| `--toolbar-text-color` | *(derived: `var(--text-color)`)* |
| `--toolbar-button-hover-opacity` | `$toolBarButtonHoverOpacity` |
| `--toolbar-button-active-opacity` | `$toolBarButtonActiveOpacity` |
| `--toolbar-button-active-background-color` | *(derived: `var(--primary)`)* |
| `--toolbar-button-active-background-hover-color` | *(derived: `var(--primary-dark)`)* |

### Buttons (primary)

| CSS custom property | SCSS variable |
|---|---|
| `--button-font-size` | `$buttonFontSize` |
| `--button-background` | `$buttonFirstColor` |
| `--button-text` | `$buttonTextColor` |
| `--button-border` | `$buttonBorderColor` |
| `--button-hover-background` | `$buttonHoverColor` |
| `--button-hover-text` | `$buttonHoverTextColor` |
| `--button-active-background` | `$buttonFirstActiveColor` |
| `--button-active-text` | `$buttonActiveTextColor` |
| `--button-focus-shadow` | `$buttonFirstActiveColor` |

### Buttons (white / outlined)

| CSS custom property | SCSS variable |
|---|---|
| `--button-white-foreground` | *(derived: `var(--text-color)`)* |
| `--button-white-background` | *(derived: `var(--background-color)`)* |
| `--button-white-background-hover` | *(hardcoded)* |

### Buttons (critical / danger)

| CSS custom property | SCSS variable |
|---|---|
| `--button-critical-background` | `$buttonCriticalFirstColor` |
| `--button-critical-text` | `$buttonCriticalTextColor` |
| `--button-critical-border` | `$buttonCriticalBorderColor` |
| `--button-critical-hover-background` | `$buttonCriticalHoverColor` |
| `--button-critical-hover-text` | `$buttonCriticalHoverTextColor` |

### Accordion and tab containers

| CSS custom property | SCSS variable |
|---|---|
| `--accordion-font-size` | `$accordionFontSize` |
| `--accordion-background` | `$accordionBackgroundColor` |
| `--accordion-text` | `$accordionTextColor` |
| `--accordion-active-background` | `$accordionActiveBackgroundColor` |
| `--accordion-active-text` | `$accordionActiveTextColor` |
| `--accordion-hover-background` | `$accordionHoverBackgroundColor` |
| `--tabcontainer-accordion-spacing` | *(hardcoded)* |

### Sidepane buttons

| CSS custom property | SCSS variable |
|---|---|
| `--sidepane-button-background` | `$sidepaneButtonBackgroundColor` |
| `--sidepane-button-text` | `$sidepaneButtonTextColor` |
| `--sidepane-button-border` | `$sidepaneButtonBorderColor` |
| `--sidepane-button-color` | *(derived: `var(--text-color)`)* |
| `--sidepane-button-hover` | `$sidepaneButtonHoverColor` |
| `--sidepane-button-active-text` | `$sidepaneButtonActiveTextColor` |
| `--sidepane-button-active-background` | `$sidepaneButtonActiveBackgroundColor` |

### Sidepane

| CSS custom property | SCSS variable |
|---|---|
| `--sidepane-border-color` | *(derived: `var(--border-color)`)* |
| `--sidepane-text-color` | *(derived: `var(--text-color)`)* |
| `--sidepane-inactive-text-color` | *(derived: `var(--text-color-inactive)`)* |
| `--sidepane-background-color` | *(hardcoded)* |
| `--sidepane-hover-color` | *(derived: `var(--menu-hover-color)`)* |
| `--sidepane-active-background-color` | *(hardcoded)* |
| `--sidepane-collapsed-width` | *(hardcoded)* |
| `--sidepane-padding-left` | *(hardcoded)* |
| `--sidepane-padding-right` | *(hardcoded)* |
| `--sidepane-padding-y` | *(hardcoded)* |

### Inputs and form controls

| CSS custom property | SCSS variable |
|---|---|
| `--input-background` | `$inputBackgroundColor` |
| `--input-foreground` | `$inputForegroundColor` |
| `--input-border-color` | *(hardcoded)* |
| `--input-focus-border-color` | *(derived: `var(--primary)`)* |
| `--input-disabled-fg` | *(derived: `color-mix(--input-foreground, white 40%)`)* |
| `--slider-handle-background` | `$sliderHandleBackgroundColor` |
| `--slider-handle-border` | *(derived: `color-mix(--slider-handle-background, black 20%)`)* |
| `--slider-handle-text` | *(derived: `var(--light-text-color)`)* |

### Popup / dialog

| CSS custom property | SCSS variable |
|---|---|
| `--popup-background` | *(derived: `var(--background-color)`)* |
| `--popup-border-color` | *(derived: `var(--panel-border-color)`)* |

### Metadata dialog

| CSS custom property | SCSS variable |
|---|---|
| `--metadata-background` | *(derived: `var(--popup-background)`)* |
| `--metadata-border-color` | *(derived: `var(--popup-border-color)`)* |
| `--metadata-heading-color` | *(derived: `var(--text-color)`)* |
| `--metadata-section-toggle-color` | *(derived: `var(--primary)`)* |
| `--metadata-header-background` | *(derived: `var(--button-active-background)`)* |
| `--metadata-header-text` | *(derived: `var(--button-active-text)`)* |

### Layer tree

| CSS custom property | SCSS variable |
|---|---|
| `--layertree-indent` | *(hardcoded)* |
| `--layertree-row-padding` | *(hardcoded)* |
| `--layertree-checkbox-bg` | *(derived: `var(--checkbox-bg)`)* |
| `--layertree-checkbox-active-bg` | *(derived: `var(--checkbox-active-bg)`)* |

---

## 7. Migration checklist

1. `getSassVariablesAssets()` — no changes needed if you call `parent::`.
2. If you replace `getSassVariablesAssets()` entirely, include
   `@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss` first.
   This is required regardless of whether your own code uses SCSS variables:
   `_css_custom_properties.scss` (part of the theme entry) uses `#{$varName}`
   interpolation for every CSS custom property and will fail to compile without
   the defaults from `_variables.scss`.
3. Check whether any SCSS variables you set are absent from `_variables.scss`.
   If so, replace them with the CSS custom property from the mapping table.
4. Replace `darken()` / `mix()` / `lighten()` on theme variables with
   `color-mix()` if runtime override responsiveness is needed.
5. Check each SCSS variable your bundle sets against `_variables.scss`.
   Any variable absent from that file is a no-op — it is not referenced anywhere
   in Mapbender's SCSS. If it appears in the mapping table (Section 6), use the
   CSS custom property instead. If it does not appear in the mapping table, it
   has no replacement — remove the assignment.
