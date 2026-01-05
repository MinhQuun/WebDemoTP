<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="qr-detail-url" content="{{ url('/admin/qrs') }}/:id/detail">
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
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Từ khóa</label>
                    <input type="text" name="keyword" value="{{ $filters['keyword'] }}" placeholder="QR, đơn hàng, mã hàng..." class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Công đoạn</label>
                    <select name="stage_id" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        @foreach($stages as $stage)
                            <option value="{{ $stage->id }}" @selected($filters['stage_id'] == $stage->id)>{{ $stage->name }}</option>
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
                        <th class="text-nowrap">Ngày tạo</th>
                        <th class="text-nowrap">QR</th>
                        <th>Đơn hàng</th>
                        <th>Mã hàng</th>
                        <th>Tên hàng</th>
                        <th>Công đoạn hiện tại</th>
                        <th>Người tạo</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($qrs as $qr)
                        <tr class="qr-row" data-qr-id="{{ $qr->id }}">
                            <td class="text-nowrap">{{ optional($qr->created_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="qr-code-cell">
                                    <div class="qr-img" data-qr="{{ $qr->qr_code }}"></div>
                                    <div class="fw-semibold small">{{ $qr->qr_code }}</div>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $qr->order_code }}</td>
                            <td class="text-nowrap">{{ $qr->product_code }}</td>
                            <td>
                                <div class="text-truncate qr-product-name" title="{{ $qr->product_name }}">
                                    {{ $qr->product_name }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $badgeClass = $qr->has_error ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary';
                                @endphp
                                <span class="badge rounded-pill {{ $badgeClass }}">
                                    {{ $qr->currentStage->name ?? 'Đang xử lý' }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $qr->created_by }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Chưa có dữ liệu. Hãy chạy seeder để tạo dữ liệu demo.</td>
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

<div class="modal fade" id="qrDetailModal" tabindex="-1" aria-labelledby="qrDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrDetailModalLabel">Chi tiết QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="qr-detail-content">
                    <div class="text-center text-muted py-4">
                        Nhấn vào một dòng trong bảng để xem chi tiết.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script src="{{ asset('js/admin-qrs.js') }}" defer></script>
</body>
</html>
