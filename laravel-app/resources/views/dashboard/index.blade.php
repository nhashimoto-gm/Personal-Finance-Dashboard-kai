@extends('layouts.app')

@section('title', __('messages.title') . ' - ' . __('messages.tabDashboard'))

@section('content')
<ul class="nav nav-tabs" role="tablist" style="margin-top: 0.5rem;">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard-pane"
            type="button" role="tab">
            <i class="bi bi-graph-up"></i> {{ __('messages.tabDashboard') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="{{ route('transactions.entry') }}">
            <i class="bi bi-plus-circle"></i> {{ __('messages.tabDataEntry') }}
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="{{ route('shops.index') }}">
            <i class="bi bi-gear"></i> {{ __('messages.tabManagement') }}
        </a>
    </li>
</ul>

<div class="container-fluid mt-4">
    <!-- Period Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('dashboard') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="start_date">{{ __('messages.startDate') }}</label>
                            <input type="date" class="form-control" name="start_date" id="start_date"
                                value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="end_date">{{ __('messages.endDate') }}</label>
                            <input type="date" class="form-control" name="end_date" id="end_date"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100" style="margin-top: 2rem;">
                                <i class="bi bi-search"></i> {{ __('messages.filter') }}
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary w-100" style="margin-top: 2rem;">
                                <i class="bi bi-arrow-clockwise"></i> {{ __('messages.reset') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-calendar-range"></i> {{ __('messages.periodTotal') }}
                    </h6>
                    <h2 class="card-title text-primary">
                        ¥{{ number_format($summary['total']) }}
                    </h2>
                    <p class="card-text text-muted small">
                        {{ $startDate }} ~ {{ $endDate }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-calculator"></i> {{ __('messages.dailyAvg') }}
                    </h6>
                    <h2 class="card-title text-success">
                        ¥{{ number_format($dailyAverage) }}
                    </h2>
                    <p class="card-text text-muted small">
                        {{ $activeDays }} {{ __('messages.daysAvg') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-receipt"></i> {{ __('messages.recordCount') }}
                    </h6>
                    <h2 class="card-title text-info">
                        {{ number_format($summary['record_count']) }}
                    </h2>
                    <p class="card-text text-muted small">
                        {{ __('messages.transactionCount') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-shop"></i> {{ __('messages.shopCount') }}
                    </h6>
                    <h2 class="card-title text-warning">
                        {{ number_format($summary['shop_count']) }}
                    </h2>
                    <p class="card-text text-muted small">
                        {{ __('messages.uniqueShops') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Shop Pie Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> {{ __('messages.shopExpense') }}</h5>
                </div>
                <div class="card-body">
                    <div id="shopChart"></div>
                </div>
            </div>
        </div>

        <!-- Category Bar Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> {{ __('messages.categoryTop10') }}</h5>
                </div>
                <div class="card-body">
                    <div id="categoryChart" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Daily Trend Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> {{ __('messages.dailyTrend') }}</h5>
                </div>
                <div class="card-body">
                    <div id="dailyChart" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Cumulative Trend Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> {{ __('messages.cumulativeTrend') }}</h5>
                </div>
                <div class="card-body">
                    <div id="cumulativeChart" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    @include('dashboard.partials.transactions-table', ['transactions' => $recentTransactions])
</div>
@endsection

@push('scripts')
<script>
    // Pass data to JavaScript
    window.dashboardData = {
        shopData: @json($shopData),
        categoryData: @json($categoryData),
        dailyData: @json($dailyData),
        periodData: @json($periodData)
    };

    // Initialize charts (charts will be rendered by app.js)
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof renderDashboardCharts === 'function') {
            renderDashboardCharts(window.dashboardData);
        }
    });
</script>
@endpush
