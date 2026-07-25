<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function profile()
    {
        if (! $user = Auth::user()) {
            return response()->json(['message' => 'Forbidden Operation'], 403);
        }

        $files = $user->files()->latest()->get();

        return view('auth.profile', compact('user', 'files'));
    }

    public function update(Request $request)
    {
        if (! $user = Auth::user()) {
            return response()->json(['message' => 'Forbidden Operation'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        return back()->with('message', 'User updated');
    }

    public function changeImg(Request $request)
    {
        if (! $user = Auth::user()) {
            return back()->with('message', 'Please Log In');
        }

        if (! $request->hasFile('avatar')) {
            return back()->with('message', 'Forbidden Operation');
        }

        if (! file_exists(storage_path('app/public/images/users/'.$user->id))) {
            mkdir(storage_path('app/public/images/users/'.$user->id), 0777, true);
        }

        // retrieve uploaded image
        $newImage = $request->file('avatar');
        // calculate hash

        // UNSECURE with md5
        // $newImageHash = md5_file($newImage);

        // SECURE with sha56
        $newImageHash = hash_file('sha256', $newImage);

        // compare hash
        if ($newImageHash == $user->avatar) {
            return redirect()->back()->with('message', 'Image not updated, same');
        }
        // Define the path to store the image
        $path = 'images/users/'.$user->id;

        Storage::deleteDirectory($path);

        // Store the image in the defined path
        $filePath = $newImage->storeAs($path, $newImageHash, 'public');

        // save new user avatar name
        $user->avatar = $newImageHash;
        $user->save();

        return redirect()->back()->with('message', 'Image updated');
    }

    public function download(string $document)
    {
        $documents = [
            'privacy' => 'privacy.pdf',
            'cookie-policy' => 'cookie-policy.pdf',
        ];

        abort_unless(array_key_exists($document, $documents), 404);

        $file = $documents[$document];
        $disk = Storage::disk('local');

        abort_unless($disk->exists($file), 404);

        return $disk->download($file, $file);
    }

    public function upload(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return back()->with('message', 'Please Log In');
        }

        if (! $request->hasFile('file')) {
            return back()->withErrors('Forbidden Operation');
        }

        $file = $request->file('file');
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        if (! in_array($extension, $allowedExtensions, true)
            || ! in_array($mimeType, $allowedMimeTypes, true)) {
            return back()->withErrors('File type not allowed');
        }

        $filename = $file->getClientOriginalName();
        $fileuid = uniqid().'.'.$extension;
        $path = $file->storeAs("docs/users/{$user->id}", $fileuid, 'local');

        File::create([
            'name' => $filename,
            'uid' => $fileuid,
            'user_id' => $user->id,
        ]);

        return back()->withMessage('Upload successful');
    }

    public function downloadPrivateFile(string $file)
    {
        $user = Auth::user();

        if (! $user) {
            return back()->with('message', 'Please Log In');
        }

        $fileRecord = File::where('uid', $file)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $fileRecord) {
            return back()->with('message', 'File not found');
        }

        $path = "docs/users/{$user->id}/{$fileRecord->uid}";

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $fileRecord->uid);
    }
}
