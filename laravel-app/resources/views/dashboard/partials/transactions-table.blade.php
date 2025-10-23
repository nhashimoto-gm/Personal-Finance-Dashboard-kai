<<<<<<< HEAD
404: Not Found
=======
{{-- Recent Transactions Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">
                    <i class="bi bi-table"></i> {{ __('messages.recentTransactions') }}
                </h5>
            </div>
            <div class="card-body">
                @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('messages.shop') }}</th>
                                    <th>{{ __('messages.category') }}</th>
                                    <th class="text-end">{{ __('messages.amount') }}</th>
                                    <th class="text-center">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date }}</td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $transaction->shop->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $transaction->category->name }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <strong>¥{{ number_format($transaction->amount) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('transactions.edit', $transaction->id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('transactions.destroy', $transaction->id) }}"
                                                  method="POST"
                                                  style="display: inline;"
                                                  onsubmit="return confirm('{{ __('messages.confirmDelete') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center mt-3">
                        {{ $transactions->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i>
                        {{ __('messages.noTransactions') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
>>>>>>> origin/claude/database-migration-guide-011CUPjfwDoKC42txwUADvae
