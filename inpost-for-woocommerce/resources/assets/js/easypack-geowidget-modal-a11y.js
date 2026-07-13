/**
 * WCAG helpers for InPost geowidget jBox modals on checkout.
 */
(function (window) {
	'use strict';

	var EasypackGeowidgetModalA11y = {
		lastFocus: null,
		trapHandler: null,
		escapeHandler: null,
		activeJbox: null,
		inertedElements: [],

		getLabels: function () {
			var cfg = window.easypack_geowidget_modal_a11y || {};
			return {
				close: cfg.close_label || 'Zamknij',
				dialog: cfg.dialog_label || 'Wybierz paczkomat',
			};
		},

		getWrapperElement: function (jbox) {
			if (!jbox || !jbox.wrapper) {
				return document.querySelector('.jBox-wrapper.jBox-Modal.jBox-open');
			}

			return jbox.wrapper[0] || jbox.wrapper;
		},

		releaseBodyScroll: function () {
			document.body.classList.remove('unscrollable');
			document.documentElement.classList.remove('unscrollable');

			if (window.jQuery) {
				window.jQuery('body, html').removeClass('unscrollable');
			}
		},

		setBackgroundInert: function (isInert) {
			var self = this;

			if (!isInert) {
				self.inertedElements.forEach(function (el) {
					el.removeAttribute('inert');
					el.removeAttribute('aria-hidden');
				});
				self.inertedElements = [];
				return;
			}

			Array.prototype.forEach.call(document.body.children, function (child) {
				if (
					child.classList.contains('jBox-wrapper') ||
					child.classList.contains('jBox-overlay') ||
					child.id === 'wpadminbar'
				) {
					return;
				}

				child.setAttribute('inert', '');
				child.setAttribute('aria-hidden', 'true');
				self.inertedElements.push(child);
			});
		},

		enhanceCloseButton: function (wrapper) {
			var labels = this.getLabels();
			var closeButton = wrapper.querySelector('.jBox-closeButton');

			if (!closeButton) {
				return;
			}

			closeButton.setAttribute('tabindex', '0');
			closeButton.setAttribute('role', 'button');
			closeButton.setAttribute('aria-label', labels.close);

			if (!closeButton.dataset.easypackA11yBound) {
				closeButton.dataset.easypackA11yBound = '1';
				closeButton.addEventListener('keydown', function (event) {
					if (event.key === 'Enter' || event.key === ' ') {
						event.preventDefault();
						closeButton.click();
					}
				});
			}
		},

		setDialogAttributes: function (wrapper) {
			var labels = this.getLabels();
			var title = wrapper.querySelector('.jBox-title');

			wrapper.setAttribute('role', 'dialog');
			wrapper.setAttribute('aria-modal', 'true');
			wrapper.setAttribute('aria-label', labels.dialog);

			if (title) {
				if (!title.id) {
					title.id = 'easypack-geowidget-modal-title';
				}
				wrapper.setAttribute('aria-labelledby', title.id);
			}
		},

		getFocusableElements: function (wrapper) {
			return Array.prototype.filter.call(
				wrapper.querySelectorAll(
					'button, [href], input, select, textarea, iframe, .jBox-closeButton, [tabindex]:not([tabindex="-1"])'
				),
				function (el) {
					return !el.disabled && el.getAttribute('aria-hidden') !== 'true';
				}
			);
		},

		installFocusTrap: function (wrapper) {
			var self = this;

			if (self.trapHandler) {
				document.removeEventListener('keydown', self.trapHandler, true);
			}

			self.trapHandler = function (event) {
				if (event.key !== 'Tab' || !wrapper.isConnected) {
					return;
				}

				var focusable = self.getFocusableElements(wrapper);

				if (!focusable.length) {
					event.preventDefault();
					return;
				}

				var first = focusable[0];
				var last = focusable[focusable.length - 1];
				var active = document.activeElement;

				if (event.shiftKey && active === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && active === last) {
					event.preventDefault();
					first.focus();
				}
			};

			document.addEventListener('keydown', self.trapHandler, true);
		},

		removeFocusTrap: function () {
			if (this.trapHandler) {
				document.removeEventListener('keydown', this.trapHandler, true);
				this.trapHandler = null;
			}
		},

		installEscapeHandler: function (jbox) {
			var self = this;

			self.activeJbox = jbox;

			if (self.escapeHandler) {
				document.removeEventListener('keydown', self.escapeHandler, true);
			}

			self.escapeHandler = function (event) {
				if (event.key !== 'Escape' && event.key !== 'Esc') {
					return;
				}

				var wrapper = self.getWrapperElement(jbox);

				if (!wrapper || !wrapper.isConnected) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				if (jbox && typeof jbox.close === 'function') {
					jbox.close();
				}
			};

			document.addEventListener('keydown', self.escapeHandler, true);
		},

		removeEscapeHandler: function () {
			if (this.escapeHandler) {
				document.removeEventListener('keydown', this.escapeHandler, true);
				this.escapeHandler = null;
			}

			this.activeJbox = null;
		},

		focusGeowidgetSearch: function (wrapper) {
			var geowidget = wrapper.querySelector('inpost-geowidget');

			if (!geowidget || !geowidget.connection || !geowidget.connection.promise) {
				return Promise.resolve(false);
			}

			return geowidget.connection.promise
				.then(function (api) {
					if (api && typeof api.clearSearch === 'function') {
						api.clearSearch();
						return true;
					}

					var iframe = geowidget.iframe || wrapper.querySelector('iframe');

					if (iframe) {
						iframe.setAttribute('tabindex', '-1');
						iframe.focus();
					}

					return false;
				})
				.catch(function () {
					return false;
				});
		},

		waitForGeowidgetSearchFocus: function (wrapper, attempt) {
			var self = this;
			var tries = typeof attempt === 'number' ? attempt : 0;

			return self.focusGeowidgetSearch(wrapper).then(function (focused) {
				if (focused || tries >= 12) {
					return focused;
				}

				return new Promise(function (resolve) {
					window.setTimeout(function () {
						resolve(self.waitForGeowidgetSearchFocus(wrapper, tries + 1));
					}, 150);
				});
			});
		},

		onOpen: function (jbox) {
			var wrapper = this.getWrapperElement(jbox);

			if (!wrapper) {
				return;
			}

			this.lastFocus = document.activeElement;
			this.setBackgroundInert(true);
			this.setDialogAttributes(wrapper);
			this.enhanceCloseButton(wrapper);
			this.installFocusTrap(wrapper);
			this.installEscapeHandler(jbox);
		},

		onOpenComplete: function (jbox) {
			var wrapper = this.getWrapperElement(jbox);

			if (!wrapper) {
				return;
			}

			this.enhanceCloseButton(wrapper);
			this.waitForGeowidgetSearchFocus(wrapper, 0);
		},

		onCloseComplete: function () {
			this.removeFocusTrap();
			this.removeEscapeHandler();
			this.setBackgroundInert(false);
			this.releaseBodyScroll();

			if (this.lastFocus && typeof this.lastFocus.focus === 'function') {
				try {
					this.lastFocus.focus();
				} catch (error) {
					// Ignore stale focus targets after checkout refresh.
				}
			}

			this.lastFocus = null;
		},

		getOptions: function (userOptions) {
			var self = this;
			var options = userOptions || {};

			return Object.assign({}, options, {
				closeButton: 'title',
				overlay: options.overlay !== false,
				closeOnEsc: options.closeOnEsc !== false,
				closeOnClick: options.closeOnClick || 'overlay',
				blockScroll: options.blockScroll !== false,
				onOpen: function () {
					self.onOpen(this);

					if (typeof options.onOpen === 'function') {
						options.onOpen.call(this);
					}
				},
				onOpenComplete: function () {
					self.onOpenComplete(this);

					if (typeof options.onOpenComplete === 'function') {
						options.onOpenComplete.call(this);
					}
				},
				onCloseComplete: function () {
					if (typeof options.onCloseComplete === 'function') {
						options.onCloseComplete.call(this);
					}

					self.onCloseComplete();
				},
			});
		},
	};

	window.EasypackGeowidgetModalA11y = EasypackGeowidgetModalA11y;
})(window);
