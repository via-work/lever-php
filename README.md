# Lever PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/via-work/lever-php?style=flat-square)](https://packagist.org/packages/via-work/lever-php)
[![Build Status](https://img.shields.io/travis/via-work/lever-php/master.svg?style=flat-square)](https://travis-ci.org/via-work/lever-php)
[![Quality Score](https://img.shields.io/scrutinizer/g/via-work/lever-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/via-work/lever-php)
[![Total Downloads](https://img.shields.io/packagist/dt/via-work/lever-php.svg?style=flat-square)](https://packagist.org/packages/via-work/lever-php)
![StyleCI](https://github.styleci.io/repos/238786347/shield?branch=master)

Super-simple Lever Data API v1 wrapper in PHP with support for Laravel.

## Installation

You can install the package via composer:

```bash
composer require via-work/lever-php
```

## Usage

#### PHP

``` php
use \ViaWork\LeverPhp\LeverPhp;

$lever = new LeverPhp('leverKey');

$lever->opportunities()->fetch();

```

#### Laravel

After installing, the package will automatically register its service provider.

To publish the config file to config/lever-php.php run:

``` bash
php artisan vendor:publish --provider="ViaWork\LeverPhp\LeverPhpServiceProvider"
```

After changing your API keys in your ENV file accordingly, you can call a Lever instance as follows:

``` php
Lever::opportunities()->fetch();
```

### Methods

This package is modeled after [Lever's Data API documentation](https://hire.lever.co/developer/documentation), so you should be able to find a method for many of the endpoints.

For example, if you would like to fetch all Opportunities you would simply call:
 
 ``` php
 Lever::opportunities()->fetch();
```


To retrieve a single opportunity you should call the same method while passing the id as a parameter: 

``` php
Lever::opportunities('250d8f03-738a-4bba-a671-8a3d73477145')->fetch();
```

To create an opportunity use the create method while passing the fields array (use same names as Lever):

``` php
$newOpportunity = [
                   'name' => 'Shane Smith',
                   'headline' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
                   'stage' => '00922a60-7c15-422b-b086-f62000824fd7',
                    ...
                  ];

Lever::opportunities()->create($newOpportunity);
```

When submiting an application on behalf of a candidate to a specific posting you should use the following method:
 
``` php
$application = [
                   'customQuestions' => [...],
                   'personalInformation' => [...],
                   'ipAddress' => '184.23.195.146',
                    ...
                  ];

Lever::opportunities('730e37db-93d3-4acf-b9de-7cfc397cef1d')
    ->sendConfirmationEmail()
    ->apply($application);
```


When an update endpoint is available, you can do it as follows:

``` php
$posting = [
             'text' => 'Infrastructure Engineer',
             'state' => 'published',
             ...
           ];

Lever::postings('730e37db-93d3-4acf-b9de-7cfc397cef1d')
    ->performAs('8d49b010-cc6a-4f40-ace5-e86061c677ed')
    ->update($posting);
```

Be aware of the resources that require some parameters to work. When creating a posting for example, the _perform_as_ parameter is required. You can pass this information with the `performAs($userId)` method.

When a resource depends on another one to work, you can simply chain the methods (order is important). For example, to retrieve the **offers** of a **opportunity**, you should execute this:

``` php
Lever::opportunities('250d8f03-738a-4bba-a671-8a3d73477145')->offers()->fetch();
```

When Lever asks to use the PUT verb, you can use `putUpdate()` instead of `update()` (POST).

#### Parameters

There are many helper methods available to include parameters in a request. For example, to _include_ the _followers_ and _expand applications_ and _stages_, when fetching opportunities, you can do so:

```php
Lever::opportunities()
    ->include('followers')
    ->expand(['applications', 'stages'])
    ->expand('posting')
    ->fetch();
```

Notice you can pass a string or an array of strings in both methods, and you can chain the same method many times if you wish. 

Not all parameters have a method available, but you can use the `addParameter($field, $value)` method for this. This method can be chained without overwriting previous values. For example:

 ```php
 Lever::opportunities()
     ->addParameter('origin', 'applied')
     ->addParameter('posting_id', 'f2f01e16-27f8-4711-a728-7d49499795a0')
     ->fetch();
 ```
Be aware that when using the same field name, the new value will be appended and not overwritten. 

#### Uploading files and resumes

LeverPhp allows you to include resumes or files when available. To do this, you have to include the file in the fields array (see example) and chaining the `hasFiles()` method (before the `create` or `update` method!). For example, you can append a resume when creating an opportunity: 


``` php
$newOpportunity = [
                   'name' => 'Shane Smith',
                   'headline' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
                   'resumeFile' => [
                        'file' => file_get_contents('path/to/resume.pdf'),
                        'name' => 'resume.pdf' 
                        'type' => mime_content_type('path/to/resume.pdf'), // application/pdf
                        ] 
                  ];

Lever::opportunities()->hasFiles()->create($newOpportunity);
```

Currently, there is no support for multiple files in one call.

#### Pagination

All Lever resources with a list endpoint (candidates, users, postings) have pagination and a max limit of 100 results per page. LeverPhp handles this automatically leveraging Laravel [LazyCollection](https://laravel.com/docs/6.x/collections#lazy-collections) class. For example, you can iterate over the whole set of Opportunities without worrying about pagination:

 ``` php
 $opportunities = Lever::opportunities()->fetch();

 foreach ($opportunities as $opportunity) {
     echo $opportunity['name];
 }
``` 

When item hundred is reached, another call is made to the API requesting the next 100 items until there are no more left.

Of course you can take advantage of all [methods available](https://laravel.com/docs/6.x/collections#the-enumerable-contract) on the LazyCollection class. 

#### Rate Limit and Exponential Backoff

By default, Lever API allows a steady state number of 10 requests/second per API key. 

To comply with this, **LeverPhp** automatically limits the number of requests to the Lever Data API to 10 per second and uses [exponential backoff](https://en.wikipedia.org/wiki/Exponential_backoff) to decrease the retry rate when a 429 or 500 response is received. Please plan you code accordingly, as a request might take much longer than expected because of this. Using some kind of queues is suggested. 

>By default, the rate limiter works in memory. This means that if you have a second PHP process (or Guzzle client) consuming the same API, you'd still possibly hit the rate limit. 

The `LeverPhp()` constructor accepts a custom store in its third parameter to overcome the in memory issue. If you are using Laravel, a custom store that uses your cache driver is already configured. Please see the documentation of [GuzzleRateLimiterMiddleware](https://github.com/spatie/guzzle-rate-limiter-middleware#custom-stores) if you need more information.

#### Client

If a method is not available for the resource you are trying to reach, you can get an instance of the Guzzle client directly by calling `Lever::client()`. Feel free to add it to the source code. Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing


Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email dev@via.work instead of using the issue tracker.

## Credits

- [Omar Sánchez](https://github.com/omarsancas)
- [Alfonso Strotgen](https://github.com/strotgen)
- [Via.work](https://github.com/via-work)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
