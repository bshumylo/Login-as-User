define(['handlers/navbar-menu', 'login-as-user:helpers/impersonation'], (Dep, impersonation) => {

    return class extends Dep {

        logout() {
            if (!impersonation.isActive()) {
                super.logout();

                return;
            }

            impersonation.stop();
            impersonation.reloadToHome();
        }
    };
});
