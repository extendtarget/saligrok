@extends('admin.layouts.master')
@section("title") Send Notifications - Dashboard
@endsection
@section('content')
<style>
    .dropzone {
        border: 2px dotted #EEEEEE !important;
    }
</style>

<div class="content mt-3">
    
        @role('Admin')
    <div class="row">
        <div class="col-6 @if($countJunkData > 0)col-xl-3 @else col-xl-4 @endif mb-2 mt-2" data-popup="tooltip"
            title="These are total registered customers on your websites who can receive only Alerts messages.">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $usersCount }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Registered Customers</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-6 @if($countJunkData > 0)col-xl-3 @else col-xl-4 @endif mb-2 mt-2" data-popup="tooltip"
            title="These are total registered push notification subscribed customers who can receive both Alerts and Push Notifications messages.">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $subscriberCount }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Subscribers</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-6 @if($countJunkData > 0)col-xl-3 @else col-xl-4 @endif mb-2 mt-2" data-popup="tooltip"
            title="These are non-registered users of your Android App who can receive only Push Notifications messages.">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $appUsers }}</div>
                            <div class="font-size-sm text-uppercase text-muted">App Users</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        @if($countJunkData > 0)
        <div class="col-6 col-xl-3 mb-2 mt-2" data-popup="tooltip" title="Alters older than 7 days are not shown to the users and hence are of no
            use. Clicking on the below button will only delete {{ $countJunkData }} Alerts data that
            are older than 7 days." onclick="confirmDelete()">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-danger" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $countJunkData }}</div>
                            <div class="font-size-sm text-uppercase text-danger">Delete Junk Data</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        @endif

    </div>
 @endrole
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-lg-flex justify-content-lg-left">
                        <ul class="nav nav-pills flex-column mr-lg-3 wmin-lg-250 mb-lg-0">
                            <li class="nav-item">
                                <a href="#toAll" class="nav-link active" data-toggle="tab">
                                    To All
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#toSelected" class="nav-link" data-toggle="tab">
                                    To Selected
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#toNonRegisteredUsers" class="nav-link" data-toggle="tab">
                                    To Non-Registered App Users
                                </a>
                            </li>
                            @if (count($zones) > 0)
                            <li class="nav-item">
                                <a href="#toZoneUsers" class="nav-link" data-toggle="tab">
                                    To Zone Users
                                </a>
                            </li>
                            @endif
                        </ul>
                        <div class="tab-content" style="width: 100%; padding: 0 25px;">
                            <div class="tab-pane fade show active" id="toAll">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    Send push notification & alert to all users
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Notification Image: </label>
                                    <div class="col-lg-9">
                                        <img class="slider-preview-image hidden" />
                                        <div class="uploader">
                                            <form method="POST" action="{{ route('admin.uploadNotificationImage') }}"
                                                enctype="multipart/form-data" class="dropzone" id="dropzone">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}"
                                                    id="csrfToken">
                                            </form>
                                            <span class="help-text text-muted">Image size: 1600x1100</span>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('admin.sendNotifiaction') }}" method="POST"
                                    enctype="multipart/form-data">
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Notification
                                            Title:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[title]"
                                                placeholder="Notification Title" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Message:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[message]"
                                                placeholder="Notification Message" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label">URL:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg"
                                                name="data[click_action]"
                                                placeholder="This link will be opened when the notification is clicked">
                                        </div>
                                    </div>
                                    <input type="hidden" name="data[badge]"
                                        value="/assets/img/favicons/favicon-96x96.png">
                                    <input type="hidden" name="data[icon]"
                                        value="/assets/img/favicons/favicon-512x512.png">
                                    <input type="hidden" name="data[image]" value="" class="notificationImage">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" id="token">
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left">
                                            <b><i class="icon-paperplane"></i></b>
                                            SEND
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="toSelected">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    Send push notification & alert to selected users
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Notification Image: </label>
                                    <div class="col-lg-9">
                                        <img class="slider-preview-image hidden" />
                                        <div class="uploader">
                                            <form method="POST" action="{{ route('admin.uploadNotificationImage') }}"
                                                enctype="multipart/form-data" class="dropzone" id="dropzone">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}"
                                                    id="csrfToken">
                                            </form>
                                            <span class="help-text text-muted">Image size: 1600x1100</span>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('admin.sendNotificationToSelectedUsers') }}" method="POST"
                                    enctype="multipart/form-data">
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Select
                                            Users:</label>
                                        <div class="col-lg-9">
                                            <select multiple="multiple" class="form-control select" data-fouc
                                                name="users[]" required="required">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Notification
                                            Title:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[title]"
                                                placeholder="Notification Title" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Message:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[message]"
                                                placeholder="Notification Message" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label">URL:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg"
                                                name="data[click_action]"
                                                placeholder="This link will be opened when the notification is clicked">
                                        </div>
                                    </div>
                                    <input type="hidden" name="data[badge]"
                                        value="/assets/img/favicons/favicon-96x96.png">
                                    <input type="hidden" name="data[icon]"
                                        value="/assets/img/favicons/favicon-512x512.png">
                                    <input type="hidden" name="data[image]" value="" class="notificationImage">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" id="token">
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left">
                                            <b><i class="icon-paperplane"></i></b>
                                            SEND
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="toNonRegisteredUsers">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    Send push notification to non registered app users
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Notification Image: </label>
                                    <div class="col-lg-9">
                                        <img class="slider-preview-image hidden" />
                                        <div class="uploader">
                                            <form method="POST" action="{{ route('admin.uploadNotificationImage') }}"
                                                enctype="multipart/form-data" class="dropzone" id="dropzone">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}"
                                                    id="csrfToken">
                                            </form>
                                            <span class="help-text text-muted">Image size: 1600x1100</span>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('admin.sendNotificationToNonRegisteredAppUsers') }}"
                                    method="POST" enctype="multipart/form-data">
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Notification
                                            Title:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[title]"
                                                placeholder="Notification Title" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Message:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[message]"
                                                placeholder="Notification Message" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label">URL:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg"
                                                name="data[click_action]"
                                                placeholder="This link will be opened when the notification is clicked">
                                        </div>
                                    </div>
                                    <input type="hidden" name="data[image]" value="" class="notificationImage">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" id="token">
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left">
                                            <b><i class="icon-paperplane"></i></b>
                                            SEND
                                        </button>
                                    </div>
                                </form>
                            </div>
                            @if (count($zones) > 0)
                            <div class="tab-pane fade" id="toZoneUsers">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    Send push notification & alert to selected Zone Users
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Notification Image: </label>
                                    <div class="col-lg-9">
                                        <img class="slider-preview-image hidden" />
                                        <div class="uploader">
                                            <form method="POST" action="{{ route('admin.uploadNotificationImage') }}"
                                                enctype="multipart/form-data" class="dropzone" id="dropzone">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}"
                                                    id="csrfToken">
                                            </form>
                                            <span class="help-text text-muted">Image size: 1600x1100</span>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('admin.sendNotificationToZoneUsers') }}" method="POST"
                                    enctype="multipart/form-data">
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Select
                                            Zones:</label>
                                        <div class="col-lg-9">
                                            <select multiple="multiple" class="form-control selectZone" data-fouc
                                                name="zones[]" required="required">
                                                @foreach ($zones as $zone)
                                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Notification
                                            Title:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[title]"
                                                placeholder="Notification Title" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label"><span
                                                class="text-danger">*</span>Message:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg" name="data[message]"
                                                placeholder="Notification Message" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label">URL:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control form-control-lg"
                                                name="data[click_action]"
                                                placeholder="This link will be opened when the notification is clicked">
                                        </div>
                                    </div>
                                    <input type="hidden" name="data[badge]"
                                        value="/assets/img/favicons/favicon-96x96.png">
                                    <input type="hidden" name="data[icon]"
                                        value="/assets/img/favicons/favicon-512x512.png">
                                    <input type="hidden" name="data[image]" value="" class="notificationImage">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" id="token">
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left">
                                            <b><i class="icon-paperplane"></i></b>
                                            SEND
                                        </button>
                                    </div>
                                </form>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <legend class="font-weight-semibold text-uppercase font-size-sm">
                        Store Push Notifications
                    </legend>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <th>ID</th>
                                <th>Store</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Image</th>
                                <th>Requested by</th>
                                <th>Action</th>
                            </thead>
                            <tbody>
                       @foreach ($store_notifications as $sN)
    <tr>
        <td>#{{ $sN->id }}</td>
        <td>{{ optional($sN->restaurant)->name ?? '' }}</td>
        <td>{{ $sN->title }}</td>
        <td>{{ $sN->message }}</td>
        <td>@if ($sN->image != null) <img src="{{ $sN->image }}" alt="noti_image" style="max-height: 6rem; width: auto;">@else No Image @endif</td>
        <td>#{{ optional($sN->user)->id ?? 'غير متوفر' }} - {{ optional($sN->user)->name ?? '' }}</td>
        <td>
            @if ($sN->status == 2)
                <a href="{{ route('admin.approveStoreNotificationAndSend', $sN->id) }}" class="btn btn-success mx-2" data-toggle="tooltip" data-placement="top" title="Approve Notification"><i class="icon-check"></i></a>
                <a href="{{ route('admin.rejectStoreNotification', $sN->id) }}" class="btn btn-danger mx-2" data-toggle="tooltip" data-placement="top" title="Reject Notification"><i class="icon-cross"></i></a>
            @else
                @if ($sN->status == 1)
                    <span class="badge badge-lg badge-success mx-2">Sent</span>
                @elseif ($sN->status == 0)
                    <span class="badge badge-lg badge-danger mx-2">Rejected</span>
                @endif
            @endif
        </td>
    </tr>
