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



## Configuration ##

The published `shibboleth.php` config file allows for a wide range of customization. If you're leveraging the local-sp functionality, you'll need to provide a variety of information about your Shibboleth IdP. If you're leveraging the Apache shibboleth SP, you should only need to verify the Shibboleth attributes that you'll be using. 

For local development, add `SHIB_EMULATE=true` to your .env file. This will enable you to login with any of the users defined in the `emulate_idp_users` array of shibboleth.php. You may add additional users if you wish.

UMN users: the shibboleth.php local-sp config is mostly ready for the UMN IdP. You'll need to provide your private key and cert in the .env file. See the example below.

```env
SHIB_SP_TYPE=local_shib
SHIB_EMULATE=false
SHIB_ENTITY_ID=your-shibboleth-entity
SHIB_ASSERTION_CONSUMER_URL=https://your-hostname/local-sp/ACS
SHIB_LOGOUT_SERVICE_URL=https://your-hostname/local-sp/Logout
SHIB_X509_CERT="Certificate here, no line returns"
SHIB_PRIVATE_KEY="Private Key Here, no Line Returns"
SHIB_IDP_ENTITY=https://idp2.shib.umn.edu/idp/shibboleth
SHIB_IDP_SSO=https://login.umn.edu/idp/profile/SAML2/Redirect/SSO
SHIB_IDP_SLO=https://login.umn.edu/idp/profile/SAML2/Redirect/SLO
SHIB_IDP_X509_SIGNING="MIIDHzCCAgegAwIBAgIUah2ROh5+3z9VKbgAKYi4SezMYjwwDQYJKoZIhvcNAQEL\nBQAwGDEWMBQGA1UEAwwNbG9naW4udW1uLmVkdTAeFw0xNjA2MjkxNzQ4MTRaFw0z\nNjA2MjkxNzQ4MTRaMBgxFjAUBgNVBAMMDWxvZ2luLnVtbi5lZHUwggEiMA0GCSqG\nSIb3DQEBAQUAA4IBDwAwggEK$
SHIB_IDP_X509_ENCRYPTION="MIIDHzCCAgegAwIBAgIUSM2i3FZUSYOcPx+9WwQrsSRtYC8wDQYJKoZIhvcNAQEL\nBQAwGDEWMBQGA1UEAwwNbG9naW4udW1uLmVkdTAeFw0xNjA2MjkxNzQ5MzhaFw0z\nNjA2MjkxNzQ5MzhaMBgxFjAUBgNVBAMMDWxvZ2luLnVtbi5lZHUwggEiMA0GCSqG\nSIb3DQEBAQUAA4IBDwAwg$
```

Also, if you use the built in local-sp module for metadata generation by accessing hostname/local-sp/Metadata, **be sure to remove md:NameIDFormat from the metadata** before submitting it to OIT.


## Usage ##

If everything is right with the world, users may now login via Shibboleth by going to `https://example.com/shibboleth-login`
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
