# SỔ TAY HƯỚNG DẪN DEVELOPER (SOP): THIẾT LẬP, ĐÓNG GÓI & TRIỂN KHAI MEKO-LMS THEME

> **Dự án:** MEKO-LMS / ACCEES LMS Ecosystem (Erasmus+ CBHE)  
> **Phiên bản tài liệu:** v1.2.1 — 11/11/2026  
> **Áp dụng cho:** Toàn bộ Lập trình viên Frontend, Backend & DevOps phụ trách phân hệ Theme (`theme_meko`)

---

## 1. TỔNG QUAN HỆ THỐNG & ĐỊNH DANH MÃ NGUỒN

* **Mã Module Dự Án:** `accees-lms-theme`
* **Moodle Frankenstyle Component:** `theme_meko`
* **Thư mục cài đặt trong Moodle:** `/var/www/html/theme/meko` (hoặc `/var/www/moodle/public/theme/meko`)
* **Nền tảng công nghệ:** Moodle 5.3 LTS, PostgreSQL 17, PHP 8.4, Bootstrap 5.3, Mustache Templates, SCSS
* **Quy chuẩn nhận diện thương hiệu:**
  - **100% Solid Color (Không dùng Gradient):** 
    + Primary Navy: `#0000B6` / `#182B89` / `#1E2F97`
    + Warm Orange (Accent/CTA): `#EA6400` / `#F57C00`
    + Success Green: `#39B500` / `#16A34A`
    + Dark Slate: `#0F172A`
  - **Typography:** Phông chữ `Be Vietnam Pro` (Google Fonts).
  - **Quy tắc 0% Emoji:** Tuyệt đối cấm sử dụng Emoji trong code, UI và tài liệu (thay thế bằng icon Font Awesome 6 hoặc SVG phẳng).

---

## 2. QUY TRÌNH 6 BƯỚC KHÉP KÍN (DEVELOPER PIPELINE)

1. **Bước 1: Setup Local Environment** (Docker + PostgreSQL 17 + Moodle 5.3)
2. **Bước 2: 1-Click Deploy Theme** (`.\deploy_local.ps1`)
3. **Bước 3: Nạp Dữ Liệu Mẫu (Golden Seed)** (12 Khóa học 16:9 + Năng lực DigComp)
4. **Bước 4: Kiểm Thử Giao Diện & E2E** (Trình duyệt & Playwright)
5. **Bước 5: Đóng Gói Bản Release (.zip)** (`.\package_theme.ps1`)
6. **Bước 6: Triển Khai Staging / Production** (CLI Upgrade & Purge Cache)

---

## 3. BƯỚC 1: THIẾT LẬP MÔI TRƯỜNG MÁY DEV CÁ NHÂN (LOCAL DOCKER)

