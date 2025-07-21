@extends('admin.layouts.master')
@section('title')
Foodomaa Utilities
@endsection

@section('content')
<div class="mt-4">
	<h2>Enable/Disable Stores</h2>
	<a href="{{ route('admin.utility.toggleStoreStatus', 'enable') }}" class="btn btn-dark btn-lg">Enable all Stores</a>
	<br><br>
	<a href="{{ route('admin.utility.toggleStoreStatus', 'disable') }}" class="btn btn-dark btn-lg">Disable all Stores</a>
	<br><br>
	<a href="{{ route('admin.utility.logoutAllAdminUsers') }}" class="btn btn-dark btn-lg">Logout All Users</a>
	<hr>
	<hr>
</div>

<footer>
	More utilities will be added in the future.
</footer>
@endsection