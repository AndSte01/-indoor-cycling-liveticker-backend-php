<?php

namespace App\Providers;

use App\Models\user;
use \Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use OutOfRangeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('password', function ($request) {
            $user = self::authenticate($request, self::AUTHENTICATION_METHOD_BASIC);

            // if an error occurred an int will be returned
            if (is_int($user)) {
                return;
            }
            return $user;
        });

        $this->app['auth']->viaRequest('token', function ($request) {
            $user = self::authenticate($request, self::AUTHENTICATION_METHOD_BEARER);

            // if an error occurred an int will be returned
            if (is_int($user)) {
                return;
            }
            return $user;
        });
    }

    // Errors that might happen during authentication
    /** @var int Error if client didn't provide authentication header */
    public const ERROR_NO_AUTHENTICATION_INFO = 1;
    /** @var int Error if the Username doesn't exists */
    public const ERROR_NO_SUCH_USER = 2;
    /** @var int Error if the password isn't correct */
    public const ERROR_INVALID_PASSWORD = 4;
    /** @var int The request was invalid (due to multiple reasons) */
    public const ERROR_INVALID_REQUEST = 8;
    /** @var int The provided bearer token wasn't correct */
    public const ERROR_INVALID_TOKEN = 16;
    /** @var int The user isn't qualified */
    public const ERROR_NOT_QUALIFIED = 32;
    /** @var int The client used the wrong authentication method */
    public const ERROR_WRONG_AUTHENTICATION_METHOD = 64;
    /** @var int A new authentication was forced by a previous call of logout(), This is not an error but desired behavior */
    public const ERROR_FORCED_AUTHENTICATION = 128;

    /** @var int All Authentication methods can be used to authenticate */
    public const AUTHENTICATION_METHOD_ANY = 0;
    /** @var int Basic authentication method (see https://datatracker.ietf.org/doc/html/rfc7617) */
    public const AUTHENTICATION_METHOD_BASIC = 1;
    /** @var int Bearer token authentication method (see https://datatracker.ietf.org/doc/html/rfc6750) */
    public const AUTHENTICATION_METHOD_BEARER = 2;

    /**
     * Authenticates a user by the selected method
     * 
     * Possible methods are:
     * - Basic Authentication (AUTHENTICATION_METHOD_BASIC) (see https://datatracker.ietf.org/doc/html/rfc7617)
     * - Bearer Authentication (AUTHENTICATION_METHOD_BEARER) (see https://datatracker.ietf.org/doc/html/rfc6750)
     * - Any of the above mentioned (AUTHENTICATION_METHOD_ANY) (note: you need to provide preferred method as parameter)
     * 
     * After receiving an return that is not 0 (meaning an error happened) exit the script as soon as possible,
     * so new authentication data can be requested from the client. This is all done inside the http header, so be careful when modifying it
     * (especially don't  modify the response code or the authentication field).
     * 
     * Please note, when using bearer authentication, errors are also logged inside the authentication header
     * (see https://datatracker.ietf.org/doc/html/rfc6750#section-3.1)
     * 
     * It depends on the method of the authentication wether a current user or a current bearer token can be provided, see the corresponding getters for mor details.
     * 
     * Note: you can authenticate twice (the header gets analyzed all over again)
     * 
     * @param Request The request to authenticate
     * @param int $method the authentication method to use
     * @param int $minimum_role the minimum role of the user that is required for successful authentication (only used when utilizing bearer authentication)
     * @param int $method_preferred the method the client should use if no authentication data is provided and any method is allowed
     * 
     * @throws OutOfRangeException If the selected method isn't in the above mentioned
     * 
     * @return int The errors that happened during authentication, or if none happened 0.
     */
    public static function authenticate(Request $request, int $method, int $method_preferred = self::AUTHENTICATION_METHOD_BASIC): user|int
    {
        // --- 1. try to get the required information from the header

        // if a special method (meaning method isn't self::AUTHENTICATION_METHOD_ANY) is defined set it as the preferred one
        if ($method != self::AUTHENTICATION_METHOD_ANY) {
            $method_preferred = $method;
        }

        // create placeholder variable for decoded method nad header content
        $method_internally = 0;
        $header_content = [];

        // try to decode header, yeah I don't get it either why I used a separate function for that stuff.
        $header_errors = self::validateDecodeHeader($request, $method_internally, $method, $method_preferred, $header_content);

        // now map the functions for easier use (note: this needs to bee done before handling $header_errors,
        // elsewise the headers can't be set correctly)
        switch ($method_internally) {
            case self::AUTHENTICATION_METHOD_BASIC:
                // decodes the header payload
                $decodeHeader = "decodeBasicAuthenticationHeader";
                break;

            case self::AUTHENTICATION_METHOD_BEARER:
                $decodeHeader = "decodeBearerAuthenticationHeader";
                break;

            default:
                // if none of the supported methods was selected throw error
                throw new OutOfRangeException("The selected authentication method isn't supported");
        }

        // handle errors that happened during decoding of the header
        switch ($header_errors) {
            case self::ERROR_NO_AUTHENTICATION_INFO:
                return $header_errors;
                break;

            case self::ERROR_INVALID_REQUEST:
                return $header_errors;
                break;

            case self::ERROR_WRONG_AUTHENTICATION_METHOD:
                return $header_errors;
                break;
        }

        // now decode the payload
        $payload_decoded = self::$decodeHeader($header_content[1]);

        // check if decoding was UNsuccessful
        if (is_bool($payload_decoded)) {
            return self::ERROR_INVALID_REQUEST;
        }

        // --- 3. we now have valid (authentication) data, now check if it authenticates a user successfully ---

        // handle the different authentication methods
        switch ($method_internally) {
            case self::AUTHENTICATION_METHOD_BASIC:
                // try authentication
                // get user form database
                $user = user::getUserByName($payload_decoded["username"]);
                if ($user == null) {
                    return self::ERROR_NO_SUCH_USER;
                }

                // check for password
                if (!$user->validatePassword($payload_decoded["password"])) {
                    return self::ERROR_INVALID_PASSWORD;
                }

                // exit switch statement
                break;

            case self::AUTHENTICATION_METHOD_BEARER:
                $decoded_token_contents = self::decodeBearerToken($payload_decoded);

                // check if token was decoded successfully
                if ($decoded_token_contents === null)
                    return self::ERROR_INVALID_TOKEN;

                // search for user in the database
                $user = user::getUserByName($decoded_token_contents[0]);

                // check if a user has been found
                if ($user == null) {
                    return self::ERROR_NO_SUCH_USER;
                }

                // check for password
                if (!$user->validateBinaryToken($decoded_token_contents[1])) {
                    return self::ERROR_INVALID_TOKEN;
                }

                // exit switch statement
                break;
        }

        // if we make it this far we can return the user
        return $user;
    }

    /**
     * Function that validates and decodes the header sent to the server.
     * 
     * Decoded information is passed by reference, only errors are passed via return.
     * This function can also check if the client used the authentication scheme desired by the server,
     * and also wether the desired method is supported by this class.
     * 
     * @param Request $request The request to work with
     * @param int &$method The variable in which the decoded method should bes stored, passed by reference.
     * @param int $desired_method The method that is desired by the server ($method is set to this value in case the client used the wrong auth scheme)
     * @param int $fallback_method The method that should be assumed in case the method used by the client couldn't be detected 
     * @param array &$ header_content The content's of the header that were found during decoding
     * 
     * @throws OutOfRangeException If the selected method isn't in the above mentioned
     * 
     * @return int the errors that happened during validation and decoding (0 in case of success)
     */
    protected static function validateDecodeHeader(Request $request, int &$method, int $desired_method, int $fallback_method, array &$header_content)
    {
        // --- 1. do some basic checks of authentication information

        // get authentication info form request
        $auth_header = $request->header("Authorization");

        // check if authentication information is present
        if ($auth_header == null) {
            // set the method to the fallback method
            $method = $fallback_method;
            // return error indication no authentication info was sent by client
            return self::ERROR_NO_AUTHENTICATION_INFO;
        }

        // --- 2. authentication information is present, now decode it and check the validity ---

        // split header in parts
        $header_content = explode(" ", $auth_header);

        // try to find out which authentication message the client used
        switch ($header_content[0]) {
            case "Basic":
                // set method to basic (note: passed by reference)
                $method = self::AUTHENTICATION_METHOD_BASIC;
                break;

            case "Bearer":
                // set method to bearer (note: passed by reference)
                $method = self::AUTHENTICATION_METHOD_BEARER;
                break;

            default:
                // set method to 0 (in hope the returned error is caught correctly)
                $method = $fallback_method; // set method (passed by reference) to fallback_method
                return self::ERROR_INVALID_REQUEST; // return error
                break;
        }

        // check if enough information is present (assuming e. g. $header_content[0]="Basic" $header_content[1]="MToy")
        if (count($header_content) < 2) {
            // return error indication the request was invalid
            return self::ERROR_INVALID_REQUEST;
        }

        // check what authentication method was required and wether the client used it correctly ($method is already checked for being in a valid range)
        switch ($desired_method) {
            case self::AUTHENTICATION_METHOD_ANY:
                // no checks need to be done
                break;

            case self::AUTHENTICATION_METHOD_BASIC:
                // if other auth method is desired by server return error
                if ($method != self::AUTHENTICATION_METHOD_BASIC) {
                    $method = $desired_method;
                    return self::ERROR_WRONG_AUTHENTICATION_METHOD;
                }
                break;

            case self::AUTHENTICATION_METHOD_BEARER:
                // if other auth method is desired by server return error
                if ($method != self::AUTHENTICATION_METHOD_BEARER) {
                    $method = $desired_method;
                    return self::ERROR_WRONG_AUTHENTICATION_METHOD;
                }
                break;

            default:
                // if none of the supported methods was selected throw error
                throw new OutOfRangeException("The selected authentication method isn't supported");
        }

        // return that everything went ok
        return 0;
    }

    /**
     * Decodes a basic authentication header
     * 
     * @param string $payload The payload to decode
     * 
     * @return array|bool Either an array containing ["username" => $username, "password" => $password] or false in case of error
     */
    protected static function decodeBasicAuthenticationHeader(string $payload = ""): array|bool
    {
        // if no payload is provided return false
        if ($payload == null)
            return false;

        // decode payload to string
        $payload_decoded = base64_decode($payload, true);

        // check if decode has been successful
        if ($payload_decoded === false)
            return false;

        // split provided authentication information in parts
        $payload_content = explode(":", $payload_decoded);

        // check if payload_content has the right size (meaning wether the correct amount of data has been provided)
        if (count($payload_content) != 2)
            return false;

        // write data in assoc array
        return [
            "username" => $payload_content[0],
            "password" => $payload_content[1]
        ];
    }

    /**
     * Decodes a bearer authentication header
     * 
     * @param string $payload The payload to decode
     * 
     * @return string|bool Either the bearer token or false in case of error
     */
    protected static function decodeBearerAuthenticationHeader(string $payload = ""): string|bool
    {
        // if no payload is provided return false
        if ($payload == null)
            return false;

        // no errors happened return payload as token
        return $payload;
    }

    /**
     * Decodes the provided bearer token and splits it into username and token
     * 
     * @param string $payload The bearer token to decode
     * 
     * @return ?array The contents of the token [username, token] or null in case something went wrong
     */
    protected static function decodeBearerToken(string $payload): ?array
    {
        // split provided bearer token into separate parts
        $token_contents = explode(":", $payload);

        // check if token contents made any sense (check if the token contained the right amount of information)
        if (count($token_contents) != 2)
            return null;

        // decode username from base 64
        $token_contents[0] = base64_decode($token_contents[0], true);

        // check if decode has been successful
        if ($token_contents[0] === false)
            return null;

        // return decoded content [username, token]
        return [$token_contents[0], $token_contents[1]];
    }
}
