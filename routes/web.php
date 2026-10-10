<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\LostFoundController;
use App\Http\Controllers\TentangController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KemahasiswaanController;
use App\Http\Controllers\PengurusUkmController;
use App\Http\Controllers\PasswordResetController;

/*
|--------------------------------------------------------------------------
| Halaman Publik - Mahasiswa (tanpa perlu login)
| Middleware 'guest.internal' memastikan jika sudah login sebagai
| kemahasiswaan/pengurus_ukm, mereka diarahkan ke dashboard masing-masing.
|--------------------------------------------------------------------------
*/
Route::middleware(['guest.internal'])->group(function () {
    Route::get('/', function () {
        $organizations = \App\Models\Organization::where('status', 'Aktif')->orderBy('name', 'asc')->get();
        $heroImages = $organizations->whereNotNull('banner_path')->pluck('banner_path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->values()->toArray();
        return view('mahasiswa.home', compact('organizations', 'heroImages'));
    });
    Route::get('/organisasi/{id}', [\App\Http\Controllers\MahasiswaController::class, 'showOrganization'])->name('organisasi.show');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/pengumuman', [AnnouncementController::class, 'index'])->name('pengumuman.index');
    Route::get('/kalender', [\App\Http\Controllers\MahasiswaController::class, 'calendar'])->name('calendar.index');
    Route::get('/lost-found', [LostFoundController::class, 'index'])->name('lost-found.index');
    Route::post('/lost-found', [LostFoundController::class, 'store'])->name('lost-found.store');
    Route::get('/tentang', [TentangController::class, 'index'])->name('tentang.index');

});


/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Universal notification mark-all-read (any authenticated user)
Route::post('/notifications/read-all', function () {
    auth()->user()->unreadNotifications->markAsRead();
    return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
})->middleware('auth')->name('notifications.read-all');

/*
|--------------------------------------------------------------------------
| Halaman Kemahasiswaan (login wajib + role: kemahasiswaan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:kemahasiswaan'])->group(function () {
    Route::get('/kemahasiswaan', [KemahasiswaanController::class, 'index'])->name('kemahasiswaan.dashboard');
    Route::get('/kemahasiswaan/organisasi', [KemahasiswaanController::class, 'organizations'])->name('kemahasiswaan.organizations.index');
    Route::get('/kemahasiswaan/pengajuan-kegiatan-laporan', [KemahasiswaanController::class, 'submissions'])->name('kemahasiswaan.submissions.index');
    Route::post('/kemahasiswaan/organisasi', [KemahasiswaanController::class, 'store'])->name('kemahasiswaan.organizations.store');
    Route::put('/kemahasiswaan/organisasi/{id}', [KemahasiswaanController::class, 'update'])->name('kemahasiswaan.organizations.update');
    Route::post('/kemahasiswaan/organisasi/{id}/reset-account', [KemahasiswaanController::class, 'resetAccount'])->name('kemahasiswaan.organizations.reset');
    Route::post('/kemahasiswaan/organisasi/{id}/toggle-status', [KemahasiswaanController::class, 'toggleStatus'])->name('kemahasiswaan.organizations.toggle-status');
    Route::get('/kemahasiswaan/organisasi/{id}/edit', [KemahasiswaanController::class, 'edit'])->name('kemahasiswaan.organizations.edit');
    Route::get('/kemahasiswaan/organisasi/{id}', [KemahasiswaanController::class, 'show'])->name('kemahasiswaan.organizations.show');
    Route::delete('/kemahasiswaan/organisasi/{id}', [KemahasiswaanController::class, 'destroy'])->name('kemahasiswaan.organizations.destroy');
    Route::post('/kemahasiswaan/pengajuan/{id}/review', [KemahasiswaanController::class, 'review'])->name('kemahasiswaan.submissions.review');
    
    Route::get('/kemahasiswaan/pengumuman', [KemahasiswaanController::class, 'announcements'])->name('kemahasiswaan.announcements.index');
    Route::post('/kemahasiswaan/pengumuman', [KemahasiswaanController::class, 'storeAnnouncement'])->name('kemahasiswaan.announcements.store');
    Route::put('/kemahasiswaan/pengumuman/{id}', [KemahasiswaanController::class, 'updateAnnouncement'])->name('kemahasiswaan.announcements.update');
    Route::post('/kemahasiswaan/pengumuman/{id}/publish', [KemahasiswaanController::class, 'publishAnnouncement'])->name('kemahasiswaan.announcements.publish');
    Route::post('/kemahasiswaan/pengumuman/{id}/reject', [KemahasiswaanController::class, 'rejectAnnouncement'])->name('kemahasiswaan.announcements.reject');
    Route::delete('/kemahasiswaan/pengumuman/{id}', [KemahasiswaanController::class, 'destroyAnnouncement'])->name('kemahasiswaan.announcements.destroy');
    Route::get('/kemahasiswaan/kontak', [KemahasiswaanController::class, 'contacts'])->name('kemahasiswaan.contacts');
    Route::get('/kemahasiswaan/kalender', [KemahasiswaanController::class, 'calendar'])->name('kemahasiswaan.calendar');
    Route::post('/kemahasiswaan/kalender', [KemahasiswaanController::class, 'storeCalendarEvent'])->name('kemahasiswaan.calendar.store');
    Route::post('/kemahasiswaan/kalender/import-pdf', [KemahasiswaanController::class, 'importPdfParse'])->name('kemahasiswaan.calendar.import-parse');
    Route::post('/kemahasiswaan/kalender/import-save', [KemahasiswaanController::class, 'importPdfSave'])->name('kemahasiswaan.calendar.import-save');
    Route::get('/kemahasiswaan/notifikasi', [KemahasiswaanController::class, 'notifications'])->name('kemahasiswaan.notifications.index');
    Route::post('/kemahasiswaan/notifikasi/read-all', [KemahasiswaanController::class, 'markAllNotificationsAsRead'])->name('kemahasiswaan.notifications.read-all');
    Route::post('/kemahasiswaan/notifikasi/{id}/read', [KemahasiswaanController::class, 'markNotificationAsRead'])->name('kemahasiswaan.notifications.read');
    Route::delete('/kemahasiswaan/notifikasi/{id}', [KemahasiswaanController::class, 'deleteNotification'])->name('kemahasiswaan.notifications.destroy');
});

/*
|--------------------------------------------------------------------------
| Halaman Pengurus UKM (login wajib + role: pengurus_ukm)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pengurus_ukm'])->group(function () {
    Route::get('/pengurus-ukm', [PengurusUkmController::class, 'index'])->name('pengurus-ukm.dashboard');
    Route::get('/pengurus-ukm/events', [PengurusUkmController::class, 'events'])->name('pengurus-ukm.events.index');
    Route::post('/pengurus-ukm/events', [PengurusUkmController::class, 'store'])->name('pengurus-ukm.events.store');
    Route::put('/pengurus-ukm/events/{event}', [PengurusUkmController::class, 'update'])->name('pengurus-ukm.events.update');
    Route::delete('/pengurus-ukm/events/{event}', [PengurusUkmController::class, 'destroy'])->name('pengurus-ukm.events.destroy');
    Route::post('/pengurus-ukm/events/{event}/complete', [PengurusUkmController::class, 'completeEvent'])->name('pengurus-ukm.events.complete');
    Route::post('/pengurus-ukm/events/{id}/create-news', [PengurusUkmController::class, 'createNewsFromEvent'])->name('pengurus-ukm.events.create-news');
    Route::get('/pengurus-ukm/profil', [PengurusUkmController::class, 'profile'])->name('pengurus-ukm.profile');
    Route::put('/pengurus-ukm/profil', [PengurusUkmController::class, 'updateProfile'])->name('pengurus-ukm.profile.update');
    Route::put('/pengurus-ukm/profil/password', [PengurusUkmController::class, 'changePassword'])->name('pengurus-ukm.profile.password');
    Route::post('/pengurus-ukm/profil/send-reset-link', [PasswordResetController::class, 'sendResetLink'])->name('pengurus-ukm.profile.send-reset-link');
    Route::get('/pengurus-ukm/kontak', [PengurusUkmController::class, 'contacts'])->name('pengurus-ukm.contacts');
    
    // Pengumuman Routes
    Route::get('/pengurus-ukm/pengumuman', [PengurusUkmController::class, 'announcements'])->name('pengurus-ukm.announcements.index');
    Route::post('/pengurus-ukm/pengumuman', [PengurusUkmController::class, 'storeAnnouncement'])->name('pengurus-ukm.announcements.store');
    Route::put('/pengurus-ukm/pengumuman/{id}', [PengurusUkmController::class, 'updateAnnouncement'])->name('pengurus-ukm.announcements.update');
    Route::delete('/pengurus-ukm/pengumuman/{id}', [PengurusUkmController::class, 'destroyAnnouncement'])->name('pengurus-ukm.announcements.destroy');
    
    // Pengajuan & Laporan Kegiatan Routes
    Route::get('/pengurus-ukm/pengajuan-laporan', [PengurusUkmController::class, 'submissions'])->name('pengurus-ukm.submissions.index');
    Route::post('/pengurus-ukm/pengajuan-laporan', [PengurusUkmController::class, 'storeSubmission'])->name('pengurus-ukm.submissions.store');
    Route::post('/pengurus-ukm/pengajuan-laporan/{id}/update', [PengurusUkmController::class, 'updateSubmission'])->name('pengurus-ukm.submissions.update');
    
    // Lost & Found Routes
    Route::get('/pengurus-ukm/lost-found', [PengurusUkmController::class, 'lostFound'])->name('pengurus-ukm.lost-found.index');
    Route::post('/pengurus-ukm/lost-found', [PengurusUkmController::class, 'storeLostItem'])->name('pengurus-ukm.lost-found.store');
    Route::post('/pengurus-ukm/lost-found/{id}/status', [PengurusUkmController::class, 'updateLostItemStatus'])->name('pengurus-ukm.lost-found.status');
});

/*
|--------------------------------------------------------------------------
| Password Reset (Public - accessed from email link)
|--------------------------------------------------------------------------
*/
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

/*
|--------------------------------------------------------------------------
| Fallback
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
