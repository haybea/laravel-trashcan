@extends('trashcan::layouts.app-' . config('trashcan.css_framework', 'bootstrap'))
@section('content')
@include('trashcan::partials.' . config('trashcan.css_framework') . '.sidebar')
<div class="{{ config('trashcan.css_framework') === 'bootstrap' ? 'main-content p-4' : 'ml-72 min-h-screen p-8' }}">
    <h1 class="{{ config('trashcan.css_framework') === 'bootstrap' ? 'h3 mb-4' : 'text-2xl font-semibold text-gray-800 dark:text-white mb-6' }}">Statistics</h1>
    <p>Extended statistics view - customize as needed.</p>
</div>
@endsection