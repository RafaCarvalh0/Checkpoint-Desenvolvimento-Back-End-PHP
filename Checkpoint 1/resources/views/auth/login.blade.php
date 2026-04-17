@extends('layouts.react')

@php
    $title = 'Login';
    $appProps = [
        'page' => 'login',
        'errors' => $errors->all(),
        'old' => [
            'email' => old('email'),
        ],
    ];
@endphp
