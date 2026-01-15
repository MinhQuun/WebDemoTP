CREATE LOGIN laravel_user WITH PASSWORD = '123456';
GO
USE [TPBS];
CREATE USER laravel_user FOR LOGIN laravel_user;
EXEC sp_addrolemember 'db_datareader', 'laravel_user';
EXEC sp_addrolemember 'db_datawriter', 'laravel_user';
-- nếu demo cần full quyền thì tạm thời:
-- EXEC sp_addrolemember 'db_owner', 'laravel_user';
GO

-- 1. TÌM BẢNG CÓ CHỨA MÃ

USE TPBS;
GO

/* (0) Tham số để lọc */
DECLARE @expected_rows BIGINT = 9580;   -- trên web hiển thị "Danh sách: 9,580"
DECLARE @top INT = 30;                  -- lấy top 30 bảng ứng viên

/* =========================
   (1) XẾP HẠNG BẢNG ỨNG VIÊN theo "tên cột giống UI" + rowcount gần 9,580
   ========================= */
;WITH rowcnt AS (
    SELECT
        s.name AS schema_name,
        t.name AS table_name,
        SUM(ps.row_count) AS rows
    FROM sys.tables t
    JOIN sys.schemas s ON s.schema_id = t.schema_id
    JOIN sys.dm_db_partition_stats ps ON ps.object_id = t.object_id AND ps.index_id IN (0,1)
    GROUP BY s.name, t.name
),
colscore AS (
    SELECT
        s.name AS schema_name,
        t.name AS table_name,
        -- chấm điểm theo cột thường có ở màn hình: ngày/qr/đơn hàng/mã hàng/tên hàng/mắt/tt/số tấm/giá/ghi chú/người tạo/công đoạn
        SUM(
            CASE
                WHEN LOWER(c.name) LIKE '%qr%' OR LOWER(c.name) LIKE '%qrcode%' OR LOWER(c.name) LIKE '%tem%' OR LOWER(c.name) LIKE '%code%' THEN 6
                WHEN LOWER(c.name) LIKE '%ngay%' OR LOWER(c.name) LIKE '%date%' OR LOWER(c.name) LIKE '%created%' OR LOWER(c.name) LIKE '%time%' THEN 4
                WHEN LOWER(c.name) LIKE '%don%' OR LOWER(c.name) LIKE '%order%' THEN 4
                WHEN LOWER(c.name) LIKE '%mahang%' OR LOWER(c.name) LIKE '%product%' OR LOWER(c.name) LIKE '%item%' THEN 4
                WHEN LOWER(c.name) LIKE '%tenhang%' OR LOWER(c.name) LIKE '%name%' THEN 3
                WHEN LOWER(c.name) LIKE '%mat%' THEN 2
                WHEN LOWER(c.name) = 'tt' OR LOWER(c.name) LIKE '%tt%' THEN 2
                WHEN LOWER(c.name) LIKE '%sotam%' OR LOWER(c.name) LIKE '%so_tam%' OR LOWER(c.name) LIKE '%qty%' OR LOWER(c.name) LIKE '%soluong%' THEN 2
                WHEN LOWER(c.name) LIKE '%gia%' OR LOWER(c.name) LIKE '%price%' THEN 2
                WHEN LOWER(c.name) LIKE '%ghichu%' OR LOWER(c.name) LIKE '%note%' THEN 1
                WHEN LOWER(c.name) LIKE '%nguoi%' OR LOWER(c.name) LIKE '%user%' OR LOWER(c.name) LIKE '%admin%' OR LOWER(c.name) LIKE '%staff%' OR LOWER(c.name) LIKE '%createby%' THEN 3
                WHEN LOWER(c.name) LIKE '%congdoan%' OR LOWER(c.name) LIKE '%stage%' OR LOWER(c.name) LIKE '%process%' THEN 3
                ELSE 0
            END
        ) AS score,
        STRING_AGG(
            CASE
                WHEN LOWER(c.name) LIKE '%qr%' OR LOWER(c.name) LIKE '%qrcode%' OR LOWER(c.name) LIKE '%tem%' OR LOWER(c.name) LIKE '%code%'
                  OR LOWER(c.name) LIKE '%ngay%' OR LOWER(c.name) LIKE '%date%' OR LOWER(c.name) LIKE '%created%' OR LOWER(c.name) LIKE '%time%'
                  OR LOWER(c.name) LIKE '%don%' OR LOWER(c.name) LIKE '%order%'
                  OR LOWER(c.name) LIKE '%mahang%' OR LOWER(c.name) LIKE '%product%' OR LOWER(c.name) LIKE '%item%'
                  OR LOWER(c.name) LIKE '%tenhang%' OR LOWER(c.name) LIKE '%name%'
                  OR LOWER(c.name) LIKE '%mat%'
                  OR LOWER(c.name) = 'tt' OR LOWER(c.name) LIKE '%tt%'
                  OR LOWER(c.name) LIKE '%sotam%' OR LOWER(c.name) LIKE '%so_tam%' OR LOWER(c.name) LIKE '%qty%' OR LOWER(c.name) LIKE '%soluong%'
                  OR LOWER(c.name) LIKE '%gia%' OR LOWER(c.name) LIKE '%price%'
                  OR LOWER(c.name) LIKE '%ghichu%' OR LOWER(c.name) LIKE '%note%'
                  OR LOWER(c.name) LIKE '%nguoi%' OR LOWER(c.name) LIKE '%user%' OR LOWER(c.name) LIKE '%admin%' OR LOWER(c.name) LIKE '%staff%' OR LOWER(c.name) LIKE '%createby%'
                  OR LOWER(c.name) LIKE '%congdoan%' OR LOWER(c.name) LIKE '%stage%' OR LOWER(c.name) LIKE '%process%'
                THEN c.name ELSE NULL END
        , ', ') AS matched_cols
    FROM sys.tables t
    JOIN sys.schemas s ON s.schema_id = t.schema_id
    JOIN sys.columns c ON c.object_id = t.object_id
    GROUP BY s.name, t.name
)
SELECT TOP (@top)
    cs.schema_name,
    cs.table_name,
    rc.rows,
    cs.score,
    ABS(ISNULL(rc.rows,0) - @expected_rows) AS rows_distance,
    cs.matched_cols
