<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QrController extends Controller
{
    private const STAGES = [
        "T\u{1ea1}o QR",
        "Ghi nh\u{1ead}n d\u{1ec7}t",
        "Ki\u{1ec3}m tra d\u{1ec7}t",
        "Ghi nh\u{1ead}n n\u{1ed1}i l\u{1b0}\u{1edb}i",
        "Ki\u{1ec3}m tra n\u{1ed1}i l\u{1b0}\u{1edb}i",
        "Ghi nh\u{1ead}n h\u{1ea5}p l\u{1b0}\u{1edb}i",
        "Ki\u{1ec3}m tra h\u{1ea5}p l\u{1b0}\u{1edb}i",
        "Ghi nh\u{1ead}n \u{0111}\u{00f3}ng ki\u{1ec7}n",
        "Ki\u{1ec3}m tra \u{0111}\u{00f3}ng ki\u{1ec7}n",
        "Ki\u{1ec3}m tra final",
        "Xu\u{1ea5}t kho",
    ];

    public function index(Request $request): View
    {
        $filters = [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'keyword' => $request->input('keyword'),
            'stage' => $request->input('stage', 'all'),
        ];

        $query = DB::table('vw_qr_list');

        $fromDate = $this->parseDate($filters['from']);
        if ($fromDate) {
            $query->where('ngay_tao', '>=', $fromDate->startOfDay());
        }

        $toDate = $this->parseDate($filters['to']);
        if ($toDate) {
            $query->where('ngay_tao', '<=', $toDate->endOfDay());
        }

        if ($filters['keyword']) {
            $keyword = '%' . trim($filters['keyword']) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('qr_text', 'like', $keyword)
                    ->orWhere('don_hang', 'like', $keyword)
                    ->orWhere('ma_hang', 'like', $keyword)
                    ->orWhere('ten_hang', 'like', $keyword)
                    ->orWhere('ghi_chu', 'like', $keyword)
                    ->orWhere('nguoi_tao', 'like', $keyword);
            });
        }

        if ($filters['stage'] && $filters['stage'] !== 'all') {
            $query->where('cong_doan_hien_tai', $filters['stage']);
        }

        $qrs = $query->orderByDesc('ngay_tao')->paginate(20)->withQueryString();

        return view('admin.qrs.index', [
            'qrs' => $qrs,
            'stages' => self::STAGES,
            'filters' => $filters,
        ]);
    }

    public function detail(string $qrText): JsonResponse
    {
        $qr = DB::table('vw_qr_list')->where('qr_text', $qrText)->first();

        if (!$qr) {
            return response()->json(['message' => 'QR not found'], 404);
        }

        $currentStageIndex = array_search($qr->cong_doan_hien_tai, self::STAGES, true);
        $timeline = collect(self::STAGES)->map(function (string $stageName, int $index) use ($currentStageIndex) {
            if ($currentStageIndex !== false && $index < $currentStageIndex) {
                $status = 'done';
            } elseif ($currentStageIndex !== false && $index === $currentStageIndex) {
                $status = 'current';
            } else {
                $status = 'pending';
            }

            return [
                'id' => $index + 1,
                'name' => $stageName,
                'status' => $status,
                'performed_at' => null,
            ];
        });

        $createdAt = $this->parseDate($qr->ngay_tao);

        return response()->json([
            'qr' => [
                'qr_code' => $qr->qr_text,
                'order_code' => $qr->don_hang,
                'product_code' => $qr->ma_hang,
                'product_name' => $qr->ten_hang,
                'machine' => null,
                'created_at' => $createdAt?->toDateTimeString(),
                'created_by' => $qr->nguoi_tao,
            ],
            'current_stage_name' => $qr->cong_doan_hien_tai,
            'current_stage_number' => $currentStageIndex !== false ? $currentStageIndex + 1 : null,
            'total_stages' => count(self::STAGES),
            'has_error' => false,
            'timeline' => $timeline,
            'logs' => [],
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
