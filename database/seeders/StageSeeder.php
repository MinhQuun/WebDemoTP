<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            'Ghi nhận thông tin dệt lưới',
            'Kiểm tra chất lượng dệt lưới',
            'Ghi nhận thông tin sửa nối lưới',
            'Kiểm tra sửa nối lưới',
            'Ghi nhận thông tin hấp lưới',
            'Kiểm tra hấp lưới',
            'Đóng kiện',
            'Kiểm tra đóng kiện',
            'Kiểm tra final',
            'Ghi nhận xuất kho',
        ];

        foreach ($stages as $index => $name) {
            Stage::updateOrCreate(
                ['sort_order' => $index + 1],
                ['name' => $name]
            );
        }
    }
}
