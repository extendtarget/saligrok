<?php

namespace Modules\CallAndOrder\Http\Controllers;

use App\AcceptDelivery;
use App\Address;
use App\Order;
use App\Rating;
use App\Setting;
use App\User;
use Artisan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use JWTAuth;
use JWTAuthException;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class CallAndOrderController extends Controller
{

    public function settings()
    {
        $permission = 'login_as_customer';

        $storeOwnersIdsWithPermission = User::role('Store Owner')->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->pluck('id')->toArray();

        $storeOwners = User::role('Store Owner')->get(['id', 'name']);

        return view('callandorder::settings', array(
            'storeOwnersIdsWithPermission' => $storeOwnersIdsWithPermission,
            'storeOwners' => $storeOwners,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveSettings(Request $request)
    {
        $allStoreOwners = User::role('Store Owner')->with('permissions')->get();
        foreach ($allStoreOwners as $storeOwner) {
            if (!empty($request->user_id) && in_array($storeOwner->id, $request->user_id)) {
                $storeOwner->givePermissionTo('login_as_customer');
            } else {
                $storeOwner->revokePermissionTo('login_as_customer');
            }
        }

        $allSettings = $request->except(['allowStoreOwnersPlaceLoginOrders', 'user_id', '_token']);

        foreach ($allSettings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting != null) {
                $setting->value = $value;
                $setting->save();
            }
        }

        $checkboxesSettings = ['allowStoreOwnersPlaceLoginOrders'];

        foreach ($checkboxesSettings as $checkboxSetting) {
            $setting = Setting::where('key', $checkboxSetting)->first();
            if (!$setting) {
                $setting = new Setting();
                $setting->key = $checkboxSetting;
            }
            if ($request->$checkboxSetting == 'true') {
                $setting->value = 'true';
                $setting->save();
            } else {
                $setting->value = 'false';
                $setting->save();
            }
        }

        Artisan::call('optimize:clear');

        return redirect()->back()->with(['success' => 'Settings Saved']);
    }

    public function users()
    {
        $roles = Role::all()->except(1);
        return view('admin.users', array(
            'roles' => $roles,
        ));
    }

    /**
     * @return mixed
     */
    public function usersDatatable()
    {
        $users = User::role('Customer')->with('roles', 'wallet');

        return Datatables::of($users)
            ->addColumn('role', function ($user) {
                return '<span class="badge badge-flat border-grey-800 text-default text-capitalize">' . implode(',', $user->roles->pluck('name')->toArray()) . '</span>';
            })
            ->addColumn('wallet', function ($user) {
                return config('setting.currencyFormat') . $user->balanceFloat;
            })
            ->editColumn('created_at', function ($user) {
                return '<span data-popup="tooltip" data-placement="left" title="' . $user->created_at->diffForHumans() . '">' . $user->created_at->format('Y-m-d - h:i A') . '</span>';
            })
            ->addColumn('action', function ($user) {

                $html = '';
                if (Module::find('CallAndOrder') && Module::find('CallAndOrder')->isEnabled()) {
                    if ($user->hasRole('Customer')) {
                        $html .= '<a href="javascript:void(0)" class="btn btn-sm btn-secondary loginAsCustomerBtn" data-id="' . $user->id . '"> Login</a>';
                    }
                }

                return $html;
            })
            ->rawColumns(['role', 'action', 'created_at'])
            ->make(true);
    }
    /**
     * @param Request $request
     */
    public function loginAsCustomer(Request $request)
    {
       
        
        if (isset($request->user_id)) {
            $user = User::where('id', $request->user_id)->first();

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
                    'running_order' => $running_order,
                    'delivery_details' => $delivery_details,
                    'addresses' => $user->addresses,
                ];

                return response()->json($response);
            } else {
                abort(404);
            }
        } else {
            $response = [
                'newCustomer' => true,
            ];
            return response()->json($response);
        }
    }

    /**
     * @param Request $request
     */
    public function registerGuestUser(Request $request)
    {
        $checkEmail = User::where('email', $request->email)->first();
        $checkPhone = User::where('phone', $request->phone)->first();

        if ($checkPhone || $checkEmail) {
            $response = [
                'email_phone_already_used' => true,
            ];
            return response()->json($response);
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
        $user = new User($payload);
        if ($user->save()) {
            $token = self::getToken($email, $password); // generate user token
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

            return response()->json($response, 201);
        } else {
            $response = ['success' => false, 'data' => 'Couldnt register user'];
        }
    }

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
}
