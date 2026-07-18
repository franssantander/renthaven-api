<?php

namespace App\Http\Controllers;

use App\Data\AuditLog\AuditLogData;
use App\Models\AuditLog;
use App\Services\AuditLog\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService) {}

    /**
     * Paginated audit trail for admins/staff (tenant-scoped) and super admins
     * (all tenants). Read-only: audit entries can never be edited or deleted.
     */
    public function index(Request $request)
    {
        return $this->auditLogService->list(
            $request->user(),
            $request->only(['module', 'action', 'user_uuid', 'date_from', 'date_to', 'search', 'tenant_business_uuid']),
            (int) $request->input('per_page', 15),
        );
    }

    /**
     * The authenticated user's own audit trail (self-serve, e.g. renters).
     */
    public function mine(Request $request)
    {
        return $this->auditLogService->listOwn($request->user(), (int) $request->input('per_page', 15));
    }

    /**
     * Display a single audit log entry.
     */
    public function show(Request $request, AuditLog $auditLog): JsonResponse
    {
        abort_unless($this->auditLogService->canView($request->user(), $auditLog), 404);

        $auditLog->load(['actor.role', 'tenantBusiness']);

        return $this->success(AuditLogData::from($auditLog), 'Audit log entry retrieved successfully.');
    }
}
