(() => {
    const detailUrlTemplate =
        document.querySelector('meta[name="qr-detail-url"]')?.content || "";
    const detailModalEl = document.getElementById("qrDetailModal");
    const detailContent = document.getElementById("qr-detail-content");
    let detailModal;

    const escapeHtml = (value = "") =>
        value
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    const formatDateTime = (value) => {
        if (!value) return "";
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return new Intl.DateTimeFormat("vi-VN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
        }).format(date);
    };

    const buildTimeline = (items = []) => {
        if (!items.length) {
            return '<div class="text-muted small">Chưa có dữ liệu timeline.</div>';
        }

        const statusLabels = {
            done: "Hoàn tất",
            current: "Đang thực hiện",
            pending: "Chưa làm",
            error: "Lỗi",
        };

        const html = items
            .map((item) => {
                const statusText = statusLabels[item.status] || item.status;
                const time = item.performed_at
                    ? formatDateTime(item.performed_at)
                    : statusText;

                return `
                <div class="timeline-item ${item.status}">
                    <div class="d-flex align-items-center mb-1">
                        <span class="status-dot"></span>
                        <span class="title">${escapeHtml(
                            item.name || ""
                        )}</span>
                    </div>
                    <div class="time">${escapeHtml(time)}</div>
                </div>`;
            })
            .join("");

        return `<div class="timeline-stepper">${html}</div>`;
    };

    const buildLogsTable = (logs = []) => {
        if (!logs.length) {
            return `<tr><td colspan="5" class="text-center text-muted">Chưa có log.</td></tr>`;
        }

        const statusClass = {
            done: "bg-success-subtle text-success",
            error: "bg-danger-subtle text-danger",
            in_progress: "bg-warning-subtle text-warning",
        };

        return logs
            .map((log) => {
                const badgeClass =
                    statusClass[log.status] ||
                    "bg-secondary-subtle text-secondary";
                return `
                <tr>
                    <td class="text-nowrap">${escapeHtml(
                        formatDateTime(log.performed_at)
                    )}</td>
                    <td>${escapeHtml(log.stage_name || "")}</td>
                    <td><span class="badge rounded-pill status-badge ${badgeClass}">${escapeHtml(
                    log.status || ""
                )}</span></td>
                    <td>${escapeHtml(log.quantity ?? "")}</td>
                    <td>${escapeHtml(log.note || "")}</td>
                </tr>`;
            })
            .join("");
    };

    const renderDetail = (data) => {
        const qr = data.qr || {};
        const statusClass = data.has_error
            ? "bg-danger-subtle text-danger"
            : "bg-success-subtle text-success";
        const statusText = data.has_error ? "Error" : "OK";

        const infoRows = [
            { label: "Đơn hàng", value: qr.order_code },
            { label: "Mã hàng", value: qr.product_code },
            { label: "Tên hàng", value: qr.product_name },
            { label: "Máy", value: qr.machine || "—" },
            { label: "Ngày tạo", value: formatDateTime(qr.created_at) },
            { label: "Người tạo", value: qr.created_by },
        ];

        const infoHtml = infoRows
            .map(
                (item) => `
                <div>
                    <div class="label">${escapeHtml(item.label)}</div>
                    <div class="fw-semibold">${escapeHtml(
                        item.value || ""
                    )}</div>
                </div>`
            )
            .join("");

        const timelineHtml = buildTimeline(data.timeline || []);
        const logsHtml = buildLogsTable(data.logs || []);
        const stageProgress =
            data.current_stage_number && data.total_stages
                ? `${data.current_stage_number}/${data.total_stages}`
                : "";

        return `
            <div class="detail-card">
                <div class="detail-header">
                    <div class="detail-qr">
                        <div class="qr-img" id="qr-detail-image" data-qr="${escapeHtml(
                            qr.qr_code || ""
                        )}"></div>
                        <div>
                            <div class="fw-semibold fs-5">${escapeHtml(
                                qr.qr_code || ""
                            )}</div>
                            <div class="text-muted small">${escapeHtml(
                                data.current_stage_name
                                    ? `Công đoạn hiện tại: ${data.current_stage_name}`
                                    : ""
                            )}</div>
                            <div class="text-muted small">${escapeHtml(
                                stageProgress
                            )}</div>
                        </div>
                    </div>
                    <span class="badge ${statusClass}">${statusText}</span>
                </div>

                <div class="detail-summary">${infoHtml}</div>

                <div>
                    <div class="fw-semibold mb-2">Tiến trình công đoạn</div>
                    ${timelineHtml}
                </div>

                <div>
                    <div class="fw-semibold mb-2">Nhật ký công đoạn</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle log-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Thời gian</th>
                                    <th>Công đoạn</th>
                                    <th>Trạng thái</th>
                                    <th>Số lượng</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>${logsHtml}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    };

    const setActiveRow = (row, rows) => {
        rows.forEach((r) => r.classList.remove("active"));
        row.classList.add("active");
    };

    const showLoading = () => {
        detailContent.innerHTML = `
            <div class="loading-state py-4">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div>Đang tải chi tiết...</div>
            </div>`;
        detailModal?.show();
    };

    const showError = (message) => {
        detailContent.innerHTML = `
            <div class="error-state py-4 text-danger">
                <div class="fw-semibold mb-1">Không tải được chi tiết</div>
                <div class="small">${escapeHtml(message)}</div>
            </div>`;
        detailModal?.show();
    };

    const renderInlineQrs = () => {
        if (typeof QRCode === "undefined") {
            return;
        }

        document.querySelectorAll(".qr-img").forEach((node) => {
            const text = node.dataset.qr || "";
            if (!text) return;

            if (node.id === "qr-detail-image") {
                if (node.dataset.rendered === text) return;
                node.innerHTML = "";
            } else if (node.dataset.rendered) {
                return;
            }

            new QRCode(node, {
                text,
                width: node.id === "qr-detail-image" ? 90 : 60,
                height: node.id === "qr-detail-image" ? 90 : 60,
                margin: 0,
            });

            node.dataset.rendered = node.id === "qr-detail-image" ? text : "1";
        });
    };

    const loadDetail = async (qrText) => {
        if (!detailUrlTemplate || !qrText) return;
        showLoading();

        try {
            const url = detailUrlTemplate.replace(
                ":id",
                encodeURIComponent(qrText)
            );
            const response = await fetch(url, {
                headers: { Accept: "application/json" },
            });

            if (!response.ok) {
                throw new Error(`Server trả về ${response.status}`);
            }

            const data = await response.json();
            detailContent.innerHTML = renderDetail(data);
            renderInlineQrs();
            detailModal?.show();
        } catch (error) {
            showError(error.message || "Đã có lỗi xảy ra.");
        }
    };

    document.addEventListener("DOMContentLoaded", () => {
        const rows = Array.from(document.querySelectorAll(".qr-row"));
        if (!rows.length) return;

        detailModal = detailModalEl ? new bootstrap.Modal(detailModalEl) : null;
        renderInlineQrs();

        rows.forEach((row) => {
            row.addEventListener("click", () => {
                setActiveRow(row, rows);
                loadDetail(row.dataset.qrText);
            });
        });
    });
})();
