define(['action-handler', 'login-as-user:helpers/impersonation'], (Dep, impersonation) => {

    return class extends Dep {

        isAvailable() {
            const model = this.view.model;
            const user = this.view.getUser();

            return user.isAdmin() &&
                !impersonation.getState(this.view.getHelper()) &&
                model.id !== user.id &&
                (model.isRegular() || model.isAdmin() || model.isPortal());
        }

        loginAsUser() {
            Espo.Ui.notifyWait();

            impersonation.start(this.view.model.id)
                .then(() => impersonation.reloadToHome())
                .catch(() => Espo.Ui.notify(false));
        }
    };
});
