@extends('layouts.dashboard')

@section('title', 'Metrics')

@section('content')
    <div class="content-body ms-5">
        <div class="content-card p-4 text-center text-white">
            <i class="bi bi-info-circle me-2"></i>
            Pembuatan atau impor metrics manual telah dinonaktifkan. Metrics dibuat otomatis saat bisnis dibuat.
            <div class="mt-3">
                <a href="{{ route('dashboard.metrics') }}" class="btn btn-primary">Kembali ke Metrics</a>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
@endpush
