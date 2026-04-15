@extends('tenant.layouts.app')
@php
    $title    = 'Códigos de Backup — 2FA';
    $pageIcon = 'key';
@endphp

@section('content')
    @include('master.2fa._backup-codes-body', [
        'codes'       => $codes,
        'justEnabled' => $justEnabled,
        'routePrefix' => $routePrefix,
    ])
@endsection
