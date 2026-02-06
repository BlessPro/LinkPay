@php
    $title = $profile->business_name;
@endphp
@extends('layouts.public')

@section('og_title', $ogTitle ?? $profile->business_name)
@section('og_description', $ogDescription ?? 'Browse products & services and contact on WhatsApp')
@section('og_image', $ogImage ?? url('/images/og-default.jpg'))
@section('og_image_width', '1200')
@section('og_image_height', '630')
@section('og_url', $ogUrl ?? route('public.listing', $profile->public_slug))
@section('og_type', $ogType ?? 'website')

@section('content')
    @include('public.partials.listing-content')
@endsection
