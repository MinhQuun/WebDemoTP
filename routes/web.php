<?php

use App\Http\Controllers\Admin\QrController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/qrs');

Route::prefix('admin')->group(function () {
    Route::get('/qrs', [QrController::class, 'index'])->name('admin.qrs.index');
    Route::get('/qrs/{qrText}/detail', [QrController::class, 'detail'])->name('admin.qrs.detail');
});
