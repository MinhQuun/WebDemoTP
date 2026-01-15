USE TPBS
SELECT * FROM TPSFO

SELECT * FROM IIM

SELECT * FROM TPFK

-- BƯỚC LẤY DỮ LIỆU SỐ MÁY ĐỂ GHÉP VÀO

-- A) Xác nhận “khóa join” đúng giữa Tickets và TPSFO
USE TPBS;
GO

DECLARE @mahang VARCHAR(20) = '3106407';

-- 1) TPSFO match theo SOPOI?
SELECT TOP 20 *
FROM dbo.TPSFO
WHERE CAST(SOPOI AS VARCHAR(50)) = @mahang
ORDER BY ngaytao DESC;

-- 2) TPSFO match theo SOITN (nhiều hệ thống có kiểu SOITN = mahang + '0' hoặc biến thể)
SELECT TOP 20 *
FROM dbo.TPSFO
WHERE CAST(SOITN AS VARCHAR(50)) LIKE @mahang + '%'
ORDER BY ngaytao DESC;

-- B — Tạo view map “mahang -> máy” (1 mahang chỉ còn 1 máy)
USE TPBS;
GO

CREATE OR ALTER VIEW dbo.vw_item_machine
AS
WITH src AS (
    SELECT
        CAST(SOPOI AS VARCHAR(50)) AS mahang,
        LTRIM(RTRIM(SOMCN))        AS may,
        SODTE                      AS ref_time,
        khoatam                    AS ref_id
    FROM dbo.TPSFO
    WHERE SOMCN IS NOT NULL AND LTRIM(RTRIM(SOMCN)) <> ''

    UNION ALL

    -- Nguồn phụ: theo SOITN (trường hợp SOITN = mahang + '0')
    SELECT
        CASE
            WHEN RIGHT(CAST(SOITN AS VARCHAR(50)), 1) = '0'
                 THEN LEFT(CAST(SOITN AS VARCHAR(50)), LEN(CAST(SOITN AS VARCHAR(50))) - 1)
            ELSE CAST(SOITN AS VARCHAR(50))
        END AS mahang,
        LTRIM(RTRIM(SOMCN)) AS may,
        SODTE               AS ref_time,
        khoatam             AS ref_id
    FROM dbo.TPSFO
    WHERE SOITN IS NOT NULL
      AND SOMCN IS NOT NULL AND LTRIM(RTRIM(SOMCN)) <> ''
),
ranked AS (
    SELECT
        mahang, may, ref_time,
        ROW_NUMBER() OVER (
            PARTITION BY mahang
            ORDER BY ref_time DESC, ref_id DESC
        ) AS rn
    FROM src
)
SELECT mahang, may, ref_time
FROM ranked
WHERE rn = 1;
GO

-- C - Test join lấy “máy” từ Tickets
USE TPBS;
GO

SELECT TOP 50
    t.ngaytao,
    t.ticketid,
    t.sodh,
    t.mahang,
    n.tenhang,
    m.may AS ten_may
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_name    n ON n.mahang = CAST(t.mahang AS VARCHAR(50))
LEFT JOIN dbo.vw_item_machine m ON m.mahang = CAST(t.mahang AS VARCHAR(50))
ORDER BY t.ngaytao DESC;

-- D — Kiểm tra “không nở dòng”
USE TPBS;
GO

-- số dòng gốc
SELECT COUNT(*) AS tickets_rows
FROM dbo.Tickets;

-- số dòng sau join máy
SELECT COUNT(*) AS joined_rows_machine
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_machine m
  ON m.mahang = CAST(t.mahang AS VARCHAR(50));

-- số dòng sau join tên hàng + máy
SELECT COUNT(*) AS joined_rows_both
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_name n
  ON n.mahang = CAST(t.mahang AS VARCHAR(50))
LEFT JOIN dbo.vw_item_machine m
  ON m.mahang = CAST(t.mahang AS VARCHAR(50));

-- Kiểm tra coverage: có bao nhiêu ticket chưa ra máy?
SELECT
  SUM(CASE WHEN m.may IS NULL THEN 1 ELSE 0 END) AS null_may,
  COUNT(*) AS total
FROM dbo.Tickets t
LEFT JOIN dbo.vw_item_machine m
  ON m.mahang = CAST(t.mahang AS VARCHAR(50));
