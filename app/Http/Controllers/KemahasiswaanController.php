<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\ActivitySubmission;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;

class KemahasiswaanController extends Controller
{
    use \App\Traits\CalendarHelper;

    public function index(): View
    {
        $totalOrganisasiAktif = \App\Models\Organization::where('status', 'Aktif')->count();
        $totalKegiatanBerjalan = \App\Models\Event::whereIn('status', ['upcoming', 'ongoing'])->count();
        $totalProposalMenunggu = \App\Models\ActivitySubmission::where('kind', 'proposal')->whereIn('status', ['diajukan', 'review'])->count();
        $totalLaporanMenunggu = \App\Models\ActivitySubmission::where('kind', 'report')->whereIn('status', ['diajukan', 'review'])->count();

        // Chart Data (Group by month for current year)
        $events = \App\Models\Event::whereYear('start_at', date('Y'))->get();
        $chartData = array_fill(1, 12, 0);
        foreach ($events as $event) {
            if ($event->start_at) {
                $month = (int) $event->start_at->format('n');
                $chartData[$month]++;
            }
        }
        $maxChartValue = max($chartData) > 0 ? max($chartData) : 1;

        $kegiatanMendatang = \App\Models\Event::where('start_at', '>=', now())
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->orderBy('start_at')
            ->take(3)
            ->get();

        $recentAnnouncements = \App\Models\Announcement::with('organization')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('kemahasiswaan.dashboard', [
            'user' => auth()->user(),
            'totalOrganisasiAktif' => $totalOrganisasiAktif,
            'totalKegiatanBerjalan' => $totalKegiatanBerjalan,
            'totalProposalMenunggu' => $totalProposalMenunggu,
            'totalLaporanMenunggu' => $totalLaporanMenunggu,
            'chartData' => $chartData,
            'maxChartValue' => $maxChartValue,
            'kegiatanMendatang' => $kegiatanMendatang,
            'recentAnnouncements' => $recentAnnouncements,
        ]);
    }

