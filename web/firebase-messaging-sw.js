importScripts("https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js");

firebase.initializeApp({
    apiKey: "AIzaSyAqTLPNZ6LdXbKvy80IMP0bt0IU6GDRP5A",
    authDomain: "zerobuy-app.firebaseapp.com",
    projectId: "zerobuy-app",
    storageBucket: "zerobuy-app.firebasestorage.app",
    messagingSenderId: "1076815467085",
    appId: "1:1076815467085:web:4f0e32313774712fbd8204",
    measurementId: "G-PTQ8CPTKDQ"
});

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function (payload) {
    const promiseChain = clients
        .matchAll({
            type: "window",
            includeUncontrolled: true
        })
        .then(windowClients => {
            for (let i = 0; i < windowClients.length; i++) {
                const windowClient = windowClients[i];
                windowClient.postMessage(payload);
            }
        })
        .then(() => {
            const title = payload.notification.title;
            const options = {
                body: payload.notification.score
              };
            return registration.showNotification(title, options);
        });
    return promiseChain;
});
self.addEventListener('notificationclick', function (event) {
    console.log('notification received: ', event)
});