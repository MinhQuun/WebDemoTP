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
            <div class="text-uppercase text-muted small fw-semibold">Xem QR &#273;&#227; t&#7841;o</div>
            <h3 class="mb-1">Danh s&#225;ch QR</h3>
        </div>
        <a href="{{ route('admin.qrs.index') }}" class="btn btn-outline-secondary btn-sm">L&#224;m m&#7899;i</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET" action="{{ route('admin.qrs.index') }}">
                <div class="col-md-3">
                    <label class="form-label mb-1">T&#7915; ng&#224;y</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">&#272;&#7871;n ng&#224;y</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">T&#7915; kh&#243;a</label>
                    <input type="text" name="keyword" value="{{ $filters['keyword'] }}" placeholder="QR, &#273;&#417;n h&#224;ng, m&#227; h&#224;ng, t&#234;n h&#224;ng" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">C&#244;ng &#273;o&#7841;n</label>
                    <select name="stage" class="form-select form-select-sm">
                        <option value="all" @selected($filters['stage'] === 'all')>T&#7845;t c&#7843;</option>
                        @foreach($stages as $stage)
                            <option value="{{ $stage }}" @selected($filters['stage'] === $stage)>{{ $stage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-1">
                    <a href="{{ route('admin.qrs.index') }}" class="btn btn-light btn-sm">X&#243;a l&#7885;c</a>
                    <button type="submit" class="btn btn-primary btn-sm">T&#236;m ki&#7871;m</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 qr-table">
                    <thead class="table-light">
                    <tr>
                        <th class="text-nowrap">Ng&#224;y t&#7841;o</th>
                        <th class="text-nowrap text-center">QR</th>
                        <th>&#272;&#417;n h&#224;ng</th>
                        <th>M&#227; h&#224;ng</th>
                        <th>T&#234;n h&#224;ng</th>
                        <th>Gi&#225;</th>
                        <th>Ghi ch&#250;</th>
                        <th>C&#244;ng &#273;o&#7841;n hi&#7879;n t&#7841;i</th>
                        <th>Ng&#432;&#7901;i t&#7841;o</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($qrs as $qr)
                        @php
                            $createdAt = $qr->ngay_tao ? \Illuminate\Support\Carbon::parse($qr->ngay_tao) : null;
                            $stageName = $qr->cong_doan_hien_tai;
                            $badgeClass = $stageName ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary';
                        @endphp
                        <tr class="qr-row" data-qr-text="{{ $qr->qr_text }}">
                            <td class="text-nowrap">{{ $createdAt?->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <div class="qr-code-cell">
                                    <div class="qr-img" data-qr="{{ $qr->qr_text }}"></div>
                                    <div class="fw-semibold small">{{ $qr->qr_text }}</div>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $qr->don_hang }}</td>
                            <td class="text-nowrap">{{ $qr->ma_hang }}</td>
                            <td>
                                <div class="text-truncate qr-product-name" title="{{ $qr->ten_hang }}">
                                    {{ $qr->ten_hang }}
                                </div>
                            </td>
                            <td class="text-nowrap">
                                {{ is_null($qr->gia) ? '' : number_format($qr->gia) }}
                            </td>
                            <td>
                                <div class="text-truncate qr-note" title="{{ $qr->ghi_chu }}">
                                    {{ $qr->ghi_chu }}
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $badgeClass }}">
                                    {{ $stageName ?? 'Ch&#432;a x&#225;c &#273;&#7883;nh' }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $qr->nguoi_tao }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Ch&#432;a c&#243; d&#7919; li&#7879;u t&#7915; view vw_qr_list.</td>
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
                <h5 class="modal-title" id="qrDetailModalLabel">Chi ti&#7871;t QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="qr-detail-content">
                    <div class="text-center text-muted py-4">
                        Nh&#7845;n v&#224;o m&#7897;t d&#242;ng trong b&#7843;ng &#273;&#7875; xem chi ti&#7871;t.
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
