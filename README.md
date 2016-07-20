Sailthru Magento2 Extension
----------------------
####codename: *MageSail*

##Instructions
1. From Magento root: `composer require sailthru/sailthru-php5-client`
2. `cd app/code && mkdir Sailthru`
3. `git clone <thisrepo>`
4.  cd back to Magento root: 
5. `bin/magento module:enable Sailthru_MageSail`
6. `bin/magento cache:flush`
7. `touch var/log/sailthru.log`
8. go to Magento Admin > Stores > Configuration > Sailthru
9. Enjoy!
