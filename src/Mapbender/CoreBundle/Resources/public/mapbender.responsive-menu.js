!(function($) {
	window.Mapbender = window.Mapbender || {};

	Mapbender.ResponsiveMenu = class ResponsiveMenu {
		constructor() {
			this.desktopBreakpoint = 1200;
			this.dropdownOffset = 3;
			this._queued = false;
		}

		init() {
			$(() => this.syncToolbars());
			$(window).on('resize', () => this.queueSyncToolbars());
			$(document).on('click', '.toolBar .menu-wrapper.dropup > button', (event) => {
				const menuWrapper = event.currentTarget.closest('.menu-wrapper');
				window.requestAnimationFrame(() => this.updateDropupLayout(menuWrapper));
			});
		}

		getMenuItemMap(toolbar) {
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

		isInlineMode(toolbar, screenWidth) {
			if (toolbar.classList.contains('has-menu-mobile')) {
				return screenWidth >= this.desktopBreakpoint;
			}
			if (toolbar.classList.contains('has-menu-desktop')) {
				return screenWidth < this.desktopBreakpoint;
			}
			return false;
		}

		resetMenuWrapperState(menuWrapper) {
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

		updateDropupLayout(menuWrapper) {
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

			menuWrapper.style.setProperty('--dropdown-bottom', (window.innerHeight - toolbarRect.top - this.dropdownOffset) + 'px');
			menuWrapper.style.setProperty('--dropdown-menu-max-height', Math.max(0, toolbarRect.top - topToolbarHeight - this.dropdownOffset) + 'px');
		}

		moveToolbarItems(toolbar, inlineMode) {
			const inlineList = toolbar.querySelector('[data-toolbar-inline-list]');
			const dropdownMenu = toolbar.querySelector('[data-toolbar-menu-list]');
			const menuWrapper = dropdownMenu && dropdownMenu.closest('.menu-wrapper');

			if (!inlineList || !dropdownMenu || !menuWrapper) {
				return;
			}

			const inlineFragment = document.createDocumentFragment();
			const menuFragment = document.createDocumentFragment();

			this.getMenuItemMap(toolbar).forEach((item) => {
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
				this.resetMenuWrapperState(menuWrapper);
				dropdownMenu.style.setProperty('display', 'none', 'important');
				menuWrapper.style.setProperty('display', 'none', 'important');
			} else {
				dropdownMenu.style.removeProperty('display');
				menuWrapper.style.removeProperty('display');
			}

            $('.toolBarItem .forced-menu-label').toggleClass('d-none', menuWrapper.style.display === 'none');

			this.updateDropupLayout(menuWrapper);
		}

		syncToolbar(toolbar) {
			this.moveToolbarItems(toolbar, this.isInlineMode(toolbar, window.innerWidth));
		}

		syncToolbars() {
			document.querySelectorAll('.toolBar.has-menu-mobile, .toolBar.has-menu-desktop').forEach((toolbar) => this.syncToolbar(toolbar));
			document.querySelectorAll('.toolBar .menu-wrapper.dropup').forEach((menuWrapper) => this.updateDropupLayout(menuWrapper));
		}

		queueSyncToolbars() {
			if (this._queued) {
				return;
			}
			this._queued = true;
			window.requestAnimationFrame(() => {
				this._queued = false;
				this.syncToolbars();
			});
		}
	};

	$(function() {
		if (!Mapbender.responsiveMenu) {
			Mapbender.responsiveMenu = new Mapbender.ResponsiveMenu();
			Mapbender.responsiveMenu.init();
		}
	});
}(jQuery));

