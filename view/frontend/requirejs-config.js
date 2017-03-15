var config = {
    paths: {
    	"Sailthru": "https://ak.sail-horizon.com/spm/spm.v1.min",
    },
    waitSeconds: 300,
    shim: {
        Sailthru: {
            exports: 'Sailthru'
        },
    }
};