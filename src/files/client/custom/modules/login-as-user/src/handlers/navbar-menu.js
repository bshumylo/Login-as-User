define(['handlers/navbar-menu', 'login-as-user:helpers/impersonation'], (Dep, impersonation) => {

    return class extends Dep {

        logout() {
            if (!impersonation.getState(this.view.getHelper())) {
                super.logout();

                return;
            }

            impersonation.stop().then(() => impersonation.reloadToHome());
        }
    };
});
