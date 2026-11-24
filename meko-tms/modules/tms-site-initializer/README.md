# ACCEES TMS page initializer

OSGi DS component, chạy sau `portal.initialized`, cấu hình site `/tms` đã tồn tại. Tạo role Site `TMS Student`, các trang public/private theo bảng quyền trong README theme, gán `acceestms_WAR_acceestmstheme` cho tập trang public. Mọi trang quản trị đều kiểm quyền ở Liferay; ẩn menu không thay thế quyền trang.

Build bằng Gradle từ `meko-tms`; deploy JAR sau WAR theme. Chạy lại áp dụng lại quyền trên các trang quản lý. Không tạo tài khoản, không nhúng mật khẩu, không tắt chính sách xác minh email và không sửa dữ liệu ngoài các trang/role đã nêu.
