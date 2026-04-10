Dưới đây là **README.md** mẫu (viết kiểu “giáo viên clone về là chạy được”), có đủ phần: **cài XAMPP 8.2.12**, **đặt project vào htdocs**, **setup DB bằng phpMyAdmin (import SQL) hoặc chạy migrations**, và **cách chạy cả Laravel + CLIP service**.

Bạn copy nguyên khối này vào file `README.md` ở root dự án là được.

---

````md
# Aquatic Plant Advisor (APA)

Web app hỗ trợ người chơi thủy sinh: quản lý bể (tanks), theo dõi thông số nước (water logs), thư viện cây (plant library), Q&A, community, và nhận diện cây bằng CLIP service.

---

## 1) Yêu cầu môi trường

### Phần Laravel (Web)
- Windows
- **XAMPP 8.2.12** (Apache + MySQL + PHP 8.2)
- Composer

> Gợi ý: mở XAMPP Control Panel và bật **Apache** + **MySQL**.

### Phần CLIP service (Python)
- Python 3.10+ (khuyến nghị 3.10/3.11)
- pip
- (khuyến nghị) tạo venv

---

## 2) Lấy source code

### Cách A: Git clone
Mở terminal và chạy:

```bash
cd C:\xampp\htdocs
git clone <YOUR_GITHUB_REPO_URL> aquatic_plant_advisor
````

### Cách B: Giải nén file dự án

Giải nén project vào:

```text
C:\xampp\htdocs\aquatic_plant_advisor
```

> Sau khi xong, cấu trúc nên kiểu:

```text
C:\xampp\htdocs\aquatic_plant_advisor\
  app\
  public\
  routes\
  ...
```

---

## 3) Setup Database (MySQL + phpMyAdmin)

### 3.1 Tạo database

Mở phpMyAdmin:

```text
http://127.0.0.1/phpmyadmin
```

Tạo database:

* Name: `aquatic_plant_advisor`
* Collation: `utf8mb4_unicode_ci`

---

### 3.2 Cách 1 (Khuyến nghị cho demo nhanh): Import SQL trực tiếp trong phpMyAdmin

Nếu dự án có file SQL dump (ví dụ: `database/aquatic_plant_advisor.sql` hoặc `schema.sql` + `seed.sql`):

1. Vào phpMyAdmin → chọn database `aquatic_plant_advisor`
2. Tab **Import**
3. Chọn file `.sql` trong dự án
4. Bấm **Go**

> Sau khi import xong: refresh, sẽ thấy các tables (users, plants, tanks, water_logs, questions, answers, posts, comments, …)

---

### 3.3 Cách 2: Chạy migrations bằng Laravel

Nếu bạn muốn tạo tables bằng migrations:

1. Đảm bảo database `aquatic_plant_advisor` đã tạo
2. Chạy lệnh:

```bash
cd C:\xampp\htdocs\aquatic_plant_advisor
php artisan migrate
```

Nếu dự án có seed:

```bash
php artisan db:seed
# hoặc
php artisan migrate --seed
```

---

## 4) Setup Laravel (.env + composer)

### 4.1 Tạo file .env

Trong thư mục project Laravel:

```bash
cd C:\xampp\htdocs\aquatic_plant_advisor
copy .env.example .env
```

Mở `.env` và chỉnh DB:

```env
APP_NAME="Aquatic Plant Advisor"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aquatic_plant_advisor
DB_USERNAME=root
DB_PASSWORD=
```

> Với XAMPP mặc định: MySQL user `root`, password rỗng.

---

### 4.2 Cài dependency + generate key

```bash
composer install
php artisan key:generate
```

Nếu dự án có dùng storage (upload ảnh, …):

```bash
php artisan storage:link
```

---

## 5) Chạy Laravel (Web)

### Cách 1: Chạy bằng artisan serve (đơn giản nhất)

```bash
cd C:\xampp\htdocs\aquatic_plant_advisor
php artisan serve --host=127.0.0.1 --port=8000
```

Mở web:

```text
http://127.0.0.1:8000
```

### Cách 2 (tùy chọn): chạy bằng Apache DocumentRoot trỏ vào /public

Nếu bạn muốn chạy dạng:

```text
http://127.0.0.1/aquatic_plant_advisor/public
```

thì đảm bảo Apache đang bật và truy cập đúng path.

---

## 6) Setup & chạy CLIP Service (Python)

> CLIP service dùng để nhận diện cây bằng image retrieval / embedding.

Giả sử thư mục service nằm trong project (ví dụ `clip_service/` hoặc `clip/`).
Vào đúng folder chứa `requirements.txt` và `main.py` (hoặc `app.py`).

### 6.1 Tạo venv + cài requirements

Ví dụ:

```bash
cd C:\xampp\htdocs\aquatic_plant_advisor\clip_service
python -m venv .venv
.\.venv\Scripts\activate
pip install -r requirements.txt
```

### 6.2 Chạy server

Ví dụ (FastAPI + uvicorn):

```bash
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

> Lần chạy đầu có thể tải model, chờ tải xong là OK.

---

## 7) Kết nối Laravel ↔ CLIP Service

Mở `.env` (Laravel) và thêm/đảm bảo có URL service:

```env
CLIP_API_URL=http://127.0.0.1:8001
```

> Nếu code đang dùng key khác (ví dụ `IMAGE_SERVICE_URL`), hãy sửa theo đúng biến mà project đang đọc.

---

## 8) Tài khoản demo (nếu có seed / SQL dump)

Nếu SQL dump/seed có tạo sẵn tài khoản:

* Admin: `admin@apa.local`
* Expert: `expert@apa.local`
* Password (thường): `password`

> Nếu bạn import file SQL của nhóm và thấy email khác thì dùng đúng email trong bảng `users`.

---

## 9) Thứ tự chạy đầy đủ (đúng chuẩn demo)

1. Bật **Apache** + **MySQL** trong XAMPP
2. Import DB bằng phpMyAdmin **hoặc** chạy migrations
3. Chạy Laravel:

   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```
4. Chạy CLIP service:

   ```bash
   uvicorn main:app --host 127.0.0.1 --port 8001 --reload
   ```
5. Mở web:

   ```text
   http://127.0.0.1:8000
   ```

---

## 10) Troubleshooting nhanh

### Composer lỗi thiếu extension zip/unzip

* Mở `php.ini` (trong XAMPP) và bật:

  * `extension=zip`
  * `extension=fileinfo`
* Restart Apache
* Chạy lại `composer install`

### Lỗi APP_KEY / 500

```bash
php artisan key:generate
php artisan config:clear
php artisan cache:clear
```

### Laravel không kết nối DB

Kiểm tra `.env`:

* DB_DATABASE đúng tên
* MySQL đang chạy
* Username/password đúng

### Không gọi được CLIP service

* Đảm bảo uvicorn đang chạy port 8001
* Mở thử:

  ```text
  http://127.0.0.1:8001/docs
  ```
* Kiểm tra `.env` Laravel `CLIP_API_URL`

---

## 11) Authors

Group project – Aquatic Plant Advisor (USTH)

```

---

Nếu bạn muốn README **khớp 100% với repo của bạn**, bạn chỉ cần gửi mình 2 thứ (copy/paste là được, không cần zip):

1) **Tên folder thật** của CLIP service trong project (ví dụ: `clip_service` hay `clip-api`), và file chạy là `main.py` hay gì  
2) File `requirements.txt` của CLIP service (để mình ghi đúng lệnh cài và đúng dependencies)

Mình sẽ chỉnh README cho đúng y chang cấu trúc dự án của bạn.
```
