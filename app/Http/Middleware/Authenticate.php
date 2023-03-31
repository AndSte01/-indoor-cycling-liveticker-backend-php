<?php

namespace App\Http\Middleware;

use App\Providers\AuthServiceProvider;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            // Authentication wasn't successful
            if ($guard == "token")
                return new JsonResponse(['Unauthorized'], 401, self::getBearerAuthenticationHeader());

            if ($guard == "password")
                return new JsonResponse(['Unauthorized'], 401, self::getBasicAuthenticationHeader());
        }

        return $next($request);
    }

    /** @var string The realm to use */
    private const realm = "app";

    /**
     * Get the required headers for basic authentication
     * 
     * @return array the required headers
     */
    static protected function getBasicAuthenticationHeader(): array
    {
        // set the authentication field in the header
        // according to https://datatracker.ietf.org/doc/html/rfc7617
        // WWW-Authenticate: Basic realm="WallyWorld", charset="UTF-8"
        return ["WWW-Authenticate" => "Basic realm=\"" . self::realm . "\", charset=\"UTF-8\""];
    }

    /**
     * Sets the header for requesting bearer authentication details
     * 
     * @param int $error Error code to use (valid options are 0, ERROR_INVALID_REQUEST, ERROR_INVALID_TOKEN, ERROR_NOT_QUALIFIED)
     * (see https://datatracker.ietf.org/doc/html/rfc6750#section-3.1)
     * 
     * @return array the required headers
     */
    static protected function getBearerAuthenticationHeader(int $error = 0): array
    {

        $error_string = "";

        // select what error to add
        switch ($error) {
            case AuthServiceProvider::ERROR_INVALID_REQUEST:
                $error_string = ", error=\"invalid_request\"";
                break;

            case AuthServiceProvider::ERROR_INVALID_TOKEN:
                $error_string = ", error=\"invalid_token\"";
                break;

            case AuthServiceProvider::ERROR_NOT_QUALIFIED:
                $error_string = ", error=\"insufficient_scope\"";
                break;
        }

        // set the authentication filed in the header
        // according to https://datatracker.ietf.org/doc/html/rfc6750
        // WWW-Authenticate: Bearer realm="example",
        //                   error="invalid_token",
        //                   error_description="The access token expired"
        return ["WWW-Authenticate" => "Bearer realm=\"" . self::realm . "\"" . $error_string];
    }
}
