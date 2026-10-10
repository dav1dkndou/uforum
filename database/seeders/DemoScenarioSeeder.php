<?php

namespace Database\Seeders;

use App\Models\ActivitySubmission;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Event;
use App\Models\LostItem;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->resetDemoTables();

            $lecturerNames = $this->loadLecturerNames();
            $studentNames = $this->loadStudentNames();

            $organizations = $this->seedOrganizations($lecturerNames, $studentNames);
            $this->seedCalendarEvents();
            $this->seedAnnouncements($organizations);
            $this->seedCurrentOrganizationWorkflow($organizations['bem'], $studentNames);
            $this->seedPublicEvents($organizations, $studentNames);
            $this->seedLostItems($organizations, $studentNames);
            $this->seedNotifications();
        });
    }

    private function resetDemoTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                'notifications',
                'organization_account_resets',
                'activity_submissions',
                'events',
                'lost_items',
                'announcements',
                'calendar_events',
                'organizations',
            ] as $table) {
                DB::table($table)->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function loadLecturerNames(): array
    {
        $raw = json_decode(file_get_contents(base_path('data/dosen.json')), true) ?? [];

        return collect($raw)
            ->flatMap(fn (array $members) => $members)
            ->pluck('nama')
            ->values()
            ->all();
    }

    private function loadStudentNames(): array
    {
        $raw = json_decode(file_get_contents(base_path('data/students.json')), true) ?? [];

        return collect($raw['students'] ?? [])
            ->pluck('nama')
            ->values()
            ->all();
    }

    private function studentName(array $students, int $index, string $fallback): string
    {
        return $students[$index] ?? $fallback;
    }

    private function lecturerName(array $lecturers, int $index, string $fallback): string
    {
        return $lecturers[$index] ?? $fallback;
    }

    private function seedOrganizations(array $lecturers, array $students): array
    {
        $bemUserId = User::where('email', 'bem.universitas@unklab.ac.id')->value('id');

        $definitions = [
            'bem' => [
                'name' => 'BEM UNKLAB Universitas',
                'abbreviation' => 'BEM U',
                'kategori' => 'BEM',
                'field' => 'Kemahasiswaan',
                'level' => 'Universitas',
                'description' => 'Badan eksekutif mahasiswa yang mengoordinasikan aspirasi, advokasi, dan program kerja lintas organisasi mahasiswa.',
                'motto' => 'Bersinergi, Melayani, Berdampak',
                'visi' => 'Menjadi organisasi mahasiswa yang responsif, profesional, dan berdampak bagi kampus.',
                'misi' => "- Menguatkan koordinasi antarorganisasi mahasiswa\n- Menyediakan ruang aspirasi dan kolaborasi\n- Mengawal program kerja yang transparan dan terukur",
                'budaya_nilai' => 'Integritas, Kolaborasi, Kepedulian',
                'program_kegiatan' => "- Rapat koordinasi bulanan\n- Bakti sosial mahasiswa\n- Leadership camp\n- Forum aspirasi mahasiswa",
                'instagram' => 'https://instagram.com/bemunklab',
                'whatsapp' => '081234567890',
                'website' => 'https://bem.unklab.ac.id',
                'member_count' => 42,
                'is_open_recruitment' => true,
                'recruitment_link' => 'https://unklab.ac.id/pendaftaran-bem',
                'recruitment_req' => "- Mahasiswa aktif\n- Minimal semester 2\n- Bersedia mengikuti masa kaderisasi",
                'ketua_name' => $this->studentName($students, 0, 'Navys Kurniawan Joshua Gosal'),
                'chair_phone' => '081234567891',
                'chair_email' => 'ketua.bem@unklab.ac.id',
                'secretary_name' => $this->studentName($students, 1, 'Juliani Iriani Kalangie'),
                'secretary_phone' => '081234567892',
                'secretary_email' => 'sekretariat.bem@unklab.ac.id',
                'treasurer_name' => $this->studentName($students, 2, 'Mario Jonatan Kolibu'),
                'treasurer_phone' => '081234567893',
                'treasurer_email' => 'bendahara.bem@unklab.ac.id',
                'advisor_name' => $this->lecturerName($lecturers, 0, 'Stenly R. Pungus, S.Kom., MT., M.M., Ph.D'),
                'advisor_phone' => '081211223344',
                'advisor_email' => 'advisor.bem@unklab.ac.id',
                'status' => 'Aktif',
                'account_user_id' => $bemUserId,
                'account_email' => 'bem.universitas@unklab.ac.id',
            ],
            'choir' => [
                'name' => 'Paduan Suara Vox Angelica',
                'abbreviation' => 'Vox',
                'kategori' => 'Choir',
                'field' => 'Seni dan Musik',
                'level' => 'Universitas',
                'description' => 'Wadah pengembangan minat dan bakat vokal mahasiswa dalam liturgi, konser, dan kegiatan seni kampus.',
                'motto' => 'Harmoni dalam Pelayanan',
                'visi' => 'Menjadi tim paduan suara yang disiplin, harmonis, dan inspiratif.',
                'misi' => "- Melatih teknik vokal secara berkesinambungan\n- Menghadirkan penampilan yang berkualitas\n- Membina semangat kebersamaan antaranggota",
                'budaya_nilai' => 'Disiplin, Kekompakan, Kerendahan Hati',
                'program_kegiatan' => "- Latihan vokal mingguan\n- Konser amal\n- Pelayanan ibadah kampus",
                'instagram' => 'https://instagram.com/voxangelicaunklab',
                'whatsapp' => '081234567894',
                'website' => 'https://voxangelica.unklab.ac.id',
                'member_count' => 28,
                'is_open_recruitment' => false,
                'recruitment_link' => null,
                'recruitment_req' => null,
                'ketua_name' => $this->studentName($students, 3, 'Brandon Davidson Kulon'),
                'chair_phone' => '081234567894',
                'chair_email' => 'ketua.vox@unklab.ac.id',
                'secretary_name' => $this->studentName($students, 4, 'Carolina Pears Pamela Langi'),
                'secretary_phone' => '081234567895',
                'secretary_email' => 'sekretariat.vox@unklab.ac.id',
                'treasurer_name' => $this->studentName($students, 5, 'Jonathan Reinald Lapian'),
                'treasurer_phone' => '081234567896',
                'treasurer_email' => 'bendahara.vox@unklab.ac.id',
                'advisor_name' => $this->lecturerName($lecturers, 1, 'Semmy W. Taju, S.Kom., M.S., Ph.D'),
                'advisor_phone' => '081211223345',
                'advisor_email' => 'advisor.vox@unklab.ac.id',
                'status' => 'Aktif',
                'account_user_id' => null,
                'account_email' => 'voxangelica@unklab.ac.id',
            ],
            'creative' => [
                'name' => 'Creative Club Pixel UNKLAB',
                'abbreviation' => 'Pixel',
                'kategori' => 'Creative Club',
                'field' => 'Desain dan Multimedia',
                'level' => 'Universitas',
                'description' => 'Komunitas kreatif untuk desain grafis, fotografi, video, dan produksi konten digital mahasiswa.',
                'motto' => 'Karya Kecil, Dampak Besar',
                'visi' => 'Menjadi ruang kreatif mahasiswa yang produktif dan kolaboratif.',
                'misi' => "- Mengasah keterampilan desain dan multimedia\n- Mendorong produksi konten kampus yang relevan\n- Membuka jejaring kolaborasi antar kreator muda",
                'budaya_nilai' => 'Kreatif, Adaptif, Kolaboratif',
                'program_kegiatan' => "- Kelas desain mingguan\n- Produksi konten kampus\n- Pameran karya anggota",
                'instagram' => 'https://instagram.com/pixelunklab',
                'whatsapp' => '081234567897',
                'website' => 'https://pixel.unklab.ac.id',
                'member_count' => 24,
                'is_open_recruitment' => true,
                'recruitment_link' => 'https://unklab.ac.id/pendaftaran-pixel',
                'recruitment_req' => "- Memiliki minat desain atau multimedia\n- Bersedia mengikuti jadwal produksi",
                'ketua_name' => $this->studentName($students, 6, 'Clarabelle Felicia Lumintang'),
                'chair_phone' => '081234567897',
                'chair_email' => 'ketua.pixel@unklab.ac.id',
                'secretary_name' => $this->studentName($students, 7, 'Mutiara Makarawung'),
                'secretary_phone' => '081234567898',
                'secretary_email' => 'sekretariat.pixel@unklab.ac.id',
                'treasurer_name' => $this->studentName($students, 8, 'David Albrian Manorek'),
                'treasurer_phone' => '081234567899',
                'treasurer_email' => 'bendahara.pixel@unklab.ac.id',
                'advisor_name' => $this->lecturerName($lecturers, 2, 'Jimmy H. Moedjahedy, S.Kom., M.M., M.Kom'),
                'advisor_phone' => '081211223346',
                'advisor_email' => 'advisor.pixel@unklab.ac.id',
                'status' => 'Aktif',
                'account_user_id' => null,
                'account_email' => 'pixel@unklab.ac.id',
            ],
            'ministries' => [
                'name' => 'Pelayanan Mahasiswa Advent',
                'abbreviation' => 'PMA',
                'kategori' => 'Ministries',
                'field' => 'Kerohanian',
                'level' => 'Universitas',
                'description' => 'Ruang pelayanan, pembinaan rohani, dan pengembangan kepemimpinan mahasiswa berbasis komunitas iman.',
                'motto' => 'Tumbuh dalam Iman dan Pelayanan',
                'visi' => 'Menjadi organisasi pelayanan yang relevan, hangat, dan berdampak.',
                'misi' => "- Mengadakan pembinaan rohani berkala\n- Menyelenggarakan pelayanan sosial\n- Menumbuhkan kepedulian dan empati",
                'budaya_nilai' => 'Iman, Kasih, Pelayanan',
                'program_kegiatan' => "- Ibadah mingguan\n- Retreat mahasiswa\n- Aksi sosial komunitas",
                'instagram' => 'https://instagram.com/pmaunklab',
                'whatsapp' => '081234567900',
                'website' => 'https://pma.unklab.ac.id',
                'member_count' => 19,
                'is_open_recruitment' => false,
                'recruitment_link' => null,
                'recruitment_req' => null,
                'ketua_name' => $this->studentName($students, 9, 'Eden Junior Mirontoneng'),
                'chair_phone' => '081234567900',
                'chair_email' => 'ketua.pma@unklab.ac.id',
                'secretary_name' => $this->studentName($students, 10, 'Nain King Roberto Najoan'),
                'secretary_phone' => '081234567901',
                'secretary_email' => 'sekretariat.pma@unklab.ac.id',
                'treasurer_name' => $this->studentName($students, 11, 'Kyra Christina Pakasi'),
                'treasurer_phone' => '081234567902',
                'treasurer_email' => 'bendahara.pma@unklab.ac.id',
                'advisor_name' => $this->lecturerName($lecturers, 3, 'Ir. Marchel Timothy Tombeng, S.Kom., MS'),
                'advisor_phone' => '081211223347',
                'advisor_email' => 'advisor.pma@unklab.ac.id',
                'status' => 'Aktif',
                'account_user_id' => null,
                'account_email' => 'pma@unklab.ac.id',
            ],
            'regional' => [
                'name' => 'Ikatan Mahasiswa Minahasa',
                'abbreviation' => 'IMM',
                'kategori' => 'Ikatan Daerah',
                'field' => 'Sosial dan Budaya',
                'level' => 'Universitas',
                'description' => 'Komunitas mahasiswa daerah yang menguatkan silaturahmi, budaya, dan jaringan antaranggota.',
                'motto' => 'Merawat Identitas, Membangun Kebersamaan',
                'visi' => 'Menjadi wadah persaudaraan daerah yang aktif dan inklusif.',
                'misi' => "- Menjaga nilai budaya daerah\n- Menjalin kebersamaan antaranggota\n- Berkontribusi dalam kegiatan kampus",
                'budaya_nilai' => 'Persaudaraan, Tanggung Jawab, Hormat',
                'program_kegiatan' => "- Malam budaya daerah\n- Diskusi mahasiswa daerah\n- Bakti sosial komunitas",
                'instagram' => 'https://instagram.com/immunklab',
                'whatsapp' => '081234567903',
                'website' => 'https://imm.unklab.ac.id',
                'member_count' => 31,
                'is_open_recruitment' => false,
                'recruitment_link' => null,
                'recruitment_req' => null,
                'ketua_name' => $this->studentName($students, 12, 'Michael Samuel Deljovi Wagey'),
                'chair_phone' => '081234567903',
                'chair_email' => 'ketua.imm@unklab.ac.id',
                'secretary_name' => $this->studentName($students, 13, 'Virginia Eva Maria Rumuat'),
                'secretary_phone' => '081234567904',
                'secretary_email' => 'sekretariat.imm@unklab.ac.id',
                'treasurer_name' => $this->studentName($students, 14, 'Andrew Jonathan Sagai'),
                'treasurer_phone' => '081234567905',
                'treasurer_email' => 'bendahara.imm@unklab.ac.id',
                'advisor_name' => $this->lecturerName($lecturers, 4, 'Prof. Andrew T. Liem, S.Si., MT., Ph.D'),
                'advisor_phone' => '081211223348',
                'advisor_email' => 'advisor.imm@unklab.ac.id',
                'status' => 'Aktif',
                'account_user_id' => null,
                'account_email' => 'imm@unklab.ac.id',
            ],
            'ukm' => [
                'name' => 'UKM Cendekia Digital',
                'abbreviation' => 'CenDig',
                'kategori' => 'UKM',
                'field' => 'Teknologi dan Inovasi',
                'level' => 'Universitas',
                'description' => 'Unit kegiatan mahasiswa yang berfokus pada inovasi digital, coding, dan pengembangan produk teknologi.',
                'motto' => 'Belajar, Bangun, Berdampak',
                'visi' => 'Menjadi ruang belajar teknologi yang progresif dan aplikatif.',
                'misi' => "- Mengadakan kelas teknologi rutin\n- Mendorong proyek inovasi mahasiswa\n- Menghubungkan anggota dengan komunitas industri",
                'budaya_nilai' => 'Inovatif, Rasa Ingin Tahu, Tangguh',
                'program_kegiatan' => "- Coding clinic\n- Hackathon internal\n- Demo day proyek anggota",
                'instagram' => 'https://instagram.com/cendigunklab',
                'whatsapp' => '081234567906',
                'website' => 'https://cendig.unklab.ac.id',
                'member_count' => 37,
                'is_open_recruitment' => true,
                'recruitment_link' => 'https://unklab.ac.id/pendaftaran-cendig',
                'recruitment_req' => "- Minat pada teknologi\n- Bersedia belajar secara mandiri",
                'ketua_name' => $this->studentName($students, 15, 'Draisen Rey'),
                'chair_phone' => '081234567906',
                'chair_email' => 'ketua.cendig@unklab.ac.id',
                'secretary_name' => $this->studentName($students, 16, 'Alexander Jonatan Tukoshi Rantung'),
                'secretary_phone' => '081234567907',
                'secretary_email' => 'sekretariat.cendig@unklab.ac.id',
                'treasurer_name' => $this->studentName($students, 17, 'Cleymency Richarde Kaparang'),
                'treasurer_phone' => '081234567908',
                'treasurer_email' => 'bendahara.cendig@unklab.ac.id',
                'advisor_name' => $this->lecturerName($lecturers, 5, 'Debby E. Sondakh, S.Kom., M.T., Ph.D'),
                'advisor_phone' => '081211223349',
                'advisor_email' => 'advisor.cendig@unklab.ac.id',
                'status' => 'Aktif',
                'account_user_id' => null,
                'account_email' => 'cendig@unklab.ac.id',
            ],
        ];

        $organizations = [];

        foreach ($definitions as $key => $data) {
            $organizations[$key] = Organization::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        return $organizations;
    }

    private function seedCalendarEvents(): void
    {
        $baseDate = Carbon::now()->startOfDay();

        $events = [
            [
                'title' => 'Perkuliahan Tatap Muka',
                'start_date' => $baseDate->copy()->addDays(1),
                'end_date' => $baseDate->copy()->addDays(1),
                'category' => 'Kegiatan Akademik',
                'location' => 'Ruang Kuliah Masing-masing',
                'organizer' => 'Kemahasiswaan',
                'description' => 'Agenda perkuliahan reguler semester berjalan.',
                'is_holiday' => false,
                'extracurricular_blocked' => false,
            ],
            [
                'title' => 'Rapat Koordinasi BEM Universitas',
                'start_date' => $baseDate->copy()->addDays(2),
                'end_date' => $baseDate->copy()->addDays(2),
                'category' => 'Kegiatan Organisasi',
                'location' => 'Ruang BEM',
                'organizer' => 'BEM UNKLAB Universitas',
                'description' => 'Pertemuan rutin untuk sinkronisasi program kerja antar-bidang.',
                'is_holiday' => false,
                'extracurricular_blocked' => false,
            ],
            [
                'title' => 'Latihan Paduan Suara Mingguan',
                'start_date' => $baseDate->copy()->addDays(4),
                'end_date' => $baseDate->copy()->addDays(4),
                'category' => 'Kegiatan Organisasi',
                'location' => 'Aula Musik',
                'organizer' => 'Paduan Suara Vox Angelica',
                'description' => 'Latihan rutin untuk persiapan pelayanan ibadah dan konser amal.',
                'is_holiday' => false,
                'extracurricular_blocked' => false,
            ],
            [
                'title' => 'Libur Nasional',
                'start_date' => $baseDate->copy()->addDays(6),
                'end_date' => $baseDate->copy()->addDays(6),
                'category' => 'Libur',
                'location' => 'Kampus',
                'organizer' => 'Kemahasiswaan',
                'description' => 'Hari libur nasional yang menutup seluruh aktivitas kampus.',
                'is_holiday' => true,
                'extracurricular_blocked' => false,
            ],
            [
                'title' => 'Masa Ujian Tengah Semester',
                'start_date' => $baseDate->copy()->addDays(10),
                'end_date' => $baseDate->copy()->addDays(14),
                'category' => 'Tidak Boleh Berkegiatan',
                'location' => 'Seluruh Kampus',
                'organizer' => 'Kemahasiswaan',
                'description' => 'Periode ujian yang menutup kegiatan ekstrakurikuler sementara.',
                'is_holiday' => false,
                'extracurricular_blocked' => true,
            ],
            [
                'title' => 'Dies Natalis Kampus',
                'start_date' => $baseDate->copy()->addDays(18),
                'end_date' => $baseDate->copy()->addDays(18),
                'category' => 'Event Kampus Besar',
                'location' => 'Lapangan Utama',
                'organizer' => 'Kemahasiswaan',
                'description' => 'Perayaan puncak hari jadi kampus dengan kolaborasi semua organisasi.',
                'is_holiday' => false,
                'extracurricular_blocked' => false,
            ],
        ];

        foreach ($events as $event) {
            CalendarEvent::updateOrCreate(
                [
                    'title' => $event['title'],
                    'start_date' => $event['start_date']->toDateString(),
                ],
                [
                    'title' => $event['title'],
                    'start_date' => $event['start_date']->toDateString(),
                    'end_date' => $event['end_date']->toDateString(),
                    'category' => $event['category'],
                    'location' => $event['location'],
                    'organizer' => $event['organizer'],
                    'description' => $event['description'],
                    'is_holiday' => $event['is_holiday'],
                    'extracurricular_blocked' => $event['extracurricular_blocked'],
                ]
            );
        }
    }

    private function seedAnnouncements(array $organizations): void
    {
        $now = Carbon::now();

        $announcements = [
            [
                'organization_id' => null,
                'title' => 'Pengumuman Akademik Semester Genap',
                'category' => 'Akademik',
                'target' => 'Semua Mahasiswa',
                'content' => 'Seluruh mahasiswa diminta memeriksa jadwal akademik semester genap dan menyesuaikan rencana studi.',
                'status' => 'sent',
                'published_at' => $now->copy()->subDay(),
            ],
            [
                'organization_id' => null,
                'title' => 'Informasi Registrasi Ulang Mahasiswa Aktif',
                'category' => 'Organisasi',
                'target' => 'Semua Mahasiswa',
                'content' => 'Mahasiswa diminta menyesuaikan registrasi ulang, validasi KRS, dan pembayaran sesuai kalender akademik yang berlaku.',
                'status' => 'sent',
                'published_at' => $now->copy()->subHours(12),
            ],
            [
                'organization_id' => null,
                'title' => 'Reminder Batas Pengumpulan LPJ',
                'category' => 'Organisasi',
                'target' => 'Semua Organisasi',
                'content' => 'Batas pengumpulan LPJ semester ini adalah akhir minggu depan. Mohon seluruh organisasi menyiapkan dokumen lengkap.',
                'status' => 'terjadwal',
                'published_at' => $now->copy()->addDays(3),
            ],
            [
                'organization_id' => null,
                'title' => 'Draft Pedoman Kegiatan Mahasiswa',
                'category' => 'Organisasi',
                'target' => 'Semua Organisasi',
                'content' => 'Pedoman baru kegiatan mahasiswa masih dalam tahap finalisasi sebelum dibagikan resmi.',
                'status' => 'draft',
                'published_at' => null,
            ],
            [
                'organization_id' => $organizations['bem']->id,
                'title' => 'BEM UNKLAB Gelar Bakti Sosial',
                'category' => 'Berita',
                'target' => 'Semua Mahasiswa',
                'content' => 'BEM UNKLAB Universitas akan melaksanakan bakti sosial ke desa binaan dengan melibatkan seluruh relawan mahasiswa.',
                'status' => 'terpublikasi',
                'published_at' => $now->copy()->subHours(6),
            ],
            [
                'organization_id' => $organizations['bem']->id,
                'title' => 'Rapat Evaluasi Program Kerja',
                'category' => 'Organisasi',
                'target' => 'Semua Mahasiswa',
                'content' => 'Pengurus BEM akan melakukan evaluasi program kerja dan membuka forum masukan dari mahasiswa.',
                'status' => 'draft',
                'published_at' => null,
            ],
            [
                'organization_id' => $organizations['bem']->id,
                'title' => 'Undangan Koordinasi Pengurus',
                'category' => 'Organisasi',
                'target' => 'advisor.bem@unklab.ac.id',
                'content' => 'Undangan koordinasi internal untuk membahas kesiapan agenda akhir semester.',
                'status' => 'ditolak',
                'published_at' => null,
            ],
            [
                'organization_id' => $organizations['choir']->id,
                'title' => 'Konser Amal Vox Angelica',
                'category' => 'Berita',
                'target' => 'Semua Mahasiswa',
                'content' => 'Paduan Suara Vox Angelica menyiapkan konser amal untuk mendukung program pelayanan sosial kampus.',
                'status' => 'terpublikasi',
                'published_at' => $now->copy()->subDays(2),
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::updateOrCreate(
                [
                    'organization_id' => $announcement['organization_id'],
                    'title' => $announcement['title'],
                ],
                $announcement
            );
        }
    }

    private function seedCurrentOrganizationWorkflow(Organization $organization, array $students): void
    {
        $leader = $this->studentName($students, 0, 'Navys Kurniawan Joshua Gosal');
        $now = Carbon::now();

        $scenarios = [
            [
                'event' => [
                    'title' => 'Seminar Leadership Mahasiswa',
                    'category' => 'Akademik',
                    'start_at' => $now->copy()->addDays(6)->setTime(9, 0),
                    'end_at' => $now->copy()->addDays(6)->setTime(12, 0),
                    'location' => 'Aula Utama UNKLAB',
                    'participants' => 120,
                    'status' => 'upcoming',
                    'registration_link' => 'https://unklab.ac.id/daftar-seminar-leadership',
                ],
                'submission' => [
                    'kind' => 'proposal',
                    'status' => 'approved',
                    'subtitle' => 'Proposal Kegiatan',
                    'description' => 'Seminar leadership untuk membekali mahasiswa dengan kemampuan kepemimpinan dan manajemen organisasi.',
                    'jenis_kegiatan' => 'Seminar',
                    'penanggung_jawab' => $leader,
                    'event_date' => $now->copy()->addDays(6)->toDateString(),
                    'waktu' => '09:00 - 12:00',
                    'lokasi' => 'Aula Utama UNKLAB',
                    'estimasi_peserta' => 120,
                ],
            ],
            [
                'event' => [
                    'title' => 'Bakti Sosial Kampus',
                    'category' => 'Pelayanan/Sosial (PKM)',
                    'start_at' => $now->copy()->subDays(12)->setTime(8, 0),
                    'end_at' => $now->copy()->subDays(12)->setTime(15, 0),
                    'location' => 'Desa Binaan Kolongan',
                    'participants' => 200,
                    'status' => 'selesai',
                    'registration_link' => null,
                ],
                'submission' => [
                    'kind' => 'proposal',
                    'status' => 'approved',
                    'subtitle' => 'Proposal Kegiatan',
                    'description' => 'Bakti sosial mahasiswa untuk mendukung kebutuhan masyarakat di desa binaan sekitar kampus.',
                    'jenis_kegiatan' => 'Pengabdian Masyarakat',
                    'penanggung_jawab' => $leader,
                    'event_date' => $now->copy()->subDays(12)->toDateString(),
                    'waktu' => '08:00 - 15:00',
                    'lokasi' => 'Desa Binaan Kolongan',
                    'estimasi_peserta' => 200,
                ],
            ],
            [
                'event' => [
                    'title' => 'Workshop Public Speaking',
                    'category' => 'Minat/Bakat',
                    'start_at' => $now->copy()->addDays(10)->setTime(13, 0),
                    'end_at' => $now->copy()->addDays(10)->setTime(16, 0),
                    'location' => 'Ruang Seminar 2',
                    'participants' => 80,
                    'status' => 'draft',
                    'registration_link' => 'https://forms.gle/public-speaking-unklab',
                ],
                'submission' => [
                    'kind' => 'proposal',
                    'status' => 'revisi',
                    'subtitle' => 'Proposal Kegiatan',
                    'description' => 'Workshop untuk meningkatkan kemampuan presentasi dan komunikasi publik pengurus organisasi.',
                    'jenis_kegiatan' => 'Workshop',
                    'penanggung_jawab' => $leader,
                    'event_date' => $now->copy()->addDays(10)->toDateString(),
                    'waktu' => '13:00 - 16:00',
                    'lokasi' => 'Ruang Seminar 2',
                    'estimasi_peserta' => 80,
                    'revision_note' => 'Tambahkan jadwal pemateri dan rincian anggaran konsumsi.',
                ],
            ],
            [
                'event' => [
                    'title' => 'Pelatihan Administrasi Organisasi',
                    'category' => 'Kebangsaan',
                    'start_at' => $now->copy()->addDays(14)->setTime(9, 0),
                    'end_at' => $now->copy()->addDays(14)->setTime(12, 0),
                    'location' => 'Ruang Rapat BEM',
                    'participants' => 35,
                    'status' => 'draft',
                    'registration_link' => null,
                ],
                'submission' => [
                    'kind' => 'proposal',
                    'status' => 'rejected',
                    'subtitle' => 'Proposal Kegiatan',
                    'description' => 'Pelatihan untuk menertibkan administrasi dan arsip organisasi selama satu semester.',
                    'jenis_kegiatan' => 'Pelatihan',
                    'penanggung_jawab' => $leader,
                    'event_date' => $now->copy()->addDays(14)->toDateString(),
                    'waktu' => '09:00 - 12:00',
                    'lokasi' => 'Ruang Rapat BEM',
                    'estimasi_peserta' => 35,
                    'revision_note' => 'Format proposal belum sesuai standar kemahasiswaan.',
                ],
            ],
        ];

        foreach ($scenarios as $scenario) {
            $event = Event::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $scenario['event']['title'],
                ],
                [
                    'organization_id' => $organization->id,
                    'user_id' => $organization->account_user_id,
                    'submission_id' => null,
                    'title' => $scenario['event']['title'],
                    'category' => $scenario['event']['category'],
                    'poster_path' => null,
                    'description' => $scenario['submission']['description'],
                    'start_at' => $scenario['event']['start_at'],
                    'end_at' => $scenario['event']['end_at'],
                    'location' => $scenario['event']['location'],
                    'registration_link' => $scenario['event']['registration_link'],
                    'participants' => $scenario['event']['participants'],
                    'status' => $scenario['event']['status'],
                ]
            );

            $submission = ActivitySubmission::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $scenario['event']['title'],
                    'kind' => $scenario['submission']['kind'],
                    'event_id' => $event->id,
                ],
                [
                    'organization_id' => $organization->id,
                    'user_id' => $organization->account_user_id,
                    'event_id' => $event->id,
                    'title' => $scenario['event']['title'],
                    'subtitle' => $scenario['submission']['subtitle'],
                    'description' => $scenario['submission']['description'],
                    'event_date' => $scenario['submission']['event_date'],
                    'kind' => $scenario['submission']['kind'],
                    'status' => $scenario['submission']['status'],
                    'jenis_kegiatan' => $scenario['submission']['jenis_kegiatan'],
                    'penanggung_jawab' => $scenario['submission']['penanggung_jawab'],
                    'poster_path' => null,
                    'proposal_path' => null,
                    'lpj_path' => null,
                    'lpj_catatan' => null,
                    'registration_link' => $scenario['event']['registration_link'],
                    'revision_note' => $scenario['submission']['revision_note'] ?? null,
                    'waktu' => $scenario['submission']['waktu'],
                    'lokasi' => $scenario['submission']['lokasi'],
                    'estimasi_peserta' => $scenario['submission']['estimasi_peserta'],
                ]
            );

            $event->update(['submission_id' => $submission->id]);
        }

        $proposalEntries = [
            [
                'title' => 'Seminar Karir Mahasiswa',
                'subtitle' => 'Proposal Kegiatan',
                'description' => 'Proposal seminar karir untuk membantu mahasiswa mempersiapkan diri memasuki dunia kerja.',
                'event_date' => $now->copy()->addDays(16)->toDateString(),
                'kind' => 'proposal',
                'status' => 'diajukan',
                'jenis_kegiatan' => 'Seminar',
                'penanggung_jawab' => $this->studentName($students, 4, 'Carolina Pears Pamela Langi'),
                'waktu' => '13:00 - 16:00',
                'lokasi' => 'Aula Utama UNKLAB',
                'estimasi_peserta' => 150,
            ],
            [
                'title' => 'Workshop Kewirausahaan Mahasiswa',
                'subtitle' => 'Proposal Kegiatan',
                'description' => 'Workshop untuk mendorong minat kewirausahaan dan ide bisnis kecil mahasiswa.',
                'event_date' => $now->copy()->addDays(18)->toDateString(),
                'kind' => 'proposal',
                'status' => 'review',
                'jenis_kegiatan' => 'Workshop',
                'penanggung_jawab' => $this->studentName($students, 5, 'Jonathan Reinald Lapian'),
                'waktu' => '09:00 - 12:00',
                'lokasi' => 'Ruang Seminar 1',
                'estimasi_peserta' => 60,
            ],
        ];

        foreach ($proposalEntries as $proposal) {
            ActivitySubmission::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $proposal['title'],
                    'kind' => $proposal['kind'],
                    'event_id' => null,
                ],
                [
                    'organization_id' => $organization->id,
                    'user_id' => $organization->account_user_id,
                    'event_id' => null,
                    'title' => $proposal['title'],
                    'subtitle' => $proposal['subtitle'],
                    'description' => $proposal['description'],
                    'event_date' => $proposal['event_date'],
                    'kind' => $proposal['kind'],
                    'status' => $proposal['status'],
                    'jenis_kegiatan' => $proposal['jenis_kegiatan'],
                    'penanggung_jawab' => $proposal['penanggung_jawab'],
                    'poster_path' => null,
                    'proposal_path' => null,
                    'lpj_path' => null,
                    'lpj_catatan' => null,
                    'registration_link' => null,
                    'revision_note' => null,
                    'waktu' => $proposal['waktu'],
                    'lokasi' => $proposal['lokasi'],
                    'estimasi_peserta' => $proposal['estimasi_peserta'],
                ]
            );
        }

        ActivitySubmission::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'title' => 'Laporan Bakti Sosial Kampus 2026',
                'kind' => 'report',
                'event_id' => null,
            ],
            [
                'organization_id' => $organization->id,
                'user_id' => $organization->account_user_id,
                'event_id' => null,
                'title' => 'Laporan Bakti Sosial Kampus 2026',
                'subtitle' => 'LPJ Kegiatan',
                'description' => 'Laporan pertanggungjawaban kegiatan bakti sosial yang sudah dilaksanakan bersama mitra desa binaan.',
                'event_date' => $now->copy()->subDays(12)->toDateString(),
                'kind' => 'report',
                'status' => 'review',
                'lpj_catatan' => 'Menunggu verifikasi lampiran foto dan bukti pengeluaran.',
                'poster_path' => null,
                'proposal_path' => null,
                'lpj_path' => null,
                'registration_link' => null,
                'revision_note' => null,
                'jenis_kegiatan' => 'Laporan',
                'penanggung_jawab' => $leader,
                'waktu' => '08:00 - 15:00',
                'lokasi' => 'Desa Binaan Kolongan',
                'estimasi_peserta' => 200,
            ]
        );
    }

    private function seedPublicEvents(array $organizations, array $students): void
    {
        $now = Carbon::now();
        $leadName = $this->studentName($students, 6, 'Clarabelle Felicia Lumintang');

        $definitions = [
            [
                'organization' => $organizations['choir'],
                'title' => 'Konser Paduan Suara',
                'category' => 'Kebudayaan',
                'start_at' => $now->copy()->addDays(8)->setTime(18, 30),
                'end_at' => $now->copy()->addDays(8)->setTime(20, 30),
                'location' => 'Auditorium Seni',
                'participants' => 180,
                'status' => 'upcoming',
                'description' => 'Konser tahunan yang menampilkan repertoar rohani dan lagu daerah.',
            ],
            [
                'organization' => $organizations['ministries'],
                'title' => 'Retreat Kerohanian Mahasiswa',
                'category' => 'Keagamaan/Kerohanian',
                'start_at' => $now->copy()->addDays(12)->setTime(8, 0),
                'end_at' => $now->copy()->addDays(12)->setTime(16, 0),
                'location' => 'Pusat Retret',
                'participants' => 75,
                'status' => 'upcoming',
                'description' => 'Retreat untuk pembinaan rohani dan kebersamaan antaranggota organisasi.',
            ],
            [
                'organization' => $organizations['creative'],
                'title' => 'Expo Inovasi Digital',
                'category' => 'Akademik',
                'start_at' => $now->copy()->addDays(15)->setTime(10, 0),
                'end_at' => $now->copy()->addDays(15)->setTime(15, 0),
                'location' => 'Lobby Gedung Teknologi',
                'participants' => 140,
                'status' => 'upcoming',
                'description' => 'Pameran karya teknologi, desain, dan prototipe digital hasil anggota UKM.',
            ],
            [
                'organization' => $organizations['creative'],
                'title' => 'Workshop Public Speaking & Personal Branding',
                'category' => 'Minat/Bakat',
                'start_at' => $now->copy()->subHour(),
                'end_at' => $now->copy()->addHour(),
                'location' => 'Ruang Seminar 3',
                'participants' => 60,
                'status' => 'upcoming',
                'description' => 'Pelatihan komunikasi presentasi dan personal branding untuk pengurus dan anggota organisasi mahasiswa.',
            ],
            [
                'organization' => $organizations['regional'],
                'title' => 'Pekan Budaya Daerah',
                'category' => 'Kebangsaan',
                'start_at' => $now->copy()->addDays(20)->setTime(9, 0),
                'end_at' => $now->copy()->addDays(20)->setTime(17, 0),
                'location' => 'Lapangan Kampus',
                'participants' => 200,
                'status' => 'upcoming',
                'description' => 'Perayaan budaya daerah dengan bazar kuliner, tari, dan pertunjukan seni.',
            ],
        ];

        foreach ($definitions as $definition) {
            $event = Event::updateOrCreate(
                [
                    'organization_id' => $definition['organization']->id,
                    'title' => $definition['title'],
                ],
                [
                    'organization_id' => $definition['organization']->id,
                    'user_id' => $definition['organization']->account_user_id,
                    'submission_id' => null,
                    'title' => $definition['title'],
                    'category' => $definition['category'],
                    'poster_path' => null,
                    'description' => $definition['description'],
                    'start_at' => $definition['start_at'],
                    'end_at' => $definition['end_at'],
                    'location' => $definition['location'],
                    'registration_link' => 'https://unklab.ac.id/register/' . strtolower(str_replace(' ', '-', $definition['title'])),
                    'participants' => $definition['participants'],
                    'status' => $definition['status'],
                ]
            );

            $submission = ActivitySubmission::updateOrCreate(
                [
                    'organization_id' => $definition['organization']->id,
                    'title' => $definition['title'],
                    'kind' => 'proposal',
                    'event_id' => $event->id,
                ],
                [
                    'organization_id' => $definition['organization']->id,
                    'user_id' => $definition['organization']->account_user_id,
                    'event_id' => $event->id,
                    'title' => $definition['title'],
                    'subtitle' => 'Pengajuan Event',
                    'description' => $definition['description'],
                    'event_date' => $definition['start_at']->toDateString(),
                    'kind' => 'proposal',
                    'status' => 'approved',
                    'jenis_kegiatan' => 'Event',
                    'penanggung_jawab' => $leadName,
                    'poster_path' => null,
                    'proposal_path' => null,
                    'lpj_path' => null,
                    'lpj_catatan' => null,
                    'registration_link' => $event->registration_link,
                    'revision_note' => null,
                    'waktu' => $definition['start_at']->format('H:i') . ' - ' . $definition['end_at']->format('H:i'),
                    'lokasi' => $definition['location'],
                    'estimasi_peserta' => $definition['participants'],
                ]
            );

            $event->update(['submission_id' => $submission->id]);
        }
    }

    private function seedLostItems(array $organizations, array $students): void
    {
        $now = Carbon::now();

        $items = [
            [
                'organization_id' => $organizations['bem']->id,
                'type' => 'lost',
                'title' => 'Dompet Kulit Hitam',
                'description' => 'Dompet hitam berisi kartu identitas mahasiswa dan beberapa kartu member kampus.',
                'date' => $now->copy()->subDays(2)->toDateString(),
                'location' => 'Perpustakaan Pusat',
                'contact_person' => $this->studentName($students, 0, 'Navys Kurniawan Joshua Gosal'),
                'contact_phone' => '081234567910',
                'status' => 'active',
            ],
            [
                'organization_id' => $organizations['bem']->id,
                'type' => 'found',
                'title' => 'Kunci Motor Honda',
                'description' => 'Sekumpulan kunci motor Honda ditemukan di dekat area parkir mahasiswa.',
                'date' => $now->copy()->subDays(4)->toDateString(),
                'location' => 'Area Parkir Timur',
                'contact_person' => $this->studentName($students, 1, 'Juliani Iriani Kalangie'),
                'contact_phone' => '081234567911',
                'status' => 'resolved',
            ],
            [
                'organization_id' => $organizations['choir']->id,
                'type' => 'found',
                'title' => 'Tas Laptop Abu-abu',
                'description' => 'Tas laptop abu-abu ditemukan di ruang kelas lantai dua setelah latihan.',
                'date' => $now->copy()->subDay()->toDateString(),
                'location' => 'Gedung Kelas Lama',
                'contact_person' => $this->studentName($students, 2, 'Mario Jonatan Kolibu'),
                'contact_phone' => '081234567912',
                'status' => 'active',
            ],
            [
                'organization_id' => null,
                'type' => 'lost',
                'title' => 'Earphone Putih',
                'description' => 'Earphone putih tertinggal setelah kuliah malam dan menunggu konfirmasi pemilik.',
                'date' => $now->copy()->subDays(3)->toDateString(),
                'location' => 'Lobi Gedung IT',
                'contact_person' => 'Mahasiswa Informasi',
                'contact_phone' => '081234567913',
                'status' => 'pending',
            ],
        ];

        foreach ($items as $item) {
            LostItem::updateOrCreate(
                [
                    'organization_id' => $item['organization_id'],
                    'title' => $item['title'],
                    'type' => $item['type'],
                ],
                [
                    'organization_id' => $item['organization_id'],
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'date' => $item['date'],
                    'location' => $item['location'],
                    'contact_person' => $item['contact_person'],
                    'contact_phone' => $item['contact_phone'],
                    'image_path' => null,
                    'status' => $item['status'],
                ]
            );
        }
    }

    private function seedNotifications(): void
    {
        $kemahasiswaanUsers = User::where('role', 'kemahasiswaan')->get();

        if ($kemahasiswaanUsers->isEmpty()) {
            return;
        }

        $now = Carbon::now();

        foreach ($kemahasiswaanUsers as $user) {
            $notifications = [
                [
                    'created_at' => $now->copy()->subMinutes(8),
                    'read_at' => null,
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => [
                        'title' => 'Pengajuan Kegiatan Baru',
                        'message' => 'BEM UNKLAB Universitas mengajukan Seminar Leadership Mahasiswa untuk review.',
                        'type' => 'pengajuan_kegiatan',
                        'icon' => 'document',
                        'action_url' => route('kemahasiswaan.submissions.index', ['tab' => 'kegiatan']),
                    ],
                ],
                [
                    'created_at' => $now->copy()->subMinutes(40),
                    'read_at' => null,
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => [
                        'title' => 'Revisi Kegiatan',
                        'message' => 'Workshop Public Speaking & Personal Branding perlu revisi lampiran proposal.',
                        'type' => 'revisi_kegiatan',
                        'icon' => 'edit',
                        'action_url' => route('kemahasiswaan.submissions.index', ['tab' => 'event']),
                    ],
                ],
                [
                    'created_at' => $now->copy()->subHours(2),
                    'read_at' => $now->copy()->subHours(1),
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => [
                        'title' => 'Laporan Masuk',
                        'message' => 'LPJ Bakti Sosial Kampus 2026 dari BEM UNKLAB Universitas sudah masuk.',
                        'type' => 'laporan_masuk',
                        'icon' => 'report',
                        'action_url' => route('kemahasiswaan.submissions.index', ['tab' => 'report']),
                    ],
                ],
                [
                    'created_at' => $now->copy()->subHours(6),
                    'read_at' => $now->copy()->subHours(5),
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => [
                        'title' => 'Pesan Baru',
                        'message' => 'BEM UNKLAB Universitas menanyakan status review proposal seminar leadership.',
                        'type' => 'pesan_baru',
                        'icon' => 'message',
                        'action_url' => route('kemahasiswaan.submissions.index', ['tab' => 'kegiatan']),
                    ],
                ],
                [
                    'created_at' => $now->copy()->subDay(),
                    'read_at' => $now->copy()->subDay(),
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => [
                        'title' => 'Informasi Penting',
                        'message' => 'Kalender akademik memasuki masa UTS dan kegiatan ekstrakurikuler dibatasi.',
                        'type' => 'informasi_penting',
                        'icon' => 'info',
                        'action_url' => route('kemahasiswaan.calendar'),
                    ],
                ],
                [
                    'created_at' => $now->copy()->subDays(2),
                    'read_at' => $now->copy()->subDays(2),
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => [
                        'title' => 'Perubahan Pengurus',
                        'message' => 'Paduan Suara Vox Angelica memperbarui data ketua dan sekretaris.',
                        'type' => 'perubahan_pengurus',
                        'icon' => 'users',
                        'action_url' => route('kemahasiswaan.organizations.index', ['q' => 'Vox']),
                    ],
                ],
            ];

            foreach ($notifications as $notification) {
                DB::table('notifications')->insert([
                    'id' => Str::uuid()->toString(),
                    'type' => $notification['type'],
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode($notification['data'], JSON_UNESCAPED_UNICODE),
                    'read_at' => $notification['read_at'],
                    'created_at' => $notification['created_at'],
                    'updated_at' => $notification['created_at'],
                ]);
            }
        }
    }
}