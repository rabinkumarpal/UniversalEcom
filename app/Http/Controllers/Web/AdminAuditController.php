<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    /**
     * Display the paginated audit log viewer.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sq) use ($q) {
                $sq->where('action', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('entity_type', 'like', "%{$q}%")
                    ->orWhere('entity_id', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($uq) use ($q) {
                        $uq->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'like', '%'.$request->input('entity_type').'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(30)->withQueryString();

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        $stats = [
            'total_events' => AuditLog::count(),
            'today_events' => AuditLog::whereDate('created_at', today())->count(),
            'unique_actors' => AuditLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        return view('admin.audit', compact('logs', 'actions', 'stats'));
    }
}
