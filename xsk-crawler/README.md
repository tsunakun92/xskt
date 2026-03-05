## XSK Crawler (Node + MySQL)

Tool cá nhân để crawl kết quả xổ số kiến thiết 3 miền (Bắc / Trung / Nam) Việt Nam bằng Node.js, lưu vào MySQL, chuẩn bị sẵn cho việc đọc dữ liệu từ Laravel sau này.

### 1. Cấu trúc thư mục

```text
xsk-crawler/
  package.json
  README.md
  sql/
    schema.sql
  src/
    index.js
    config/
      db.js
      sources.js
    core/
      constants.js
      reconciler.js
      sanityCheck.js
    sources/
      xosoComVn.js
    jobs/
      crawlXsmbJob.js
```

### 2. Thiết lập MySQL

1. Tạo database, ví dụ:

```sql
CREATE DATABASE xsk_crawler CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Chạy file `sql/schema.sql` để tạo các bảng cần thiết.

### 3. Cài đặt dependencies

```bash
cd xsk-crawler
npm install
```

### 4. Cấu hình kết nối DB

Tạo file `.env` trong thư mục `xsk-crawler` (cùng cấp `package.json`) với nội dung tương tự:

```text
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=secret
DB_NAME=xsk_crawler
```

### 5. Chạy thử crawler XSMB mẫu

Crawler mẫu hiện chỉ minh họa flow end-to-end (kết nối, gọi HTTP, parse khung cơ bản, lưu snapshot) cho XSMB từ `xoso.com.vn`. Bạn cần điều chỉnh selector/logic parse phù hợp với HTML thực tế của website.

```bash
node src/jobs/crawlXsmbJob.js
```

Sau khi mọi thứ ổn, bạn có thể cấu hình cron trong `src/index.js` để tự động chạy theo lịch.


