<?php

namespace SlowCheetah\KeycloakWebGuard\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use SlowCheetah\KeycloakWebGuard\Exceptions\KeycloakCallbackException;
use SlowCheetah\KeycloakWebGuard\Facades\KeycloakWeb;

class AuthController extends Controller
{
    /**
     * Redirect to login
     *
     * @return view
     */
    public function login()
    {
        $referer = request()->headers->get('referer');
        if ($referer) {
            redirect()->setIntendedUrl($referer);
        }
        $url = KeycloakWeb::getLoginUrl();
        KeycloakWeb::saveState();

        return redirect($url);
    }

    /**
     * Redirect to logout
     *
     * @return view
     */
    public function logout()
    {
        $url = KeycloakWeb::getLogoutUrl();
        KeycloakWeb::forgetToken();
        return redirect($url);
    }

    /**
     * Redirect to register
     *
     * @return view
     */
    public function register()
    {
        $url = KeycloakWeb::getRegisterUrl();
        return redirect($url);
    }

    /**
     * Keycloak callback page
     *
     * @throws KeycloakCallbackException
     *
     * @return view
     */
    public function callback(Request $request)
    {
        // Check for errors from Keycloak
        if (! empty($request->input('error'))) {
            $error = $request->input('error_description');
            $error = ($error) ?: $request->input('error');

            throw new KeycloakCallbackException($error);
        }

        // Check given state to mitigate CSRF attack
        $state = $request->input('state');
        if (empty($state) || ! KeycloakWeb::validateState($state)) {
            KeycloakWeb::forgetState();

            throw new KeycloakCallbackException('Invalid state');
        }

        // Change code for token
        $code = $request->input('code');
        if (! empty($code)) {
            $token = KeycloakWeb::getAccessToken($code);

            if (Auth::validate($token)) {
                $url = config('keycloak-web.redirect_url', '/admin');
                return redirect()->intended($url);
            }
        }

        return redirect(route('keycloak.login'));
    }
}
