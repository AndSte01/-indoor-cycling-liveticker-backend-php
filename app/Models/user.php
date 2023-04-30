<?php

namespace App\Models;

use App\Helpers\ServerTimeHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class user extends Model
{
    // use all the little helpers
    use HasFactory;

    // no need to talk about primary id since it is exactly what eloquent assumes

    /**
     * @var array The model's default values for attributes.
     */
    protected $attributes = [];

    // no default timestamps
    public $timestamps = false;

    /**
     * @var array The attributes that are protected from  mass assigning.
     */
    protected $guarded = ['id', 'password_hash', 'password_salt', 'binary_timestamp', 'binary_token'];

    /**
     * @var array The attributes that are hidden from api output
     */
    protected $hidden = ['id', 'password_hash', 'password_salt', 'binary_timestamp', 'binary_token'];

    /**
     * @var array The attributes that should be cast.
     */
    protected $casts = [
        'binary_timestamp' => 'datetime',
    ];


    // --- Authentication related stuff ---

    /** @var int Time after which an binary token expires */
    const TOKEN_EXPIRATION_TIME = 86400; // 24 hours

    /** @var int Authenticate the user with his password */
    const AUTHENTICATE_WITH_PASSWORD = 0;
    /** @var int Authenticate the user with a binary token */
    const AUTHENTICATE_WITH_TOKEN = 1;

    /** @var int The length of binary values in bytes (restricted by database) */
    private const BINARY_LENGTH = 64;

    /** @var string The hash algorithm used to hash password with salt */
    private const HASH_ALGORITHM = "sha3-512"; // please note the binary length of hash filed (must be 64 byte long, in migrations)

    /**
     * Searches for a user in the database by it's name
     * 
     * @param string $username The name of the user that one wants to find
     * 
     * @return ?self The user if one was found
     */
    public static function getUserByName(string $username): ?self
    {
        return user::where("name", $username)->first();
    }

    /**
     * Checks wether a password matches the user in the database or not
     * 
     * @param string The password given that should be checked against the one in the database
     * 
     * @return bool Wether the password matches the on in the database or not
     */
    public function validatePassword(string $password): bool
    {
        // generate password_hash with salt to later compare it to the one stored in the database
        $provided_password_hash = self::generatePasswordHash($password, $this->password_salt);

        // check if password hashes match if not return error
        if (strcmp($provided_password_hash, $this->password_hash) !== 0)
            return false;

        // password is correct return true
        return true;
    }

    /**
     * Changes the user password in the database
     * 
     * @param string The new user password
     */
    public function setPassword(string $password)
    {
        // create new random salt
        $this->password_salt = random_bytes(self::BINARY_LENGTH);

        // generate new password hash
        $this->password_hash = self::generatePasswordHash($password, $this->password_salt);

        // safe updated version in database
        $this->save();
    }

    /**
     * Generates a password hash for the provided password with the provided salt (random bytes)
     * 
     * @param string $password The password to hash
     * @param string $salt The salt used to hash the password
     * 
     * @return string Binary representation of the generated hash
     */
    private static function generatePasswordHash(string $password, string $salt): string
    {
        // hash is generated from string with layout $password:$salt
        return hash(self::HASH_ALGORITHM, $password . ":" . $salt, true);
    }

    /**
     * Checks wether the binary token is valid for the provided user
     * 
     * @return bool Wether the check was successful or not
     */
    public function validateBinaryToken(string $token): bool
    {
        // decode token to binary
        $binary_token = base64_decode($token, true);

        // check if decode has been successful
        if ($binary_token === false)
            return false;

        // check if token match
        if ($binary_token != $this->binary_token)
            return false;

        // check time between now (database server) and time of generation
        $delta_t = ServerTimeHelper::getServerTime()->timestamp - $this->binary_timestamp->timestamp;

        // if binary_tokens are generated in the future (that mustn't happen) something is very wrong so return false
        if ($delta_t > self::TOKEN_EXPIRATION_TIME || $delta_t < 0)
            return false;

        // all test were passed return true
        return true;
    }

    /**
     * Generates (and therefore gets) a new binary token for the provided user, if a token already exist it is overwritten
     * 
     * @param string The newly generated binary token
     */
    private function getBinaryToken(): string
    {
        // generate new binary token
        $this->binary_token = random_bytes(self::BINARY_LENGTH);
        $this->binary_timestamp = ServerTimeHelper::getServerTime();

        // save changes in the database
        $this->save();

        // return the generated token
        return $this->binary_token;
    }

    /**
     * Get's the binary token of the provided user. ALL ALREADY EXISTING TOKENS BECOME INVALID!
     * 
     * @return string The bearer token of the user
     */
    public function getBearerToken(): string
    {
        // get a new binary token that later is used for the bearer token
        // DON'T USE THE TOKEN STORED IN THE USER, some additional checks are performed by the below used function
        $binary_token = $this->getBinaryToken();

        // generate bearer token to return
        return self::generateBearerToken($this->name, $binary_token);
    }

    /**
     * Generates a bearer token
     */
    private static function generateBearerToken(string $username, string $binary_token): string
    {
        return base64_encode($username) . ":" . base64_encode($binary_token);
    }

    /**
     * Get's the current time of the server
     */
    private static function getServerTime()
    {
        return strtotime(DB::select('SELECT NOW() AS now')[0]->now);
    }
}
