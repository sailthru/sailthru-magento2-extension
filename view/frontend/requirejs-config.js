// var config = {
//     paths: {
//     "Sailthru": "https://ak.sail-horizon.com/onsite/personalize.v0.0.4.min",
//     },
//     waitSeconds: 300,
//     shim: {
//         Sailthru: {
//             exports: 'Sailthru'
//         },
//     }
// };
var config = {
    paths: {
    	"Sailthru": "http://127.0.0.1:8080/spm.v1.min",
    },
    waitSeconds: 300,
    shim: {
        Sailthru: {
            exports: 'Sailthru'
        },
    }
};