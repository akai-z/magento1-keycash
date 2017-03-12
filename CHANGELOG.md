# Changelog
All notable changes to this project will be documented in this file.

## [0.2.1](https://github.com/keycash/magento1-keycash/tree/0.2.1) - 2017-03-12
[Full Changelog](https://github.com/keycash/magento1-keycash/compare/0.2.0...0.2.1)

**Added**
* Config option for cron heartbeat interval.

**Changed**
* Improve orders addresses countries ISO code conversion.
* Cron heartbeat notification message.

**Fixed**
* Order model getSalesOrderCollection method limit issue.

## [0.2.0](https://github.com/keycash/magento1-keycash/tree/0.2.0) - 2017-02-21
[Full Changelog](https://github.com/keycash/magento1-keycash/compare/0.1.0...0.2.0)

**Added**
* Title to sales order grid verification status icons.
* The ability to update status of KeyCash orders that are cancelled for the first time.
* New filter options to sales order grid verification status column.

**Changed**
* Convert discount prices to positive numbers.
* Verification state 'verified' code to 'complete'.
* Sales order grid verification status 'verified' icon size and fix its alignment.
* Sales order grid verification status column label.
* Reduce sales order grid verification status column width.
* Make verification state value more readable in order view page.

**Fixed**
* An issue related to mass verifying an already submitted single order.
* Showing order view page verification tab for closed orders.
* A small issue in adminhtml order controller mass verification action order creation.
* Force returning verification date in order verification retrieve request.

**Removed**
* Unnecessary filter options from sales order grid verification status column.

## [0.1.0](https://github.com/keycash/magento1-keycash/tree/0.1.0) - 2017-02-19
* Initial release.
