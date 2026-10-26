# PHP2Shell — HARD

Minimal player package. It contains only the Docker challenge runtime and setup files.

Requirements: Docker Desktop.

## Cách chạy

1. Mở PowerShell tại đúng thư mục `php2shell-hard`.
2. Chạy:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

Hoặc chạy `setup.bat` bằng cách double-click.

`setup.ps1` tự tạo `.env` từ `.env.example` nếu file chưa tồn tại. Không cần sửa `.env` để khởi động challenge local.

Target: `http://127.0.0.1:44158/`  
Proof status: `http://127.0.0.1:44159/status.php`

Nếu platform cấp verifier session, người chơi chỉ cần điền các giá trị đó vào `.env` để nickname/ranking hoạt động remote. Khi chưa được cấp các giá trị này, challenge vẫn khởi động và cho phép test local, nhưng phần submit remote sẽ báo verifier chưa được cấu hình. Không tự đoán hoặc commit session token. Exploits, walkthroughs, flags, operator secrets và local demo checkers được loại khỏi package.
