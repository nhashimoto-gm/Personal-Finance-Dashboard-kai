@extends('layouts.app')

@section('title', __('messages.title') . ' - ' . __('messages.tabDataEntry'))

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.tabDataEntry') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="transaction_date" class="form-label">{{ __('messages.date') }}</label>
                            <input type="date"
                                   class="form-control @error('transaction_date') is-invalid @enderror"
                                   id="transaction_date"
                                   name="transaction_date"
                                   value="{{ old('transaction_date', date('Y-m-d')) }}"
                                   required>
                            @error('transaction_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">{{ __('messages.amount') }}</label>
                            <input type="number"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   id="amount"
                                   name="amount"
                                   value="{{ old('amount') }}"
                                   min="1"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="shop_id" class="form-label">{{ __('messages.shop') }}</label>
                            <select class="form-select @error('shop_id') is-invalid @enderror"
                                    id="shop_id"
                                    name="shop_id"
                                    required>
                                <option value="">{{ __('messages.selectShop') }}</option>
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                        {{ $shop->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shop_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">{{ __('messages.category') }}</label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id"
                                    name="category_id"
                                    required>
                                <option value="">{{ __('messages.selectCategory') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> {{ __('messages.addTransaction') }}
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> {{ __('messages.backToDashboard') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
