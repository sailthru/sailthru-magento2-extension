var config = {
    paths: {
    "Sailthru": "https://ak.sail-horizon.com/onsite/personalize.v0.0.4.min",
    },
    waitSeconds: 300,
    shim: {
        Sailthru: {
            exports: 'Sailthru'
        },
    }
};
