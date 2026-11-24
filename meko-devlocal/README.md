# MEKOSOFT DevLocal Stack (`meko.localhost`)

Môi trường Docker Local chuẩn dùng chung cho toàn bộ team Dev trên **Docker Desktop** (Windows / macOS / Linux):
- **Liferay 7.4 GA132 CE** (`/` CMS & `/tms` phân hệ TMS)
- **Moodle 5.3 LTS** (`/lms/` phân hệ LMS dùng trực tiếp `../meko-lms/theme_meko` và `../meko-lms/local/meko_competencies`, brand `meko`)
- **Mailpit** (`/mail/` hoặc `localhost:8025` — bắt toàn bộ email test SMTP nội bộ)
- **PostgreSQL 17** (1 instance dùng chung chứa 2 database tách biệt: `lportal` và `accees_lms`)
- **Nginx Gateway** (cổng `80`, định tuyến `meko.localhost` + trang `/dev-info`)

> **Tại sao dùng `meko.localhost`?** Theo chuẩn **RFC 6761**, mọi tên miền `*.localhost` tự động phân giải về `127.0.0.1` trên Chrome, Edge, Firefox và `curl`/`Invoke-WebRequest` mà **không cần quyền Administrator để sửa file `hosts`**, đồng thời **tránh 100% việc DNS-over-HTTPS trỏ nhầm ra domain thật `mekosoft.vn`**.

---

## 1. Cấu trúc cổng & Đường dẫn

| Dịch vụ / Phân hệ | URL / Host | Container | Ghi chú |
| :--- | :--- | :--- | :--- |
| **Liferay CMS** | `http://meko.localhost/` | `devlocal-liferay` | Trang CMS gốc của Liferay |
| **Liferay TMS** | `http://meko.localhost/tms` | `devlocal-liferay` | Tự động chuyển sang `/web/tms` |
| **Moodle 5.3 LMS** | `http://meko.localhost/lms/` | `devlocal-moodle` | Theme `theme_meko` (`brand_profile=meko`) + `local_meko_competencies` |
| **Mailpit Web UI** | `http://meko.localhost/mail/` | `devlocal-mailpit` | SMTP nội bộ `mailpit:1025`, Host UI `localhost:8025` |
| **Dev Info Dashboard** | `http://meko.localhost/dev-info` | `devlocal-gateway` | Bảng thông tin Docker, Port, DB, Tài khoản |
| **PostgreSQL 17** | `localhost:5433` | `devlocal-postgres` | DB `lportal` (`liferay`) & `accees_lms` (`lms_user`) |
| **OSGi Gogo Shell** | `localhost:11311` | `devlocal-liferay` | Debug bundle OSGi |

---

## 2. Hướng dẫn cài đặt 1-Click cho Dev

### Windows PowerShell:
```powershell
.\setup.ps1
# Chỉ chạy Moodle + Postgres + Mailpit + Gateway (nhẹ RAM, ~400 MB, không bật Liferay):
.\setup.ps1 -MoodleOnly
# Nếu muốn kéo code mới nhất từ monorepo meko-dle:
.\setup.ps1 -PullLatest
```

### Linux / macOS:
```bash
chmod +x ./setup.sh
./setup.sh
# Chỉ chạy Moodle + Postgres + Mailpit + Gateway (nhẹ RAM, không bật Liferay):
./setup.sh --moodle-only
# Hoặc kéo mới nhất từ monorepo meko-dle:
./setup.sh --pull-latest
```

### Lưu ý khi máy Dev bị trùng Port `80` (IIS / XAMPP / Laragon)
Script `setup.ps1` tự động kiểm tra nếu có process khác (không phải Docker Desktop) đang giữ port `80`. Nếu bị trùng:
1. Tạo/mở file `.env` (copy từ `.env.example`) và đổi:
   ```env
   HTTP_PORT=8090
   ```
2. Chạy lại `.\setup.ps1` (hoặc `./setup.sh`), sau đó truy cập qua `http://meko.localhost:8090/` (và `/tms`, `/lms/`, `/dev-info`).

> **Lưu ý Docker Desktop:** Nên cấp tối thiểu **6–8 GB RAM** và đặt Disk image location sang ổ `D:\` hoặc `E:\` để tránh đầy ổ `C:\`. Lần đầu khởi tạo Liferay mất ~2–4 phút.

---

## 3. Tài khoản kiểm thử

### Liferay 7.4 GA132
- **Admin:** `test@liferay.com` / `test` *(Liferay yêu cầu đổi mật khẩu ở lần đăng nhập đầu tiên)*

### Moodle 5.3 (`/lms/`)
- **Admin:** `admin` / `AdminMoodle2026!@#`
- **Tech Lead:** `pqhuy` / `PqHuy2026!@#`
- **Giảng viên:** `teacher1` .. `teacher4` / `Teacher2026!@#`
- **Sinh viên:** `student1`, `b2204927` / `Student2026!@#`

### PostgreSQL 17 (`localhost:5433`)
- **Liferay DB:** `lportal` — User: `liferay` / Pass: `LiferayPass2026!@#`
- **Moodle DB:** `accees_lms` — User: `lms_user` / Pass: `LmsPassword2026!@#`
