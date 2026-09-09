/**
 * Speed My A$$ Up (SMAU) - Video Facade Click Handler
 * Part of the Security Sentinel Suite by securemyass.com.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const facade = e.target.closest('.smau-video-facade');
        if (!facade) return;

        const embedUrl = facade.getAttribute('data-embed');
        if (!embedUrl) return;

        const iframe = document.createElement('iframe');
        iframe.setAttribute('src', embedUrl + (embedUrl.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1');
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '1');
        iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;border-radius:8px;';

        facade.innerHTML = '';
        facade.appendChild(iframe);
    });
});
