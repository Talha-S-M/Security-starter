@php
    use Pitbphp\Security\Support\SecurityRoutes;

    $pageTitle = $title ?? 'PITB Security';
    $pageSubtitle = $subtitle ?? null;
    $standalone = $standalone ?? request()->routeIs(SecurityRoutes::adminName('partials.*'));
@endphp

@if ($standalone)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    @include('security::admin.partials.styles')
</head>
<body class="pitb-security pitb-security-page">
    <div class="admin-app">
        @include('security::admin.partials.sidebar-nav')

        <main class="admin-main">
            <header class="page-header">
                <h1>{{ $pageTitle }}</h1>
                @if ($pageSubtitle)
                    <p class="page-subtitle">{{ $pageSubtitle }}</p>
                @endif
            </header>

            @include('security::admin.partials.alerts')

            <div class="page-body">
@else
<div class="pitb-security">
    @include('security::admin.partials.styles')

    @if ($pageTitle)
        <header class="page-header">
            <h1>{{ $pageTitle }}</h1>
            @if ($pageSubtitle)
                <p class="page-subtitle">{{ $pageSubtitle }}</p>
            @endif
        </header>
    @endif

    @include('security::admin.partials.alerts')

    <div class="page-body">
@endif
