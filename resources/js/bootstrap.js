window._ = require('lodash');

try {
    window.bootstrap = require('bootstrap');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo for real-time (Soketi / Pusher).
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

var runtimeConfig = typeof window !== 'undefined' && window.SHAMROCK_ECHO_CONFIG;
var wsHost = runtimeConfig ? runtimeConfig.wsHost : (process.env.MIX_PUSHER_HOST || '127.0.0.1');
var wsPort = runtimeConfig ? runtimeConfig.wssPort : parseInt(process.env.MIX_PUSHER_PORT || '6001', 10);
var forceTLS = runtimeConfig ? runtimeConfig.forceTLS : false;
var key = runtimeConfig ? runtimeConfig.key : (process.env.MIX_PUSHER_APP_KEY || 'app-key');
var cluster = runtimeConfig ? runtimeConfig.cluster : (process.env.MIX_PUSHER_APP_CLUSTER || 'mt1');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: key,
    cluster: cluster,
    wsHost: wsHost,
    wsPort: runtimeConfig ? runtimeConfig.wsPort : wsPort,
    wssPort: wsPort,
    forceTLS: forceTLS,
    disabledTransports: ['sockjs'],
    enabledTransports: ['ws', 'wss'],
});

if (typeof window !== 'undefined') {
    console.log('[Echo] Configured for ' + (forceTLS ? 'wss' : 'ws') + '://' + wsHost + ':' + wsPort + ' (key: ' + key + ')');
    try {
        var conn = window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection;
        if (conn) {
            conn.bind('connecting', function () { console.log('[Echo] Connecting to ' + wsHost + ':' + wsPort + '...'); });
            conn.bind('connected', function () { console.log('[Echo] Connected to ' + wsHost + ':' + wsPort); });
            conn.bind('unavailable', function () { console.warn('[Echo] Connection unavailable'); });
            conn.bind('failed', function () { console.warn('[Echo] Connection failed'); });
            conn.bind('error', function (err) { console.warn('[Echo] Connection error:', err); });
        }
    } catch (e) { console.warn('[Echo] Could not bind connection events:', e); }
}
