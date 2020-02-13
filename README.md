# MageSail 
##### Sailthru Magento 2 Extension
----------------------

## Installation Instructions

1. Get the module
	via composer  `composer require sailthru/sailthru-magento2-extension`

2. Enable the module
    `bin/magento module:enable Sailthru_MageSail`

3. Upgrade the database
	`bin/magento setup:upgrade`
   *(Depending on Magento mode, you may need to run `magento setup:di:compile`)*

4. Go to Magento Admin > Stores > Configuration > Sailthru to configure. Visit the [Sailthru Documentation Site](https://getstarted.sailthru.com/integrations/magento/magento-2-extension/) for setup documentation.

*__Note__: If sync'ing variant products with no visible individual URL, you should enable "Preserve Fragments" in Sailthru [here][2].*

## Javascript Setup
The Sailthru MageSail module comes ready to use Sailthru's new PersonalizeJs javascript. To add page-tracking and gather onsite data like pageviews and clicks: 

1. Set your "Site Domain" [here][3].
2. Add your Customer ID (found [here][4]) to vendor/sailthru/sailthru-magento2-extension/view/frontend/web/spm.js 

**Please contact Sailthru to learn more about and enable Site Personalization Manager.**

## To define transactional template to be overriden by Sailthru

Declare new transactional email template in `./etc/template_config.xml` file that can be created within any module.
Each template definition in `./etc/template_config.xml` has four required parameters that are defined in `./etc/template_list.xsd` file.
Required parameters are:
1. id - transactional email template identifier in Magento 2
2. name - template title which will be displayed in `Admin Panel -> Stores -> Configuration -> Sailthru -> Transactionals -> General Transactionals` dropdown list
3. custom_template_source - `Core Config Path` to native Magento 2 transactional email template ID value. Is utilized when custom email template overrides default Magento 2 email template
4. sort_order - template sort order


After updating the `./etc/template_config.xml` file run `php bin/magento cache:clean config` to clean Config cache.
To extend or change structure of a transactional email templates override config file use `./etc/template_list.xsd` file.

## Queue Setting

1. Upgrade the database and generate queue
    `bin/magento setup:upgrade`
    *(Depending on Magento mode, you may need to run `magento setup:di:compile`)*

2. Run cron `bin/magento cron:run`

3. Go to *Admin > Stores > Configuration > Sailthru > Messaging* to configure and set *Yes* for *Process Sailthru Emails By Queue*

[1]: https://getstarted.sailthru.com/integrations/overview/
[2]: https://my.sailthru.com/settings/spider
[3]: https://my.sailthru.com/settings/domains
[4]: https://my.sailthru.com/settings/api_postbacks

