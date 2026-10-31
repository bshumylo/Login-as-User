/**
 * Client side of "login as user". The state itself lives in a server-signed HttpOnly cookie,
 * the frontend only asks the backend to start or stop it.
 */
define(() => {

    return {
        /**
         * @param {module:helper} helper
         * @return {?{actorId: string, actorName: string}}
         */
        getState(helper) {
            return helper.getAppParam('loginAsUser') || null;
        },

        /**
         * @param {string} userId
         * @return {Promise}
         */
        start(userId) {
            return Espo.Ajax.postRequest('LoginAsUser/start', {userId: userId});
        },

        /**
         * @return {Promise}
         */
        stop() {
            return Espo.Ajax.postRequest('LoginAsUser/stop', {});
        },

        reloadToHome() {
            window.location.hash = '';
            window.location.reload();
        },

        /**
         * Versions before 1.0.0 kept the target user id in a plain cookie. It is not used anymore.
         */
        removeLegacyCookie() {
            document.cookie = 'login-as-user-id=; SameSite=Lax; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        },
    };
});
