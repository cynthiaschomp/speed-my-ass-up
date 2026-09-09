/**
 * Speed My A$$ Up (SMAU) - Smart Delayed Script Scheduler
 * Part of the Security Sentinel Suite by securemyass.com.
 */
(() => {
    let triggered = false;
    const events = ['keydown', 'mousedown', 'mousemove', 'touchstart', 'touchmove', 'wheel', 'scroll'];

    const triggerScripts = () => {
        if (triggered) return;
        triggered = true;

        events.forEach(evt => window.removeEventListener(evt, triggerScripts, { passive: true }));

        const delayed = document.querySelectorAll('script[type="smau-delayed-script"]');
        delayed.forEach(script => {
            const replacement = document.createElement('script');
            [...script.attributes].forEach(attr => {
                if (attr.nodeName !== 'type' && attr.nodeName !== 'data-smau-src') {
                    replacement.setAttribute(attr.nodeName, attr.nodeValue);
                }
            });

            if (script.hasAttribute('data-smau-src')) {
                replacement.src = script.getAttribute('data-smau-src');
            } else {
                replacement.text = script.text;
            }

            script.parentNode.replaceChild(replacement, script);
        });

        window.dispatchEvent(new CustomEvent('smau-all-scripts-loaded'));
    };

    events.forEach(evt => window.addEventListener(evt, triggerScripts, { passive: true }));
    // Safety fallback: execute after 4s idle to ensure analytics aren't lost
    setTimeout(triggerScripts, 4000);
})();
