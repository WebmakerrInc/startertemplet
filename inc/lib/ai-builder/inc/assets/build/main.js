(function(){
        if ( 'undefined' === typeof window || 'undefined' === typeof document ) {
                return;
        }

        const root = document.getElementById( 'ai-builder-root' );
        if ( ! root ) {
                return;
        }

        const vars = window.aiBuilderVars || {};
        const apiSettings = window.wpApiSettings || {};
        const auth = apiSettings.zipwp_auth || {};

        const buildBuilderUrl = () => {
                const baseUrl = auth.screen_url || vars.screenUrl || 'https://app.zipwp.com/auth';
                try {
                        const url = new URL( baseUrl, window.location.origin );
                        const params = new URLSearchParams( url.search );

                        if ( auth.redirect_url ) {
                                params.set( 'redirect_url', auth.redirect_url );
                        }
                        if ( auth.source ) {
                                params.set( 'source', auth.source );
                        }
                        if ( auth.utmSource ) {
                                params.set( 'utm_source', auth.utmSource );
                        }
                        if ( auth.partner_id ) {
                                params.set( 'partner_id', auth.partner_id );
                        }
                        if ( vars.siteUrl ) {
                                params.set( 'site', vars.siteUrl );
                        }

                        url.search = params.toString();
                        return url.toString();
                } catch ( error ) {
                        return baseUrl;
                }
        };

        const builderUrl = buildBuilderUrl();

        const wrapper = document.createElement( 'div' );
        wrapper.className = 'ai-builder-remote-wrapper';

        const header = document.createElement( 'div' );
        header.className = 'ai-builder-remote-header';

        const title = document.createElement( 'h1' );
        title.textContent = vars.pageTitle || 'Build with AI';
        header.appendChild( title );

        const actions = document.createElement( 'div' );
        actions.className = 'ai-builder-remote-actions';

        const openButton = document.createElement( 'button' );
        openButton.type = 'button';
        openButton.className = 'ai-builder-remote-open';
        openButton.textContent = vars.openInNewTabLabel || 'Open in a new tab';
        openButton.addEventListener( 'click', () => {
                window.open( builderUrl, '_blank', 'noopener,noreferrer' );
        } );
        actions.appendChild( openButton );
        header.appendChild( actions );

        const loader = document.createElement( 'div' );
        loader.className = 'ai-builder-remote-loader';
        loader.innerHTML = '<span class="spinner"></span><span>' + ( vars.loadingMessage || 'Loading AI Builder…' ) + '</span>';

        const frame = document.createElement( 'iframe' );
        frame.src = builderUrl;
        frame.title = vars.iframeTitle || 'AI Site Builder';
        frame.setAttribute( 'frameborder', '0' );
        frame.setAttribute( 'allowfullscreen', 'true' );
        frame.setAttribute( 'allow', 'clipboard-write *; microphone; camera; fullscreen' );
        frame.className = 'ai-builder-remote-frame';

        let loaded = false;
        const markLoaded = () => {
                if ( loaded ) {
                        return;
                }
                loaded = true;
                wrapper.classList.add( 'is-loaded' );
        };

        frame.addEventListener( 'load', () => {
                markLoaded();
        } );

        frame.addEventListener( 'error', () => {
                wrapper.classList.add( 'has-error' );
                loader.innerHTML = '<span class="dashicons dashicons-warning"></span><span>' + ( vars.errorMessage || 'We couldn\'t load the AI Builder. Please use the button above to open it in a new tab.' ) + '</span>';
        } );

        const timeout = window.setTimeout( () => {
                if ( ! loaded ) {
                        loader.classList.add( 'is-delayed' );
                }
        }, 6000 );

        root.innerHTML = '';
        wrapper.appendChild( header );
        wrapper.appendChild( loader );
        wrapper.appendChild( frame );
        root.appendChild( wrapper );

        window.addEventListener( 'message', ( event ) => {
                if ( ! event || ! event.data ) {
                        return;
                }

                if ( 'ai-builder:loaded' === event.data ) {
                        window.clearTimeout( timeout );
                        markLoaded();
                }
        } );
})();
