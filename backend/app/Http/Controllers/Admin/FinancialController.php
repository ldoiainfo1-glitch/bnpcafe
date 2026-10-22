<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminInvoiceResource;
use App\Http\Resources\AdminPaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FinancialController extends Controller
{
    public function payments(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Payment::class);

        return AdminPaymentResource::collection(
            Payment::query()
                ->with(['user', 'membership.chapter', 'membership.plan', 'invoice'])
                ->orderByDesc('created_at')
                ->paginate(25),
        );
    }

    public function invoices(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Payment::class);

        return AdminInvoiceResource::collection(
            Invoice::query()
                ->with('user')
                ->orderByDesc('issued_at')
                ->paginate(25),
        );
    }
}
