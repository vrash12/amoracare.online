/* AMOR reading preferences: no remote requests, tracking, or external overlay. */
(function () {
	'use strict';

	var storageKey = 'amor-reading-preferences-v1';
	var defaults = { textSize: 100, lineSpacing: false, letterSpacing: false, highContrast: false, underline: false, reduceMotion: false, readingGuide: false };
	var classes = { lineSpacing: 'amor-a11y-line-spacing', letterSpacing: 'amor-a11y-letter-spacing', highContrast: 'amor-a11y-contrast', underline: 'amor-a11y-underline', reduceMotion: 'amor-a11y-reduce-motion' };
	var root = document.documentElement;
	// Load after the landing-page stylesheet so percentages use its actual base size.
	var baseFontSize = parseFloat(window.getComputedStyle(root).fontSize) || 16;
	var preferences = Object.assign({}, defaults);
	try {
		var saved = JSON.parse(window.localStorage.getItem(storageKey));
		if (saved && typeof saved === 'object') {
			Object.keys(defaults).forEach(function (key) {
				if (key === 'textSize') {
					if ([100, 125, 150, 175, 200].indexOf(saved[key]) !== -1) preferences[key] = saved[key];
				} else if (typeof saved[key] === 'boolean') preferences[key] = saved[key];
			});
		}
	} catch (error) { /* Private browsing and unavailable storage still allow controls. */ }

	function applyPagePreferences() {
		if (preferences.textSize === 100) root.style.removeProperty('font-size');
		else root.style.setProperty('font-size', String(baseFontSize * preferences.textSize / 100) + 'px', 'important');
		root.classList.toggle('amor-a11y-text-large', preferences.textSize > 100);
		Object.keys(classes).forEach(function (key) { root.classList.toggle(classes[key], preferences[key]); });
	}
	applyPagePreferences();

	function init() {
		var trigger = document.getElementById('amor-a11y-trigger');
		var dialog = document.getElementById('amor-a11y-dialog');
		if (!trigger || !dialog) return;
		var increase = document.getElementById('amor-a11y-increase');
		var resetText = document.getElementById('amor-a11y-reset-text');
		var textValue = document.getElementById('amor-a11y-text-value');
		var status = document.getElementById('amor-a11y-status');
		var guide = document.getElementById('amor-reading-guide');
		var guideHelp = document.getElementById('amor-a11y-guide-help');
		var toggles = Array.prototype.slice.call(dialog.querySelectorAll('[data-amor-preference]'));
		var previousFocus = null;
		var guideY = Math.round(window.innerHeight / 2);
		var pointerFrame = 0;
		var inertElements = [];
		var announceTimer = 0;
		var isNative = typeof dialog.showModal === 'function';
		function isOpen() { return dialog.hasAttribute('open'); }

		function announce(message) {
			window.clearTimeout(announceTimer);
			status.textContent = '';
			announceTimer = window.setTimeout(function () { status.textContent = message; }, 40);
		}
		function updateGuide() {
			guide.hidden = !preferences.readingGuide || isOpen();
			guideY = Math.max(1, Math.min(window.innerHeight - 4, guideY));
			guide.style.top = String(guideY) + 'px';
			guideHelp.hidden = !preferences.readingGuide;
		}
		function updateControls() {
			textValue.textContent = String(preferences.textSize) + '%';
			increase.disabled = preferences.textSize === 200;
			resetText.disabled = preferences.textSize === 100;
			toggles.forEach(function (button) {
				var selected = preferences[button.getAttribute('data-amor-preference')];
				button.setAttribute('aria-pressed', selected ? 'true' : 'false');
				button.querySelector('.amor-a11y-state').textContent = selected ? 'On' : 'Off';
			});
			updateGuide();
		}
		function commit(message) {
			applyPagePreferences();
			updateControls();
			try { window.localStorage.setItem(storageKey, JSON.stringify(preferences)); } catch (error) { /* In-memory preferences remain available. */ }
			if (message) announce(message);
		}
		function restoreBackground() {
			inertElements.forEach(function (element) { element.inert = false; });
			inertElements = [];
		}
		function afterClose() {
			root.classList.remove('amor-a11y-modal-open');
			trigger.setAttribute('aria-expanded', 'false');
			restoreBackground();
			updateGuide();
			if (previousFocus && previousFocus.isConnected) previousFocus.focus({ preventScroll: true });
		}
		function closeDialog() {
			if (!isOpen()) return;
			if (isNative) dialog.close();
			else { dialog.removeAttribute('open'); afterClose(); }
		}
		trigger.addEventListener('click', function () {
			previousFocus = document.activeElement;
			if (isNative) dialog.showModal();
			else {
				dialog.setAttribute('open', '');
				dialog.setAttribute('role', 'dialog');
				dialog.setAttribute('aria-modal', 'true');
				Array.prototype.forEach.call(document.body.children, function (element) {
					if (element !== dialog && !element.contains(dialog) && !element.inert && element.tagName !== 'SCRIPT' && element.tagName !== 'STYLE') {
						element.inert = true;
						inertElements.push(element);
					}
				});
			}
			root.classList.add('amor-a11y-modal-open');
			trigger.setAttribute('aria-expanded', 'true');
			updateGuide();
			document.getElementById('amor-a11y-close').focus({ preventScroll: true });
		});
		document.getElementById('amor-a11y-close').addEventListener('click', closeDialog);
		dialog.querySelector('a[href="#accessibility"]').addEventListener('click', function () {
			var statement = document.getElementById('accessibility');
			if (statement) {
				var details = statement.closest('details');
				if (details) details.open = true;
				var focusTarget = details ? details.querySelector('summary') : statement;
				if (focusTarget.tagName !== 'SUMMARY' && !focusTarget.hasAttribute('tabindex')) focusTarget.setAttribute('tabindex', '-1');
				previousFocus = focusTarget;
			}
			closeDialog();
		});
		dialog.addEventListener('close', afterClose);
		dialog.addEventListener('cancel', function (event) { event.preventDefault(); closeDialog(); });
		dialog.addEventListener('click', function (event) {
			if (event.target !== dialog) return;
			var bounds = dialog.getBoundingClientRect();
			if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) closeDialog();
		});
		dialog.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); closeDialog(); return; }
			if (event.key !== 'Tab') return;
			var focusable = Array.prototype.filter.call(dialog.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]'), function (element) { return element.getClientRects().length > 0; });
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
			else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
		});
		increase.addEventListener('click', function () { preferences.textSize = Math.min(200, preferences.textSize + 25); commit('Page text size: ' + preferences.textSize + ' percent.'); if (increase.disabled) resetText.focus(); });
		resetText.addEventListener('click', function () { preferences.textSize = 100; commit('Page text size reset to 100 percent.'); increase.focus(); });
		toggles.forEach(function (button) {
			button.addEventListener('click', function () {
				var key = button.getAttribute('data-amor-preference');
				preferences[key] = !preferences[key];
				commit(key === 'readingGuide' && preferences[key] ? 'Reading guide on. Close this panel, then use your pointer or Alt plus the Up and Down arrow keys.' : '');
			});
		});
		document.getElementById('amor-a11y-reset-all').addEventListener('click', function () { preferences = Object.assign({}, defaults); commit('All website reading preferences reset. Your device’s reduced-motion preference is still respected.'); });
		function moveGuide(event) {
			if (!preferences.readingGuide || isOpen()) return;
			guideY = event.clientY;
			if (!pointerFrame) pointerFrame = window.requestAnimationFrame(function () { pointerFrame = 0; updateGuide(); });
		}
		document.addEventListener('pointermove', moveGuide, { passive: true });
		document.addEventListener('pointerdown', moveGuide, { passive: true });
		document.addEventListener('keydown', function (event) {
			if (!preferences.readingGuide || isOpen() || !event.altKey || event.ctrlKey || event.metaKey || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) return;
			if (event.target.closest('input, textarea, select, [contenteditable="true"]')) return;
			event.preventDefault();
			guideY += event.key === 'ArrowUp' ? -24 : 24;
			updateGuide();
		});
		window.addEventListener('resize', updateGuide, { passive: true });
		updateControls();
		trigger.hidden = false;
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
	else init();
}());
