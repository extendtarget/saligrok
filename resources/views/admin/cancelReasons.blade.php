@extends('admin.layouts.master')
@section("title") Cancel Reasons - Dashboard
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Cancel Reasons</span>
                <i class="icon-circle-right2 mr-2"></i>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
    </div>
</div>
<div class="content">
    <div class="d-flex justify-content-between my-2">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left mb-3" data-toggle="modal" data-target="#createCancelReason">
                <b><i class="icon-plus2"></i></b>
                Add Cancel Reason
            </button>
            
            <!-- Modal -->
            <div class="modal fade" id="createCancelReason" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Cancel Reason</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('admin.cancelReason.create') }}" method="POST">
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Cancel Reason:</label>
                                    <div class="col-lg-9">
                                        <input type="text"
                                            class="form-control form-control-lg" name="reason"
                                            placeholder="Enter name of reason of order cancellation">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Select Role Type:</label>
                                    <div class="col-lg-9">
                                        <select class="form-control select" data-fouc
                                            name="role_id">
                                            <option value="" selected disabled>Select Role</option>
                                            @foreach ($roles as $role)
                                                @if ($role->id == 1 || $role->name == ("Store Owner"))
                                                    <option class="text-capitalize" value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endif
                                                @if ($role->hasPermissionTo('order_actions'))
                                                    <option class="text-capitalize" value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @csrf
                                <button type="submit" class="btn btn-primary float-right mx-3">Create</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>
                                ID
                            </th>
                            <th>
                                Reason
                            </th>
                            <th width="20%">
                                Role
                            </th>
                            <th>
                                Usage
                            </th>
                            <th>
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reasons as $reason)
                        <tr>
                            <td>#{{ $reason->id }}</td>
                            <td>{{ $reason->cancel_reason }}</td>
                            <td>{{ $reason->role->name }}</td>
                            <td>{{ $reason->usage }}</td>
                            <td>
                                <a class="btn btn-danger" href="{{ route('admin.cancelReason.delete', $reason->id) }}"><i class="icon-trash"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $('.select').select2();
</script>
@endsection