Để tránh tình trạng Docker Desktop làm **đầy ổ C:** (ổ cài Windows), toàn bộ dữ liệu máy ảo và Docker Image phải được lưu trên ổ đĩa dữ liệu (ví dụ: ổ `E:\`).

### 3.1 Bật tính năng ảo hóa Windows & WSL2
Chạy PowerShell với quyền Administrator (Run as Administrator):
```powershell
dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart
dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart
```
*Sau khi chạy xong, khởi động lại máy tính (Restart PC).*

### 3.2 Cấu hình Docker Desktop lưu trữ trên ổ E:
1. Mở **Docker Desktop** ➔ Chọn biểu tượng **Settings (Bánh răng)**.
2. Vào mục **Resources** ➔ **Advanced** (hoặc **VM resources**).
3. Tại ô **Disk image location**, bấm **Browse...** và chọn thư mục: `E:\DockerData\DockerDesktopWSL`.
4. Bấm **Apply & restart**. Toàn bộ file ổ đĩa ảo `.vhdx` nặng hàng chục GB sẽ nằm trên ổ `E:\`, bảo vệ an toàn cho ổ `C:\`.

### 3.3 Khởi chạy cụm Container Moodle 5.3 + PostgreSQL 17
Tại thư mục gốc dự án, di chuyển vào thư mục `local_dev` và khởi động container:
```powershell
cd local_dev
docker compose up -d
```
Cụm container gồm:
* `accees-postgres-cluster`: PostgreSQL 17 (Cổng nội bộ `5432`)
* `accees-moodle-app`: Moodle 5.3 LTS (Ánh xạ cổng host: `http://localhost:8089`)

---

## 4. BƯỚC 2 & 3: 1-CLICK DEPLOY THEME & NẠP DỮ LIỆU MẪU

Chúng tôi đã đóng gói toàn bộ quy trình copy mã nguồn, phân quyền `www-data`, tạo symlink tương thích, kích hoạt theme và bơm dữ liệu vào **1 script duy nhất**:

### Dành cho Windows (PowerShell):
```powershell
# Chạy ngay tại thư mục gốc theme_meko:
.\deploy_local.ps1 -Container "accees-moodle-app" -Port "8089"
```

### Dành cho Linux / macOS / WSL (Bash):
```bash
chmod +x ./deploy_local.sh
./deploy_local.sh --container "accees-moodle-app" --port "8089"
```

### Dữ liệu mẫu tự động được tạo sẵn:
1. **12 Khóa học tiêu chuẩn MEKO-LMS** với ảnh bìa tỷ lệ vàng `16:9` chuẩn thiết kế.
2. **Khung Năng Lực Số (CBE)**: 5 miền sinh viên (DigComp 2.2) và 6 miền giảng viên (DigCompEdu).
3. **Bộ tài khoản kiểm thử phân quyền đầy đủ:**
   * **Quản trị viên (Admin):** `admin` / `AdminMoodle2026!@#`
   * **Tech Lead:** `pqhuy` / `PqHuy2026!@#`
   * **Giảng viên:** `teacher1` / `Teacher2026!@#` *(được gán dạy 4 môn)*
   * **Sinh viên:** `student1` / `Student2026!@#` *(được ghi danh học 6 môn)*

---

## 5. BƯỚC 4: KỊCH BẢN KIỂM THỬ (TEST CASES CHECKLIST)

Mỗi developer sau khi sửa đổi mã nguồn bắt buộc phải kiểm tra thông suốt 5 màn hình chính tại `http://localhost:8089`:

| STT | Màn hình kiểm thử | URL | Tiêu chí nghiệm thu (Acceptance Criteria) |
| :---: | :--- | :--- | :--- |
| **TC-01** | **Trang chủ (Frontpage)** | `/` | • Logo ACCEES/MEKO nét, đúng tỉ lệ.<br>• Chuyển đổi tab Sinh viên (5 miền) & Giảng viên (6 miền) mượt mà.<br>• **Footer:** 3 icon mạng xã hội kích thước cố định `18px x 18px`.<br>• **Tuyệt đối không còn mã chuỗi dạng `[[...]]`**.<br>• **Không có viền nét đứt đỏ `Deprecated style in use`**. |
| **TC-02** | **Danh mục Năng lực số** | `/course/index.php` | • Danh sách 12 khóa học hiển thị chuẩn thẻ Card, ảnh bìa 16:9.<br>• Bộ lọc Năng lực số (DigComp) hoạt động chính xác. |
| **TC-03** | **Đăng nhập (Login)** | `/login/index.php` | • Form đăng nhập tinh gọn (Clean & Streamlined).<br>• Đăng nhập thành công với tài khoản `student1` và `admin`. |
| **TC-04** | **Bàn làm việc sinh viên** | `/my/` | • Thống kê tiến độ (Đang học, Hoàn thành, Chưa bắt đầu).<br>• Hiển thị số lượng Huy hiệu số (Digital Badges). |
| **TC-05** | **Chi tiết khóa học** | `/course/view.php?id=2` | • Header khóa học, danh sách bài giảng, chuẩn đầu ra CBE. |

---

## 6. BƯỚC 5: ĐÓNG GÓI BẢN RELEASE THEME (.ZIP)

Khi tính năng đã hoàn thiện và kiểm thử đạt yêu cầu, chạy script đóng gói tự động:

### Lệnh đóng gói trên Windows:
```powershell
.\package_theme.ps1
```

### Lệnh đóng gói trên Linux / macOS:
```bash
chmod +x ./package_theme.sh
./package_theme.sh
```

### Kết quả xuất ra:
File ZIP được tạo tự động tại thư mục `releases/` (ví dụ: `releases/theme_meko_2026110907.zip`), dung lượng ~1.4 MB:
* **Cấu trúc bên trong file ZIP bắt buộc:**
  ```text
  theme_meko_2026110907.zip
  └── meko/
      ├── classes/
      ├── lang/
      ├── layout/
      ├── pix/
      ├── scss/
      ├── templates/
      ├── config.php
      ├── lib.php
      ├── settings.php
      ├── styles.css
      └── version.php
  ```
* Toàn bộ thư mục rác `.git/`, `local_dev/`, `test-results/`, `*.draft` đều được tự động loại bỏ.

---

## 7. BƯỚC 6: TRIỂN KHAI LÊN SERVER STAGING / PRODUCTION

### Cách 1: Triển khai qua lệnh CLI (Khuyên dùng 100% cho DevOps)
1. Đẩy mã nguồn vào máy chủ qua Git hoặc rsync:
   ```bash
   cd /var/www/html/theme/meko
   git pull origin develop
   ```
2. Thực thi 2 lệnh CLI nội bộ Moodle:
   ```bash
   # Tự động migrate CSDL theo version.php
   php /var/www/html/admin/cli/upgrade.php --non-interactive

   # Xóa sạch cache để giao diện mới có hiệu lực tức thì
   php /var/www/html/admin/cli/purge_caches.php
   ```

### Cách 2: Triển khai qua giao diện Moodle Admin (Web UI)
1. Đăng nhập quyền `admin` trên trang web Moodle.
2. Vào **Site administration** ➔ **Plugins** ➔ **Install plugins** (`/admin/tool/installaddon/index.php`).
3. Kéo thả file `theme_meko_xxx.zip` vào và bấm **"Install plugin from the ZIP file"**.
4. Bấm **"Upgrade Moodle database now"** để hoàn tất.

---

## 8. SỔ TAY XỬ LÝ LỖI (TROUBLESHOOTING KNOWLEDGE BASE)

| Mã lỗi / Hiện tượng | Nguyên nhân gốc rễ | Cách xử lý dứt điểm |
| :--- | :--- | :--- |
| **Ổ C bị đầy đột ngột (< 2 GB)** | Docker Desktop lưu file ảo `ext4.vhdx` và installer cache tại `%TEMP%`. | Chuyển vị trí Virtual Disk sang `E:\DockerData\DockerDesktopWSL` trong Docker Settings. Xóa các file `.d4w` trong `%TEMP%`. |
| **WSL báo `Catastrophic failure`** | Tính năng `VirtualMachinePlatform` bị tắt trong BIOS hoặc Windows Features. | Chạy lệnh `dism.exe` bật tính năng ảo hóa và khởi động lại Windows. |
| **Lỗi HTTP 500: `Component was not valid: theme_accees_lms_theme`** | Layout gọi `$OUTPUT->render_from_template('theme_accees_lms_theme/...')` khi cài đặt dưới tên `meko`. | Dùng tên component động: `$themename = $PAGE->theme->name; $OUTPUT->render_from_template('theme_' . $themename . '/navbar', ...);`. |
| **Viền đỏ nét đứt: `Deprecated style in use (.mr, .ml, .text-left)`** | Moodle 5.3 chạy Bootstrap 5, quét và cảnh báo khi phát hiện các class căn lề cũ của Bootstrap 4. | Thay thế toàn bộ: `.mr-*` ➔ `.me-*`, `.ml-*` ➔ `.ms-*`, `.text-left` ➔ `.text-start`. |
| **Lỗi `Must use https address in wwwroot when ssl proxy enabled!`** | Cấu hình database mang từ production có bật `$CFG->sslproxy = true;`. | Trong môi trường local, đặt `$CFG->sslproxy = false;` trong file `config.php`. |
| **PowerShell báo lỗi `The string is missing the terminator: "`** | File `.ps1` chứa tiếng Việt lưu chuẩn UTF-8 No BOM bị Windows-1252 đọc sai ký tự nháy `0x93`. | Luôn lưu các file PowerShell script dưới định dạng **UTF-8 with BOM (`utf-8-sig`)**. |

