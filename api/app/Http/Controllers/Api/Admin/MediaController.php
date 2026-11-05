<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $media = Media::latest()->paginate($request->input('per_page', 24));
        return MediaResource::collection($media);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $disk = 'public';

        $extension = $file->getClientOriginalExtension();
        $safeFileName = Str::uuid() . '.' . $extension;
        $path = $file->storeAs('media', $safeFileName, $disk);

        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        $media = Media::create([
            'uploaded_by'   => $request->user()->id,
            'disk'          => $disk,
            'path'          => $path,
            'original_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'mime_type'     => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes'    => $file->getSize(),
            'width'         => $width,
            'height'        => $height,
            'alt_text'      => $request->input('alt_text'),
            'caption'       => $request->input('caption'),
        ]);

        return (new MediaResource($media))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Media $media): JsonResponse
    {
        if ($media->disk && $media->path && Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }
}
