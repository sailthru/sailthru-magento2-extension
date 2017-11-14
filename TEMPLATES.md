All email templates sent via Sailthru from Magento will contain two vars *subj* and *content. *The subj var should be used to populate the sailthru template with the subject line from Magento. The var content will contain the full HTML of the Magento default template. In addition to these vars each template will have a number of other vars depending on the transactional. This will allow you complete flexibility in how you design your Sailthru templates. 

To push email transactionals from Magento directly through Sailthru untouched you can use the "Use Current Magento Template". This option will send the HTML as configured in Magento, and will use the template configured in Magento. This means if you have overridden the default message in Magento we will use that version. 

For other transactionals we have provided a list of available templates and their associated vars below. Please review carefully with your account manager. 

#### **Customer Related Emails**

* Create Account Confirmation Email

* Create Account Email Confirmed

* Create Account Email

* Forgotten Password Email

* Password Reminder Email

* Reset Password Email

* Newsletter Subscription Confirmed

* Newsletter UnSubscribe Confirmed

#### **Sales Related Emails**

* Sales Order Template

* Sales Shipment

* Sales Order Comment Template (Registered User)

* Sales Order Comment Template (Guest User)

* Sales Email Shipment (Registered User)

* Sales Email Shipment (Guest User)

* Credit Memo Issued (Registered User)

* Credit Memo Issued (Guest User)

##### Create Account Confirmation Email

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>store_name</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>customer_email</td>
    <td>string</td>
    <td>jsmith@example.com</td>
  </tr>
  <tr>
    <td>customer_url</td>
    <td>string</td>
    <td>http://example.com/customer/account/</td>
  </tr>
  <tr>
    <td>account_confirmation_url</td>
    <td>string</td>
    <td>http://example.com/</td>
  </tr>
  <tr>
    <td>customer</td>
    <td>object</td>
    <td>{  
   "Magento_id":"2",
   "name":"John Smith",
   "Suffix":"",
   "prefix":"",
   "firstName":"John",
   "middleName":"Brendan",
   "lastName":"Smith",
   "store":"Default Store View",
   "customerGroup":"General",
   "created_date":"2017-11-02",
   "created_time":1509618495,
   "defaultBillingAddress":[  

   ],
   "defaultShippingAddress":[  
   ]
}</td>
  </tr>
  <tr>
    <td>magento_id</td>
    <td>number</td>
    <td>2</td>
  </tr>
  <tr>
    <td>firstName</td>
    <td>string</td>
    <td>John</td>
  </tr>
  <tr>
    <td>middleName</td>
    <td>string</td>
    <td>Brendan</td>
  </tr>
  <tr>
    <td>lastName</td>
    <td>string</td>
    <td>Smith</td>
  </tr>
  <tr>
    <td>store</td>
    <td>string</td>
    <td>English</td>
  </tr>
  <tr>
    <td>created_date</td>
    <td>date</td>
    <td>2017-10-24</td>
  </tr>
  <tr>
    <td>created_time</td>
    <td>datetime</td>
    <td>1509618495</td>
  </tr>
  <tr>
    <td>registration</td>
    <td>object</td>
    <td>registration: { "name" : "John Smith"}</td>
  </tr>
</table>


##### Create Account Email Confirmed

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>customer_account_url</td>
    <td>string</td>
    <td>http://example.com/</td>
  </tr>
  <tr>
    <td>customer_email</td>
    <td>string</td>
    <td>jsmith@example.com</td>
  </tr>
  <tr>
    <td>customer_name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>store_name</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>customer_url</td>
    <td>string</td>
    <td>http://example.com/customer/account/</td>
  </tr>
  <tr>
    <td>reset_url</td>
    <td>string</td>
    <td>http://example.com/?12343HFYSHWAHASDKASKDJASHD</td>
  </tr>
  <tr>
    <td>customer</td>
    <td>object</td>
    <td>{  
   "magento_id":"2",
   "name":"John Smith",
   "suffix":"",
   "prefix":"",
   "firstName":"John",
   "middleName":"Brendan",
   "lastName":"Smith",
   "store":"Default Store View",
   "customerGroup":"General",
   "created_date":"2017-11-02",
   "created_time":1509618495,
   "defaultBillingAddress":[],
   "defaultShippingAddress":[ ]
}</td>
  </tr>
  <tr>
    <td>magento_id</td>
    <td>number</td>
    <td>2</td>
  </tr>
  <tr>
    <td>firstName</td>
    <td>string</td>
    <td>John</td>
  </tr>
  <tr>
    <td>middleName</td>
    <td>string</td>
    <td>Brendan</td>
  </tr>
  <tr>
    <td>lastName</td>
    <td>string</td>
    <td>Smith</td>
  </tr>
  <tr>
    <td>store</td>
    <td>string</td>
    <td>English</td>
  </tr>
  <tr>
    <td>created_date</td>
    <td>date</td>
    <td>2017-10-24</td>
  </tr>
  <tr>
    <td>created_time</td>
    <td>datetime</td>
    <td>1508870913</td>
  </tr>
  <tr>
    <td>registration</td>
    <td>object</td>
    <td>{ "name" : "John Smith"}</td>
  </tr>
