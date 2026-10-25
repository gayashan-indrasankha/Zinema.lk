/* Same-origin browser writes carry the session-bound token. No token is sent
 * to external adapters. Server-rendered forms also contain hidden fields. */
(() => {
    const nativeFetch = window.fetch.bind(window);
    window.fetch = (input, init) => {
        const request = new Request(input, init);
        if (new URL(request.url).origin === location.origin && !['GET', 'HEAD', 'OPTIONS'].includes(request.method)) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                const headers = new Headers(request.headers);
                headers.set('X-CSRF-Token', token);
                return nativeFetch(new Request(request, {headers}));
            }
        }
        return nativeFetch(request);
    };
})();
