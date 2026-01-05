<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Qr;
use App\Models\QrStageLog;
use App\Models\Stage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'keyword' => $request->input('keyword'),
            'stage_id' => $request->input('stage_id'),
        ];

        $query = Qr::query()
            ->with('currentStage')
            ->withExists(['logs as has_error' => function ($q) {
                $q->where('status', QrStageLog::STATUS_ERROR);
            }]);

        if ($filters['from_date']) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if ($filters['to_date']) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if ($filters['keyword']) {
            $keyword = '%' . trim($filters['keyword']) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('qr_code', 'like', $keyword)
                    ->orWhere('order_code', 'like', $keyword)
                    ->orWhere('product_code', 'like', $keyword)
                    ->orWhere('product_name', 'like', $keyword);
            });
        }

        if ($filters['stage_id']) {
            $query->where('current_stage_id', $filters['stage_id']);
        }

        $qrs = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $stages = Stage::orderBy('sort_order')->get();

        return view('admin.qrs.index', compact('qrs', 'stages', 'filters'));
    }

    public function detail(Qr $qr): JsonResponse
    {
        $qr->load('currentStage');
        $stages = Stage::orderBy('sort_order')->get();
        $logs = QrStageLog::with('stage')
            ->where('qr_id', $qr->id)
            ->orderBy('performed_at')
            ->get();

        $timeline = $stages->map(function (Stage $stage) use ($logs, $qr) {
            $stageLog = $logs->where('stage_id', $stage->id)->sortByDesc('performed_at')->first();

            if ($stageLog && $stageLog->status === QrStageLog::STATUS_ERROR) {
                $status = 'error';
            } elseif ($stageLog && $stageLog->status === QrStageLog::STATUS_DONE) {
                $status = 'done';
            } elseif ($qr->current_stage_id === $stage->id) {
                $status = 'current';
            } else {
                $status = 'pending';
            }

            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'status' => $status,
                'performed_at' => $stageLog?->performed_at?->toDateTimeString(),
            ];
        });

        $hasError = $logs->contains(fn (QrStageLog $log) => $log->status === QrStageLog::STATUS_ERROR);
        $currentStageIndex = $timeline->search(fn ($item) => $item['id'] === $qr->current_stage_id);
        $totalStages = $stages->count();

        return response()->json([
            'qr' => [
                'id' => $qr->id,
                'qr_code' => $qr->qr_code,
                'order_code' => $qr->order_code,
                'product_code' => $qr->product_code,
                'product_name' => $qr->product_name,
                'machine' => $qr->machine,
                'created_at' => $qr->created_at?->toDateTimeString(),
                'created_by' => $qr->created_by,
            ],
            'current_stage_name' => $qr->currentStage?->name,
            'current_stage_number' => $currentStageIndex !== false ? $currentStageIndex + 1 : null,
            'total_stages' => $totalStages,
            'has_error' => $hasError,
            'timeline' => $timeline,
            'logs' => $logs->map(fn (QrStageLog $log) => [
                'performed_at' => $log->performed_at?->toDateTimeString(),
                'stage_name' => $log->stage?->name,
                'status' => $log->status,
                'quantity' => $log->quantity,
                'note' => $log->note,
            ]),
        ]);
    }
}
