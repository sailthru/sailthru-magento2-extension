require(["Sailthru"], function (Sailthru) {
	console.log("Edit this file to use SPM onsite!");
    var client_id = '<ENTER_CLIENT_ID_HERE>';
    Sailthru.init({ customerId: client_id});
});