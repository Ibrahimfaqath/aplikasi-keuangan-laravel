<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'action' => 'nullable|string|max:30',
            'model' => 'nullable|string|max:20',
            'period' => 'nullable|string|max:10',
        ]);

        $query = AuditLog::where('user_id', Auth::id());

        if (! empty($filters['action'])) {
            if ($filters['action'] === 'auth') {
                $query->where('action', 'like', 'auth.%');
            } elseif (array_key_exists($filters['action'], AuditLog::ACTION_LABELS)) {
                $query->where('action', $filters['action']);
            }
        }

        $modelMap = [
            'transaction' => Transaction::class,
            'budget' => Budget::class,
            'category' => Category::class,
            'account' => User::class,
        ];

        if (! empty($filters['model']) && isset($modelMap[$filters['model']])) {
            $query->where('model_type', $modelMap[$filters['model']]);
        } elseif (($filters['model'] ?? null) === 'auth') {
            $query->whereNull('model_type')->where('action', 'like', 'auth.%');
        }

        $periodCuts = [
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7)->startOfDay(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->subDays(90)->startOfDay(),
            'year' => now()->startOfYear(),
        ];

        if (! empty($filters['period']) && isset($periodCuts[$filters['period']])) {
            $query->where('created_at', '>=', $periodCuts[$filters['period']]);
        }

        $logs = $query->latest('id')->paginate(15)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
