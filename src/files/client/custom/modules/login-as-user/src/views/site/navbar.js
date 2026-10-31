define(['views/site/navbar', 'login-as-user:helpers/impersonation'], (Dep, impersonation) => {

    return class extends Dep {

        setupMenu() {
            super.setupMenu();

            if (!impersonation.isActive()) {
                return;
            }

            const item = (this.menuDataList || []).find(item => item.name === 'logout');

            if (item) {
                item.label = this.translate('Return to My Account', 'labels');
            }
        }
    };
});
