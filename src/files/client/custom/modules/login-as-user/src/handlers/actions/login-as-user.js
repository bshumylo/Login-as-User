define(['action-handler', 'login-as-user:helpers/impersonation'], (Dep, impersonation) => {

    return class extends Dep {

        isAvailable() {
            const model = this.view.model;
            const user = this.view.getUser();

            return user.isAdmin() &&
                !impersonation.isActive() &&
                model.id !== user.id &&
                (model.isRegular() || model.isAdmin() || model.isPortal());
        }

        loginAsUser() {
            impersonation.start(this.view.model.id);
            impersonation.reloadToHome();
        }
    };
});
