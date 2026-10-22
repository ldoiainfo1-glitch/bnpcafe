<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return InvoiceResource::collection(
            Invoice::query()
                ->where('user_id', request()->user()->id)
                ->orderByDesc('issued_at')
                ->paginate(15),
        );
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        abort_unless($invoice->user_id === request()->user()->id, 404);

        return new InvoiceResource($invoice);
    }
}
