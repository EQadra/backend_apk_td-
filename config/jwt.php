<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | Don't forget to set this in your .env file, as it will be used to sign
    | your tokens. A helper command is provided for this:
    | `php artisan jwt:secret`
    |
    | Note: This will be used for Symmetric algorithms only (HMAC),
    | since RSA and ECDSA use a private/public key combo (See below).
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    |
    | The algorithm you are using, will determine whether your tokens are
    | signed with a random string (defined in `JWT_SECRET`) or using the
    | following public & private keys.
    |
    | Symmetric Algorithms:
    | HS256, HS384 & HS512 will use `JWT_SECRET`.
    |
    | Asymmetric Algorithms:
    | RS256, RS384 & RS512 / ES256, ES384 & ES512 will use the keys below.
    |
    */

    'keys' => [
        /*
        |--------------------------------------------------------------------------
        | Public Key
        |--------------------------------------------------------------------------
        |
        | A path or resource to your public key.
        |
        | E.g. 'file://path/to/public/key'
        |
        */

        'public' => env('JWT_PUBLIC_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Private Key
        |--------------------------------------------------------------------------
        |
        | A path or resource to your private key.
        |
        | E.g. 'file://path/to/private/key'
        |
        */

        'private' => env('JWT_PRIVATE_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Passphrase
        |--------------------------------------------------------------------------
        |
        | The passphrase for your private key. Can be null if none set.
        |
        */

        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for.
    | Defaults to 1 hour (60 minutes).
    |
    | You can also set this to null, to yield a never expiring token.
    | Some people may want this behaviour for e.g. a mobile app.
    | This is not particularly recommended, so make sure you have appropriate
    | systems in place to revoke the token if necessary.
    | Notice: If you set this to null you should remove 'exp' element from 'required_claims' list.
    |
    */

    'ttl' => env('JWT_TTL', 1440), // ✅ 24 HORAS POR DEFECTO

    /*
    |--------------------------------------------------------------------------
    | Refresh time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token can be refreshed
    | within. I.E. The user can refresh their token within a 2 week window of
    | the original token being created until they must re-authenticate.
    | Defaults to 2 weeks (20160 minutes).
    |
    | You can also set this to null, to yield an infinite refresh time.
    | Some may want this behaviour for e.g. a mobile app.
    | This is not particularly recommended, so make sure you have appropriate
    | systems in place to revoke the token if necessary.
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 43200), // ✅ 30 DÍAS PARA REFRESCAR

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Specify the required claims that must exist in any token.
    | A TokenInvalidException will be thrown if any of these claims are not
    | present in the payload.
    |
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claim keys to be persisted when refreshing a token.
    | `sub` and `iat` will automatically be persisted, in
    | addition to the these claims.
    |
    | Note: If a claim does not exist then it will be ignored.
    |
    */

    'persistent_claims' => [
        // 'foo',
        // 'bar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | This will determine whether a `prv` claim is automatically added to
    | the token. The purpose of this is to ensure that if you have multiple
    | authentication models e.g. `App\Models\User` and `App\OtherPerson`,
    | then we should prevent one authentication request from impersonating
    | another unnecessarily if the JWT payload is not completely trusted.
    |
    | https://github.com/tymondesigns/jwt-auth/wiki/Locking-Subject
    |
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This property gives the jwt timestamp claims some "leeway".
    | Meaning that if you have any unavoidable shifts between systems on
    | different networks, it will allow these small shifts to not throw
    | the token invalid.
    |
    */

    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | In order to invalidate tokens, you must have the blacklist enabled.
    | If you do not want or need this functionality, then set this value to false.
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    | -------------------------------------------------------------------------
    | Blacklist Grace Period
    | -------------------------------------------------------------------------
    |
    | When multiple concurrent requests are made with the same JWT,
    | it is possible that some of them fail, due to token regeneration
    | on every request.
    |
    | Set grace period in seconds to prevent parallel request failure.
    |
    */

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Cookies encryption
    |--------------------------------------------------------------------------
    |
    | By default Laravel encrypts cookies for security, however if you decide
    | to not decrypt cookies, you will need to modify the way cookies are
    | sent and received. Set this value to true if you want to decrypt
    | cookies. Note: Laravel cookie encryption is enabled by default,
    | and encrypts all cookies sent over the wire. If you set this to true,
    | Laravel will NOT encrypt the cookie, but remember that it is not
    | recommended to send JWT tokens over unencrypted cookies.
    |
    */

    'decrypt_cookies' => false,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Specify the various providers used throughout the package.
    |
    */

    'providers' => [
        /*
        |--------------------------------------------------------------------------
        | JWT Provider
        |--------------------------------------------------------------------------
        |
        | Specify the provider that is used to create and decode the tokens.
        |
        */

        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,

        /*
        |--------------------------------------------------------------------------
        | Authentication Provider
        |--------------------------------------------------------------------------
        |
        | Specify the provider that is used to authenticate users.
        |
        */

        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,

        /*
        |--------------------------------------------------------------------------
        | Storage Provider
        |--------------------------------------------------------------------------
        |
        | Specify the provider that is used to store tokens in the blacklist.
        |
        */

        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];