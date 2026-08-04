<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\MockCustomer;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CustomerAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request)
    {
        $customers = MockCustomer::with(['subscriptions' => fn ($q) => $q->active()])
            ->search($request->input('q'))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->withCount('subscriptions')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(MockCustomer $customer)
    {
        $customer->load([
            'subscriptions.plan.variant.product',
            'subscriptions.orders.fulfillment',
            'addresses',
            'notes.user',
        ]);
        return view('admin.customers.show', compact('customer'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = MockCustomer::create(array_merge(
            $request->validated(),
            ['uuid' => (string) \Illuminate\Support\Str::uuid(), 'is_mock' => true, 'environment' => 'local', 'source' => 'admin']
        ));

        $this->audit->log('customer.created', $customer, null, $customer->toArray(), "Cliente {$customer->name} creado por admin");

        return redirect()->route('admin.customers.show', $customer)
                         ->with('success', "Cliente {$customer->name} creado.");
    }

    public function edit(MockCustomer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, MockCustomer $customer)
    {
        $before = $customer->toArray();
        $customer->update($request->validated());
        $this->audit->log('customer.updated', $customer, $before, $customer->fresh()->toArray());

        return redirect()->route('admin.customers.show', $customer)
                         ->with('success', 'Cliente actualizado.');
    }

    public function destroy(MockCustomer $customer)
    {
        $this->audit->log('customer.deleted', $customer, $customer->toArray());
        $customer->delete();
        return redirect()->route('admin.customers.index')
                         ->with('success', 'Cliente eliminado (soft delete).');
    }
}
