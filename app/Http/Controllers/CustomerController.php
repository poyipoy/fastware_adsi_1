<?php

namespace App\Http\Controllers;

use App\Models\Customer;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Menampilkan daftar Pengguna
    public function index()
    {
        $customers = Customer::where('status', 0)->latest()->get();

        return view('customer.index', compact('customers'))->with('i', (request()->input('page', 1) - 1) * 5);
    }

    public function edit(Customer $customer)
    {

        return view('customer.edit', compact('customer'));
    }

    public function show(Customer $customer)
    {
        return view('customer.show', compact('customer'));
    }

    public function create()
    {
        return view('customer.create');
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update([
            'customer_code' => $request->customer_code ?? $customer->customer_code,
            'name_customer' => $request->name_customer ?? $customer->name_customer,
            'area' => $request->area ?? $customer->area,
            'email' => $request->email ?? $customer->email,
            'no_telp' => $request->no_telp ?? $customer->no_telp,
        ]);

        $customer->save();

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully');
    }

    public function store(Request $request)
    {
        $request->merge(['status' => $request->status ?? 0]);

        Customer::create([
            'customer_code' => $request->customer_code,
            'name_customer' => $request->name_customer,
            'area' => $request->area,
            'email' => $request->email,
            'no_telp' => $request->no_telp,
            'status' => $request->status
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer berhasil dibuat');
    }

    public function destroy(Customer $customer)
    {
        $customer->status = 1; // Set is_active menjadi 1 sebelum menghapus pengguna
        $customer->save(); // Simpan perubahan

        return redirect()->route('customers.index')->with('success', 'Customer berhasil dihapus');
    }
}
