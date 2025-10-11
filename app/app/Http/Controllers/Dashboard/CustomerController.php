<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Search customers for transaction autocomplete
     */
    public function search(Request $request)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $query = $request->get('q', '');

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'customers' => []
                ]);
            }

            $customers = Customer::where('business_id', $business->id)
                ->where(function($q) use ($query) {
                    $q->where('customer_name', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->select('id', 'customer_name as name', 'phone', 'email')
                ->orderBy('customer_name')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'customers' => $customers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching customers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();

            $customer = Customer::create([
                'business_id' => $business->id,
                'customer_name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'first_purchase_date' => now()->toDateString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a customer detail
     */
    public function show(Request $request, int $id)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $customer = Customer::where('business_id', $business->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'customer_type' => $customer->customer_type,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a customer
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $customer = Customer::where('business_id', $business->id)->findOrFail($id);

            $customer->customer_name = $request->name;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a customer
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();
            $customer = Customer::where('business_id', $business->id)->findOrFail($id);

            // Optional: guard if customer linked to transactions; for now allow delete
            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting customer: ' . $e->getMessage()
            ], 500);
        }
    }
}
