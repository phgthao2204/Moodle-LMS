# MEKOSOFT LMS THEME (`theme_meko`)

Bộ nhận diện thương hiệu chính thức cho Hệ thống Đào tạo Số MEKOSOFT (Moodle LMS).

- **GitHub Repository:** `https://github.com/huyriddle/meko-lms-theme.git`
- **Active Dev Branch:** `develop`
- **Moodle Frankenstyle Component:** `theme_meko`
- **Tên thư mục Theme trong Moodle:** `meko`
- **Đường dẫn thư mục cài đặt trên Server/Container:** `/var/www/moodle/public/theme/meko`
- **Theme kế thừa:** `theme_boost` (Moodle Core Child Theme)
- **Tài liệu Dự án (Google Drive):** [Thư mục Tài liệu MEKO-LMS trên Google Drive](https://drive.google.com/drive/folders/1kjLERBL9MXiERgtQ-8j3DcLDqjd3KC5H?usp=drive_link) *(Toàn bộ mockup, đặc tả khung năng lực lưu trữ tập trung tại Drive, không đưa file docs nặng lên Git)*
- **Hệ màu chuẩn 100% Solid Colors:**
  - **Primary (Xanh chủ đạo):** `#1E2F97` (MEKOSOFT Royal Navy Blue)
  - **Accent / CTA (Cam nhấn):** `#F57C00` (MEKOSOFT Warm Orange)
  - **Support / Success (Xanh lá):** `#6F8A4F` & `#16A34A` (MEKO Green)
  - **Dark / Footer:** `#0F172A`
  - **Phông chữ:** `Be Vietnam Pro` (Google Fonts)

> Xem [BRAND_GUIDELINES.md](./BRAND_GUIDELINES.md) để biết quy chuẩn logo, màu sắc, typography, sitemap menu chính và hướng dẫn cài/kích hoạt theme.

---

## PHẦN 1: DÀNH CHO DEVELOPER (LÀM VIỆC TRÊN MÁY TÍNH CÁ NHÂN)

> [!IMPORTANT]
> **Tất cả lập trình viên thực hiện code trên máy tính cá nhân (Local PC)**, sử dụng VS Code hoặc PHPStorm. Tuyệt đối không code trực tiếp trên Server hay Container.

### Bước 1: Clone mã nguồn về máy cá nhân
Mở Terminal / Git Bash / PowerShell trên máy tính cá nhân:
```bash
git clone https://github.com/huyriddle/meko-lms-theme.git meko-lms-theme
cd meko-lms-theme
```
*(Nếu GitHub yêu cầu đăng nhập, nhập username GitHub và dùng **Personal Access Token (PAT)** làm mật khẩu).*

### Bước 2: Tạo nhánh phát triển cá nhân từ `develop`
Tuyệt đối **không code trực tiếp trên `main` hoặc `develop`**. Mỗi bạn tạo nhánh riêng theo phân công:

```bash
# Cập nhật code mới nhất từ develop
git checkout develop
git pull origin develop

# Tạo nhánh tính năng riêng:
git checkout -b feature/<ten-dev>-<tinh-nang>

# Ví dụ mẫu:
# git checkout -b feature/khoa-layout
# git checkout -b feature/yen-header-navbar
# git checkout -b feature/thao-format-tiles
```

### Bước 3: Lập trình và kiểm thử trên máy cá nhân
- Mở thư mục dự án bằng VS Code hoặc PHPStorm.
- Chỉnh sửa SCSS tại `scss/`, Templates Mustache tại `templates/`, Layouts tại `layout/`.
- Tuân thủ chuẩn PSR-12, Logicless Templates, không gõ cứng màu, dùng biến màu MEKO.

### Bước 4: Commit và Push lên GitHub
```bash
git add .
git commit -m "feat: cap nhat giao dien header navbar chuan meko"
git push -u origin feature/<ten-nhanh-cua-ban>
```

### Bước 5: Tạo Pull Request (PR)
1. Truy cập GitHub: `https://github.com/huyriddle/meko-lms-theme`
2. Chọn **Compare & pull request** từ nhánh của bạn vào nhánh **`develop`**.
3. Team Lead (Huy) sẽ review code, nghiệm thu và merge vào hệ thống.

---

## PHẦN 2: DEPLOY VÀ KIỂM THỬ TRÊN CONTAINER MOODLE (LOCAL DEPLOY)

### 🚀 CÁCH 1: 1-CLICK DEPLOY VÀO CONTAINER MOODLE CÓ SẴN (KHUYÊN DÙNG NHẤT)

Dành cho Developer đã có sẵn container Moodle đang chạy trên Docker máy cá nhân. Script sẽ tự động nhận diện container, copy theme vào đúng thư mục, set quyền `www-data`, kích hoạt theme `meko`, bật `themedesignermode` (sửa SCSS/Mustache chỉ cần F5) và nạp 12 khóa học mẫu kèm chuẩn đầu ra năng lực số CBE.

#### Trên Windows (PowerShell):
```powershell
# Chạy trực tiếp tại thư mục gốc theme_meko:
.\deploy_local.ps1

# Hoặc tùy biến tên container/port nếu cần:
.\deploy_local.ps1 -Container "ten_container_moodle" -Port 8088
```

#### Trên Linux / macOS / WSL / Git Bash:
```bash
# Cấp quyền thực thi và chạy:
chmod +x ./deploy_local.sh
./deploy_local.sh

# Hoặc tùy biến:
./deploy_local.sh --container "ten_container_moodle" --port 8088
```

Sau khi chạy xong, mở trình duyệt:
* **URL:** `http://localhost:8088` (hoặc port Moodle trên máy bạn)
* **Tài khoản Admin:** `admin` / `AdminMoodle2026!@#`
* **Tài khoản Giảng viên:** `teacher1` / `Teacher2026!@#`
* **Tài khoản Sinh viên:** `student1` / `Student2026!@#`

---

### 🐳 CÁCH 2: KHỞI TẠO TOÀN BỘ MOODLE DOCKER STACK TỪ ĐẦU (FULL LOCAL STACK)

Nếu máy bạn **chưa có sẵn môi trường Moodle**, sử dụng bộ cài đặt tự động tại thư mục `local_dev/`:

```bash
cd local_dev

# Trên Windows PowerShell:
.\setup_local_dev.ps1

# Trên Linux / macOS:
chmod +x ./setup_local_dev.sh
./setup_local_dev.sh
```
Script sẽ tự động khởi chạy Docker Compose gồm Moodle 5.3 + PostgreSQL 16 và import bản cơ sở dữ liệu mẫu chuẩn (`moodle_golden_seed.sql.gz`).

---

### 🛠️ CÁCH 3: CÁC THAO TÁC THỦ CÔNG (MANUAL) KHI CẦN

Nếu cần thao tác bằng lệnh riêng lẻ trong container:
* **Webroot Moodle:** `/var/www/html` hoặc `/var/www/moodle/public`
* **Thư mục Theme:** `/var/www/html/theme/meko` (bắt buộc tên thư mục là `meko`)
* **Copy thủ công:**
  ```bash
  docker cp . <container_name>:/var/www/html/theme/meko/
  ```
* **Kích hoạt & Xóa cache bằng Moodle CLI:**
  ```bash
  docker exec <container_name> php /var/www/html/admin/cli/cfg.php --name=theme --set=meko
  docker exec <container_name> php /var/www/html/admin/cli/cfg.php --name=themedesignermode --set=1
  docker exec <container_name> php /var/www/html/admin/cli/purge_caches.php
  ```

---

## PHẦN 3: CẤU TRÚC THƯ MỤC MÃ NGUỒN THEME

### Sitemap menu chính

Menu trang chủ được khai báo trong `layout/frontpage.php` và tự ẩn các mục không phù hợp với quyền hiện tại:

| Mục | Đường dẫn Moodle | Hiển thị |
|---|---|---|
| Trang chủ | `/` | Mọi người |
| Danh mục khóa học | `/course/index.php` | Mọi người |
| Khóa học của tôi | `/my/courses.php` | Người dùng đã đăng nhập |
| Khung năng lực số | `/admin/tool/lp/competencyframeworks.php?pagecontextid={system-context-id}` | Người có quyền xem competency, khi tính năng được bật |
| Quản trị hệ thống | `/admin/search.php` | Site administrator |

Trang Moodle bên trong tiếp tục dùng điều hướng chuẩn của Boost; theme bổ sung liên kết danh mục khóa học cho người dùng đã đăng nhập.

```text
theme/meko/
├── config.php              # Cấu hình theme Moodle ($THEME->name = 'meko', kế thừa boost)
├── version.php             # Thông tin phiên bản & component ($plugin->component = 'theme_meko')
├── lib.php                 # Hàm nạp SCSS và tài nguyên (theme_meko_get_main_scss_content)
├── settings.php            # Trang cài đặt Theme trong Site Administration
├── deploy_local.ps1        # Script 1-Click deploy theme & nạp data vào local container (Windows)
├── deploy_local.sh         # Script 1-Click deploy theme & nạp data vào local container (Linux/Mac)
├── local_dev/              # Docker compose stack & Golden Seed database cho môi trường dev
├── cli/                    # CLI scripts nạp dữ liệu mẫu chuẩn (seed_dev_data.php)
├── competencies.php        # Trang Khung Năng lực số (CBE)
├── lang/
│   ├── en/theme_meko.php   # Ngôn ngữ Tiếng Anh
│   └── vi/theme_meko.php   # Ngôn ngữ Tiếng Việt
├── layout/
│   ├── frontpage.php       # Layout Trang chủ Portal
│   ├── coursecategory.php  # Layout Danh mục Khóa học
│   ├── competencies.php    # Layout trang Khung Năng lực số
│   ├── course.php          # Trang chi tiết khóa học, dữ liệu Moodle
│   ├── mycourses.php       # Layout Khóa học của tôi, lấy khóa học/tiến độ/huy hiệu từ Moodle
│   ├── drawers.php         # Layout tiêu chuẩn các trang bên trong
│   └── login.php           # Layout trang Đăng nhập SSO
├── templates/
│   ├── frontpage.mustache  # Template Mustache Trang chủ MEKOSOFT
│   ├── catalog.mustache    # Template Danh mục Khóa học
│   ├── competencies.mustache # Template trang Khung Năng lực số
│   ├── course_detail.mustache # Trang chi tiết, nội dung khóa học Moodle
│   ├── mycourses.mustache  # Dashboard khóa học với lọc, tìm kiếm và chế độ xem
│   ├── navbar.mustache     # Template Header điều hướng chính
│   └── footer.mustache     # Template Chân trang thương hiệu MEKO
├── js/
│   └── course_detail.js    # Điều hướng chủ đề trong trang Tiles
│   └── mycourses.js        # Lọc, tìm kiếm, sắp xếp và đổi kiểu hiển thị khóa học
├── scss/
│   ├── preset/default.scss # Biến màu thương hiệu Solid (#1E2F97, #F57C00, #6F8A4F)
│   ├── post.scss           # Tập tin SCSS chính nạp các components
│   ├── components/         # CSS Navbar, Cards, Buttons, Hero, Footer
│   └── pages/              # CSS Trang chủ, Khóa học của tôi, Course Tiles, Login
└── pix/
    ├── logo.png / logo.svg # Logo chuẩn MEKOSOFT
    ├── favicon.ico         # Icon trình duyệt
    └── screenshot.png      # Ảnh chụp giao diện xem trước trong Moodle
```

### Trang chi tiết khóa học

Trang `/course/view.php?id=...` dùng `layout/course.php`. Với format Tiles, các thẻ chủ đề, tiến độ và danh sách hoạt động được tạo từ section/activity data của Moodle; nhấn thẻ để xem hoạt động trong chủ đề đó. Khi bật chế độ chỉnh sửa, Moodle tiếp tục render giao diện quản trị gốc của course format. Các format khác giữ nội dung course format gốc. Không đặt CSS reset xóa `background-image` toàn cục vì format Tiles sử dụng ảnh nền cho một số kiểu tile. Thông tin giảng viên, tiến độ hoàn thành và năng lực liên kết dùng dữ liệu Moodle theo quyền người dùng; không cần REST token.
