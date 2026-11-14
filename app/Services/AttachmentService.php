<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * رفع وحذف المرفقات (صور وPDF) على الفواتير والسندات.
 *
 * الملفات على القرص الخاص (storage/app/private) مش public — الوصول ليها
 * بيعدّي على controller محمي، عشان ما حدش يفتح صورة فاتورة بلينك مباشر.
 */
class AttachmentService
{
    public const MAX_SIZE_KB = 8192; // ٨ ميجا

    public const ALLOWED_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public function store(Model $attachable, UploadedFile $file, User $user, ?string $notes = null): Attachment
    {
        return DB::transaction(function () use ($attachable, $file, $user, $notes) {
            // اسم عشوائي على القرص، والاسم الأصلي بيتخزّن في العمود
            $directory = 'attachments/'.Str::of(class_basename($attachable))->lower().'/'.$attachable->getKey();
            $path = $file->store($directory, 'local');

            return Attachment::create([
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize() ?: 0,
                'user_id' => $user->id,
                'notes' => $notes,
            ]);
        });
    }

    /** بيحذف السطر والملف مع بعض */
    public function delete(Attachment $attachment): void
    {
        DB::transaction(function () use ($attachment) {
            $disk = Storage::disk($attachment->disk);

            if ($disk->exists($attachment->path)) {
                $disk->delete($attachment->path);
            }

            $attachment->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Attachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'mimeType' => $attachment->mime_type,
            'size' => $attachment->size,
            'isImage' => $attachment->isImage(),
            'isPdf' => $attachment->isPdf(),
            'uploadedBy' => $attachment->user?->name,
            'uploadedAt' => $attachment->created_at?->toDateString(),
            'previewUrl' => route('attachments.show', $attachment),
            'downloadUrl' => route('attachments.download', $attachment),
            'notes' => $attachment->notes,
        ];
    }
}