</table>


##### Create Account Email

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>customer_account_url</td>
    <td>string</td>
    <td>http://example.com/</td>
  </tr>
  <tr>
    <td>customer_email</td>
    <td>string</td>
    <td>jsmith@example.com</td>
  </tr>
  <tr>
    <td>customer_name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>store_name</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>customer_url</td>
    <td>string</td>
    <td>http://example.com/customer/account/</td>
  </tr>
  <tr>
    <td>reset_url</td>
    <td>string</td>
    <td>http://example.com/?12343HFY</td>
  </tr>
  <tr>
    <td>customer</td>
    <td>object</td>
    <td>{  
   "magento_id":"2",
   "name":"John Smith",
   "suffix":"",
   "prefix":"",
   "firstName":"John",
   "middleName":"",
   "lastName":"Smith",
   "store":"Default Store View",
   "customerGroup":"General",
   "created_date":"2017-11-02",
   "created_time":1509618495,
   "defaultBillingAddress":[  
   ],
   "defaultShippingAddress":[  

   ]
}</td>
  </tr>
  <tr>
    <td>magento_id</td>
    <td>number</td>
    <td></td>
  </tr>
  <tr>
    <td>firstName</td>
    <td>string</td>
    <td>John</td>
  </tr>
  <tr>
    <td>middleName</td>
    <td>string</td>
    <td>Brendan</td>
  </tr>
  <tr>
    <td>lastName</td>
    <td>string</td>
    <td>Smith</td>
  </tr>
  <tr>
    <td>store</td>
    <td>string</td>
    <td>English</td>
  </tr>
  <tr>
    <td>created_date</td>
    <td>date</td>
    <td>2017-10-24</td>
  </tr>
  <tr>
    <td>created_time</td>
    <td>datetime</td>
    <td>1508870913</td>
  </tr>
  <tr>
    <td>registration</td>
    <td>object</td>
    <td>{ "name" : "John Smith"}</td>
  </tr>
</table>


##### Forgotten Password Email

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>customer_name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>reset_password_url</td>
    <td>string</td>
    <td>http://example.com/?12343HFY</td>
  </tr>
</table>


##### Password Reminder Email

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>customer_name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>customer_account_url</td>
    <td>string</td>
    <td>http://example.com/customer/account/</td>
  </tr>
</table>


##### Reset Password Email

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>customer_name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>store_name</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>store@example.org</td>
  </tr>
  <tr>
    <td>store_phone</td>
    <td>string</td>
    <td>1-800 111-1111</td>
  </tr>
</table>


##### Newsletter Subscription

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>subscriber_confirmation_url</td>
    <td>string</td>
    <td>http://example.com/?12343H</td>
  </tr>
</table>


