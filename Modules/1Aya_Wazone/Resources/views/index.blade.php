@extends('wazone::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('wazone.name') !!}
    </p>
    <br>
    <span class="font-weight-normal mr-2">Showing results for "{{ $status }}"</span>
    <?php
        echo ($status);
        //print_r($output);
    ?>
@endsection
