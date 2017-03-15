# MageSail 
##### Sailthru Magento 2 Extension
----------------------

## Installation Instructions

1. Get the module
	via composer  `composer require sailthru/sailthru-magento2-extension`

2. Enable the module
    `bin/magento module:enable Sailthru_Magesail`

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


[1]: https://getstarted.sailthru.com/integrations/overview/
[2]: https://my.sailthru.com/settings/spider
[3]: https://my.sailthru.com/settings/domains
[4]: https://my.sailthru.com/settings/api_postbacks