FROM colscore cs
LEFT JOIN rowcnt rc ON rc.schema_name = cs.schema_name AND rc.table_name = cs.table_name
WHERE cs.score >= 12  -- điểm tối thiểu để loại bớt bảng rác
ORDER BY cs.score DESC, rows_distance ASC, rc.rows DESC;
GO

USE TPBS;
GO
EXEC sp_help 'dbo.Tickets';

-- 2. TÌM BẢNG CÓ CHỨA TÊN HÀNG
USE TPBS;
GO

;WITH C AS (
    SELECT
        s.name  AS schema_name,
        t.name  AS table_name,
        LOWER(c.name) AS col
    FROM sys.tables t
    JOIN sys.schemas s ON s.schema_id = t.schema_id
    JOIN sys.columns c ON c.object_id = t.object_id
)
SELECT TOP 50
    schema_name,
    table_name,
    STRING_AGG(col, ', ') AS cols
FROM C
GROUP BY schema_name, table_name
HAVING
    -- phải có cột mã hàng (hoặc gần giống)
    SUM(CASE WHEN col LIKE '%mahang%' OR col LIKE '%product%' OR col LIKE '%item%' THEN 1 ELSE 0 END) > 0
    AND
    -- phải có cột tên hàng (hoặc tên sp)
    SUM(CASE WHEN col LIKE '%tenhang%' OR col LIKE '%tensp%' OR col LIKE '%itemname%' OR col LIKE '%productname%' OR col LIKE '%name%' THEN 1 ELSE 0 END) > 0
ORDER BY table_name;

--2.1. Tự động tìm bảng nào chứa mahang = 3215045 và trả về “Tên hàng” mẫu
USE TPBS;
GO

DECLARE @v NVARCHAR(50) = N'3215045'; -- đổi theo Tickets.mahang bạn muốn test

IF OBJECT_ID('tempdb..#cand') IS NOT NULL DROP TABLE #cand;
CREATE TABLE #cand(
  schema_name SYSNAME,
  table_name  SYSNAME,
  mahang_col  SYSNAME,
  ten_col     SYSNAME
);

;WITH T AS (
  SELECT
    s.name AS schema_name,
    t.name AS table_name,
    c.name AS col_name
  FROM sys.tables t
  JOIN sys.schemas s ON s.schema_id = t.schema_id
  JOIN sys.columns c ON c.object_id = t.object_id
)
INSERT INTO #cand(schema_name, table_name, mahang_col, ten_col)
SELECT
  x.schema_name,
  x.table_name,
  MAX(CASE WHEN x.col_name LIKE '%mahang%' OR x.col_name LIKE '%product%' OR x.col_name LIKE '%item%' THEN x.col_name END) AS mahang_col,
  MAX(CASE WHEN x.col_name LIKE '%tenhang%' OR x.col_name LIKE '%tensp%' OR x.col_name LIKE '%itemname%' OR x.col_name LIKE '%productname%' OR x.col_name LIKE '%name%' THEN x.col_name END) AS ten_col
FROM T x
GROUP BY x.schema_name, x.table_name
HAVING
  SUM(CASE WHEN x.col_name LIKE '%mahang%' OR x.col_name LIKE '%product%' OR x.col_name LIKE '%item%' THEN 1 ELSE 0 END) > 0
  AND
  SUM(CASE WHEN x.col_name LIKE '%tenhang%' OR x.col_name LIKE '%tensp%' OR x.col_name LIKE '%itemname%' OR x.col_name LIKE '%productname%' OR x.col_name LIKE '%name%' THEN 1 ELSE 0 END) > 0;

IF OBJECT_ID('tempdb..#found') IS NOT NULL DROP TABLE #found;
CREATE TABLE #found(
  schema_name SYSNAME,
  table_name  SYSNAME,
  mahang_col  SYSNAME,
  ten_col     SYSNAME,
  hit_count   INT,
  sample_ten  NVARCHAR(200)
);

DECLARE @sql NVARCHAR(MAX) = N'';

