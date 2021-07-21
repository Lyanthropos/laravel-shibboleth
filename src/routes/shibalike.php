<?php
if(config("shibboleth.register_routes") !== false && config("shibboleth.emulate_idp") == true) { // use !== to handle case where it's not defined
    Route::group(['middleware' => 'web'], function () {
        Route::get('emulated/idp', '\StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@emulateIdp');
        Route::post('emulated/idp', '\StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@emulateIdp');
        Route::get('emulated/login', '\StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@emulateLogin');
        Route::get('emulated/logout', '\StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@emulateLogout');
    });
}