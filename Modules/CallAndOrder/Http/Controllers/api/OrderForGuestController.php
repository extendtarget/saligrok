<?php

namespace Modules\CallAndOrder\Http\Controllers\api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use JWTAuth;
use JWTAuthException;



// created by qusay
class OrderForGuestController extends Controller
{
    
    public function loginAsCustomer( Request $request ){
        
        if ( $user = $this->isCustomerExists($request->phone, $request->email) ) {
            return $this->loginGuestUser($request);
        }
        
        return $this->registerGuestUser($request);
    }
    
    public function saveAddress(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'house' => 'string',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'token' => 'required|string',
            'user_id' => 'required|numeric',
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'data' => $validator->errors()->first()
            ], 400));
        }
        
    }
    
    /* helper functions */
    
    private function registerGuestUser(Request $request) {
        
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|numeric',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string'
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'data' => $validator->errors()->first()
            ], 400));
        }
        
        
        if (isset($request->email) && $request->email != null && $request->email != '') {
            $email = $request->email;
        } else {
            $email = str_replace('+', '', $request->phone) . '@' . $request->getHttpHost();
        }

        if (isset($request->password) && $request->password != null && $request->password != '') {
            $password = $request->password;
        } else {
            $password = substr(str_shuffle('123456789'), 0, 6);
        }
        
        
        $payload = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $email,
            'password' => \Hash::make($password),
            'auth_token' => '',
            'user_ip' => $request->ip() . ' - Guest Checkout',
        ];
        
        DB::beginTransaction();
        try {
            
            $user = new User($payload);
            
            if ( $user->save() ) {
                $token = $this->getToken($email, $password); // generate user token
                if (!is_string($token)) {
                    return response()->json(['success' => false, 'data' => 'Token generation failed'], 201);
                }
                $user->auth_token = $token;
                $user->save();
    
                $user->assignRole('Customer');
                $default_address = null;
    
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
                        'delivery_pin' => $user->delivery_pin,
                        'wallet_balance' => $user->balanceFloat,
                        'avatar' => $user->avatar,
                        'tax_number' => $user->tax_number,
                    ],
                    'running_order' => null,
                    'addresses' => $user->addresses,
                ];
    
                DB::commit();
                
                return response()->json($response, 201);
            }
            
            
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return response()->json(['success' => false, 'data' => $throwable->getMessage()], 400);
        }
        
        
    }
    
    private function isCustomerExists( $phone, $email ) {
        
        $user = User::where('phone', $phone)
        ->orWhere('email', $email)
        ->first();
   
        return $user;
    }
    
    private function getToken($email, $password) {
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
    
    private function loginGuestUser($request) {
        
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric',
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'data' => $validator->errors()->first()
            ], 400));
        }
        
        $user = User::where('phone', $request->phone)->first();
        
        if ( $user ) {
            
            $default_address = $this->setDefaultAddressAfterLogin($user);
            
            $response = [
                'newCustomer' => false,
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
                ],
                'running_order' => null,
                'delivery_details' => null,
                'addresses' => $user->addresses,
            ];
            
            return response()->json($response);
        }

    }
    
    private function setDefaultAddressAfterLogin($user) {
        if ($user->default_address_id !== 0) {
            $default_address = Address::where('id', $user->default_address_id)->get(['address', 'house', 'latitude', 'longitude', 'tag'])->first();
        } else {
            $default_address = null;
        }
        
        return $default_address;
    }
    
    
}