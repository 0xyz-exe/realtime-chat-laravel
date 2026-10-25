<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/chat');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('chat.index'))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'create'])->name('chat.create');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/chat/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'send'])->name('chat.send');
});

require __DIR__.'/auth.php';
