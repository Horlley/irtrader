(function () {
    const page = document.getElementById('app-page');
    const title = document.getElementById('page-title');
    const scriptsHost = document.getElementById('page-scripts');

    if (!page || !title) {
        return;
    }

    let controller = null;
    let navigating = false;
    let pageEventController = null;

    const style = document.createElement('style');
    style.textContent = `
        body.ajax-loading #app-page {
            opacity: .55;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);

    function sameOriginUrl(url) {
        try {
            const next = new URL(url, window.location.href);
            return next.origin === window.location.origin ? next : null;
        } catch (e) {
            return null;
        }
    }

    function shouldHandleLink(event, link) {
        if (!link || !link.matches('[data-ajax-link]')) {
            return false;
        }

        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return false;
        }

        if (link.target || link.hasAttribute('download')) {
            return false;
        }

        const url = sameOriginUrl(link.href);

        if (!url || url.pathname === window.location.pathname && url.search === window.location.search) {
            return false;
        }

        return true;
    }

    function cleanupCurrentPage() {
        if (pageEventController) {
            pageEventController.abort();
            pageEventController = null;
        }

        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            try {
                jQuery.fn.dataTable.tables({ api: true }).destroy();
            } catch (e) {}
        }

        if (window.Chart) {
            try {
                page.querySelectorAll('canvas').forEach((canvas) => {
                    const chart = Chart.getChart ? Chart.getChart(canvas) : null;
                    if (chart) {
                        chart.destroy();
                    }
                });
            } catch (e) {}
        }

        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    function syncSidebar(doc) {
        const currentLinks = Array.from(document.querySelectorAll('.sidebar a'));
        const nextLinks = Array.from(doc.querySelectorAll('.sidebar a.active'));
        const activeKeys = nextLinks.map((link) => link.getAttribute('href') || '').filter(Boolean);

        currentLinks.forEach((link) => link.classList.remove('active'));

        currentLinks.forEach((link) => {
            const href = link.getAttribute('href') || '';

            if (activeKeys.includes(href)) {
                link.classList.add('active');
            }
        });

        const activeTaxLink = document.querySelector('.sidebar ul ul a.active');
        const taxSubmenu = activeTaxLink ? activeTaxLink.closest('ul') : null;

        if (taxSubmenu) {
            taxSubmenu.style.display = '';
        }
    }

    function updatePageFromDocument(doc, responseUrl) {
        const nextPage = doc.getElementById('app-page');
        const nextTitle = doc.getElementById('page-title');

        if (!nextPage) {
            throw new Error('Conteudo AJAX invalido.');
        }

        cleanupCurrentPage();

        page.innerHTML = nextPage.innerHTML;

        if (nextTitle) {
            title.innerHTML = nextTitle.innerHTML;
        }

        if (doc.title) {
            document.title = doc.title;
        }

        syncSidebar(doc);

        if (scriptsHost) {
            scriptsHost.innerHTML = '';
        }

        return executePageScripts(doc, responseUrl);
    }

    async function executePageScripts(doc, responseUrl) {
        const scripts = Array.from(doc.querySelectorAll('#page-scripts script'));
        pageEventController = new AbortController();

        for (const script of scripts) {
            if (script.src) {
                await executeExternalScript(script.src, responseUrl);
                continue;
            }

            executeInlineScript(script.textContent || '');
        }

        document.dispatchEvent(new Event('DOMContentLoaded'));
        document.dispatchEvent(new CustomEvent('ajax:navigated', {
            detail: {
                url: window.location.href
            }
        }));
    }

    async function executeExternalScript(src, responseUrl) {
        const scriptUrl = sameOriginUrl(new URL(src, responseUrl).href);

        if (!scriptUrl) {
            return;
        }

        const response = await fetch(scriptUrl.href, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Nao foi possivel carregar script da pagina.');
        }

        executeInlineScript(await response.text());
    }

    function executeInlineScript(code) {
        if (!code.trim()) {
            return;
        }

        withScopedPageEvents(function () {
            Function(code)();
        });
    }

    function withScopedPageEvents(callback) {
        if (!pageEventController) {
            callback();
            return;
        }

        const originalDocumentAdd = document.addEventListener;
        const originalWindowAdd = window.addEventListener;

        document.addEventListener = function (type, listener, options) {
            return originalDocumentAdd.call(document, type, listener, withPageSignal(options));
        };

        window.addEventListener = function (type, listener, options) {
            return originalWindowAdd.call(window, type, listener, withPageSignal(options));
        };

        try {
            callback();
        } finally {
            document.addEventListener = originalDocumentAdd;
            window.addEventListener = originalWindowAdd;
        }
    }

    function withPageSignal(options) {
        if (!pageEventController) {
            return options;
        }

        if (options && typeof options === 'object') {
            return Object.assign({}, options, {
                signal: options.signal || pageEventController.signal
            });
        }

        return {
            capture: Boolean(options),
            signal: pageEventController.signal
        };
    }

    async function navigate(url, pushState) {
        if (navigating) {
            return;
        }

        navigating = true;

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        document.body.classList.add('ajax-loading');

        try {
            const response = await fetch(url, {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Falha ao carregar pagina.');
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const responseUrl = response.url || url;

            await updatePageFromDocument(doc, responseUrl);

            if (pushState) {
                window.history.pushState({}, '', responseUrl);
            }
        } catch (e) {
            if (e.name !== 'AbortError') {
                window.location.href = url;
            }
        } finally {
            navigating = false;
            document.body.classList.remove('ajax-loading');
        }
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a');

        if (!shouldHandleLink(event, link)) {
            return;
        }

        event.preventDefault();
        navigate(link.href, true);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-ajax-form]');

        if (!form || String(form.method || 'get').toLowerCase() !== 'get') {
            return;
        }

        const url = sameOriginUrl(form.action || window.location.href);

        if (!url) {
            return;
        }

        const params = new URLSearchParams(new FormData(form));
        const query = params.toString();

        event.preventDefault();
        navigate(url.pathname + (query ? `?${query}` : ''), true);
    });

    document.addEventListener('change', function (event) {
        const field = event.target.closest('[data-ajax-auto-submit]');

        if (!field || !field.form || !field.form.matches('[data-ajax-form]')) {
            return;
        }

        field.form.requestSubmit();
    });

    window.addEventListener('popstate', function () {
        navigate(window.location.href, false);
    });

    window.AppNavigation = {
        navigate: navigate
    };
})();
