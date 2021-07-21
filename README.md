Laravel Shibboleth Service Provider
===================================

This package provides an easy way to implement Shibboleth Authentication for 6, 7 and 8. It can either use an existing Shibboleth SP (like apache mod_shib) or it can serve as its own Shib SP. 

## Installation ##

Because this is a fork, you'll need to add the fork to your composer.json file. Add this entry to your repositories array:

```json
    {
      "type": "vcs",
      "url": "https://github.com/UMN-LATIS/laravel-shibboleth"
    },
```

Use [composer][1] to require the latest release into your project:

    composer require razorbacks/laravel-shibboleth


Publish the default configuration file and entitlement migrations:

    php artisan vendor:publish --provider="StudentAffairsUwm\Shibboleth\ShibbolethServiceProvider"

You can also publish the views for the shibalike emulated IdP login:

    php artisan vendor:publish --provider="StudentAffairsUwm\Shibboleth\ShibalikeServiceProvider"


Change the driver to `shibboleth` in
your `config/auth.php` file.

```php
'providers' => [
    'users' => [
        'driver' => 'shibboleth',
        'model'  => App\User::class,
    ],
],
```

Now users may login via Shibboleth by going to `https://example.com/shibboleth-login`
and logout using `https://example.com/shibboleth-logout` so you can provide a custom link
or redirect based on email address in the login form.

```php
@if (Auth::guest())
    <a href="/shibboleth-login">Login</a>
@else
    <a href="/shibboleth-logout">
        Logout {{ Auth::user()->name }}
    </a>
@endif
```

You may configure server variable mappings in `config/shibboleth.php` such as
the user's first name, last name, entitlements, etc. You can take a look at them
by reading what's been populated into the `$_SERVER` variable after authentication.

```php
<?php print_r($_SERVER);
```

## Local Users

This was designed to work side-by-side with the native authentication system
for projects where you want to have both Shibboleth and local users.
If you would like to allow local registration as well as authenticate Shibboleth
users, then use laravel's built-in auth system.

    php artisan make:auth


[1]:https://getcomposer.org/