#### **Magento Sales Order Template**

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>customer_name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>store_name</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>store@example.org</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>number</td>
    <td>000000003</td>
  </tr>
  <tr>
    <td>created_at</td>
    <td>string</td>
    <td>November 1, 2017 at 8:30:36 PM PDT</td>
  </tr>
  <tr>
    <td>shipping_description</td>
    <td>string</td>
    <td>Flat Rate - Fixed</td>
  </tr>
  <tr>
    <td>order</td>
    <td>object</td>
    <td>{  
   "id":"3",
   "items":[  
      {  
         "id":"MS05-L-Blue",
         "title":"Helios EverCool™ Tee",
         "options":[  
            {  
               "label":"Color",
               "value":"Blue"
            },
            {  
               "label":"Size",
               "value":"L"
            }
         ],
         "qty":1,
         "url":"http://example.org/index.php/helios-evercool-trade-tee.html",
         "image":"http://example.org/media/catalog/product//m/s/ms05-blue_main.jpg",
         "price":"24.0000"
      }
   ],
   "adjustments":[  
      {  
         "title":"Shipping",
         "price":500
      },
      {  
         "title":"Discount",
         "price":0
      },
      {  
         "title":"Tax",
         "price":0
      }
   ],
   "tenders":"",
   "name":"John Smith",
   "status":"pending",
   "state":"new",
   "created_date":"2017-11-02 03:30:36",
   "total":"29.0000",
   "subtotal":"24.0000",
   "couponCode":null,
   "discount":"0.0000",
   "shippingDescription":"Flat Rate - Fixed",
   "isGuest":0,
   "billingAddress":{  
      "city":"New York",
      "state":"New York",
      "state_code":"NY",
      "country":"United States",
      "country_code":"US",
      "postal_code":"11111",
      "name":"John Smith",
      "company":"",
      "telephone":"555-555-5555",
      "street1":"38 New Avenue",
      "street2":""
   },
   "shippingAddress":{  
      "city":"New York",
      "state":"New York",
      "state_code":"NY",
      "country":"United States",
      "country_code":"US",
      "postal_code":"11111",
      "name":"Alex Silverman",
      "company":"",
      "telephone":"555-555-5555",
      "street1":"38 New Avenue",
      "street2":""
   }
}</td>
  </tr>
  <tr>
    <td>created_date</td>
    <td>string</td>
    <td>2017-10-24</td>
  </tr>
  <tr>
    <td>isGuest</td>
    <td>boolean</td>
    <td>false</td>
  </tr>
</table>


#### **Magento Order Comment**

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>order_status</td>
    <td>string</td>
    <td>store@example.org</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>number</td>
    <td>000000003</td>
  </tr>
  <tr>
    <td>account_url</td>
    <td>string</td>
    <td>November 1, 2017 at 8:30:36 PM PDT</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>store@example.com</td>
  </tr>
  <tr>
    <td>order_comment</td>
    <td>string</td>
    <td>This is a comment</td>
  </tr>
</table>


#### **Order Comment**

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>order_status</td>
    <td>string</td>
    <td>Shipped</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>number</td>
    <td>000000003</td>
  </tr>
  <tr>
    <td>account_url</td>
    <td>string</td>
    <td>http://example.com/customer/account</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>help@example.com</td>
  </tr>
  <tr>
    <td>order_comment</td>
    <td>string</td>
    <td>2017-10-24</td>
  </tr>
</table>


#### **Order Comment (Guest)**

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>order_status</td>
    <td>string</td>
    <td>Shipped</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>number</td>
    <td>000000003</td>
  </tr>
  <tr>
    <td>account_url</td>
    <td>string</td>
    <td>http://example.com/customer/account</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>Flat Rate - Fixed</td>
  </tr>
  <tr>
    <td>order_comment</td>
    <td>string</td>
    <td>This is a comment</td>
  </tr>
</table>


#### Shipment Email

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>order_status</td>
    <td>string</td>
    <td>Shipped</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>number</td>
    <td>000000003</td>
  </tr>
  <tr>
    <td>account_url</td>
    <td>string</td>
    <td>http://example.com/customer/account</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>Flat Rate - Fixed</td>
  </tr>
  <tr>
    <td>order_comment</td>
    <td>string</td>
    <td>This is a comment</td>
  </tr>
</table>


#### **Shipment Email (Guest)**

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>order_status</td>
    <td>string</td>
    <td>Shipped</td>
  </tr>
  <tr>
    <td>increment_id</td>
    <td>number</td>
    <td>000000003</td>
  </tr>
  <tr>
    <td>account_url</td>
    <td>string</td>
    <td>http://example.com/customer/account</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>Flat Rate - Fixed</td>
  </tr>
  <tr>
    <td>order_comment</td>
    <td>string</td>
    <td>2017-10-24</td>
  </tr>
</table>


#### **Credit Memos**

<table>
  <tr>
    <td>vars</td>
    <td>type</td>
    <td>example</td>
  </tr>
  <tr>
    <td>name</td>
    <td>string</td>
    <td>John Smith</td>
  </tr>
  <tr>
    <td>store_name</td>
    <td>string</td>
    <td>Default Store</td>
  </tr>
  <tr>
    <td>account_url</td>
    <td>string</td>
    <td>http://example.com/customer/account</td>
  </tr>
  <tr>
    <td>store_email</td>
    <td>string</td>
    <td>help@example.com</td>
  </tr>
  <tr>
    <td>creditmemo_id</td>
    <td>string</td>
    <td>000000002</td>
  </tr>
  <tr>
    <td>order_id</td>
    <td>string</td>
    <td>000000006</td>
  </tr>
  <tr>
    <td>shipping_description</td>
    <td>string</td>
    <td>Flat Rate - Fixed</td>
  </tr>
</table>
