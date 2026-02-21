/**
 * Frontend JavaScript for kkorsakov-htmx plugin.
 *
 * @package Kkorsakov\Htmx
 * @since 1.0.0
 */

(function() {
	'use strict';

	if (typeof htmx === 'undefined') {
		console.error('kkorsakov-htmx: HTMX library is not loaded.');
		return;
	}

	document.body.addEventListener('htmx:configRequest', function(event) {
		if (typeof kkorsakovHtmxSettings !== 'undefined') {
			if (kkorsakovHtmxSettings.nonce) {
				event.detail.headers['X-WP-Nonce'] = kkorsakovHtmxSettings.nonce;
			}
		}
	});

	document.body.addEventListener('htmx:beforeRequest', function(event) {
		if (typeof kkorsakovHtmxSettings !== 'undefined' && kkorsakovHtmxSettings.endpoint) {
			var target = event.detail.pathInfo ? event.detail.pathInfo.requestPath : '';
			var params = event.detail.parameters || {};
			if (target.indexOf('kkorsakov-htmx/v1/fragment') !== -1 && !params.target) {
				var requestPath = event.detail.pathInfo ? event.detail.pathInfo.finalRequestPath : '';
				if (requestPath) {
					var url = new URL(requestPath, window.location.origin);
					if (!url.searchParams.has('target') && event.detail.target) {
						var selector = event.detail.target.getAttribute('hx-target') || '';
						if (selector) {
							url.searchParams.set('target', selector);
							event.detail.pathInfo.finalRequestPath = url.pathname + url.search;
						}
					}
				}
			}
		}
	});

	document.body.addEventListener('htmx:afterOnLoad', function(event) {
		var trigger = event.detail.xhr.getResponseHeader('HX-Trigger');
		if (trigger) {
			var customEvent = new CustomEvent('kkorsakovHtmx:' + trigger, {
				detail: {
					xhr: event.detail.xhr,
					target: event.detail.target
				}
			});
			document.dispatchEvent(customEvent);
		}
	});

	document.body.addEventListener('htmx:responseError', function(event) {
		console.error('kkorsakov-htmx: Response error', event.detail.xhr.status, event.detail.xhr.statusText);
	});

	document.body.addEventListener('htmx:sendError', function(event) {
		console.error('kkorsakov-htmx: Send error', event.detail);
	});

	window.kkorsakovHtmx = {
		refresh: function(element) {
			if (element && htmx) {
				htmx.trigger(element, 'refresh');
			}
		},
		ajax: function(method, url, config) {
			if (htmx) {
				htmx.ajax(method, url, config || {});
			}
		},
		loadFragment: function(target, context, args) {
			if (typeof kkorsakovHtmxSettings === 'undefined') {
				console.error('kkorsakov-htmx: Settings not available.');
				return;
			}

			var url = kkorsakovHtmxSettings.endpoint + '?target=' + encodeURIComponent(target);

			if (context) {
				url += '&context=' + encodeURIComponent(context);
			}

			if (args && typeof args === 'object') {
				Object.keys(args).forEach(function(key) {
					url += '&args[' + encodeURIComponent(key) + ']=' + encodeURIComponent(args[key]);
				});
			}

			return url;
		}
	};

})();
