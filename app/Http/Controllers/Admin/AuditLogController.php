<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->input('action'), fn ($q, $a) => $q->byAction($a))
            ->when($request->input('user_id'), fn ($q, $uid) => $q->where('user_id', $uid))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
