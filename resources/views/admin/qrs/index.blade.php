<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xem QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin-qrs.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold">Xem QR đã tạo</div>
            <h3 class="mb-1">Danh sách QR</h3>
        </div>
        <a href="{{ route('admin.qrs.index') }}" class="btn btn-outline-secondary btn-sm">Làm mới</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET" action="{{ route('admin.qrs.index') }}">
                <div class="col-md-3">
                    <label class="form-label mb-1">Từ ngày</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Đến ngày</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Từ khóa</label>
                    <input type="text" name="keyword" value="{{ $filters['keyword'] }}" placeholder="QR, Mã hàng, Tên hàng" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Công đoạn</label>
                    <select name="stage" class="form-select form-select-sm">
                        <option value="all" @selected($filters['stage'] === 'all')>Tất cả</option>
                        @foreach($stages as $stage)
                            <option value="{{ $stage }}" @selected($filters['stage'] === $stage)>{{ $stage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-1">
                    <a href="{{ route('admin.qrs.index') }}" class="btn btn-light btn-sm">Xóa lọc</a>
                    <button type="submit" class="btn btn-primary btn-sm">Tìm kiếm</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 qr-table">
                    <thead class="table-light">
                    <tr>
                        <th class="text-nowrap text-center">QR</th>
                        <th class="text-nowrap">Mã hàng</th>
                        <th>Tên hàng</th>
                        <th>Công đoạn</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($qrs as $qr)
                        @php
                            $stageName = trim($qr->cong_doan_hien_tai ?? '');
                            $currentIndex = ($stageName !== '') ? array_search($stageName, $stages) : false;
                        @endphp
                        <tr>
                            <td class="text-center">
                                <div class="qr-code-cell">
                                    <div class="qr-img" data-qr="{{ $qr->qr_text }}"></div>
                                    <div class="fw-semibold small">{{ $qr->qr_text }}</div>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $qr->ma_hang }}</td>
                            <td>
                                <div class="text-truncate qr-product-name" title="{{ $qr->ten_hang }}">
                                    {{ $qr->ten_hang }}
                                </div>
                            </td>
                            <td>
                                <div class="stage-list">
                                    @foreach($stages as $index => $stage)
                                        @php
                                            if ($currentIndex !== false && $index < $currentIndex) {
                                                $status = 'done';
                                            } elseif ($currentIndex !== false && $index === $currentIndex) {
                                                $status = 'current';
                                            } else {
                                                $status = 'pending';
                                            }
                                        @endphp
                                        <span class="stage-chip stage-{{ $status }}">{{ $stage }}</span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Chưa có dữ liệu từ view vw_qr_list.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $qrs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script src="{{ asset('js/admin-qrs.js') }}" defer></script>
</body>
</html>
