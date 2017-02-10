// require(["Sailthru"], function (Sailthru) {
//     console.log(Sailthru);
//     console.log('Want to use SPM with JSON? Configure it here!');
// }); 
require(["Sailthru"], function (Sailthru) {
    console.log(Sailthru);
    console.log('Want to use SPM with JSON?! WOO');
    var client_id = '45124590f1851dcdcf7ac2e38d793cd2';
    Sailthru.init({ customerId: client_id});
});