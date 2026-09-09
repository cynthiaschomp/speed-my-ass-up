/**
 * Speed My A$$ Up (SMAU) - Hover Intent Speculative Prefetcher
 * Part of the Security Sentinel Suite by securemyass.com.
 */
(() => {
    // If native Speculation Rules API is supported and active, preloading is handled natively
    if (HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules')) {
        return;
    }

    const preloaded = new Set();
    let hoverTimer = null;

    const prefetch = (url) => {
        if (!url || preloaded.has(url)) return;
        if (url.indexOf(location.origin) !== 0) return; // Same-origin only
        if (url.includes('/wp-admin') || url.includes('/wp-login.php') || url.includes('?')) return;

        preloaded.add(url);
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'document';
        document.head.appendChild(link);
    };

    const handleMouseOver = (e) => {
        const a = e.target.closest('a');
        if (!a || !a.href) return;

        hoverTimer = setTimeout(() => {
            prefetch(a.href);
        }, 65);
    };

    const handleMouseOut = () => {
        if (hoverTimer) {
            clearTimeout(hoverTimer);
            hoverTimer = null;
        }
    };

    const handleTouchStart = (e) => {
        const a = e.target.closest('a');
        if (a && a.href) {
            prefetch(a.href);
        }
    };

    document.addEventListener('mouseover', handleMouseOver, { passive: true });
    document.addEventListener('mouseout', handleMouseOut, { passive: true });
    document.addEventListener('touchstart', handleTouchStart, { passive: true });
})();
