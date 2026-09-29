<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminMediaController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = MediaAsset::with('uploader');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('filename', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('folder')) {
            $query->where('folder', $request->folder);
        }

        if ($request->filled('type')) {
            if ($request->type === 'images') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($request->type === 'documents') {
                $query->where('mime_type', 'not like', 'image/%');
            }
        }

        $assets = $query->orderByDesc('created_at')->paginate(24)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($assets);
        }

        $totalCount = MediaAsset::count();
        $totalBytes = (int) MediaAsset::sum('size');
        $imagesCount = MediaAsset::where('mime_type', 'like', 'image/%')->count();
        $documentsCount = MediaAsset::where('mime_type', 'not like', 'image/%')->count();

        $formattedTotalSize = $totalBytes >= 1048576
            ? number_format($totalBytes / 1048576, 2).' MB'
            : number_format($totalBytes / 1024, 1).' KB';

        $folders = MediaAsset::distinct('folder')->pluck('folder')->filter()->values();

        return view('admin.media.index', compact(
            'assets',
            'totalCount',
            'totalBytes',
            'formattedTotalSize',
            'imagesCount',
            'documentsCount',
            'folders'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:20480',
            'folder' => 'nullable|string|max:50',
        ]);

        $folder = $validated['folder'] ?? 'general';
        $uploadedAssets = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('media/'.$folder, 'public');
            $url = Storage::disk('public')->url($path);
            $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
            $size = $file->getSize() ?: 0;
            $dimensions = null;

            if (str_starts_with($mimeType, 'image/')) {
                $imgInfo = @getimagesize($file->getRealPath());
                if ($imgInfo) {
                    $dimensions = "{$imgInfo[0]}x{$imgInfo[1]}";
                }
            }

            $asset = MediaAsset::create([
                'name' => $file->getClientOriginalName(),
                'filename' => basename($path),
                'path' => $path,
                'url' => $url,
                'disk' => 'public',
                'mime_type' => $mimeType,
                'size' => $size,
                'dimensions' => $dimensions,
                'folder' => $folder,
                'user_id' => auth()->id(),
            ]);

            $uploadedAssets[] = $asset;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => count($uploadedAssets).' file(s) uploaded successfully.',
                'assets' => $uploadedAssets,
            ]);
        }

        return redirect()->route('admin.media.index')
            ->with('success', count($uploadedAssets).' file(s) uploaded successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $asset = MediaAsset::findOrFail($id);

        if ($asset->path && Storage::disk($asset->disk ?? 'public')->exists($asset->path)) {
            Storage::disk($asset->disk ?? 'public')->delete($asset->path);
        }

        $name = $asset->name;
        $asset->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Asset [{$name}] deleted.",
            ]);
        }

        return redirect()->route('admin.media.index')
            ->with('success', "Asset [{$name}] deleted.");
    }

    public function api(Request $request): JsonResponse
    {
        $query = MediaAsset::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('folder')) {
            $query->where('folder', $request->folder);
        }

        if ($request->filled('only_images') && $request->boolean('only_images')) {
            $query->where('mime_type', 'like', 'image/%');
        }

        $assets = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($assets);
    }
}
