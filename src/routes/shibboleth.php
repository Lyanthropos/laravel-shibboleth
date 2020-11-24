<?php

// This is optional because in multitennant land, this creates trouble
if(config("shibboleth.register_routes")) {
    Route::group(['middleware' => 'web'], function () {
        Route::name('shibboleth-login')->get('/shibboleth-login', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@login');
        Route::name('shibboleth-authenticate')->get('/shibboleth-authenticate', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@idpAuthenticate');
        Route::name('shibboleth-logout')->get('/shibboleth-logout', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@destroy');
        // When using the local SP option, we register a few extra routes to handle the SP side
        if(config('shibboleth.sp_type') == "local_shib") {
            Route::name('local-sp-login')->get('/local-sp/Login', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@localSPLogin');
            Route::name('local-sp-logout')->get('/local-sp/Logout', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@localSPLogout');
            // when we can require laravel 7.7, use ->withoutMiddleware(['csrf'])
            Route::name('local-sp-acs')->post('/local-sp/ACS', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@localSPACS');
            Route::name('local-sp-metadata')->get('/local-sp/Metadata', 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@localSPMetadata');
        }
    });
}