@endforeach
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $store_notifications->appends($_GET)->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function readURL(input) {
       if (input.files && input.files[0]) {
           let reader = new FileReader();
           reader.onload = function (e) {
               $('.slider-preview-image')
                   .removeClass('hidden')
                   .attr('src', e.target.result)
                   .width(300)
           };
           reader.readAsDataURL(input.files[0]);
       }
    }
    

    $(function() {
       $('.form-control-uniform').uniform();
       $('.selectZone').select2({
        placeholder: "Select Zones to Send Notifications",
       });
       $('.select').select2({
           minimumResultsForSearch: Infinity,
           placeholder: 'Select Users',
           ajax: { 
                url: "{{route('admin.getUsersToSendNotification')}}",
                type: "get",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function (response) {
                    console.log(response);
                    return {
                        results: response
                    };
                },
                cache: true
            }
       });

    });

    @if($subscriberCount == 0)
        $.jGrowl("There are no subscribers to send push notifications.", {
            position: 'bottom-center',
            header: 'Wooopsss ⚠️',
            theme: 'bg-warning',
            life: '5000'
        }); 
    @endif
</script>
<script type="text/javascript">
    Dropzone.options.dropzone =
     {
        maxFilesize: 12,
        renameFile: function(file) {
            var dt = new Date();
            var time = dt.getTime();
           return time+file.name;
        },
        acceptedFiles: ".jpeg,.jpg,.png,.gif",
        addRemoveLinks: true,
        timeout: 50000,
        removedfile: function(file) 
        {
           $('.notificationImage').attr('value', "");
            var fileRef;
            return (fileRef = file.previewElement) != null ? fileRef.parentNode.removeChild(file.previewElement) : void 0;
        },
        success: function(file, response) 
        {
            console.log(response.success);
            $('.notificationImage').attr('value', '/assets/img/various/' +response.success);
        },
        error: function(file, response)
        {
           return false;
        }
    };

    function confirmDelete()
    {
          var r = confirm("Are you sure? This action is irreversible!");
          if (r == true) {
            let url = "{{ url('admin/delete-alerts-junk') }}";
            window.location.href = url;
          }
    }
</script>
@endsection