@extends('tenant.layouts.app')
@php
    $title    = 'Autenticação em Dois Fatores';
    $pageIcon = 'shield-lock';
@endphp

@section('content')
    @include('master.2fa._setup-body', [
        'enabled'     => $enabled,
        'qrImage'     => $qrImage,
        'secret'      => $secret,
        'routePrefix' => $routePrefix,
    ])
@endsection
