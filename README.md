# Yandex Metrika - report API tool

[![Latest Stable Version](https://img.shields.io/packagist/v/tavgear/yandex-metrika-reporting-ga.svg)](https://packagist.org/packages/tavgear/yandex-metrika-reporting-ga)

Yandex Metrika Reporting API Library. Compatible with Google Analytics Core Reporting API (v3)

## Installation

```bash
$ composer require tavgear/yandex-metrika-reporting-ga
```

## Usage
### Getting a one-row report

Create client.

```php
<?php
use Tvg\YandexMetrika\ReportingGa\Client;
require_once 'vendor/autoload.php';

$ymClient = new Client('');
```

Setup parameters and get report.

```php
// Set counter and metrics
$ymClient
        ->setCounterId(29761725)
        ->setMetrics(['ga:pageviews', 'ga:sessions']);

// Get views and sessions for today
$r     = $ymClient->getRow();
//Array
//(
//    [ga:sessions] => 441
//    [ga:pageviews] => 1869
//)
```

### Setting periods

```php
// Get views and sessions for week
$r     = $ymClient
        ->setPeriod(7) // from 7 days ago to now
        ->getRow();
//Array
//(
//    [ga:pageviews] => 152469
//    [ga:sessions] => 31328
//)
```

```php
// Get views and sessions over the past month
$r     = $ymClient
        ->setPeriod(new DateTime('first day of 1 months ago'), new DateTime('last day of 1 months ago'))
        ->getRow();
//Array
//(
//    [ga:pageviews] => 658855
//    [ga:sessions] => 137752
//)
```

### Setting dimentions

```php
// Get views and sessions for last two days with dates
$r     = $ymClient
        ->setPeriod(Client::DATE_YESTERDAY)
        ->setDimensions('ga:date')
        ->request()
        ->getRowsAsArray();
//Array
//(
//    [0] => Array
//        (
//            [ga:date] => 20200725
//            [ga:pageviews] => 9742
//            [ga:sessions] => 2169
//        )
//
//    [1] => Array
//        (
//            [ga:date] => 20200726
//            [ga:pageviews] => 2012
//            [ga:sessions] => 472
//        )
//
//)
```

### Working with large reports

```php
// Set parameters for views and sessions for last 60 days with country and dates dimentions
$ymClient
        ->setPeriod(60)
        ->setDimensions(['ga:country', 'ga:date']);
        
// Get total count rows
$count = $ymClient->getAllRowsCount();
// 3367

// Get the entire report by requests of 500 rows
foreach ($ymClient->getRows(500) as $row) {
    $r = $row;
}
//Array
//(
//    [ga:country] => Russia
//    [ga:date] => 20200528
//    [ga:pageviews] => 25757
//    [ga:sessions] => 5518
//)
//Array
//(
//    [ga:country] => Russia
//    [ga:date] => 20200527
//    [ga:pageviews] => 24935
//    [ga:sessions] => 5305
//)
// ...
```

### Convert to csv-format

```php
// Get the entire report and save to csv file
$ymClient->saveToCsv('report.csv');
// File contents:
//ga:country,ga:date,ga:pageviews,ga:sessions
//Russia,20200528,25757,5518
//Russia,20200527,24935,5305
//Russia,20200604,23896,4940
//Russia,20200603,22991,4825
//...
```

### Filtering and sorting

```php
// Get data for Belarus sorted by date
$r = $ymClient
        ->setFilters('ga:country==Belarus')
        ->setSort('ga:date')
        ->request()
        ->getRowsAsArray();
//Array
//(
//    [0] => Array
//        (
//            [ga:country] => Belarus
//            [ga:date] => 20200527
//            [ga:pageviews] => 1172
//            [ga:sessions] => 249
//        )
//
//    [1] => Array
//        (
//            [ga:country] => Belarus
//            [ga:date] => 20200528
//            [ga:pageviews] => 1463
//            [ga:sessions] => 311
//        )
// ..
// ..
```

## API Documentation

You can find full information on request parameters on the YandexMetrikaAPI page.

[https://yandex.ru/dev/metrika/doc/api2/ga/intro-docpage/](https://yandex.ru/dev/metrika/doc/api2/ga/intro-docpage/)

## License

Licensed under the MIT License
