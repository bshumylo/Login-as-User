define(['views/site/navbar', 'login-as-user:helpers/impersonation'], (Dep, impersonation) => {

    return class extends Dep {

        setup() {
            super.setup();

            impersonation.removeLegacyCookie();
        }

        setupMenu() {
            super.setupMenu();

            if (!impersonation.getState(this.getHelper())) {
                return;
            }

            const item = (this.menuDataList || []).find(item => item.name === 'logout');

            if (item) {
                item.label = this.translate('Return to My Account', 'labels');
            }
        }

        afterRender() {
            super.afterRender();

            this.renderBanner();
        }

        onRemove() {
            this.removeBanner();

            super.onRemove();
        }

        removeBanner() {
            document.querySelectorAll('.login-as-user-banner').forEach(element => element.remove());
        }

        renderBanner() {
            this.removeBanner();

            const state = impersonation.getState(this.getHelper());

            if (!state) {
                return;
            }

            const banner = document.createElement('div');

            banner.className = 'login-as-user-banner';
            banner.setAttribute('role', 'status');

            Object.assign(banner.style, {
                position: 'fixed',
                left: '12px',
                bottom: 'calc(12px + env(safe-area-inset-bottom, 0px))',
                zIndex: '1100',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                padding: '6px 6px 6px 14px',
                borderRadius: '20px',
                background: '#b94a48',
                color: '#fff',
                fontSize: '13px',
                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.35)',
            });

            const text = document.createElement('span');

            text.textContent = this.translate('actingAs', 'labels')
                .replace('{name}', this.getUser().get('name') || this.getUser().get('userName') || '');

            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'btn btn-default btn-xs';
            button.textContent = this.translate('Return to My Account', 'labels');

            button.addEventListener('click', () => {
                button.disabled = true;

                impersonation.stop().then(() => impersonation.reloadToHome());
            });

            banner.append(text, button);
            document.body.append(banner);
        }
    };
});
