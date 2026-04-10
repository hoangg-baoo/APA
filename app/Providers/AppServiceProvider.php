<?php

namespace App\Providers;

use App\Models\Plant;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        // register(): nơi đăng ký bindings vào service container (bind/singleton)
        // File này hiện không đăng ký gì
    }

    public function boot(): void
    {
        // boot(): chạy khi Laravel khởi động xong, phù hợp để share view data, event listener, macro, ...
        // Ở đây: tạo menu navbar cho Plant Library và cache lại

        // Share plant categories menu for navbar dropdown (cached)
        View::share('plantNavMenu', Cache::remember('plant_nav_menu_v1', 600, function () {
            // View::share('plantNavMenu', ...) : chia sẻ biến $plantNavMenu cho MỌI Blade view
            // Cache::remember(key, seconds, callback): nếu cache có sẵn thì lấy, không có thì chạy callback để tạo rồi lưu cache
            // 'plant_nav_menu_v1': tên cache key (v1 để sau này đổi logic thì đổi key cho sạch)
            // 600: cache 600 giây = 10 phút

            $plants = Plant::query()
                ->select(['id', 'name'])
                // select chỉ lấy id và name để query nhẹ hơn (không lấy description/care_guide...)
                ->orderBy('name')
                // sắp xếp theo tên cây (alphabet)
                ->get();
                // get() lấy toàn bộ kết quả thành collection

            $groups = [];
            // $groups sẽ lưu nhóm theo "Genus + species" (2 từ đầu trong tên)

            foreach ($plants as $p) {
                // Duyệt từng plant lấy từ DB

                $name = trim((string) $p->name);
                // Ép name về string rồi trim khoảng trắng 2 đầu

                if ($name === '') continue;
                // Nếu tên rỗng thì bỏ qua (không đưa vào menu)

                $parts = preg_split('/\s+/', $name);
                // Tách tên theo khoảng trắng (1 hoặc nhiều space) thành mảng từ

                if (!$parts || count($parts) < 2) continue;
                // Nếu không tách được hoặc tên chỉ có 1 từ -> bỏ qua
                // Vì menu muốn lấy 2 từ đầu (Genus + species)

                $prefix = trim($parts[0] . ' ' . $parts[1]); // Genus + species
                // Lấy 2 từ đầu tạo prefix (vd "Anubias barteri")

                $key = Str::lower($prefix);
                // Tạo key dạng chữ thường để dùng làm key mảng (tránh phân biệt hoa/thường)

                $slug = Str::slug($prefix);
                // Tạo slug (vd "anubias-barteri") để dùng cho URL/anchor nếu cần

                if (!isset($groups[$key])) {
                    // Nếu nhóm này chưa tồn tại thì tạo nhóm mới
                    $groups[$key] = [
                        'label' => $prefix,
                        // label để hiển thị tên nhóm (2 từ đầu)

                        'slug' => $slug,
                        // slug dùng cho route/link (nếu bạn dùng)

                        'prefix' => $prefix,
                        // lưu lại prefix gốc (trùng label, nhưng để rõ ý)

                        'children' => [],
                        // children là danh sách các plant thuộc nhóm này
                    ];
                }

                $rest = trim(mb_substr($name, mb_strlen($prefix)));
                // Lấy phần còn lại sau prefix (vd " var. nana", " gold", ...)
                // mb_* để xử lý Unicode (tên có dấu/ký tự đặc biệt) an toàn hơn

                $childLabel = $rest !== '' ? $rest : $prefix; // if exact match, keep as base
                // Nếu có phần còn lại thì label con = phần còn lại
                // Nếu tên đúng bằng prefix (không có phần dư) thì label con = prefix luôn

                $groups[$key]['children'][] = [
                    'id' => $p->id,
                    // id plant để link sang trang chi tiết

                    'label' => $childLabel,
                    // nhãn hiển thị ở dropdown (phần còn lại hoặc prefix)

                    'name' => $name,
                    // lưu full name để sort chính xác
                ];
            }

            // Convert to list, sort by label
            $menu = array_values($groups);
            // Chuyển associative array $groups thành list indexed array (0,1,2...)

            usort($menu, fn($a, $b) => strcasecmp($a['label'], $b['label']));
            // Sort các group theo label (không phân biệt hoa/thường)

            // Limit for UI (avoid too huge dropdown)
            $menu = array_slice($menu, 0, 20);
            // Chỉ lấy tối đa 20 group để dropdown không quá dài

            // Sort children + cap
            foreach ($menu as &$g) {
                // Duyệt từng group (dùng & để sửa trực tiếp trong mảng)

                usort($g['children'], fn($a, $b) => strcasecmp($a['name'], $b['name']));
                // Sort các children theo full name (alphabet)

                $g['children'] = array_slice($g['children'], 0, 12);
                // Mỗi group chỉ lấy tối đa 12 children để UI gọn
            }
            unset($g);
            // Hủy reference để tránh bug reference về sau (best practice khi foreach dùng &)

            return $menu;
            // Callback trả về $menu -> Cache::remember sẽ lưu giá trị này vào cache
        }));
    }
}