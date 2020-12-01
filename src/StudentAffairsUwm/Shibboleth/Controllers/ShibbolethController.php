<?php

namespace StudentAffairsUwm\Shibboleth\Controllers;

use Illuminate\Auth\GenericUser;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use StudentAffairsUwm\Shibboleth\Entitlement;
use StudentAffairsUwm\Shibboleth\ConfigurationBackwardsCompatabilityMapper;

use OneLogin\Saml2\Auth as OneLogin_Saml2_Auth;
use OneLogin\Saml2\Error as OneLogin_Saml2_Error;

class ShibbolethController extends Controller
{
    /**
     * Service Provider
     * @var Shibalike\SP
     */
    private $sp;

    /**
     * Identity Provider
     * @var Shibalike\IdP
     */
    private $idp;

    /**
     * Configuration
     * @var Shibalike\Config
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct(GenericUser $user = null)
    {
        if (config('shibboleth.emulate_idp') === true) {
            $this->config         = new \Shibalike\Config();
            $this->config->idpUrl = '/emulated/idp';

            $stateManager = $this->getStateManager();

            $this->sp = new \Shibalike\SP($stateManager, $this->config);
            $this->sp->initLazySession();

            $this->idp = new \Shibalike\IdP($stateManager, $this->getAttrStore(), $this->config);
        }

        $this->user = $user;
    }

    /**
     * Create the session, send the user away to the IDP
     * for authentication.
     */
    public function login()
    {
        if (config('shibboleth.emulate_idp') === true) {
            return Redirect::to(action('\\' . __CLASS__ . '@emulateLogin')
                . '?target=' .  action('\\' . __CLASS__ . '@idpAuthenticate'));
        }

        return Redirect::to(url('/') . config('shibboleth.' . config('shibboleth.sp_type') . '.idp_login')
            . '?target=' . action('\\' . __CLASS__ . '@idpAuthenticate'));
    }

    /**
     * Setup authentication based on returned server variables
     * from the IdP.
     */
    public function idpAuthenticate()
    {
        if (empty(config('shibboleth.user'))) {
            ConfigurationBackwardsCompatabilityMapper::map();
        }

        foreach (config('shibboleth.user') as $local => $server) {
            $map[$local] = $this->getServerVariable($server);
        }

        if (empty($map[config('shibboleth.authfield')])) {
            return abort(403, 'Unauthorized');
        }

        $userClass = config('auth.providers.users.model', 'App\User');

        // Attempt to login with the email, if success, update the user model
        // with data from the Shibboleth headers (if present)
        if (Auth::attempt(array(config('shibboleth.authfield') => $map[config('shibboleth.authfield')]), true)) {
            $user = $userClass::where(config('shibboleth.authfield'), '=', $map[config('shibboleth.authfield')])->first();

            // Update the model as necessary
            $user->update($map);
        }

        // Add user and send through auth.
        elseif (config('shibboleth.add_new_users', true)) {
            $map['password'] = 'shibboleth';
            $user = $userClass::create($map);
            Auth::attempt(array(config('shibboleth.authfield') => $map[config('shibboleth.authfield')]), true);
        }

        else {
            return abort(403, 'Unauthorized');
        }

        Session::regenerate();

        // $entitlementString = $this->getServerVariable(config('shibboleth.entitlement'));
        // if (!empty($entitlementString)) {
        //     $entitlements = Entitlement::findInString($entitlementString);
        //     $user->entitlements()->sync($entitlements);
        // }

        $route = config('shibboleth.authenticated');

        return redirect()->intended($route);
    }

    /**
     * Destroy the current session and log the user out, redirect them to the main route.
     */
    public function destroy()
    {
        Auth::logout();
        Session::flush();

        if (config('shibboleth.emulate_idp') == true) {
            return Redirect::to(action('\\' . __CLASS__ . '@emulateLogout'));
        }

        return Redirect::to(url('/') . config('shibboleth.' . config('shibboleth.sp_type') . '.idp_logout'));
    }

