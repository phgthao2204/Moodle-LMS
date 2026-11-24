# MEKO-DLE Ecosystem (`MEKO-LMS` + `MEKO-TMS`)

Monorepo chính thức của hệ sinh thái đào tạo số **MEKOSOFT (`MEKO-DLE`)** gồm **MEKO-LMS (Moodle 5.3 LTS)**, **MEKO-TMS (Liferay 7.4 GA132 CE)**, **PostgreSQL 17**, **Mailpit** và bộ cài đặt **Docker DevLocal**.

---

## 1. Clone Repo & Đọc Tài liệu Theo Thứ tự

```bash
git clone -b develop https://github.com/huyriddle/meko-dle.git
cd meko-dle
```

Mở trực tiếp [`docs/index.html`](docs/index.html) bằng trình duyệt (không cần web server) để xem cổng tài liệu + `15` Mockups HTML, hoặc đọc lần lượt trong thư mục [`docs/`](docs/):

0. [`docs/00_SYSTEM_OVERVIEW.md`](docs/00_SYSTEM_OVERVIEW.md) — Kiến trúc tổng thể LMS <-> TMS và luồng nghiệp vụ chính.
1. [`docs/01_DEV_QUICKSTART.md`](docs/01_DEV_QUICKSTART.md) — Domain, Port, DB, quy chuẩn đặt tên OSGi/Moodle & UI (Solid `#0000B6` / `#39B500` / `#EA6400`, font `Be Vietnam Pro`, **0% Emoji**, **0% Gradient**, **0% Hardcode**).
2. [`docs/02_DATA_FLOW_SB_AND_API.md`](docs/02_DATA_FLOW_SB_AND_API.md) — Luồng đồng bộ dữ liệu, 10 bảng Service Builder & 20 APIs/Commands.
3. [`docs/03_CODING_PLAYBOOK.md`](docs/03_CODING_PLAYBOOK.md) — Code mẫu Portlet, Service Builder, React & Moodle WS.
4. [`docs/04_DEV_TASKS_AND_MOCKUPS.md`](docs/04_DEV_TASKS_AND_MOCKUPS.md) — Phân công Task & bản đồ Mockup theo từng vai trò.
5. [`docs/05_GIT_DEPLOY_AND_CICD.md`](docs/05_GIT_DEPLOY_AND_CICD.md) — Quy trình Git Flow, Commit, Pull Request & CI/CD.

---

## 2. Khởi chạy Môi trường Dev Local (`http://meko.localhost`)

Yêu cầu: Đã cài **Docker Desktop** (nên cấp 6–8 GB RAM cho WSL2/Docker VM nếu chạy kèm Liferay). Tên miền `meko.localhost` tự phân giải về `127.0.0.1` theo chuẩn **RFC 6761** (**không cần sửa file `hosts`**).

### Chế độ Đầy đủ (Liferay CMS/TMS + Moodle LMS + Postgres 17 + Mailpit)
```powershell
# Windows PowerShell
cd meko-devlocal
.\setup.ps1
```
```bash
# Linux / macOS
cd meko-devlocal
chmod +x ./setup.sh
./setup.sh
```

### Chế độ Nhẹ máy cho Dev LMS / Database (~400 MB RAM, bỏ qua Liferay)
```powershell
# Windows PowerShell
cd meko-devlocal
.\setup.ps1 -MoodleOnly
```
```bash
# Linux / macOS
cd meko-devlocal
./setup.sh --moodle-only
```

> **Nếu bị trùng Port `80` (IIS / XAMPP / Laragon):** mở `meko-devlocal/.env` (copy từ `.env.example`), đổi `HTTP_PORT=8090` rồi chạy lại `setup.ps1` / `setup.sh`, sau đó truy cập qua `http://meko.localhost:8090/`. Xem chi tiết tại [`meko-devlocal/README.md`](meko-devlocal/README.md).

### Đường dẫn & Tài khoản Kiểm thử Mặc định

