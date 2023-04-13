<?php

namespace Tests;

use App\Http\Middleware\Authenticate;
use App\Models\user;
use App\Providers\AuthServiceProvider;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Faker\Factory as Faker;

class AuthenticationTest extends TestCase
{
    // empty the database
    use DatabaseMigrations;

    /**
     * Test wether authentication works properly or not
     *
     * @return void
     */
    public function test_user_authentication()
    {
        $faker = Faker::create();

        // seed two new users to the database
        $users = user::factory()->count(2)->create();

        // test wether users exist in the database
        $users_in_db = user::all();

        // set tho correct types
        // Note they are countable but for some reason php doesn't belief me
        $this->assertCount(count($users), $users_in_db);

        // now we try to change the password and check wether the user changed as well
        $passwords = [];
        /** @var user $user */
        foreach ($users_in_db as $user) {
            $pass = $faker->word();
            $user->setPassword($pass);
            $passwords[] = $pass;
        }


        // check if salts and passwords are generate correctly
        $users_in_db = user::all();

        $i = 0;
        foreach ($users_in_db as $user) {
            $this->assertNotNull($user->password_salt);
            $this->assertEquals($user->password_hash, hash("sha3-512", $passwords[$i] . ":" . $user->password_salt, true));
            $i++;
        }


        // check wether the users match or not
        // $this->assertNotEquals($users[0], $users_in_db[0]);


        // check if authentication by password works
        $users_in_db = user::all();

        $i = 0;
        foreach ($users_in_db as $user) {
            $this->assertTrue($user->validatePassword($passwords[$i]));
            $i++;
        }


        // check if failed authentication get's detected successfully
        $this->get('/user');
        $this->checkResponse(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC);

        $this->refreshApplication();


        // Authenticate with an non existing user
        $this->get('/user', $this->generateAuthHeaders(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC, new user(['name' => 'none']), '123'));
        $this->checkResponse(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC, true);

        $this->refreshApplication();


        // Authenticate with existing user but wrong password (use numeric values)
        $this->get('/user', $this->generateAuthHeaders(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC, $users_in_db[0], null));
        $this->checkResponse(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC, true);

        $this->refreshApplication();


        // test all users for authentication
        $users_in_db = user::all();

        $i = 0;
        foreach ($users_in_db as $user) {
            $this->get('/user', $this->generateAuthHeaders(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC, $user, $passwords[$i]));
            $this->response->assertContent(self::generateBearerToken(user::find($user->id))); // we need the fresh user from the database

            // start new (no cached results)
            $this->refreshApplication();
            $i++;
        }


        // authenticate with token even though password is required
        $this->get('/user', $this->generateAuthHeaders(AuthServiceProvider::AUTHENTICATION_METHOD_BEARER, $users_in_db[0], '123'));
        $this->checkResponse(AuthServiceProvider::AUTHENTICATION_METHOD_BASIC, true); // note basic ist required so the response should be checked for that

        $this->refreshApplication();

        // add test checking for binary token (generation and timestamp, use the results from the test beforehand)
        $i = 0;
        foreach ($users_in_db as $user) {
            // get the current token
            $current_token = $user->getBearerToken();

            // request a new token
            $new_token = $user->getBearerToken();

            // check if the match (they shouldn't)
            $this->assertFalse(!strcmp($current_token, $new_token)); // see definition of strcmp to understand the '!'
            $i++;
        }

        // TODO check if authentication with bearer tokens work


        // TODO Add test for authenticating with password even though bearer token is required


        // TODO test for bearer token expiring


        // TODO test if bearer tokens change every time the user retrieves a new one


    }

    /**
     * A simple tool to generate authentication headers
     * 
     * @param int $method The expected/desired method
     * @param ?user $user The users that tried to authenticate. If null an unsuccessful authentication is expected
     * @param ?string $password The correct password (only required for basic authentication)
     */
    protected static function generateAuthHeaders(int $method, ?user $user, ?string $password): array
    {
        switch ($method) {
            case AuthServiceProvider::AUTHENTICATION_METHOD_BASIC:
                return ["Authorization" => "Basic " . base64_encode($user->name . ":" . $password)];

            case AuthServiceProvider::AUTHENTICATION_METHOD_BEARER:
                return ["Authorization" => "Bearer " . self::generateBearerToken($user)];
        }
        return [];
    }
    /**
     * Generates an bearer Token for the user
     * 
     * @param user $user The user to generate the Token for
     */
    protected static function generateBearerToken(user $user): string
    {
        return base64_encode($user->name) . ":" . base64_encode($user->binary_token);
    }

    /**
     * A simple tool to test the returned headers and content
     * 
     * @param int $method The expected/desired method
     * @param bool $failure Adds check for correct error message
     */
    protected function checkResponse(int $method, bool $failure = true): void
    {
        $this->response->assertUnauthorized();
        // authentication headers must always be present
        switch ($method) {
            case AuthServiceProvider::AUTHENTICATION_METHOD_BASIC:
                $this->response->assertHeader("www-authenticate", "Basic realm=\"app\", charset=\"UTF-8\"");
                break;

            case AuthServiceProvider::AUTHENTICATION_METHOD_BEARER:
                $this->response->assertHeader("www-authenticate", "Bearer realm=\"app\"");
                break;
        }

        // expect failure
        if ($failure) {
            $this->response->assertExactJson(["Unauthorized"]);
        }
    }
}
