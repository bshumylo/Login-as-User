/**
 * Keeps the "login as user" state in a cookie read by the backend authentication hook.
 */
define(() => {

    const COOKIE_NAME = 'login-as-user-id';

    const getCookie = () => {
        const prefix = COOKIE_NAME + '=';

        const part = document.cookie
            .split(';')
            .map(item => item.trim())
            .find(item => item.startsWith(prefix));

        return part ? decodeURIComponent(part.substring(prefix.length)) : null;
    };

    return {
        isActive() {
            return !!getCookie();
        },

        start(userId) {
            const secure = window.location.protocol === 'https:' ? '; Secure' : '';

            document.cookie = COOKIE_NAME + '=' + encodeURIComponent(userId) + '; SameSite=Lax; path=/' + secure;
        },

        stop() {
            document.cookie = COOKIE_NAME + '=; SameSite=Lax; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        },

        reloadToHome() {
            window.location.hash = '';
            window.location.reload();
        },
    };
});
