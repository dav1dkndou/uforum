@extends('layouts.app')

@section('title', 'Dashboard Organisasi - Sistem Pengurus UKM')

@section('content')
<div class="pengurus-dashboard">
    <h1>Dashboard Organisasi</h1>
    <p class="pengurus-subtitle">Selamat datang kembali! Berikut ringkasan kondisi organisasi Anda.</p>

    <section class="calendar-panel" aria-label="Kalender kegiatan organisasi">
        <div class="calendar-head">
            <h2>
                <span class="calendar-head-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                Kalender Kegiatan Akademik &amp; Organisasi
            </h2>
            <p>Tampilan kalender kegiatan untuk 2 semester / 1 tahun akademik (Read-Only)</p>
        </div>


        <ul class="calendar-legend" aria-label="Legend kalender">
            <li><span class="dot blue"></span> Kegiatan Akademik</li>
            <li><span class="dot green"></span> Kegiatan Organisasi</li>
            <li><span class="dot red"></span> Tidak Boleh Berkegiatan</li>
            <li><span class="dot yellow"></span> Libur</li>
            <li><span class="dot purple"></span> Event Kampus Besar</li>
        </ul>

        <div class="calendar-month-nav">
            <a href="?year={{ $currentMonth == 1 ? $currentYear - 1 : $currentYear }}&month={{ $currentMonth == 1 ? 12 : $currentMonth - 1 }}" class="cal-nav-btn btn-sm btn-outline-secondary" aria-label="Bulan sebelumnya" style="text-decoration:none; padding:4px 8px;">&#x2039;</a>
            <h3 style="display:inline-block; margin:0 15px;">{{ $monthName }} {{ $currentYear }}</h3>
            <a href="?year={{ $currentMonth == 12 ? $currentYear + 1 : $currentYear }}&month={{ $currentMonth == 12 ? 1 : $currentMonth + 1 }}" class="cal-nav-btn btn-sm btn-outline-secondary" aria-label="Bulan berikutnya" style="text-decoration:none; padding:4px 8px;">&#x203A;</a>
        </div>

        <div class="calendar-grid-wrap">
            <table class="calendar-grid" aria-label="Tabel kalender {{ $monthName }} {{ $currentYear }}">
                <thead>
                    <tr>
                        <th>Min</th>
                        <th>Sen</th>
                        <th>Sel</th>
                        <th>Rab</th>
                        <th>Kam</th>
                        <th>Jum</th>
                        <th>Sab</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calendarGrid as $week)
                    <tr>
                        @foreach($week as $day)
                            @if($day)
                                <td>
                                    <span class="day">{{ $day['day'] }}</span>
                                    @foreach($day['events'] as $ce)
                                        @php
                                            $colorClass = 'blue';
                                            if($ce->category == 'Kegiatan Organisasi') $colorClass = 'green';
                                            if(str_contains(strtolower($ce->category), 'tidak boleh')) $colorClass = 'red';
                                            if(str_contains($ce->category, 'Libur')) $colorClass = 'yellow';
                                            if($ce->category == 'Event Kampus Besar') $colorClass = 'purple';
                                        @endphp
                                        <span class="event-chip {{ $colorClass }}" title="{{ $ce->title }}">{{ $ce->title }}</span>
                                    @endforeach
                                    @foreach($day['ukmEvents'] as $ue)
                                        <span class="event-chip green" title="{{ $ue->title }}">{{ $ue->title }}</span>
                                    @endforeach
                                </td>
                            @else
                                <td></td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="all-events-list">
            <h3>Semua Kegiatan Bulan {{ $monthName }} {{ $currentYear }}</h3>
            <div id="calendarListContainer">
                @foreach($events as $ce)
                    @php
                        $colorClass = 'blue';
                        if($ce->category == 'Kegiatan Organisasi') $colorClass = 'green';
                        if(str_contains(strtolower($ce->category), 'tidak boleh')) $colorClass = 'red';
                        if(str_contains($ce->category, 'Libur')) $colorClass = 'yellow';
                        if($ce->category == 'Event Kampus Besar') $colorClass = 'purple';
                    @endphp
                    <article class="list-item {{ $colorClass }} cal-list-row">
                        <div>
                            <h4>{{ $ce->title }}</h4>
                            <p>{{ \Carbon\Carbon::parse($ce->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($ce->end_date)->format('d M Y') }}</p>
                        </div>
                        <span>{{ $ce->category }}</span>
                    </article>
                @endforeach
                @foreach($ukmEvents as $ue)
                    <article class="list-item green cal-list-row">
                        <div>
                            <h4>{{ $ue->title }}</h4>
                            <p>{{ $ue->start_at ? $ue->start_at->format('d M Y') : '-' }}</p>
                        </div>
                        <span>Kegiatan Organisasi</span>
                    </article>
                @endforeach
            </div>
            
            {{-- Client-side Pagination Controls --}}
            <div class="cal-pagination" id="calPagination" style="display: none; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: center; align-items: center; gap: 8px;">
                <button class="cal-page-btn" onclick="changeCalPage(-1)" style="padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer;">&#x2039;</button>
                <div class="cal-page-numbers" id="calPageNumbers" style="display: flex; gap: 4px;"></div>
                <button class="cal-page-btn" onclick="changeCalPage(1)" style="padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer;">&#x203A;</button>
            </div>
        </div>
    </section>

    @php
        $org = auth()->user()->organization;
        $fields = [
            'Logo Organisasi' => $org?->logo_path,
            'Banner Organisasi' => $org?->banner_path,
            'Deskripsi' => $org?->description,
            'Visi' => $org?->vision,
            'Misi' => $org?->mission,
            'Values' => $org?->values,
            'Struktur' => $org?->structure,
            'WhatsApp' => $org?->whatsapp,
            'Email' => $org?->email,
        ];
        $filledCount = 0;
        $missing = [];
        foreach ($fields as $label => $val) {
            if (!empty($val)) {
                $filledCount++;
            } else {
                $missing[] = $label;
            }
        }
        $progress = count($fields) > 0 ? round(($filledCount / count($fields)) * 100) : 0;
    @endphp

    <section style="margin-top: 32px; background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #d9dee8;">
        <h3 style="margin: 0 0 16px; font-size: 18px; color: #111827;">Progress Profil Organisasi</h3>
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
            <div style="flex: 1; height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden;">
                <div style="height: 100%; width: {{ $progress }}%; background: {{ $progress == 100 ? '#10b981' : '#3b82f6' }}; border-radius: 999px;"></div>
            </div>
            <strong style="color: #374151;">{{ $progress }}%</strong>
        </div>
        
        @if(count($missing) > 0)
        <div style="font-size: 14px; color: #6b7280; line-height: 1.6;">
            <p style="margin: 0 0 8px; font-weight: 600; color: #4b5563;">Menunggu dilengkapi:</p>
            <ul style="margin: 0; padding-left: 20px; color: #ef4444;">
                @foreach($missing as $item)
                    <li>{{ $item }} belum diisi</li>
                @endforeach
            </ul>
        </div>
        @else
        <div style="font-size: 14px; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Profil organisasi sudah 100% lengkap!
        </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    let currentCalPage = 1;
    const itemsPerPage = 9;
    let listItems = [];

    document.addEventListener('DOMContentLoaded', () => {
        listItems = Array.from(document.querySelectorAll('.cal-list-row'));
        renderPagination();
    });

    function renderPagination() {
        const totalPages = Math.ceil(listItems.length / itemsPerPage);
        const pagination = document.getElementById('calPagination');
        
        if (totalPages <= 1) {
            pagination.style.display = 'none';
            listItems.forEach(item => item.style.display = 'flex');
            return;
        }

        pagination.style.display = 'flex';
        
        listItems.forEach((item, index) => {
            const start = (currentCalPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            if (index >= start && index < end) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        const numbersContainer = document.getElementById('calPageNumbers');
        numbersContainer.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'cal-page-btn ' + (i === currentCalPage ? 'active' : '');
            
            // Inline styles for consistency
            btn.style.padding = '6px 12px';
            btn.style.border = '1px solid ' + (i === currentCalPage ? '#5e3191' : '#d1d5db');
            btn.style.background = i === currentCalPage ? '#5e3191' : '#fff';
            btn.style.color = i === currentCalPage ? '#fff' : '#374151';
            btn.style.borderRadius = '6px';
            btn.style.cursor = 'pointer';
            
            btn.onclick = () => {
                currentCalPage = i;
                renderPagination();
                document.querySelector('.all-events-list').scrollIntoView({ behavior: 'smooth' });
            };
            numbersContainer.appendChild(btn);
        }
    }

    function changeCalPage(delta) {
        const totalPages = Math.ceil(listItems.length / itemsPerPage);
        const newPage = currentCalPage + delta;
        
        if (newPage >= 1 && newPage <= totalPages) {
            currentCalPage = newPage;
            renderPagination();
            document.querySelector('.all-events-list').scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>
@endpush
