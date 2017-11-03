## Magento Templates:

You can now configure the following emails to be sent via Sailthru with a Sailthru Template, a custom Magento template or the default Magento Template.

#### Customer Related Emails

* Create Account Confirmation Email
* Create Account Email Confirmed
* Create Account Email
* Forgotten Password Email
* Password Reminder Email
* Reset Password Email
* Newsletter Subscription Confirmed
* Newsletter UnSubscribe Confirmed

#### Sales Related Emails

* Sales Order Template
* Sales Shipment
* Sales Order Comment Template (Registered User)
* Sales Order Comment Template (Guest User)
* Sales Email Shipment (Registered User)
* Sales Email Shipment (Guest User)
* Credit Memo Issued  (Registered User)
* Credit Memo Issued (Guest User)

Each template will always include the vars `subj` and `content`

`subj`
Use as the Subject line in Sailthru templates when you wish to use the subject line passed from Magento.

`content`
this var will always contain the full HTML of the email that Magento would send.

In addition each template may have include vars specific to the context of the email. This allows you to have complete control of the design of the email in Sailthru should you need it.

Magento allows you to configure if a user needs to confirm their email address or not. We've provided template options to meet all registration options.

#####  Create Account Confirmation Email
| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| store_name | string |  Default Store|
| customer_email | string | jsmith@example.com |
| customer_url | string | http://example.com/customer/account/|
| account_confirmation_url | string | http://example.com/ |
| customer | object |  `{ "magento_id" : "2" , "name" : "John Smith" , "suffix" : "" , "prefix" : "" , "firstName" : "John" , "middleName" : "" , "lastName" : "Smith" , "store" : "Default Store View" , "customerGroup" : "General" , "created_date" : "2017-11-02" , "created_time" : 1509618495 , "defaultBillingAddress" : [ ] , "defaultShippingAddress" : [ ]}`|
| magento_id | number | 1 |
| firstName | string | John |
| middleName | string | Brendan |
| lastName | string | Smith |
| store | string | English |
| customerGroup | string | English |
| created_date | date | 2017-10-24 |
| created_date | datetime | 1508870913 |
| created_date | object | ` { "name" : "John Smith"}` |

#####  Create Account Email Confirmed

| vars | type | example |
|--- | --- | ---|
| customer_account_url | string | http://example.com/ |
| customer_email | string | jsmith@example.com |
| customer_name | string | John Smith|
| name | string | John Smith|
| store_name | string |  Default Store|
| customer_url | string | http://example.com/customer/account/|
| reset_url | string | http://example.com/?12343HFYSHWAHASDKASKDJASHD |
| customer | object |  `{ "magento_id" : "2" , "name" : "John Smith" , "suffix" : "" , "prefix" : "" , "firstName" : "John" , "middleName" : "" , "lastName" : "Smith" , "store" : "Default Store View" , "customerGroup" : "General" , "created_date" : "2017-11-02" , "created_time" : 1509618495 , "defaultBillingAddress" : [ ] , "defaultShippingAddress" : [ ]}`|
| magento_id | number | 1 |
| firstName | string | John |
| middleName | string | Brendan |
| lastName | string | Smith |
| store | string | English |
| customerGroup | string | English |
| created_date | date | 2017-10-24 |
| created_date | datetime | 1508870913 |
| created_date | object | ` { "name" : "John Smith"}` |

#####  Create Account Email

| vars | type | example |
|--- | --- | ---|
| customer_account_url | string | http://example.com/ |
| customer_email | string | jsmith@example.com |
| customer_name | string | John Smith|
| name | string | John Smith|
| store_name | string |  Default Store|
| customer_url | string | http://example.com/customer/account/|
| reset_url | string | http://example.com/?12343HFY |
| customer | object |  `{ "magento_id" : "2" , "name" : "John Smith" , "suffix" : "" , "prefix" : "" , "firstName" : "John" , "middleName" : "" , "lastName" : "Smith" , "store" : "Default Store View" , "customerGroup" : "General" , "created_date" : "2017-11-02" , "created_time" : 1509618495 , "defaultBillingAddress" : [ ] , "defaultShippingAddress" : [ ]}`|
| magento_id | number | 1 |
| firstName | string | John |
| middleName | string | Brendan |
| lastName | string | Smith |
| store | string | English |
| customerGroup | string | English |
| created_date | date | 2017-10-24 |
| created_date | datetime | 1508870913 |
| created_date | object | ` { "name" : "John Smith"}` |

#####  Forgotten Password Email

| vars | type | example |
|--- | --- | ---|
| customer_name | string | John Smith|
| reset_password_url | string | http://example.com/?12343HFY |