| Thành phần | URL / Host | Tài khoản / Thông số |
| :--- | :--- | :--- |
| **Dev Info Dashboard** | `http://meko.localhost/dev-info` | Trang trạng thái Docker, Port, DB, Account |
| **Liferay CMS** | `http://meko.localhost/` | Admin: `test@liferay.com` / `test` *(đổi lần đầu)* |
| **Liferay TMS** | `http://meko.localhost/tms` | Tự chuyển sang `/web/tms` |
| **Moodle 5.3 LMS** | `http://meko.localhost/lms/` | Admin: `admin` / `AdminMoodle2026!@#`<br>Tech Lead: `pqhuy` / `PqHuy2026!@#`<br>Giảng viên: `teacher1`..`teacher4` / `Teacher2026!@#`<br>Sinh viên: `student1`, `b2204927` / `Student2026!@#` |
| **Mailpit Web UI** | `http://meko.localhost/mail/` | SMTP nội bộ `mailpit:1025`, Web UI `localhost:8025` |
| **PostgreSQL 17** | `localhost:5433` | Liferay DB: `lportal` (`liferay` / `LiferayPass2026!@#`)<br>Moodle DB: `accees_lms` (`lms_user` / `LmsPassword2026!@#`) |

---

## 3. Khu vực Code Theo Phân hệ

- **Moodle LMS (`meko-lms/`):**
  - Theme: [`meko-lms/theme_meko/`](meko-lms/theme_meko/)
  - Plugins: [`meko-lms/local/meko_competencies/`](meko-lms/local/meko_competencies/) *(cùng `local_meko_imscp`, `local_meko_sync_api`)*
  - `theme_meko` và `local/meko_competencies` được mount trực tiếp vào `devlocal-moodle`; sau khi sửa PHP/Template/SCSS có thể purge cache nhanh:
    ```bash
    docker exec devlocal-moodle php /var/www/html/admin/cli/purge_caches.php
    ```
- **Liferay TMS (`meko-tms/`):**
  - OSGi Modules: [`meko-tms/modules/`](meko-tms/modules/) và Theme [`meko-tms/themes/accees-tms-theme/`](meko-tms/themes/accees-tms-theme/)
  - Khi build ra `.jar` / `.war`, copy vào `meko-devlocal/liferay/deploy/` để hot-deploy vào container Liferay.

---

## 4. Quy trình Git: Tạo Nhánh, Commit, Push & Pull Request

> **Không push trực tiếp lên `main`, `staging`, hoặc `develop`.** Mọi thay đổi đều tách nhánh từ `develop` và merge qua Pull Request.

1. **Cập nhật `develop` và tạo nhánh task mới:**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feat/MEKO-LMS-03-imscp-export
   ```
   *(Quy ước nhánh: `feat/<task-id>-<mô-tả>` hoặc `fix/<task-id>-<mô-tả>`)*

2. **Kiểm tra trước khi commit:**
   - **0% Emoji** trong code/UI (`.github/workflows/ci-lms.yml` và `ci-tms.yml` sẽ fail nếu có emoji).
   - **0% Gradient**, không hardcode IP, mật khẩu, API Token hoặc domain cũ (`dev3.accees.vn`, `dev5.accees.vn`).
   - Không commit file cấu hình local (`meko-devlocal/.env`, `meko-devlocal/moodle/config.php`).

3. **Commit theo chuẩn Conventional Commits & Push:**
   ```bash
   git status
   git add <thư_mục_hoặc_file_thay_đổi>
   git commit -m "feat(lms): [MEKO-LMS-03] implement IMS CC export handler"
   git push -u origin feat/MEKO-LMS-03-imscp-export
   ```
   *(Type: `feat`, `fix`, `docs`, `refactor`, `chore` | Scope: `lms`, `tms`, `infra`, `samples`, `all`)*

4. **Tạo Pull Request (PR) vào `develop`:**
   - Chọn **Base: `develop`** <- **Compare: `feat/...`**
   - Hoàn tất checklist trong `.github/PULL_REQUEST_TEMPLATE.md`
   - Chờ **GitHub Actions CI xanh** + **1 Approve** -> **Squash and Merge**
   - Khi release Staging: mở PR **`develop` -> `staging`** để kích hoạt `cd-staging.yml`