    public function organizations(\Illuminate\Http\Request $request): View
    {
        $q = $request->input('q');
        $category = $request->input('category');
        $perPage = 10;

        $query = \App\Models\Organization::query();
        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('kategori', 'like', "%{$q}%")
                    ->orWhere('field', 'like', "%{$q}%")
                    ->orWhere('ketua_name', 'like', "%{$q}%");
            });
        }

        if (!empty($category)) {
            $query->where('kategori', $category);
        }

        $organizations = $query->orderBy('name')->paginate($perPage)->withQueryString();

        // counts for stats (global, not page-limited)
        $totalOrgs = \App\Models\Organization::count();
        $countBEM = \App\Models\Organization::where('kategori', 'BEM')->count();
        $countChoir = \App\Models\Organization::where('kategori', 'Choir')->count();
        $countCreativeClub = \App\Models\Organization::where('kategori', 'Creative Club')->count();
        $countMinistries = \App\Models\Organization::where('kategori', 'Ministries')->count();
        $countIkatanDaerah = \App\Models\Organization::where('kategori', 'Ikatan Daerah')->count();

        $dosenRaw = json_decode(file_get_contents(base_path('data/dosen.json')), true) ?? [];
        $studentsRaw = json_decode(file_get_contents(base_path('data/students.json')), true) ?? [];
        
        $dosen = collect();
        foreach ($dosenRaw as $faculty => $members) {
            foreach ($members as $m) {
                $dosen->push($m['nama']);
            }
        }
        $dosen = $dosen->sort()->values();

        $students = collect($studentsRaw['students'] ?? [])->pluck('nama')->sort()->values();

        return view('kemahasiswaan.organisasi.index', [
            'user' => auth()->user(),
            'organizations' => $organizations,
            'totalOrgs' => $totalOrgs,
            'countBEM' => $countBEM,
            'countChoir' => $countChoir,
            'countCreativeClub' => $countCreativeClub,
            'countMinistries' => $countMinistries,
            'countIkatanDaerah' => $countIkatanDaerah,
            'q' => $q,
            'category' => $category,
            'dosen' => $dosen,
            'students' => $students,
        ]);
    }

    public function submissions(\Illuminate\Http\Request $request): View
    {
        $tabInput = $request->string('tab')->lower()->value();
        $tab = in_array($tabInput, ['kegiatan', 'event', 'report']) ? $tabInput : 'kegiatan';
        $query = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));

        $qb = \App\Models\ActivitySubmission::with(['organization', 'user']);
        
        if ($tab === 'report') {
            $qb->where('kind', 'report');
        } elseif ($tab === 'event') {
            $qb->where('kind', 'proposal')->whereNotNull('event_id');
        } else {
            $qb->where('kind', 'proposal')->whereNull('event_id');
        }

        if ($status !== '') {
            $qb->where('status', $status);
        }

        if ($query !== '') {
            $q = $query;
            $qb->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('subtitle', 'like', "%{$q}%")
                    ->orWhereHas('organization', function ($oq) use ($q) {
                        $oq->where('name', 'like', "%{$q}%");
                    });
            });
        }

        $rows = $qb->orderBy('event_date', 'desc')->get();

        $items = $rows->map(function ($r) {
            $statusLabels = [
                'diajukan' => 'Diajukan',
                'review' => 'Sedang Direview',
                'revisi' => 'Revisi',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
            ];
            return [
                'id' => $r->id,
                'title' => $r->title,
                'subtitle' => $r->subtitle,
                'organization' => $r->organization ? $r->organization->name : null,
                'submitted_by' => $r->user ? $r->user->name : null,
                'submitted_email' => $r->user ? $r->user->email : null,
                'jenis_kegiatan' => $r->jenis_kegiatan,
                'penanggung_jawab' => $r->penanggung_jawab,
                'event_date' => optional($r->event_date)->toDateString(),
                'waktu' => $r->waktu,
                'lokasi' => $r->lokasi,
                'estimasi_peserta' => $r->estimasi_peserta,
                'status' => $r->status,
                'status_label' => $statusLabels[$r->status] ?? ucfirst($r->status),
                'kind' => $r->kind,
                'description' => $r->description,
                'poster_path' => $r->poster_path,
                'proposal_path' => $r->proposal_path,
                'lpj_path' => $r->lpj_path,
                'lpj_catatan' => $r->lpj_catatan,
                'registration_link' => $r->registration_link,
                'revision_note' => $r->revision_note,
                'created_at' => $r->created_at ? $r->created_at->translatedFormat('d M Y, H:i') : null,
            ];
        })->values();

        return view('kemahasiswaan.pengajuan.index', [
            'user' => auth()->user(),
            'items' => $items,
            'tab' => $tab,
            'q' => $query,
            'status' => $status,
        ]);
    }

    public function review(Request $request, $id)
    {
        $data = $request->validate([
            'action' => 'required|string|in:diajukan,review,revisi,approved,rejected',
            'notes' => 'nullable|string',
            'revision_note' => 'nullable|string|max:2000',
        ]);

        // Try updating DB-backed submission if exists
        $submission = ActivitySubmission::find($id);
        if ($submission) {
            $submission->status = $data['action'];

            // Save revision note if action is revisi
            if ($data['action'] === 'revisi' && !empty($data['revision_note'])) {
                $submission->revision_note = $data['revision_note'];
            }

            $submission->save();

            // Sync with Event so Mahasiswa can see it, and handle un-approving
            if ($submission->event_id) {
                $event = \App\Models\Event::find($submission->event_id);
                if ($event) {
                    if ($data['action'] === 'approved') {
                        $event->status = 'upcoming';
                    } else {
                        $event->status = 'draft';
                    }
                    $event->save();
                }
            }

            // Notify the pengurus UKM user about the status change
            $submissionOwner = User::find($submission->user_id);
            if ($submissionOwner) {
                $statusLabels = [
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'revisi' => 'Perlu Revisi',
                ];
                $statusLabel = $statusLabels[$data['action']] ?? ucfirst($data['action']);
                $notifType = $data['action'] === 'revisi' ? 'revisi_kegiatan' : 'pengajuan_kegiatan';
                $notifIcon = $data['action'] === 'approved' ? 'document' : ($data['action'] === 'revisi' ? 'edit' : 'info');

                $submissionOwner->notify(new GeneralNotification(
                    "Pengajuan {$statusLabel}",
                    "Pengajuan \"{$submission->title}\" telah {$statusLabel} oleh Kemahasiswaan." .
                        (!empty($data['revision_note']) ? " Catatan: {$data['revision_note']}" : ''),
                    $notifType,
                    $notifIcon,
                    route('pengurus-ukm.submissions.index')
                ));
            }

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Status berhasil diperbarui', 'submission' => $submission], 200);
            }

            return back()->with('success', 'Status pengajuan berhasil diperbarui menjadi ' . ucfirst($data['action']));
        }

        // If no DB record, return 404
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan'], 404);
        }

        return back()->with('error', 'Pengajuan tidak ditemukan.');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create', \App\Models\Organization::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:50',
            'kategori' => 'required|string|max:100',
            'level' => 'nullable|string|max:100',
            'account_email' => 'required|email|max:255|unique:users,email',
            'account_password' => 'required|string|min:8',
            'description' => 'nullable|string',
            // Struktur pengurus
            'ketua_name' => 'nullable|string|max:255',
            'advisor_name' => 'nullable|string|max:255',
            'advisor_phone' => 'nullable|string|max:50',
            'advisor_email' => 'nullable|email|max:255|ends_with:@unklab.ac.id',
            'chair_phone' => 'nullable|string|max:50',
            'chair_email' => 'nullable|email|max:255|ends_with:@student.unklab.ac.id',
            'secretary_name' => 'nullable|string|max:255',
            'secretary_phone' => 'nullable|string|max:50',
            'secretary_email' => 'nullable|email|max:255|ends_with:@student.unklab.ac.id',
            'treasurer_name' => 'nullable|string|max:255',
            'treasurer_phone' => 'nullable|string|max:50',
            'treasurer_email' => 'nullable|email|max:255|ends_with:@student.unklab.ac.id',
        ], [
            'account_email.unique' => 'Email akun ini sudah digunakan oleh user lain.',
            'account_password.required' => 'Password wajib diisi saat membuat organisasi baru.',
            'advisor_email.ends_with' => 'Email Pembina harus menggunakan domain @unklab.ac.id',
            'chair_email.ends_with' => 'Email Ketua harus menggunakan domain @student.unklab.ac.id',
            'secretary_email.ends_with' => 'Email Sekretaris harus menggunakan domain @student.unklab.ac.id',
            'treasurer_email.ends_with' => 'Email Bendahara harus menggunakan domain @student.unklab.ac.id',
        ]);

        // Status selalu Aktif saat pertama kali dibuat
        $data['status'] = 'Aktif';

        // Use DB transaction to ensure atomicity
        $credentials = null;
        $org = null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request, &$org, &$credentials) {
            // Create the user account first
            $acctEmail = $data['account_email'];
            $acctPassword = $request->input('account_password');

            $user = \App\Models\User::create([
                'name' => $data['name'],
                'email' => $acctEmail,
                'password' => $acctPassword,
                'role' => 'pengurus_ukm',
            ]);

            $data['account_user_id'] = $user->id;

            $org = \App\Models\Organization::create($data);

            $credentials = ['status' => 'created', 'email' => $acctEmail, 'password' => $acctPassword];
        });

        $resp = ['message' => 'Organisasi berhasil disimpan', 'organization' => $org];
        if (!is_null($credentials)) {
            $resp['credentials'] = $credentials;
        }
        return response()->json($resp, 201);
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        $org = \App\Models\Organization::findOrFail($id);

        $this->authorize('update', $org);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:50',
            'kategori' => 'required|string|max:100',
            'level' => 'nullable|string|max:100',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'account_email' => 'required|email|max:255|unique:users,email,' . $org->account_user_id,
            'account_password' => 'nullable|string|min:8',
            'description' => 'nullable|string',
            // Struktur pengurus
            'ketua_name' => 'nullable|string|max:255',
            'advisor_name' => 'nullable|string|max:255',
            'advisor_phone' => 'nullable|string|max:50',
            'advisor_email' => 'nullable|email|max:255|ends_with:@unklab.ac.id',
            'chair_phone' => 'nullable|string|max:50',
            'chair_email' => 'nullable|email|max:255|ends_with:@student.unklab.ac.id',
            'secretary_name' => 'nullable|string|max:255',
            'secretary_phone' => 'nullable|string|max:50',
            'secretary_email' => 'nullable|email|max:255|ends_with:@student.unklab.ac.id',
            'treasurer_name' => 'nullable|string|max:255',
            'treasurer_phone' => 'nullable|string|max:50',
            'treasurer_email' => 'nullable|email|max:255|ends_with:@student.unklab.ac.id',
        ], [
            'account_email.unique' => 'Email akun ini sudah digunakan oleh user lain.',
            'advisor_email.ends_with' => 'Email Pembina harus menggunakan domain @unklab.ac.id',
            'chair_email.ends_with' => 'Email Ketua harus menggunakan domain @student.unklab.ac.id',
            'secretary_email.ends_with' => 'Email Sekretaris harus menggunakan domain @student.unklab.ac.id',
            'treasurer_email.ends_with' => 'Email Bendahara harus menggunakan domain @student.unklab.ac.id',
        ]);

        if ($data['kategori'] === 'BEM' && $data['status'] === 'Nonaktif') {
            abort(403, 'Akun BEM tidak dapat dinonaktifkan.');
        }

        $oldStatus = $org->status;
        $org->update($data);

        // Send email notification to organization if status changed to Nonaktif
        if ($oldStatus !== 'Nonaktif' && $org->status === 'Nonaktif') {
            $orgEmail = $request->input('custom_email_to') ?: $org->account_email;
            
            $subject = $request->input('custom_email_subject') ?: "Notifikasi: Akun Organisasi {$org->name} Dinonaktifkan";
            
            $messageBody = $request->input('custom_email_message') ?: 
                "Yth. Pengurus {$org->name},\n\n" .
                "Dengan ini kami menginformasikan bahwa akun organisasi Anda telah dinonaktifkan " .
                "oleh pihak Kemahasiswaan pada tanggal " . now()->translatedFormat('d F Y, H:i') . ".\n\n" .
                "Silakan hubungi pihak Kemahasiswaan untuk informasi lebih lanjut.\n\n" .
                "Hormat kami,\nSistem UForum - Kemahasiswaan UNKLAB";

            if ($orgEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::raw($messageBody, function ($message) use ($orgEmail, $subject) {
                        $message->to($orgEmail)->subject($subject);
                    });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send org deactivation email on update: ' . $e->getMessage());
                }
            }
        }

        // Update the related user's email or password if changed
        if ($org->account_user_id) {
            $user = \App\Models\User::find($org->account_user_id);
            if ($user) {
                if ($user->email !== $data['account_email']) {
                    $user->email = $data['account_email'];
                }
                if ($request->filled('account_password')) {
                    $user->password = $request->input('account_password');
                }
                $user->save();
            }
        }

        return response()->json(['message' => 'Organisasi berhasil diupdate', 'organization' => $org->fresh()], 200);
    }

    public function destroy($id)
    {
        $org = \App\Models\Organization::findOrFail($id);

        $this->authorize('delete', $org);

        $org->delete();

        return response()->json(['message' => 'Organisasi berhasil dihapus'], 200);
    }

    public function show($id)
    {
        $org = \App\Models\Organization::findOrFail($id);
        return response()->json(['organization' => $org], 200);
    }

    public function edit($id)
    {
        $org = \App\Models\Organization::findOrFail($id);
        $users = \App\Models\User::orderBy('name')->get();

        return view('kemahasiswaan.organisasi.edit', [
            'organization' => $org,
            'users' => $users,
        ]);
    }

    public function resetAccount(\Illuminate\Http\Request $request, $id)
    {
        $org = \App\Models\Organization::findOrFail($id);

        $this->authorize('update', $org);

        if (empty($org->account_user_id)) {
            return response()->json(['message' => 'Organisasi belum terhubung dengan akun pengurus'], 422);
        }

        $user = \App\Models\User::find($org->account_user_id);
        if (!$user) {
            return response()->json(['message' => 'Akun pengguna tidak ditemukan'], 422);
        }

        $data = $request->validate([
            'password' => 'nullable|string|min:8|max:255',
        ]);

        // Use provided password if present, otherwise generate
        $plain = $data['password'] ?? \Illuminate\Support\Str::random(12);
        $user->password = $plain;
        $user->save();

        // Log audit: who reset and when (do NOT store the password)
        \Illuminate\Support\Facades\DB::table('organization_account_resets')->insert([
            'organization_id' => $org->id,
            'admin_user_id' => auth()->id(),
            'ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Password berhasil direset', 'credentials' => ['email' => $user->email, 'password' => $plain, 'status' => 'reset']], 200);
    }

    public function toggleStatus(\Illuminate\Http\Request $request, $id)
    {
        $org = \App\Models\Organization::findOrFail($id);
        $this->authorize('toggleStatus', $org);

        if ($org->kategori === 'BEM') {
            abort(403, 'Akun BEM tidak dapat dinonaktifkan.');
        }

        $org->status = $org->status === 'Aktif' ? 'Nonaktif' : 'Aktif';
        $org->save();

        // Send email notification to advisor and organization when organization is deactivated
        if ($org->status === 'Nonaktif') {
            $advisorEmail = $org->advisor_email;
            $orgEmail = $request->input('custom_email_to') ?: $org->account_email;
            
            $subject = $request->input('custom_email_subject') ?: "Notifikasi: Akun Organisasi {$org->name} Dinonaktifkan";
            
            $messageBody = $request->input('custom_email_message') ?: 
                "Yth. Pembina & Pengurus {$org->name},\n\n" .
                "Dengan ini kami menginformasikan bahwa akun organisasi Anda telah dinonaktifkan " .
                "oleh pihak Kemahasiswaan pada tanggal " . now()->translatedFormat('d F Y, H:i') . ".\n\n" .
                "Silakan hubungi pihak Kemahasiswaan untuk informasi lebih lanjut.\n\n" .
                "Hormat kami,\nSistem UForum - Kemahasiswaan UNKLAB";

            $recipients = array_filter([$advisorEmail, $orgEmail]);

            if (!empty($recipients)) {
                try {
                    \Illuminate\Support\Facades\Mail::raw($messageBody, function ($message) use ($recipients, $subject) {
                        $message->to($recipients)->subject($subject);
                    });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send org deactivation email: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => 'Status organisasi berhasil diubah menjadi ' . $org->status,
            'status' => $org->status
        ]);
    }

    public function announcements(\Illuminate\Http\Request $request): View
    {
        $query = \App\Models\Announcement::with('organization');

        $q = $request->input('q');
        $status = $request->input('status');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhereHas('organization', function ($oq) use ($q) {
                        $oq->where('name', 'like', "%{$q}%");
                    });
            });
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $announcements = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $totalCount = \App\Models\Announcement::count();
        $publishedCount = \App\Models\Announcement::whereIn('status', ['terpublikasi', 'sent'])->count();
        $scheduledCount = \App\Models\Announcement::where('status', 'terjadwal')->count();
        $draftCount = \App\Models\Announcement::where('status', 'draft')->count();

        return view('kemahasiswaan.pengumuman.index', [
            'user' => auth()->user(),
            'announcements' => $announcements,
            'totalCount' => $totalCount,
            'publishedCount' => $publishedCount,
            'scheduledCount' => $scheduledCount,
            'draftCount' => $draftCount,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function storeAnnouncement(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'target' => 'required|string|max:100',
            'manual_email' => 'nullable|email|max:255',
            'content' => 'nullable|string',
            'status' => 'required|string|in:draft,terjadwal,terpublikasi',
            'published_at' => 'nullable|date',
        ]);

        $target = $data['target'];
        if ($target === 'Input Manual') {
            if (empty($data['manual_email'])) {
                return response()->json(['errors' => ['manual_email' => ['Alamat email wajib diisi untuk Input Manual']]], 422);
            }
            $target = $data['manual_email'];
        }
        $data['target'] = $target;

        $announcement = \App\Models\Announcement::create($data);

        if ($announcement->status === 'terpublikasi') {
            $announcement->published_at = now();
            $announcement->save();
            $this->sendAnnouncementEmail($announcement);
        }

        return response()->json(['message' => 'Pengumuman berhasil dibuat', 'announcement' => $announcement], 201);
    }

    public function updateAnnouncement(\Illuminate\Http\Request $request, $id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'target' => 'required|string|max:100',
            'manual_email' => 'nullable|email|max:255',
            'content' => 'nullable|string',
            'status' => 'required|string|in:draft,terjadwal,terpublikasi',
            'published_at' => 'nullable|date',
        ]);

        $target = $data['target'];
        if ($target === 'Input Manual') {
            if (empty($data['manual_email'])) {
                return response()->json(['errors' => ['manual_email' => ['Alamat email wajib diisi untuk Input Manual']]], 422);
            }
            $target = $data['manual_email'];
        }
        $data['target'] = $target;

        $oldStatus = $announcement->status;
        $announcement->update($data);

        if ($announcement->status === 'terpublikasi' && $oldStatus !== 'terpublikasi') {
            $announcement->published_at = now();
            $announcement->save();
            $this->sendAnnouncementEmail($announcement);
        }

        return response()->json(['message' => 'Pengumuman berhasil diupdate', 'announcement' => $announcement]);
    }

    public function destroyAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json(['message' => 'Pengumuman berhasil dihapus']);
    }

    public function rejectAnnouncement(\Illuminate\Http\Request $request, $id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->status = 'ditolak';
        $announcement->save();

        return response()->json(['message' => 'Pengumuman berhasil ditolak']);
    }

    public function publishAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->status = 'terpublikasi';
        $announcement->published_at = now();
        $announcement->save();

        $this->sendAnnouncementEmail($announcement);

        return response()->json(['message' => 'Pengumuman berhasil dipublikasikan & email dikirim']);
    }

    private function sendAnnouncementEmail($announcement)
    {
        $emailTarget = $announcement->target;
        if ($emailTarget === 'Semua Mahasiswa') {
            $emailTarget = 'student252@student.unklab.ac.id';
        }

        if (filter_var($emailTarget, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::to($emailTarget)->send(new \App\Mail\AnnouncementMail($announcement));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Email failed: ' . $e->getMessage());
            }
        }
    }

    public function contacts(\Illuminate\Http\Request $request)
    {
        $q = $request->input('q');
        $query = \App\Models\Organization::query();
        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('kategori', 'like', "%{$q}%")
                    ->orWhere('field', 'like', "%{$q}%");
            });
        }

        $organizations = $query->orderBy('name')->get();
        $totalOrgs = \App\Models\Organization::count();
        $countBEM = \App\Models\Organization::where('kategori', 'BEM')->count();
        $countUKM = \App\Models\Organization::where('kategori', 'UKM')->count();

        return view('shared.contacts', [
            'user' => auth()->user(),
            'organizations' => $organizations,
            'totalOrgs' => $totalOrgs,
            'countBEM' => $countBEM,
            'countUKM' => $countUKM,
            'q' => $q,
            'title' => 'Kontak Pengurus UKM'
        ]);
    }

    public function calendar(\Illuminate\Http\Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        
        $calendarData = $this->generateCalendarData($year, $month);

        return view('kemahasiswaan.calendar.index', array_merge([
            'user' => auth()->user(),
        ], $calendarData));
    }

    public function storeCalendarEvent(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category' => 'required|string|max:100',
            'location' => 'nullable|string|max:255',
            'organizer' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_holiday' => 'nullable|boolean',
            'extracurricular_blocked' => 'nullable|boolean',
        ]);
        $data['is_holiday'] = $request->boolean('is_holiday');
        $data['extracurricular_blocked'] = $request->boolean('extracurricular_blocked');

        \App\Models\CalendarEvent::create($data);

        return redirect()->route('kemahasiswaan.calendar')->with('success', 'Kegiatan berhasil ditambahkan');
    }

    public function importPdfParse(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $file = $request->file('pdf_file');
        $path = $file->store('temp-imports', 'local');
        $fullPath = storage_path('app/private/' . $path);

        // Fallback for older Laravel
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $path);
        }

        try {
            $parser = new \App\Services\CalendarPdfParserService();
            $events = $parser->parse($fullPath);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca PDF: ' . $e->getMessage());
        }

        if (empty($events)) {
            @unlink($fullPath);
            return back()->with('error', 'Tidak ditemukan event/tanggal di dalam PDF. Pastikan format PDF memiliki tanggal dan judul kegiatan.');
        }

        // Store parsed events in session for preview
        session(['pdf_parsed_events' => $events, 'pdf_temp_path' => $fullPath]);

        return redirect()->route('kemahasiswaan.calendar', ['preview' => 1])
            ->with('success', 'Berhasil mengekstrak ' . count($events) . ' kegiatan dari PDF. Silakan review sebelum menyimpan.');
    }

    public function importPdfSave(\Illuminate\Http\Request $request)
    {
        $events = session('pdf_parsed_events', []);
        if (empty($events)) {
            return back()->with('error', 'Tidak ada data event untuk disimpan. Silakan upload PDF terlebih dahulu.');
        }

        // Get selected events (user can deselect some)
        $selectedIndices = $request->input('selected_events', []);

        $saved = 0;
        foreach ($events as $i => $event) {
            // If user deselected specific events, skip them
            if (!empty($selectedIndices) && !in_array($i, $selectedIndices)) {
                continue;
            }

            // Allow inline title/category edits
            $title = $request->input("event_title_{$i}", $event['title']);
            $category = $request->input("event_category_{$i}", $event['category']);

            \App\Models\CalendarEvent::create([
                'title' => $title,
                'start_date' => $event['start_date'],
                'end_date' => $event['end_date'],
                'category' => $category,
                'location' => $event['location'] ?? null,
                'organizer' => $event['organizer'] ?? 'Import PDF',
                'description' => $event['description'] ?? 'Auto-imported from PDF',
                'is_holiday' => (bool) $request->input("event_is_holiday_{$i}", false),
                'extracurricular_blocked' => (bool) $request->input("event_extracurricular_blocked_{$i}", false),
            ]);
            $saved++;
        }

        // Cleanup
        $tempPath = session('pdf_temp_path');
        if ($tempPath && file_exists($tempPath)) {
            @unlink($tempPath);
        }
        session()->forget(['pdf_parsed_events', 'pdf_temp_path']);

        return redirect()->route('kemahasiswaan.calendar')
            ->with('success', "Berhasil menyimpan {$saved} kegiatan ke kalender!");
    }

    public function notifications(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $user = auth()->user();
        $query = $user->notifications();

        // All notifications for counting
        $allNotifications = $user->notifications()->get();
        $totalNotifications = $allNotifications->count();
        $unreadCount = $user->unreadNotifications()->count();

        // Calculate counts for filters
        $counts = [
            'semua' => $totalNotifications,
            'pengajuan_kegiatan' => $allNotifications->where('data.type', 'pengajuan_kegiatan')->count(),
            'revisi_kegiatan' => $allNotifications->where('data.type', 'revisi_kegiatan')->count(),
            'laporan_masuk' => $allNotifications->where('data.type', 'laporan_masuk')->count(),
            'pesan_baru' => $allNotifications->where('data.type', 'pesan_baru')->count(),
            'perubahan_pengurus' => $allNotifications->where('data.type', 'perubahan_pengurus')->count(),
            'informasi_penting' => $allNotifications->where('data.type', 'informasi_penting')->count(),
        ];

        // Filter functionality
        $filter = $request->query('filter', 'semua');
        
        if ($filter !== 'semua') {
            $query->where('data->type', $filter);
        }

        $filteredNotifications = $query->paginate(15);

        return view('kemahasiswaan.notifications.index', compact('filteredNotifications', 'totalNotifications', 'unreadCount', 'counts', 'filter'));
    }

    public function markAllNotificationsAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }

    public function markNotificationAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return back()->with('success', 'Notifikasi ditandai dibaca.');
    }

    public function deleteNotification($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();
        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

}
