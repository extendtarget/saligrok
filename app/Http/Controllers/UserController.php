<?php

namespace App\Http\Controllers;

use Hash;
use JWTAuth;
use App\User;
use App\Order;
use Throwable;
use App\Rating;
use App\SmsOtp;
use App\Address;
use App\PushToken;
use App\PushNotify;
use App\Restaurant;
use JWTAuthException;
use App\AcceptDelivery;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * @param $email
     * @param $password
     * @return mixed
     */
    private function getToken($email, $password)
    {
        $token = null;
        try {
            if (!$token = JWTAuth::attempt(['email' => $email, 'password' => $password])) {
                return response()->json([
                    'response' => 'error',
                    'message' => 'Password or email is invalid..',
                    'token' => $token,
                ]);
            }
        } catch (JWTAuthException $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Token creation failed',
            ]);
        }
        return $token;
    }

    /**
     * @param Request $request
     */
    public function login(Request $request)
    {

        $user = User::where('email', $request->email)->get()->first();

        //check if it is coming from social login,
        if ($request->accessToken != null) {

            //check socialtoken validation
            $validation = $this->validateAccessToken($request->email, $request->provider, $request->accessToken);
            if ($validation) {
                if ($user) {
                    //user exists -> check if user has phone
                    if ($user->phone != null) {
                        // user has phone
                        //LOGIN USER
                        $token = JWTAuth::fromUser($user);
                        $user->auth_token = $token;

                        // Add address if address present
                        if (isset($request->address['lat'])) {
                            $address = new Address();
                            $address->user_id = $user->id;
                            $address->latitude = $request->address['lat'];
                            $address->longitude = $request->address['lng'];
                            $address->address = $request->address['address'];
                            $address->house = $request->address['house'];
                            $address->tag = $request->address['tag'];
                            $address->save();
                            $user->default_address_id = $address->id;
                        }

                        if (is_null($user->referral_code)) {
                            $user->referral_code = Str::random(6);
                        }

                        if (!empty($request->referral_code)) {
                            $referredByUser = User::where('referral_code', $request->referral_code)->first();
                            if ($referredByUser) {
                                $user->referred_by = $referredByUser->id;
                            }
                        }

                        $user->save();
                        if ($user->default_address_id !== 0) {
                            $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                        } else {
                            $default_address = null;
                        }

                        $running_order = null;

                        $response = [
                            'success' => true,
                            'data' => [
                                'id' => $user->id,
                                'auth_token' => $token,
                                'name' => $user->name,
                                'email' => $user->email,
                                'phone' => $user->phone,
                                'default_address_id' => $user->default_address_id,
                                'default_address' => $default_address,
                                'wallet_balance' => $user->balanceFloat,
                                'avatar' => $user->avatar,
                                'tax_number' => $user->tax_number,
                                'referral_code' => $user->referral_code,
                            ],
                            'running_order' => $running_order,
                        ];
                        return response()->json($response);
                    }
                    if ($request->phone != null) {
                        $checkPhone = User::where('phone', $request->phone)->first();
                        if ($checkPhone) {
                            $response = [
                                'email_phone_already_used' => true,
                            ];
                            return response()->json($response);
                        } else {
                            try {
                                $user->phone = $request->phone;
                                $user->save();
                                $token = JWTAuth::fromUser($user);
                                $user->auth_token = $token;

                                // Add address if address present
                                if (isset($request->address['lat'])) {
                                    $address = new Address();
                                    $address->user_id = $user->id;
                                    $address->latitude = $request->address['lat'];
                                    $address->longitude = $request->address['lng'];
                                    $address->address = $request->address['address'];
                                    $address->house = $request->address['house'];
                                    $address->tag = $request->address['tag'];
                                    $address->save();
                                    $user->default_address_id = $address->id;
                                }

                                if (is_null($user->referral_code)) {
                                    $user->referral_code = Str::random(6);
                                }

                                if (!empty($request->referral_code)) {
                                    $referredByUser = User::where('referral_code', $request->referral_code)->first();
                                    if ($referredByUser) {
                                        $user->referred_by = $referredByUser->id;
                                    }
                                }

                                $user->save();
                            } catch (Throwable $e) {
                                $response = ['success' => false, 'data' => 'Something went wrong. Please try again...'];
                                return response()->json($response, 201);
                            }

                            if ($user->default_address_id !== 0) {
                                $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                            } else {
                                $default_address = null;
                            }

                            $running_order = null;

                            $response = [
                                'success' => true,
                                'data' => [
                                    'id' => $user->id,
                                    'auth_token' => $token,
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'phone' => $user->phone,
                                    'default_address_id' => $user->default_address_id,
                                    'default_address' => $default_address,
                                    'wallet_balance' => $user->balanceFloat,
                                    'avatar' => $user->avatar,
                                    'tax_number' => $user->tax_number,
                                    'referral_code' => $user->referral_code,
                                ],
                                'running_order' => $running_order,
                            ];

                            return response()->json($response);
                        }
                    } else {
                        $response = [
                            'enter_phone_after_social_login' => true,
                        ];
                        return response()->json($response);
                    }
                } else {
                    // there is no user with this email..

                    if ($request->phone != null) {
                        $checkPhone = User::where('phone', $request->phone)->first();
                        if ($checkPhone) {
                            $response = [
                                'email_phone_already_used' => true,
                            ];
                            return response()->json($response);
                        } else {
                            enSovCheck($request);

                            //reg user
                            $user = new User();
                            $user->name = $request->name;
                            $user->email = $request->email;
                            $user->phone = $request->phone;
                            $user->password = Hash::make(Str::random(8));
                            $user->user_ip = $request->ip();
                            $user->referral_code = Str::random(6);

                            try {
                                $user->save();
                                $user->assignRole('Customer');
                                $token = JWTAuth::fromUser($user);
                                $user->auth_token = $token;

                                // Add address if address present
                                if (isset($request->address['lat'])) {
                                    $address = new Address();
                                    $address->user_id = $user->id;
                                    $address->latitude = $request->address['lat'];
                                    $address->longitude = $request->address['lng'];
                                    $address->address = $request->address['address'];
                                    $address->house = $request->address['house'];
                                    $address->tag = $request->address['tag'];
                                    $address->save();
                                    $user->default_address_id = $address->id;
                                }

                                if (is_null($user->referral_code)) {
                                    $user->referral_code = Str::random(6);
                                }

                                if (!empty($request->referral_code)) {
                                    $referredByUser = User::where('referral_code', $request->referral_code)->first();
                                    if ($referredByUser) {
                                        $user->referred_by = $referredByUser->id;
                                    }
                                }

                                $user->save();
                            } catch (Throwable $e) {
                                $response = ['success' => false, 'data' => 'Something went wrong. Please try again...'];
                                return response()->json($response, 201);
                            }

                            if ($user->default_address_id !== 0) {
                                $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                            } else {
                                $default_address = null;
                            }

                            $running_order = null;

                            $response = [
                                'success' => true,
                                'data' => [
                                    'id' => $user->id,
                                    'auth_token' => $token,
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'phone' => $user->phone,
                                    'default_address_id' => $user->default_address_id,
                                    'default_address' => $default_address,
                                    'wallet_balance' => $user->balanceFloat,
                                    'avatar' => $user->avatar,
                                    'tax_number' => $user->tax_number,
                                    'referral_code' => $user->referral_code,
                                ],
                                'running_order' => $running_order,
                            ];
                            return response()->json($response);
                        }
                    } else {
                        // SHOW ENTER PHONE NUMBER
                        $response = [
                            'enter_phone_after_social_login' => true,
                        ];
                        return response()->json($response);
                    }
                }
            } else {
                $response = false;
                return response()->json($response);
            }
        }

        // if user exists, check user

        if ($request->password != null) {
            if ($user && Hash::check($request->password, $user->password)) // The passwords match...
            {
                $token = self::getToken($request->email, $request->password);
                $user->auth_token = $token;

                // Add address if address present
                if (isset($request->address['lat'])) {
                    $address = new Address();
                    $address->user_id = $user->id;
                    $address->latitude = $request->address['lat'];
                    $address->longitude = $request->address['lng'];
                    $address->address = $request->address['address'];
                    $address->house = $request->address['house'];
                    $address->tag = $request->address['tag'];
                    $address->save();
                    $user->default_address_id = $address->id;
                }

                if (is_null($user->referral_code)) {
                    $user->referral_code = Str::random(6);
                }

                if (!empty($request->referral_code)) {
                    $referredByUser = User::where('referral_code', $request->referral_code)->first();
                    if ($referredByUser) {
                        $user->referred_by = $referredByUser->id;
                    }
                }

                $user->save();
                if ($user->default_address_id !== 0) {
                    $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                } else {
                    $default_address = null;
                }

                $running_order = null;

                $response = [
                    'success' => true,
                    'data' => [
                        'id' => $user->id,
                        'auth_token' => $token,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'default_address_id' => $user->default_address_id,
                        'default_address' => $default_address,
                        'wallet_balance' => $user->balanceFloat,
                        'avatar' => $user->avatar,
                        'tax_number' => $user->tax_number,
                        'referral_code' => $user->referral_code,
                    ],
                    'running_order' => $running_order,
                ];
                return response()->json($response, 201);
            } else {
                $response = ['success' => false, 'data' => 'DONOTMATCH'];
                return response()->json($response, 201);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function loginWithOtp(Request $request)
    {
        $otpTable = SmsOtp::where('phone', $request->phone)->first();
        if ($otpTable && $request->otp == $otpTable->otp) {

            //check if user exists...
            $user = User::where('phone', $request->phone)->first();

            if ($user) {
                //user exists, save the address and login user
                if (isset($request->address['lat'])) {
                    $address = new Address();
                    $address->user_id = $user->id;
                    $address->latitude = $request->address['lat'];
                    $address->longitude = $request->address['lng'];
                    $address->address = $request->address['address'];
                    $address->house = $request->address['house'];
                    $address->tag = $request->address['tag'];
                    $address->save();
                    $user->default_address_id = $address->id;
                }
                $token = JWTAuth::fromUser($user);
                $user->auth_token = $token;

                if (is_null($user->referral_code)) {
                    $user->referral_code = Str::random(6);
                }

                if (!empty($request->referral_code)) {
                    $referredByUser = User::where('referral_code', $request->referral_code)->first();
                    if ($referredByUser) {
                        $user->referred_by = $referredByUser->id;
                    }
                }

                $user->save();

                if ($user->default_address_id !== 0) {
                    $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                } else {
                    $default_address = null;
                }
                $running_order = null;

                $response = [
                    'success' => true,
                    'data' => [
                        'id' => $user->id,
                        'auth_token' => $token,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'default_address_id' => $user->default_address_id,
                        'default_address' => $default_address,
                        'wallet_balance' => $user->balanceFloat,
                        'avatar' => $user->avatar,
                        'tax_number' => $user->tax_number,
                        'referral_code' => $user->referral_code,
                    ],
                    'running_order' => $running_order,
                ];
                return response()->json($response);
            } else {

                //new user...

                $randomPassword = Str::random(8);
                $payload = [
                    'password' => Hash::make($randomPassword),
                    'email' => $request->email,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'auth_token' => '',
                    'user_ip' => $request->ip(),
                    'referral_code' => Str::random(6),
                ];

                // try {

                $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                    'phone' => ['required'],
                ]);

                $user = new User($payload);
                if ($user->save()) {

                    $token = self::getToken($request->email, $randomPassword); // generate user token

                    if (!is_string($token)) {
                        return response()->json(['success' => false, 'data' => 'Token generation failed'], 201);
                    }

                    $user = User::where('email', $request->email)->get()->first();

                    $user->auth_token = $token; // update user token

                    // Add address if address present
                    if (isset($request->address['lat'])) {
                        $address = new Address();
                        $address->user_id = $user->id;
                        $address->latitude = $request->address['lat'];
                        $address->longitude = $request->address['lng'];
                        $address->address = $request->address['address'];
                        $address->house = $request->address['house'];
                        $address->tag = $request->address['tag'];
                        $address->save();
                        $user->default_address_id = $address->id;
                    }

                    if (is_null($user->referral_code)) {
                        $user->referral_code = Str::random(6);
                    }

                    if (!empty($request->referral_code)) {
                        $referredByUser = User::where('referral_code', $request->referral_code)->first();
                        if ($referredByUser) {
                            $user->referred_by = $referredByUser->id;
                        }
                    }

                    $user->save();
                    $user->assignRole('Customer');

                    if ($user->default_address_id !== 0) {
                        $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                    } else {
                        $default_address = null;
                    }

                    $response = [
                        'success' => true,
                        'data' => [
                            'id' => $user->id,
                            'auth_token' => $token,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'default_address_id' => $user->default_address_id,
                            'default_address' => $default_address,
                            'wallet_balance' => $user->balanceFloat,
                            'avatar' => $user->avatar,
                            'tax_number' => $user->tax_number,
                            'referral_code' => $user->referral_code,
                        ],
                        'running_order' => null,
                    ];

                    if (config('setting.enableReferAndEarn') == "true") {
                        if ((config('setting.referralBonusType') == "registration")) {

                            if (!empty($request->referral_code)) {
                                $referredByUser = User::where('id', $user->referred_by)->with('wallet')->first(); // Use 'find' instead of 'where' for single user retrieval
                                if ($referredByUser) {

                                    $referralBonusReferringUser = config('setting.referralSuccessAmountReferringUser') * 100;
                                    $referredByUser->deposit($referralBonusReferringUser, ['description' => "Referral Bonus Deposited"]);

                                    $alert = new PushNotify();
                                    $alert->sendWalletAlert($referredByUser->id, $referralBonusReferringUser, "Referral Bonus Deposited", 'deposit');
                                }

                                $referralBonusReferredUser = config('setting.referralSuccessAmountReferredUser') * 100;
                                $user->deposit($referralBonusReferredUser, ['description' => "Referral Bonus Deposited"]);

                                $alert = new PushNotify();
                                $alert->sendWalletAlert($user->id, $referralBonusReferredUser, "Referral Bonus Deposited", 'deposit');
                            }
                        }
                    }

                    return response()->json($response);
                } else {
                    $response = ['success' => false, 'data' => 'Couldnt register user'];
                }
                // } catch (Throwable $e) {
                //     $response = ['success' => false, 'data' => 'Couldnt register user.'];
                //     return response()->json($response, 201);
                // }

            }
        } else {
            //otp not present... error
            $response = ['success' => false, 'data' => 'DONOTMATCH'];
            return response()->json($response, 201);
        }
    }

    /**
     * @param Request $request
     */
    public function register(Request $request)
    {
        enSovCheck($request);

        $checkEmail = User::where('email', $request->email)->first();
        $checkPhone = User::where('phone', $request->phone)->first();

        if ($checkPhone || $checkEmail) {
            $response = [
                'email_phone_already_used' => true,
            ];
            return response()->json($response);
        }

        $payload = [
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
            'auth_token' => '',
            'user_ip' => $request->ip(),
            'referral_code' => Str::random(6),
        ];

        try {

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'phone' => ['required'],
            ]);

            $user = new User($payload);
            if ($user->save()) {

                $token = self::getToken($request->email, $request->password); // generate user token

                if (!is_string($token)) {
                    return response()->json(['success' => false, 'data' => 'Token generation failed'], 201);
                }

                $user = User::where('email', $request->email)->get()->first();

                $user->auth_token = $token; // update user token

                // Add address if address present
                if (isset($request->address['lat'])) {
                    $address = new Address();
                    $address->user_id = $user->id;
                    $address->latitude = $request->address['lat'];
                    $address->longitude = $request->address['lng'];
                    $address->address = $request->address['address'];
                    $address->house = $request->address['house'];
                    $address->tag = $request->address['tag'];
                    $address->save();
                    $user->default_address_id = $address->id;
                }

                if (is_null($user->referral_code)) {
                    $user->referral_code = Str::random(6);
                }

                if (!empty($request->referral_code)) {
                    $referredByUser = User::where('referral_code', $request->referral_code)->first();
                    if ($referredByUser) {
                        $user->referred_by = $referredByUser->id;
                    }
                }

                $user->save();
                $user->assignRole('Customer');

                if ($user->default_address_id !== 0) {
                    $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
                } else {
                    $default_address = null;
                }

                $response = [
                    'success' => true,
                    'data' => [
                        'id' => $user->id,
                        'auth_token' => $token,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'default_address_id' => $user->default_address_id,
                        'default_address' => $default_address,
                        'wallet_balance' => $user->balanceFloat,
                        'avatar' => $user->avatar,
                        'tax_number' => $user->tax_number,
                        'referral_code' => $user->referral_code,
                    ],
                    'running_order' => null,
                ];

                if (config('setting.enableReferAndEarn') == "true") {
                    if ((config('setting.referralBonusType') == "registration")) {

                        if (!empty($request->referral_code)) {
                            $referredByUser = User::where('id', $user->referred_by)->with('wallet')->first(); // Use 'find' instead of 'where' for single user retrieval
                            if ($referredByUser) {

                                $referralBonusReferringUser = config('setting.referralSuccessAmountReferringUser') * 100;
                                $referredByUser->deposit($referralBonusReferringUser, ['description' => "Referral Bonus Deposited"]);

                                $alert = new PushNotify();
                                $alert->sendWalletAlert($referredByUser->id, $referralBonusReferringUser, "Referral Bonus Deposited", 'deposit');
                            }

                            $referralBonusReferredUser = config('setting.referralSuccessAmountReferredUser') * 100;
                            $user->deposit($referralBonusReferredUser, ['description' => "Referral Bonus Deposited"]);

                            $alert = new PushNotify();
                            $alert->sendWalletAlert($user->id, $referralBonusReferredUser, "Referral Bonus Deposited", 'deposit');
                        }
                    }
                }
            } else {
                $response = ['success' => false, 'data' => 'Couldnt register user'];
            }
        } catch (Throwable $e) {
            $response = ['success' => false, 'data' => 'Couldnt register user.'];
            return response()->json($response, 201);
        }

        return response()->json($response, 201);
    }
    // Extend By Aya ========================================
     /**
     * Log out the authenticated user and invalidate the token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Invalidate the current token
            JWTAuth::invalidate(JWTAuth::getToken());
            $user->auth_token = null;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Successfully logged out']);
        } catch (JWTAuthException $e) {
            return response()->json(['success' => false, 'message' => 'Failed to logout, please try again'], 500);
        }
    }

    /**
     * Refresh the JWT token for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Refresh the token
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $user->auth_token = $newToken;
            $user->save();

            $default_address = $user->default_address_id !== 0
                ? Address::where('id', $user->default_address_id)->select(['address', 'house', 'latitude', 'longitude', 'tag'])->first()
                : null;

            $response = [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'auth_token' => $newToken,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'default_address_id' => $user->default_address_id,
                    'default_address' => $default_address,
                    'wallet_balance' => $user->balanceFloat,
                    'avatar' => $user->avatar,
                    'tax_number' => $user->tax_number,
                    'referral_code' => $user->referral_code,
                ],
            ];

            return response()->json($response);
        } catch (JWTAuthException $e) {
            return response()->json(['success' => false, 'message' => 'Failed to refresh token'], 500);
        }
    }
    // ===============================================================================

    /**
     * @param Request $request
     */
    public function updateUserInfo(Request $request)
    {
        $user = auth()->user();

        if ($user) {

            if ($user->default_address_id !== 0) {
                $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
            } else {
                $default_address = null;
            }

            $running_order = Order::where('user_id', $user->id)
                ->whereIn('orderstatus_id', ['1', '2', '3', '4', '7', '8'])
                ->where('unique_order_id', $request->unique_order_id)
                ->with('restaurant')
                ->first();

            $delivery_details = null;
            if ($running_order) {
                if ($running_order->orderstatus_id == 3 || $running_order->orderstatus_id == 4) {
                    //get assigned delivery guy and get the details to show to customers
                    $delivery_guy = AcceptDelivery::where('order_id', $running_order->id)->first();
                    if ($delivery_guy) {
                        $delivery_user = User::where('id', $delivery_guy->user_id)->first();
                        $delivery_details = $delivery_user->delivery_guy_detail;
                        if (!empty($delivery_details)) {
                            $delivery_details = $delivery_details->toArray();
                            $delivery_details['phone'] = $delivery_user->phone;
                        }

                        $ratings = Rating::where('delivery_id', $delivery_user->id)->select(['rating_delivery', 'review_delivery'])->get();
                        $averageRating = number_format((float) $ratings->avg('rating_delivery'), 1, '.', '');
                        $delivery_details['rating'] = $averageRating;
                    }
                }
            }

            $response = [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'auth_token' => $user->auth_token,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'default_address_id' => $user->default_address_id,
                    'default_address' => $default_address,
                    'wallet_balance' => $user->balanceFloat,
                    'avatar' => $user->avatar,
                    'tax_number' => $user->tax_number,
                    'referral_code' => $user->referral_code,
                ],
                'running_order' => $running_order,
                'delivery_details' => $delivery_details,
            ];

            return response()->json($response);
        }
    }

    /**
     * @param Request $request
     */
    public function checkRunningOrder(Request $request)
    {
        $user = auth()->user();

        if ($user) {
            $running_order = Order::where('user_id', $user->id)
                ->whereIn('orderstatus_id', ['1', '2', '3', '4', '7'])
                ->get();

            if (count($running_order) > 0) {
                $success = true;
                return response()->json($success);
            } else {
                $success = false;
                return response()->json($success);
            }
        }
    }

    /**
     * @param $email
     * @param $provider
     * @param $accessToken
     */
    public function validateAccessToken($email, $provider, $accessToken)
    {
        if ($provider == 'facebook') {
            // validate facebook access token
            $curl = Curl::to('https://graph.facebook.com/app/?access_token=' . $accessToken)->get();
            $curl = json_decode($curl);

            if (isset($curl->id)) {
                if ($curl->id == config('setting.facebookAppId')) {
                    return true;
                }
                return false;
            }
            return false;
        }
        if ($provider == 'google') {
            // validate google access token
            $curl = Curl::to('https://www.googleapis.com/oauth2/v3/tokeninfo?access_token=' . $accessToken)->get();
            $curl = json_decode($curl);

            if (isset($curl->email)) {
                if ($curl->email == $email) {
                    return true;
                }
                return false;
            }
            return false;
        }
    }

    /**
     * @param Request $request
     */
    public function getWalletTransactions(Request $request)
    {
        $user = auth()->user();
        // $user = auth()->user();
        if ($user) {
            // $balance = sprintf('%.2f', $user->balanceFloat);
            $balance = $user->balanceFloat;
            $transactions = $user->transactions()->orderBy('id', 'DESC')->get();

            $response = [
                'success' => true,
                'balance' => $balance,
                'transactions' => $transactions,
            ];
            return response()->json($response);
        } else {
            $response = [
                'success' => false,
            ];
            return response()->json($response);
        }
    }

    /**
     * @param Request $request
     */
    public function changeAvatar(Request $request)
    {
        $user = auth()->user();
        $user->avatar = $request->avatar;
        $user->save();
        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     */
    public function checkBan(Request $request)
    {
        $user = auth()->user();
        if ($user->is_active) {
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    /**
     * @param $id
     */
    public function toggleFavorite(Request $request)
    {
        $user = auth()->user();
        $restaurant = Restaurant::find($request->id);
        $restaurant->toggleFavorite();
        $restaurant->makeHidden(['delivery_areas']);
        $restaurant->is_favorited = $restaurant->isFavorited();
        $restaurant->avgRating = storeAvgRating($restaurant->ratings);
        return response()->json($restaurant);
    }

    /**
     * @param Request $request
     */
    public function updateTaxNumber(Request $request)
    {
        $user = auth()->user();
        $user->tax_number = $request->tax_number;
        $user->save();

        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     */
    // public function deleteUserProfile(Request $request)
    // {
    //     try {
    //         $user = User::where('id', $request->user_id)->where('auth_token', $request->token)->first();

    //         if ($user) {

    //             $running_order = Order::where('user_id', $user->id)
    //                 ->whereIn('orderstatus_id', ['1', '2', '3', '4', '7'])
    //                 ->count();

    //             if ($running_order > 0) {
    //                 return response()->json(['success' => false]);
    //             } else {
    //                 if ($user->id != 1) {
    //                     DB::beginTransaction();
    //                     $user->name = 'Deleted Account';
    //                     $user->email = Str::random(6) . '@mail.com';
    //                     $user->phone = rand(1111111111, 9999999999);
    //                     $user->password = Hash::make(Str::random(6));
    //                     $user->save();

    //                     $getAllTokens = PushToken::where('user_id', $user->id)->get();

    //                     if (count($getAllTokens) > 0) {
    //                         foreach ($getAllTokens as $userToken) {
    //                             $userToken->delete();
    //                         }
    //                     }
    //                     DB::commit();
    //                     return response()->json(['success' => true]);
    //                 }
    //             }
    //         } else {
    //             return response()->json(['success' => false, 'message' => "User not found!"]);
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['success' => false, 'message' => $e->getMessage()]);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         return response()->json(['success' => false, 'message' => $th->getMessage()]);
    //     }
    // }

public function deleteUserProfile(Request $request)
{
    try {
        $user = User::where('id', $request->user_id)->where('auth_token', $request->token)->first();

        if (!$user) {
            \Log::channel('view_order_issue')->warning('User not found for deletion', [
                'user_id' => $request->user_id,
                'token' => $request->token
            ]);
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود']);
        }

        $activeOrders = Order::where('user_id', $user->id)
            ->whereNotIn('orderstatus_id', [5, 6]) 
            ->count();

        if ($activeOrders > 0) {
            \Log::channel('view_order_issue')->warning('Cannot delete user due to active orders', [
                'user_id' => $user->id,
                'active_orders_count' => $activeOrders
            ]);
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستخدم لوجود ' . $activeOrders . ' طلبات قيد العمل'
            ]);
        }

        // التحقق من أن المستخدم ليس المسؤول (ID != 1)
        if ($user->id == 1) {
            \Log::channel('view_order_issue')->warning('Attempt to delete admin user', ['user_id' => $user->id]);
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف حساب المسؤول']);
        }

        // تعطيل حساب المستخدم بدلاً من الحذف
        DB::beginTransaction();
        $user->name = 'Deleted Account';
        $user->email = Str::random(6) . '@deleted.com';
        $user->phone = rand(1111111111, 9999999999);
        $user->password = Hash::make(Str::random(6));
        $user->is_active = false; 
        $user->auth_token = null; 
        $user->save();

        
        $getAllTokens = PushToken::where('user_id', $user->id)->get();
        if ($getAllTokens->count() > 0) {
            foreach ($getAllTokens as $userToken) {
                $userToken->delete();
            }
        }

        DB::commit();
        \Log::channel('view_order_issue')->info('User profile deactivated successfully', ['user_id' => $user->id]);
        return response()->json(['success' => true, 'message' => 'تم تعطيل الحساب بنجاح']);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::channel('view_order_issue')->error('Error deactivating user profile', [
            'user_id' => $request->user_id,
            'error' => $e->getMessage()
        ]);
        return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تعطيل الحساب: ' . $e->getMessage()]);
    } catch (\Throwable $th) {
        DB::rollBack();
        \Log::channel('view_order_issue')->error('Unexpected error deactivating user profile', [
            'user_id' => $request->user_id,
            'error' => $th->getMessage()
        ]);
        return response()->json(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $th->getMessage()]);
    }
}
    /**
     * @param Request $request
     */
    public function updateUserProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }
        $running_order = Order::where('user_id', $user->id)
            ->whereIn('orderstatus_id', ['1', '2', '3', '4', '7', '10', '11', '12'])
            ->get();

        if (count($running_order) > 0) {
            return response()->json(['success' => false, 'has_running_order' => true]);
        }
        if (!empty($request->email)) {
            $check = User::where('email', $request->email)->where('id', '!=', $user->id)->first();
            if ($check) {
                return response()->json(['email_phone_already_used' => true]);
            } else {
                $user->email = $request->email;
            }
        }
        if (!empty($request->phone)) {
            $check = User::where('phone', $request->phone)->where('id', '!=', $user->id)->first();
            if ($check) {
                return response()->json(['email_phone_already_used' => true]);
            } else {
                $user->phone = $request->phone;
            }
        }
        if (!empty($request->name)) {
            $user->name = $request->name;
        }
        $user->save();
        $info = [
            'success' => true,
        ];
        return response()->json(['success' => true, 'data' => $info]);
    }

    public function getReferredUsers(Request $request)
    {
        $user = auth()->user();

        if ($user) {
            $referredUsers = User::where('referred_by', $user->id)
                ->with(['orders' => function ($query) {
                    $query->where('orderstatus_id', 5);
                }])
                ->select('id', 'name', 'phone', 'created_at')
                ->get();

            if (count($referredUsers) > 0) {
                $response = [
                    'success' => true,
                    'available' => true,
                    'list' => $referredUsers,
                    'referralCount' => count($referredUsers),
                ];
            } else {
                $response = [
                    'success' => false,
                    'available' => false,
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => "User not authorized",
            ];
        }
        return response()->json($response);
    }
};