    /**
     * Emulate a login via Shibalike
     */
    public function emulateLogin()
    {
        $from = (Request::input('target') != null) ? Request::input('target') : $this->getServerVariable('HTTP_REFERER');

        $this->sp->makeAuthRequest($from);
        $this->sp->redirect();
    }

    /**
     * Emulate a logout via Shibalike
     */
    public function emulateLogout()
    {
        $this->sp->logout();

        $referer = $this->getServerVariable('HTTP_REFERER');

        Auth::logout();
        Session::flush();
    }

    /**
     * Emulate the 'authentication' via Shibalike
     */
    public function emulateIdp()
    {
        $data = [];

        if (Request::input('username') != null) {
            $username = (Request::input('username') === Request::input('password')) ?
                Request::input('username') : '';

            $userAttrs = $this->idp->fetchAttrs($username);
            if ($userAttrs) {
                $this->idp->markAsAuthenticated($username);
                $this->idp->redirect("/shibboleth-authenticate");
            }

            $data['error'] = 'Incorrect username and/or password';
        }

        return view('vendor.shibalike.IdpLogin', $data);
    }

    /**
     * Function to get an attribute store for Shibalike
     */
    private function getAttrStore()
    {
        return new \Shibalike\Attr\Store\ArrayStore(config('shibboleth.emulate_idp_users'));
    }

    /**
     * Gets a state manager for Shibalike
     */
    private function getStateManager()
    {
        $session = \UserlandSession\SessionBuilder::instance()
            ->setSavePath(sys_get_temp_dir())
            ->setName('SHIBALIKE_BASIC')
            ->build();

        return new \Shibalike\StateManager\UserlandSession($session);
    }

    /**
     * Wrapper function for getting server variables.
     * Since Shibalike injects $_SERVER variables Laravel
     * doesn't pick them up. So depending on if we are
     * using the emulated IdP or a real one, we use the
     * appropriate function.
     */
    private function getServerVariable($variableName)
    {
        if (config('shibboleth.emulate_idp') == true) {
            return isset($_SERVER[$variableName]) ?
                $_SERVER[$variableName] : null;
        }
        if (config('shibboleth.sp_type') == "local_shib") {
            if(Request::session("shibAttributes")) {
                $deserialized = unserialize(Request::session()->get("shibAttributes"));
                return isset($deserialized[$variableName]) ?
                (is_array($deserialized[$variableName])?$deserialized[$variableName][0]:$deserialized[$variableName]) : null;
            }
        }

        $variable = Request::server($variableName);

        return (!empty($variable)) ?
            $variable :
            Request::server('REDIRECT_' . $variableName);
    }

    /*
     * Simple function that allows configuration variables
     * to be either names of views, or redirect routes.
     */
    private function viewOrRedirect($view)
    {
        return (View::exists($view)) ? view($view) : Redirect::to($view);
    }

    public function localSPLogin() {

        $auth = new OneLogin_Saml2_Auth(config('shibboleth.local_settings'));
        $auth->login(null,array(),false,false,false,false);
    }

    public function localSPACS() {
        $auth = new OneLogin_Saml2_Auth(config('shibboleth.local_settings'));

        $auth->processResponse();

        $errors = $auth->getErrors();

        if (!empty($errors)) {
            return array('error' => $errors, 'last_error_reason' => $auth->getLastErrorReason());
        }

        if (!$auth->isAuthenticated()) {
            return array('error' => 'Could not authenticate', 'last_error_reason' => $auth->getLastErrorReason());
        }

        // foreach($auth->getAttributes() as $key=>$value) {
            Request::session()->flash("shibAttributes",serialize($auth->getAttributes()));
        // }
        // Request::session()->flash($auth->getAttributes());
        return Redirect::action('\\' . __CLASS__ . '@idpAuthenticate');

        
    }
    
    public function localSPMetadata() {
        $auth = new OneLogin_Saml2_Auth(config('shibboleth.local_settings'));
        $settings = $auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (empty($errors)) {
            return response($metadata, 200, ['Content-Type' => 'text/xml']);
        } else {

            throw new InvalidArgumentException(
                'Invalid SP metadata: ' . implode(', ', $errors),
                OneLogin_Saml2_Error::METADATA_SP_INVALID
            );
        }
        
    }

}
