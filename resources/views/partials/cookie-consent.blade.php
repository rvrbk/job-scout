{{--
    Cookie consent for Google Analytics.

    Params:
      $gaId           GA4 measurement id. Nothing is rendered when empty.
      $autoPageView   false when the app sends its own page_view events (SPA routers).
      $theme          CSS custom properties that style the banner to match the site:
                      --cc-bg, --cc-fg, --cc-border, --cc-radius, --cc-font, --cc-shadow,
                      --cc-accent, --cc-accent-fg, --cc-btn-radius, --cc-btn-weight,
                      --cc-btn-transform, --cc-btn-spacing, --cc-bar.
      $extraCss       Optional extra CSS for site specific details.

    Analytics is not loaded, and gtag() does not exist, until the visitor accepts.
    The choice is kept in localStorage under "cookie-consent" ("granted" or "denied").

    The banner follows the page language (<html lang>). Apps whose language changes
    without a reload call window.setCookieConsentLanguage('xx'). Call
    window.openCookieSettings() from a link to let visitors change their mind.
--}}
@if(!empty($gaId))
<style>
    #cookie-consent{position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:2147483000;max-width:34rem;margin:0 auto;padding:1rem 1.125rem;box-sizing:border-box;background:var(--cc-bg,#fff);color:var(--cc-fg,#1f2937);border:1px solid var(--cc-border,#e5e7eb);border-top:var(--cc-bar,1px solid var(--cc-border,#e5e7eb));border-radius:var(--cc-radius,.75rem);box-shadow:var(--cc-shadow,0 10px 30px rgba(0,0,0,.15));font:400 14px/1.5 var(--cc-font,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif);animation:cc-in .25s ease-out}
    #cookie-consent p{margin:0 0 .875rem}
    #cookie-consent .cc-actions{display:flex;gap:.5rem}
    #cookie-consent button{flex:1 1 0;padding:.55rem .875rem;border-radius:var(--cc-btn-radius,.5rem);border:1px solid var(--cc-accent,#1f2937);font:inherit;font-weight:var(--cc-btn-weight,600);text-transform:var(--cc-btn-transform,none);letter-spacing:var(--cc-btn-spacing,normal);cursor:pointer;transition:filter .15s ease,background .15s ease,color .15s ease}
    #cookie-consent .cc-decline{background:transparent;color:var(--cc-fg,#1f2937);border-color:var(--cc-border-strong,var(--cc-accent,#1f2937))}
    #cookie-consent .cc-accept{background:var(--cc-accent,#1f2937);color:var(--cc-accent-fg,#fff)}
    #cookie-consent button:hover{filter:brightness(.93)}
    #cookie-consent button:focus-visible{outline:2px solid var(--cc-accent,#2563eb);outline-offset:2px}
    @keyframes cc-in{from{transform:translateY(.75rem);opacity:0}to{transform:none;opacity:1}}
    @media (prefers-reduced-motion:reduce){#cookie-consent{animation:none}}
    @media (min-width:640px){#cookie-consent{left:auto;right:1.5rem;bottom:1.5rem;margin:0}}
    #cookie-consent{ {!! collect($theme ?? [])->map(fn ($value, $name) => $name.':'.$value.';')->implode('') !!} }
    {!! $extraCss ?? '' !!}
</style>
<script>
    (function () {
        var GA_ID = @js($gaId);
        var AUTO_PAGE_VIEW = @js($autoPageView ?? true);
        var KEY = 'cookie-consent';

        var TEXT = {
            en: { label: "Cookie consent", message: "We use Google Analytics cookies to understand how this site is used. They are only set if you accept.", accept: "Accept", decline: "Decline" },
            fr: { label: "Consentement aux cookies", message: "Nous utilisons des cookies Google Analytics pour comprendre comment ce site est utilisé. Ils ne sont déposés que si vous acceptez.", accept: "Accepter", decline: "Refuser" },
            es: { label: "Consentimiento de cookies", message: "Usamos cookies de Google Analytics para entender cómo se utiliza este sitio. Solo se instalan si las aceptas.", accept: "Aceptar", decline: "Rechazar" },
            pt: { label: "Consentimento de cookies", message: "Usamos cookies do Google Analytics para entender como este site é utilizado. Só são definidos se aceitar.", accept: "Aceitar", decline: "Recusar" },
            lg: { label: "Okukkiriza kuki", message: "Tukozesa kuki za Google Analytics okutegeera engeri omukutu guno gye gukozesebwamu. Ziteekebwawo bwokka bw'oba ozikkirizza.", accept: "Kkiriza", decline: "Nedda" }
        };

        var language = 'en';

        function pickLanguage(code) {
            var primary = String(code || '').toLowerCase().split(/[-_]/)[0];
            return TEXT[primary] ? primary : 'en';
        }

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

        function fillBanner(banner) {
            var text = TEXT[language];
            banner.setAttribute('aria-label', text.label);
            banner.innerHTML =
                '<p>' + text.message + '</p>' +
                '<div class="cc-actions">' +
                '<button type="button" class="cc-decline" data-choice="denied">' + text.decline + '</button>' +
                '<button type="button" class="cc-accept" data-choice="granted">' + text.accept + '</button>' +
                '</div>';
        }

        function showBanner() {
            if (document.getElementById('cookie-consent')) { return; }

            var banner = document.createElement('div');
            banner.id = 'cookie-consent';
            banner.setAttribute('role', 'dialog');
            fillBanner(banner);
            banner.addEventListener('click', function (event) {
                var choice = event.target && event.target.getAttribute && event.target.getAttribute('data-choice');
                if (choice) { choose(choice); }
            });
            document.body.appendChild(banner);
        }

        window.setCookieConsentLanguage = function (code) {
            language = pickLanguage(code);
            var banner = document.getElementById('cookie-consent');
            if (banner) { fillBanner(banner); }
        };

        window.openCookieSettings = showBanner;

        language = pickLanguage(document.documentElement.lang);

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
