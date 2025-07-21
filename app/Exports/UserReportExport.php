<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\User;

class UserReportExport implements FromView
{
	public function view(): View
	{	
		$search_data['user'] = @$_GET['user'];		

		$users = User::orderBy('id', 'DESC')->with('roles', 'wallet')->get();

		if(!empty($search_data['user'])){
			$users = User::where('name', 'LIKE', '%' . $search_data['user'] . '%')
			->orWhere('email', 'LIKE', '%' . $search_data['user'] . '%')
			->with('roles', 'wallet')
			->get();
		}
		
		return view('admin.newreports.export.userReportExport', compact('users'));
	}
}