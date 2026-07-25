<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [ArticleController::class, 'index'])->name('home');
// UNSECURE
Route::get('/articles/search', [ArticleController::class, 'search'])->name('articles.search');
// SECURE: throttle:5,1
// Route::get('/articles/search', [ArticleController::class, 'search'])->middleware('throttle:5,1')->name('articles.search');

Route::middleware(['auth'])->group(function () {

    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::middleware(['article.author'])->group(function () {
        Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    });
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');

    Route::patch('/profile', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/img/change', [UserController::class, 'changeImg'])->name('change.img');
    Route::post('/profile/files', [UserController::class, 'upload'])->name('files.upload');
    Route::get('/download/{file}', [UserController::class, 'downloadPrivateFile'])
        ->name('download.private');

    Route::get('/documents/{document}/download', [UserController::class, 'download'])
        ->whereIn('document', ['privacy', 'cookie-policy'])
        ->name('download');

    Route::middleware(['admin'])->prefix('dashboard')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/articles', [AdminController::class, 'articles'])->name('admin.articles');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');

        Route::post('/users/{id}/toggle', [AdminController::class, 'toggleUsersAdmin'])->name('admin.users.toggle');
        Route::post('/articles/{id}/toggle', [AdminController::class, 'toggleArticleStatus'])->name('admin.articles.toggle');
    });
    Route::post('/articles/{articleId}/comments', [CommentController::class, 'store'])
        ->middleware(['block.suspicious'])
        ->name('comments.store');
});

Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