SELECT @sql = @sql + N'
BEGIN TRY
  INSERT INTO #found(schema_name, table_name, mahang_col, ten_col, hit_count, sample_ten)
  SELECT
    N''' + c.schema_name + N''',
    N''' + c.table_name  + N''',
    N''' + c.mahang_col  + N''',
    N''' + c.ten_col     + N''',
    COUNT(*) AS hit_count,
    MAX(LEFT(CAST(' + QUOTENAME(c.ten_col) + N' AS NVARCHAR(200)), 200)) AS sample_ten
  FROM ' + QUOTENAME(c.schema_name) + N'.' + QUOTENAME(c.table_name) + N'
  WHERE
    LTRIM(RTRIM(CAST(' + QUOTENAME(c.mahang_col) + N' AS NVARCHAR(50)))) = LTRIM(RTRIM(@v))
    OR TRY_CONVERT(INT, ' + QUOTENAME(c.mahang_col) + N') = TRY_CONVERT(INT, @v);
END TRY
BEGIN CATCH
END CATCH;'
FROM #cand c;

EXEC sp_executesql @sql, N'@v NVARCHAR(50)', @v=@v;

SELECT *
FROM #found
WHERE hit_count > 0
ORDER BY hit_count DESC, schema_name, table_name;
GO

SELECT name, compatibility_level
FROM sys.databases
WHERE name = 'TPBS';

ALTER DATABASE TPBS SET COMPATIBILITY_LEVEL = 150;
GO

-- tbldongkien có trùng mahang không?
SELECT TOP 20 mahang, COUNT(*) AS cnt
FROM dbo.tbldongkien
GROUP BY mahang
HAVING COUNT(*) > 1
ORDER BY cnt DESC;

-- tblphieugiaohang có trùng mahang không?
SELECT TOP 20 mahang, COUNT(*) AS cnt
FROM dbo.tblphieugiaohang
GROUP BY mahang
HAVING COUNT(*) > 1
ORDER BY cnt DESC;

USE TPBS;
GO

CREATE OR ALTER VIEW dbo.vw_item_name AS
WITH dk AS (
    SELECT mahang, MAX(tenhang) AS tenhang
    FROM dbo.tbldongkien
    GROUP BY mahang
),
pg AS (
    SELECT mahang, MAX(tenhang) AS tenhang
    FROM dbo.tblphieugiaohang
    GROUP BY mahang
)
SELECT
    COALESCE(dk.mahang, pg.mahang) AS mahang,
    COALESCE(dk.tenhang, pg.tenhang) AS tenhang
FROM dk
FULL JOIN pg ON pg.mahang = dk.mahang;
GO

SELECT TOP 50
    t.ngaytao,
    t.ticketid,
    t.sodh,
    t.mahang,
    n.tenhang AS ten_hang,
    t.dongia,
    t.ghichu
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_name n ON n.mahang = t.mahang
ORDER BY t.ngaytao DESC;

-- số dòng Tickets gốc
SELECT COUNT(*) AS tickets_rows FROM dbo.Tickets;

-- số dòng sau join (nếu join đúng qua vw_item_name thì phải bằng tickets_rows)
SELECT COUNT(*) AS joined_rows
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_name n ON n.mahang = t.mahang;


USE TPBS;
GO

CREATE OR ALTER VIEW dbo.vw_qr_list AS
SELECT
    t.ngaytao AS ngay_tao,
    t.ticketid AS qr_text,
    t.sodh AS don_hang,
    t.mahang AS ma_hang,
    n.tenhang AS ten_hang,
    ISNULL(t.dongia, 0) AS gia,
    t.ghichu AS ghi_chu,
    a.FullName AS nguoi_tao,

    CASE
        WHEN t.xuatkho IS NOT NULL AND t.xuatkho <> 0 THEN N'Xuất kho'
        WHEN t.kiemtrafinal IS NOT NULL THEN N'Kiểm tra final'
        WHEN t.kiemtradongkien IS NOT NULL THEN N'Kiểm tra đóng kiện'
        WHEN t.ghinhandongkien IS NOT NULL THEN N'Ghi nhận đóng kiện'
        WHEN t.kiemtrahapluoi IS NOT NULL THEN N'Kiểm tra hấp lưới'
        WHEN t.ghinhanhapluoi IS NOT NULL THEN N'Ghi nhận hấp lưới'
        WHEN t.kiemtranoiluoi IS NOT NULL THEN N'Kiểm tra nối lưới'
        WHEN t.ghinhannoiluoi IS NOT NULL THEN N'Ghi nhận nối lưới'
        WHEN t.kiemtradet IS NOT NULL THEN N'Kiểm tra dệt'
        WHEN t.ghinhandet IS NOT NULL THEN N'Ghi nhận dệt'
        ELSE N'Tạo QR'
    END AS cong_doan_hien_tai
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_name n ON n.mahang = t.mahang
LEFT JOIN dbo.Admins a ON a.AdminID = t.nguoitao;
GO

SELECT
  @@SERVERNAME AS servername,
  SERVERPROPERTY('InstanceName') AS instance_name,
  @@SERVICENAME AS service_name;


