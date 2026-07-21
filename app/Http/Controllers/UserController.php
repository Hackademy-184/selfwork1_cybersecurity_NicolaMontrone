<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function profile()
    {
        if (!$user = Auth::user()) {
            return response()->json(['message' => 'Forbidden Operation'], 403);
        }

        return view('auth.profile', compact('user'));
    }

    public function update(Request $request)
    {
        if (!$user = Auth::user()) {
            return response()->json(['message' => 'Forbidden Operation'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return back()->with('message','User updated');
    }
    public function changeImg(Request $request)
    {
        if(!$user = Auth::user()){
            return back()->with('message','Please Log In');
        }
        
        if(!$request->hasFile('avatar')) {
            return back()->with('message','Forbidden Operation');
        }
        
        if (!file_exists(storage_path("app/public/images/users/".$user->id))) {
            mkdir(storage_path("app/public/images/users/".$user->id), 0777, true);
        }

        // retrieve uploaded image
        $newImage = $request->file('avatar');
        // calculate hash

        // UNSECURE with md5
        $newImageHash = md5_file($newImage);

        // SECURE with sha56
        // $newImageHash = hash_file('sha256', $newImage);
    
        // compare hash
        if($newImageHash == $user->avatar){
            return redirect()->back()->with('message','Image not updated, same');
        }
        // Define the path to store the image
        $path = "images/users/".$user->id;

       
        Storage::deleteDirectory($path);
    
        
        // Store the image in the defined path
        $filePath = $newImage->storeAs($path, $newImageHash, 'public');
    
        // save new user avatar name
        $user->avatar = $newImageHash;
        $user->save();

        return redirect()->back()->with('message','Image updated');
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
}
