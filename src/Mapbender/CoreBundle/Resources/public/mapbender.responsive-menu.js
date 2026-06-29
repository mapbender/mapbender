!(function($) {
	const DESKTOP_BREAKPOINT = 1200;
	const DROPDOWN_OFFSET = 3;

	function getMenuItemMap(toolbar) {
		const serialized = toolbar.dataset.menuItemMap || '[]';

		if (toolbar._responsiveMenuMapSource !== serialized) {
			toolbar._responsiveMenuMapSource = serialized;
			try {
				toolbar._responsiveMenuMap = JSON.parse(serialized);
			} catch (error) {
				toolbar._responsiveMenuMap = [];
			}
		}
		return toolbar._responsiveMenuMap || [];
	}

	function isInlineMode(toolbar, screenWidth) {
		if (toolbar.classList.contains('has-menu-mobile')) {
			return screenWidth >= DESKTOP_BREAKPOINT;
		}
		if (toolbar.classList.contains('has-menu-desktop')) {
			return screenWidth < DESKTOP_BREAKPOINT;
		}
		return false;
	}

	function resetMenuWrapperState(menuWrapper) {
		const button = menuWrapper.querySelector('button');

		menuWrapper.classList.remove('open');
		if (!button) {
			return;
		}

		button.classList.remove('active');
		const icon = button.querySelector('i');
		if (icon) {
			icon.classList.add('fa-bars');
			icon.classList.remove('fa-xmark');
		}
	}

	function updateDropupLayout(menuWrapper) {
		if (!menuWrapper || !menuWrapper.classList.contains('dropup')) {
			return;
		}

		if (!menuWrapper.classList.contains('open')) {
			menuWrapper.style.removeProperty('--dropdown-bottom');
			menuWrapper.style.removeProperty('--dropdown-menu-max-height');
			return;
		}

		const toolbar = menuWrapper.closest('.toolBar');
		if (!toolbar) {
			return;
		}

		const toolbarRect = toolbar.getBoundingClientRect();
		const topToolbar = document.querySelector('.toolBar.top');
		const topToolbarHeight = topToolbar ? topToolbar.getBoundingClientRect().height : 0;

		menuWrapper.style.setProperty('--dropdown-bottom', (window.innerHeight - toolbarRect.top - DROPDOWN_OFFSET) + 'px');
		menuWrapper.style.setProperty('--dropdown-menu-max-height', Math.max(0, toolbarRect.top - topToolbarHeight - DROPDOWN_OFFSET) + 'px');
	}

	function moveToolbarItems(toolbar, inlineMode) {
		const inlineList = toolbar.querySelector('[data-toolbar-inline-list]');
		const dropdownMenu = toolbar.querySelector('[data-toolbar-menu-list]');
		const menuWrapper = dropdownMenu && dropdownMenu.closest('.menu-wrapper');

		if (!inlineList || !dropdownMenu || !menuWrapper) {
			return;
		}

		const inlineFragment = document.createDocumentFragment();
		const menuFragment = document.createDocumentFragment();

		getMenuItemMap(toolbar).forEach((item) => {
			const element = document.getElementById(item.id);

			if (!element || !toolbar.contains(element)) {
				return;
			}

			if (inlineMode || item.type === 'inline_items') {
				inlineFragment.appendChild(element);
			} else {
				menuFragment.appendChild(element);
			}
		});

		inlineList.appendChild(inlineFragment);
		dropdownMenu.appendChild(menuFragment);

		if (inlineMode) {
			resetMenuWrapperState(menuWrapper);
			dropdownMenu.style.setProperty('display', 'none', 'important');
			menuWrapper.style.setProperty('display', 'none', 'important');
		} else {
			dropdownMenu.style.removeProperty('display');
			menuWrapper.style.removeProperty('display');
		}

		updateDropupLayout(menuWrapper);
	}

	function syncToolbar(toolbar) {
		moveToolbarItems(toolbar, isInlineMode(toolbar, window.innerWidth));
	}

	function syncToolbars() {
		document.querySelectorAll('.toolBar.has-menu-mobile, .toolBar.has-menu-desktop').forEach(syncToolbar);
		document.querySelectorAll('.toolBar .menu-wrapper.dropup').forEach(updateDropupLayout);
	}

	let queued = false;
	function queueSyncToolbars() {
		if (queued) {
			return;
		}
		queued = true;
		window.requestAnimationFrame(() => {
			queued = false;
			syncToolbars();
		});
	}

	$(syncToolbars);
	$(window).on('resize', queueSyncToolbars);
	$(document).on('click', '.toolBar .menu-wrapper.dropup > button', function() {
		const menuWrapper = this.closest('.menu-wrapper');

		window.requestAnimationFrame(() => {
			updateDropupLayout(menuWrapper);
		});
	});
}(jQuery));

