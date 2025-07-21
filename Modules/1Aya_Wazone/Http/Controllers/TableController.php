<?php

namespace Modules\Wazone\Http\Controllers;

use App\User;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class TableController extends Controller {

    public function editRestaurant($id) {
        $restaurant = Restaurant::where('id', $id)->first();

        return view('wazone::editRestaurant', array(
            'restaurant' => $restaurant,
        ));
    }

    public function updateRestaurant(Request $request) {
        // dd($request->all());
        $restaurant = Restaurant::where('id', $request->id)->first();
        try {
            $restaurant->name = $request->name;
            $restaurant->phone = $request->phone;
            $restaurant->save();
            return redirect(route('Wazone.editRestaurant', $restaurant->id) . $request->window_redirect_hash)->with(['success' => 'Restaurant Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    public function editUser($id) {
        $user = User::where('id', $id)->first();
        $roles = Role::all()->except(1);

        return view('wazone::editUser', array(
            'user' => $user,
            'roles' => $roles,
        ));
    }

    public function updateUser(Request $request) {
        $user = User::where('id', $request->id)->first();
        try {
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->save();
            return redirect(route('Wazone.editUser', $user->id) . $request->window_redirect_hash)->with(['success' => 'User Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    public function usersDatatable() {
        $users = User::with('roles', 'wallet');

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
                if ($user->is_notifiable) { 
                    $notify = 'bbtn btn-lg btn-success ml-1'; 
                    $tooltip = 'Notification is ON'; 
                } else {
                    $notify = 'bbtn btn-lg btn-danger ml-1'; 
                    $tooltip = 'Notification is OFF'; 
                }
                return '<div class="btn-group btn-group-justified align-items-center"><a href="' . route('Wazone.editUser', $user->id) . '" class="btn btn-md btn-primary"> Edit </a>
                <a href="' . route('Wazone.saveUserNotifiable', $user->id) . '" class="' . $notify . '" data-popup="tooltip" title="' . $tooltip . '" data-placement="bottom">  <i class="icon-comment"></i> </a></div>';
            })
            ->rawColumns(['role', 'action', 'created_at'])
            ->make(true);
    }

    public function storesDatatable()
    {
        $restaurants = Restaurant::where('is_accepted', '1')->with('users', 'users.roles', 'delivery_areas')->get();

        return Datatables::of($restaurants)

            ->editColumn('image', function ($restaurant) {
                return '<img src="' . $restaurant->image . '" alt="' . $restaurant->name . '" height="65" width="65" style="border-radius: 0.275rem;">';
            })

            ->editColumn('name', function ($restaurant) {
                return '<p class="text-center">' . $restaurant->name . '</p>';
            })

            ->editColumn('phone', function ($restaurant) {
                $html = '<p class="text-center">' . $restaurant->phone . '</p>';
                return $html;
            })

            ->addColumn('action', function ($restaurant) {
                if ($restaurant->is_notifiable) { 
                    $notify = 'bbtn btn-lg btn-success ml-1'; 
                    $tooltip = 'Notification is ON'; 
                } else {
                    $notify = 'bbtn btn-lg btn-danger ml-1'; 
                    $tooltip = 'Notification is OFF'; 
                }
                return '<div class="btn-group btn-group-justified align-items-center"><a href="' . route('Wazone.editRestaurant', $restaurant->id) . '" class="btn btn-md btn-primary"> Edit </a>
                <a href="' . route('Wazone.saveRestaurantNotifiable', $restaurant->id) . '" class="' . $notify . '">  <i class="icon-comment"></i> </a></div>';
            })

            ->rawColumns(['image', 'name', 'phone', 'action'])
            ->make(true);
    }
}
