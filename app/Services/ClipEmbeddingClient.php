<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\Http;

use RuntimeException;

class ClipEmbeddingClient
// Khai báo class ClipEmbeddingClient: client gọi CLIP service để lấy embedding vector
{
    public function embedFromUploadedFile(UploadedFile $file): array
    {
        // embedFromUploadedFile(): nhận file upload từ user, trả về vector embedding (mảng số)

        $baseUrl = rtrim(config('clip.base_url'), '/');
        // Lấy config clip.base_url (vd http://127.0.0.1:8000) và bỏ dấu "/" cuối nếu có
        // rtrim(..., '/') để tránh trường hợp baseUrl có "/" rồi bạn nối thêm "/embed" bị "//embed"

        $timeout = (int) config('clip.timeout', 60);
        // Lấy config clip.timeout, nếu không có thì mặc định 60 giây
        // (int) ép kiểu để chắc chắn timeout là số nguyên

        $resp = Http::timeout($timeout)
            // Set timeout cho request HTTP (quá thời gian thì fail)

            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            // attach(): gửi multipart/form-data
            // - field name: 'file' (bên FastAPI /embed sẽ nhận file theo key này)
            // - file content: đọc bytes từ file thực trên server bằng file_get_contents()
            // - filename: tên file gốc để gửi kèm (giúp backend biết tên file)

            ->post($baseUrl . '/embed');
            // Gửi POST tới {baseUrl}/embed (endpoint FastAPI CLIP)

        if (!$resp->ok()) {
            // ok() true khi status code 200-299
            // Nếu lỗi (4xx/5xx) thì throw exception

            throw new RuntimeException('CLIP service error: ' . $resp->status() . ' ' . $resp->body());
            // Ném lỗi kèm status code + body để debug (vd 500 Internal Server Error)
        }

        $data = $resp->json();
        // Parse response body JSON thành mảng PHP

        if (!isset($data['vector']) || !is_array($data['vector'])) {
            // Kiểm tra response có key 'vector' và nó là array không

            throw new RuntimeException('Invalid CLIP response');
            // Nếu không đúng format mong đợi thì throw lỗi
        }

        return $data['vector'];
        // Trả về vector embedding (vd mảng 512 số float)
    }

    public function embedFromPath(string $absolutePath, string $filename = 'image.jpg'): array
    {
        // embedFromPath(): nhận đường dẫn file ảnh trên server, trả về vector embedding
        // Dùng khi bạn đã lưu ảnh vào disk rồi (vd ảnh plant library) và muốn embed batch

        $baseUrl = rtrim(config('clip.base_url'), '/');
        // Lấy base_url và bỏ dấu "/" cuối (giống hàm trên)

        $timeout = (int) config('clip.timeout', 60);
        // Lấy timeout (giống hàm trên)

        $resp = Http::timeout($timeout)
            // Set timeout cho request

            ->attach('file', file_get_contents($absolutePath), $filename)
            // attach file từ đường dẫn tuyệt đối:
            // - đọc bytes của file ảnh từ $absolutePath
            // - gửi kèm filename (mặc định image.jpg nếu không truyền)

            ->post($baseUrl . '/embed');
            // POST tới endpoint /embed của CLIP service

        if (!$resp->ok()) {
            // Nếu status code không ok (không thuộc 2xx)

            throw new RuntimeException('CLIP service error: ' . $resp->status() . ' ' . $resp->body());
            // Ném lỗi để phía controller/job bắt và xử lý (show error / retry)
        }

        $data = $resp->json();
        // Parse JSON response

        if (!isset($data['vector']) || !is_array($data['vector'])) {
            // Check format response phải có vector dạng array

            throw new RuntimeException('Invalid CLIP response');
            // Nếu sai format thì throw lỗi
        }

        return $data['vector'];
        // Trả vector embedding
    }
}