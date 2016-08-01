Sailthru Magento2 Extension
----------------------
####codename: *MageSail*

##Instructions
1. From Magento root: `composer require sailthru/sailthru-php5-client`
2. `cd app/code && mkdir Sailthru`
3.  git clone or copy MageSail folder into `app/code/Sailthru/`
4.  cd back to Magento root: 
5. `bin/magento module:enable Sailthru_MageSail`
6. `bin/magento cache:flush`
7. go to Magento Admin > Stores > Configuration > Sailthru
8. If configured products (simple products) don't have their own public URLs, enable "Preserve Fragments" in Sailthru UI at https://my.sailthru.com/settings/spider
9. Set sail!

## SPM Setup
The Sailthru MageSail module comes ready to use Sailthru's new PersonalizeJs javascript, enabling Site Personalization Manager. To use:
1. Add your site under "Site Domain" at https://my.sailthru.com/settings/domains
2. To use more advanced features, edit 'app/code/Sailthru/MageSail/view/frontend/web/spm.js' to add SPM callbacks and more. Or create your own file with the RequireJS method as shown in spm.js