#####  Password Reminder Email

| vars | type | example |
|--- | --- | ---|
| customer_name | string | John Smith|
| customer_account_url | string | http://example.com/customer/account/|


#####  Reset Password Email

| vars | type | example |
|--- | --- | ---|
| customer_name | string | John Smith|
| store_name | string | Default Store |
| store_email | string | store@example.org |
| store_phone | string |1-800 111-1111 |

#####  Newsletter Subscription

| vars | type | example |
|--- | --- | ---|
| subscriber_confirmation_url | string | http://example.com/?12343H |


#####  Newsletter Subscription

No additional vars are available in this transaction. Use the `{content}` var to pass the HTML from Magento and you can use the `profile` var within the Sailthru template if you need to access the user profile data.

#### Magento Sales Order Template

| vars | type | example |
|--- | --- | ---|
| customer_name | string | John Smith|
| store_name | string | Default Store |
| store_email | string | store@example.org |
| increment_id | number | 000000003 |
| created_at   | string | November 1, 2017 at 8:30:36 PM PDT |
| shipping_description   | string |  Flat Rate - Fixed |
| order   | object |  ` { "id" : "3" , "items" : [ { "id" : "MS05-L-Blue" , "title" : "Helios EverCool™ Tee" , "options" : [ { "label" : "Color" , "value" : "Blue"} , { "label" : "Size" , "value" : "L"}] , "qty" : 1 , "url" : "http://example.org/index.php/helios-evercool-trade-tee.html" , "image" : "http://example.org/media/catalog/product//m/s/ms05-blue_main.jpg" , "price" : "24.0000"}] , "adjustments" : [ { "title" : "Shipping" , "price" : 500} , { "title" : "Discount" , "price" : 0} , { "title" : "Tax" , "price" : 0}] , "tenders" : "" , "name" : "John Smith" , "status" : "pending" , "state" : "new" , "created_date" : "2017-11-02 03:30:36" , "total" : "29.0000" , "subtotal" : "24.0000" , "couponCode" : null , "discount" : "0.0000" , "shippingDescription" : "Flat Rate - Fixed" , "isGuest" : 0 , "billingAddress" : { "city" : "New York" , "state" : "New York" , "state_code" : "NY" , "country" : "United States" , "country_code" : "US" , "postal_code" : "11111" , "name" : "John Smith" , "company" : "" , "telephone" : "555-555-5555" , "street1" : "38 Foolhardy Avenue" , "street2" : ""} , "shippingAddress" : { "city" : "New York" , "state" : "New York" , "state_code" : "NY" , "country" : "United States" , "country_code" : "US" , "postal_code" : "11111" , "name" : "Alex Silverman" , "company" : "" , "telephone" : "555-555-5555" , "street1" : "38 Foolhardy Avenue" , "street2" : ""}` |
| created_date   | string |  2017-10-24 |
| isGuest   | string |  2017-10-24 |

#### Magento Order Comment

| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| increment_id | string | Default Store |
| order_status | string | store@example.org |
| increment_id | number | 000000003 |
| account_url   | string | November 1, 2017 at 8:30:36 PM PDT |
| store_email   | string |  store@example.com |
| order_comment   | string |  This is a comment |


####  Order Comment

| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| increment_id | string | Default Store |
| order_status | string | Shipped |
| increment_id | number | 000000003 |
| account_url   | string | http://example.com/customer/account |
| store_email   | string |  Flat Rate - Fixed |
| order_comment   | string |  2017-10-24 |

####  Order Comment  (Guest)

| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| increment_id | string | Default Store |
| order_status | string | Shipped |
| increment_id | number | 000000003 |
| account_url   | string | http://example.com/customer/account |
| store_email   | string |  Flat Rate - Fixed |
| order_comment   | string |  2017-10-24 |


####  Shipment Email

| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| order_status | string | Shipped |
| increment_id | number | 000000003 |
| account_url   | string | http://example.com/customer/account |
| store_email   | string |  Flat Rate - Fixed |
| order_comment   | string |  2017-10-24 |

####  Shipment Email (Guest)

| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| order_status | string | Shipped |
| increment_id | number | 000000003 |
| account_url   | string | http://example.com/customer/account |
| store_email   | string |  Flat Rate - Fixed |
| order_comment   | string |  2017-10-24 |

####  Credit Memos

| vars | type | example |
|--- | --- | ---|
| name | string | John Smith|
| order_status | string | Shipped |
| increment_id | number | 000000003 |
| store_name | string | Default Store |
| account_url   | string | http://example.com/customer/account |
| store_email   | string |  Flat Rate - Fixed |
| order_comment   | string |  2017-10-24 |