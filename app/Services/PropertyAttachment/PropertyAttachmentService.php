<?php

namespace App\Services\PropertyAttachment;

use App\Models\PropertyAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropertyAttachmentService
{
    /**
     * Store the given uploaded images and attach them to the given model.
     *
     * @param array<int, UploadedFile> $files
     * @param array<int, string|null> $captions
     * @return array<int, PropertyAttachment>
     */
    public function attach(Model $attachable, array $files, array $captions = []): array
    {
        return DB::transaction(function () use ($attachable, $files, $captions) {
            $nextSortOrder = (int) $attachable->attachments()->max('sort_order') + 1;

            $attachments = [];
            foreach (array_values($files) as $index => $file) {
                $path = $file->store('property-attachments', 'public');

                $attachments[] = PropertyAttachment::create([
                    'attachable_type' => $attachable->getMorphClass(),
                    'attachable_id' => $attachable->getKey(),
                    'disk' => 'public',
                    'path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'caption' => $captions[$index] ?? null,
                    'sort_order' => $nextSortOrder + $index,
                    'uploaded_by' => Auth::id(),
                ]);
            }

            return $attachments;
        });
    }

    public function delete(PropertyAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }
}
