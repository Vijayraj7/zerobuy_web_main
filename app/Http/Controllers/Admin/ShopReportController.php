<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopReport;
use Illuminate\Http\Request;

class ShopReportController extends Controller
{
    /**
     * Display a listing of shop reports.
     */
    public function index(Request $request)
    {
        $query = ShopReport::with(['customer.user', 'shop'])
            ->latest('id');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search by shop name or customer name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('shop', function ($shopQuery) use ($search) {
                    $shopQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('customer.user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $reports = $query->paginate(20);

        return view('admin.shop-reports.index', compact('reports'));
    }

    /**
     * Display the specified report.
     */
    public function show($id)
    {
        $report = ShopReport::with(['customer.user', 'shop'])->findOrFail($id);

        return view('admin.shop-reports.show', compact('report'));
    }

    /**
     * Update the status of the specified report.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,resolved,rejected',
        ]);

        $report = ShopReport::findOrFail($id);
        $report->update([
            'status' => $request->status,
        ]);

        return back()->withSuccess(__('Report status updated successfully'));
    }

    /**
     * Remove the specified report from storage.
     */
    public function destroy($id)
    {
        $report = ShopReport::findOrFail($id);
        $report->delete();

        return back()->withSuccess(__('Report deleted successfully'));
    }
}
