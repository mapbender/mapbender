/**
 * Generic mode-switcher utility (props / template toggle).
 * Used by per-layer tooltip popovers (StyleInstanceEditor) and
 * the page-level feature info section.
 */

function initModeSwitcher(switcher, propsPanel, templatePanel, modeInput, initialMode) {
    if (!switcher) return;
    setMode(switcher, propsPanel, templatePanel, modeInput, initialMode);
    switcher.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
            setMode(switcher, propsPanel, templatePanel, modeInput, radio.value);
        });
    });
}

function setMode(switcher, propsPanel, templatePanel, modeInput, mode) {
    switcher.querySelectorAll('input[type="radio"]').forEach(r => { r.checked = r.value === mode; });
    if (propsPanel)    propsPanel.style.display    = mode === 'props'    ? '' : 'none';
    if (templatePanel) templatePanel.style.display = mode === 'template' ? '' : 'none';
    if (modeInput) modeInput.value = mode === 'template' ? 'template' : '';
}

// Feature info mode switcher — page-level, independent of the layer table
$(function() {
    const modeInput = document.querySelector('input[id$="_featureInfoMode"]');
    const initialMode = modeInput?.value === 'template' ? 'template' : 'props';
    initModeSwitcher(
        document.querySelector('.featureinfo-mode-switcher'),
        document.querySelector('.featureinfo-props-panel'),
        document.querySelector('.featureinfo-template-panel'),
        modeInput,
        initialMode
    );
});
