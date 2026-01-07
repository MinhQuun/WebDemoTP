# WebDemoTP - QR List (SQL Server)

This project shows a QR list page at `/admin/qrs`. It reads data from SQL Server views and renders all stages inline per row.

## Overview
- Route: `GET /admin/qrs`
- Data source: SQL Server view `dbo.vw_qr_list`
- Machine name: joined from `dbo.vw_item_machine`
- QR image: rendered in the browser by `qrcodejs` (CDN)

## Prerequisites
- PHP 8.2+
- Composer
- SQL Server reachable from this machine
- Microsoft ODBC Driver 17 or 18 for SQL Server
- PHP extensions: `pdo_sqlsrv`, `sqlsrv`

## SQL Server Views
### dbo.vw_qr_list (required)
Expected columns:
- `ngay_tao` (datetime)
- `qr_text` (nvarchar)
- `don_hang` (nvarchar)
- `ma_hang` (nvarchar)
- `ten_hang` (nvarchar)
- `gia` (numeric/int)
- `ghi_chu` (nvarchar)
- `nguoi_tao` (nvarchar)
- `cong_doan_hien_tai` (nvarchar)

### dbo.vw_item_machine (required)
Expected columns:
- `mahang` (nvarchar)
- `may` (nvarchar)

The controller joins by `CAST(q.ma_hang AS VARCHAR(50)) = m.mahang`.
Make sure `vw_item_machine` returns **one row per `mahang`** to avoid duplicate rows.

## Setup
1) Install dependencies
```
composer install
php artisan key:generate
```
2) Configure `.env` for SQL Server
```
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=TPBS
DB_USERNAME=your_user
DB_PASSWORD=your_pass
SESSION_DRIVER=file
CACHE_STORE=file
```
3) Clear config cache
```
php artisan config:clear
```
4) Run the app
```
php artisan serve
```
Open: http://127.0.0.1:8000/admin/qrs

## Filters
- `from` and `to`: filter by `ngay_tao` (inclusive end-of-day).
- `keyword`: searches `qr_text`, `don_hang`, `ma_hang`, `ten_hang`, `ghi_chu`, `nguoi_tao`.
- `stage`: exact match on `cong_doan_hien_tai`.

The stage dropdown is built from `QrController::STAGES`. Update this list if your DB uses different labels.

## UI Notes
- Columns shown: QR, Product (code + name + machine), and full stage list.
- Stage chips: done / current / pending colors.
- QR image uses `qrcodejs` CDN, so network access is required. If you want offline use, download the library and serve locally.

## Troubleshooting
- `SQLSTATE[08001] ... target machine actively refused it`:
  - SQL Server not reachable or TCP/IP disabled.
  - Check `DB_HOST`, `DB_PORT`, firewall, and SQL Server service.
- Session query errors:
  - Keep `SESSION_DRIVER=file` for local demo.
- No data:
  - Verify `dbo.vw_qr_list` exists and your SQL user has permission.

## Main Files
- `app/Http/Controllers/Admin/QrController.php` (query + filters)
- `resources/views/admin/qrs/index.blade.php` (UI)
- `public/css/admin-qrs.css` (styles)
- `public/js/admin-qrs.js` (QR rendering)
