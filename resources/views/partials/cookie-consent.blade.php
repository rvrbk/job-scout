{{--
    Cookie consent for Google Analytics.

    Params:
      $gaId           GA4 measurement id. Nothing is rendered when empty.
      $autoPageView   false when the app sends its own page_view events (SPA routers).

    Analytics is not loaded, and gtag() does not exist, until the visitor accepts.
    The choice is kept in localStorage under "cookie-consent" ("granted" or "denied").
    Call window.openCookieSettings() from a link to let visitors change their mind.
--}}
@if(!empty($gaId))
<style>
    #cookie-consent{position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:2147483000;max-width:34rem;margin:0 auto;padding:1rem 1.125rem;border-radius:.75rem;border:1px solid #e5e7eb;background:#fff;color:#1f2937;box-shadow:0 10px 30px rgba(0,0,0,.15);font:14px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
    #cookie-consent p{margin:0 0 .75rem}
    #cookie-consent .cookie-consent__actions{display:flex;gap:.5rem;justify-content:flex-end}
    #cookie-consent button{flex:1 1 0;max-width:9rem;padding:.5rem .875rem;border-radius:.5rem;border:1px solid #1f2937;background:#fff;color:#1f2937;font:inherit;font-weight:600;cursor:pointer}
    #cookie-consent button:hover{background:#f3f4f6}
    #cookie-consent button:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
    @media (prefers-color-scheme:dark){#cookie-consent{background:#1f2937;color:#f3f4f6;border-color:#374151}#cookie-consent button{background:#1f2937;color:#f3f4f6;border-color:#f3f4f6}#cookie-consent button:hover{background:#374151}}
</style>
<script>
    (function () {
        var GA_ID = @js($gaId);
        var AUTO_PAGE_VIEW = @js($autoPageView ?? true);
        var KEY = 'cookie-consent';

        function read() {
            try { return window.localStorage.getItem(KEY); } catch (e) { return null; }
        }

        function write(value) {
            try { window.localStorage.setItem(KEY, value); } catch (e) {}
        }

        function loadAnalytics(sendInitialPageView) {
            if (typeof window.gtag === 'function') {
                return;
            }

            window['ga-disable-' + GA_ID] = false;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', GA_ID, AUTO_PAGE_VIEW ? {} : { send_page_view: false });

            if (!document.getElementById('ga-gtag-js')) {
                var script = document.createElement('script');
                script.id = 'ga-gtag-js';
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID);
                document.head.appendChild(script);
            }

            if (sendInitialPageView && !AUTO_PAGE_VIEW) {
                window.gtag('event', 'page_view', {
                    page_path: window.location.pathname + window.location.search,
                    page_title: document.title
                });
            }
        }

        function removeAnalytics() {
            window['ga-disable-' + GA_ID] = true;
            window.gtag = undefined;

            var host = window.location.hostname.split('.');
            var domains = [window.location.hostname];
            for (var i = 1; i < host.length - 1; i++) {
                domains.push('.' + host.slice(i).join('.'));
            }

            document.cookie.split(';').forEach(function (cookie) {
                var name = cookie.split('=')[0].trim();
                if (name === '_ga' || name.indexOf('_ga_') === 0 || name === '_gid' || name.indexOf('_gat') === 0) {
                    domains.forEach(function (domain) {
                        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + domain;
                    });
                    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                }
            });
        }

        function closeBanner() {
            var banner = document.getElementById('cookie-consent');
            if (banner) { banner.remove(); }
        }

        function choose(value) {
            write(value);
            closeBanner();
            if (value === 'granted') {
                loadAnalytics(true);
            } else {
                removeAnalytics();
            }
        }

        function showBanner() {
            if (document.getElementById('cookie-consent')) { return; }

            var banner = document.createElement('div');
            banner.id = 'cookie-consent';
            banner.setAttribute('role', 'dialog');
            banner.setAttribute('aria-label', 'Cookie consent');
            banner.innerHTML =
                '<p>We use Google Analytics cookies to understand how this site is used. They are only set if you accept.</p>' +
                '<div class="cookie-consent__actions">' +
                '<button type="button" data-choice="denied">Decline</button>' +
                '<button type="button" data-choice="granted">Accept</button>' +
                '</div>';
            banner.addEventListener('click', function (event) {
                var choice = event.target && event.target.getAttribute && event.target.getAttribute('data-choice');
                if (choice) { choose(choice); }
            });
            document.body.appendChild(banner);
        }

        window.openCookieSettings = showBanner;

        var stored = read();
        if (stored === 'granted') {
            loadAnalytics(false);
        } else if (stored !== 'denied') {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showBanner);
            } else {
                showBanner();
            }
        }
    })();
</script>
@endif
