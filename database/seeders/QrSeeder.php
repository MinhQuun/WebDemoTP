<?php

namespace Database\Seeders;

use App\Models\Qr;
use App\Models\QrStageLog;
use App\Models\Stage;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class QrSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('vi_VN');
        $stages = Stage::orderBy('sort_order')->get();

        if ($stages->isEmpty()) {
            $this->command?->warn('No stages found. Run StageSeeder first.');
            return;
        }

        $productNames = [
            'Lưới cước 380/12 x 0.7/8"(2.2cm) x 300MD x 30M.XQD',
            'Lưới cước 380/9 x 0.5/8"(1.6cm) x 300MD x 30M.XQD',
            'Lưới nylon 700/21 x 20CM x 60MD x 15m',
            'Lưới PE 630/12 x 25CM x 120MD x 45m',
            'Lưới sợi carbon 500/18 x 40CM x 80MD x 50m',
            'Lưới mono 400/9 x 15CM x 50MD x 80m',
            'Lưới tráng kẽm 800/25 x 10CM x 200MD x 20m',
            'Lưới HDPE 520/10 x 30CM x 90MD x 60m',
            'Lưới phủ UV 600/15 x 18CM x 150MD x 40m',
            'Lưới chịu nhiệt 710/20 x 22CM x 110MD x 25m',
        ];

        $machines = ['K19', 'K21', 'K10', 'K05', null];
        $operators = ['Bích Trâm'];
        $normalNotes = [
            'Hoàn thành đúng kế hoạch.',
            'Đã kiểm tra kích thước đạt.',
            'Đã ghi nhận số liệu vận hành.',
            'Chuyển tiếp sang công đoạn kế tiếp.',
            'Kiểm tra ngẫu nhiên đạt chuẩn.',
            'Đã làm sạch khu vực máy.',
        ];
        $errorNotes = [
            'Phát hiện lỗi sợi, cần kiểm tra lại.',
            'Thiết bị cảnh báo nhiệt độ, tạm dừng.',
            'Sai thông số căng lưới, chờ xử lý.',
            'Chất lượng dệt chưa đạt, cần kiểm tra.',
        ];

        for ($i = 0; $i < 100; $i++) {
            $createdAt = Carbon::now()
                ->subDays(random_int(0, 60))
                ->setTime(random_int(6, 18), random_int(0, 59));

            $orderCode = 'L' . str_pad((string) $faker->numberBetween(9000, 9999), 4, '0', STR_PAD_LEFT);
            $productCode = $faker->numerify('3######');
            $qrCode = $orderCode . '-T' . str_pad((string) $faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT);

            $qr = Qr::create([
                'qr_code' => $qrCode,
                'order_code' => $orderCode,
                'product_code' => $productCode,
                'product_name' => Arr::random($productNames),
                'machine' => Arr::random($machines),
                'created_by' => Arr::random($operators),
            ]);

            $qr->created_at = $createdAt;
            $qr->updated_at = $createdAt;
            $qr->save();

            $logTime = (clone $createdAt)->addMinutes($faker->numberBetween(10, 120));
            $logCount = min($stages->count(), $faker->numberBetween(2, 8));
            $hasError = $faker->boolean(15);
            $errorStageId = null;
            $lastDoneStageId = null;

            foreach ($stages->take($logCount) as $index => $stage) {
                $status = QrStageLog::STATUS_DONE;
                if ($hasError && $errorStageId === null && ($index === $logCount - 1 || $faker->boolean(30))) {
                    $status = QrStageLog::STATUS_ERROR;
                    $errorStageId = $stage->id;
                } else {
                    $lastDoneStageId = $stage->id;
                }

                $log = QrStageLog::create([
                    'qr_id' => $qr->id,
                    'stage_id' => $stage->id,
                    'status' => $status,
                    'performed_at' => $logTime,
                    'performed_by' => Arr::random($operators),
                    'quantity' => $faker->boolean(65) ? $faker->numberBetween(1, 500) : null,
                    'note' => $status === QrStageLog::STATUS_ERROR
                        ? Arr::random($errorNotes)
                        : ($faker->boolean(25) ? Arr::random($normalNotes) : null),
                ]);

                $log->created_at = $logTime;
                $log->updated_at = $logTime;
                $log->save();

                $logTime = (clone $logTime)->addMinutes($faker->numberBetween(20, 240));
            }

            $currentStageId = $errorStageId;

            if ($currentStageId === null) {
                $lastDoneStage = $lastDoneStageId
                    ? $stages->firstWhere('id', $lastDoneStageId)
                    : null;
                $nextStage = $lastDoneStage
                    ? $stages->firstWhere('sort_order', '>', $lastDoneStage->sort_order)
                    : null;

                $currentStageId = $nextStage?->id ?? $lastDoneStage?->id;
            }

            $qr->update(['current_stage_id' => $currentStageId]);
        }
    }
}
