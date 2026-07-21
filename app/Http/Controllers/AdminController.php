<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;

class AdminController extends Controller
{
    public function dashboard()
	{
		return view("admin.dashboard");
	}

    public function articles()
    {
        $users = User::latest()->get();
        $articles = Article::latest()->get();
        return view('admin.articles', compact('articles'));
    }

    public function users()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }
    
    public function toggleArticleStatus($id) {
        $article = Article::findOrFail($id);
        $article->published = !$article->published;
        $article->save();
        return back()->with('message', 'Article status updated');
    }

	public function toggleUsersAdmin($id)
	{
		$user = User::findOrFail($id);
        $user->is_admin = !$user->is_admin;
        $user->save();
        return back()->with('message', 'User role updated');
	}
}
