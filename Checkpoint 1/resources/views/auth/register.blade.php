@extends('layouts.react')

@php
    $title = 'Cadastrar usuario';
    $appProps = [
        'page' => 'register',
        'errors' => $errors->all(),
        'old' => [
            'name' => old('name'),
            'email' => old('email'),
        ],
    ];
@endphp
