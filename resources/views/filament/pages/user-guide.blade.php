<x-filament-panels::page>
    <div x-data="{
        searchQuery: '',
        activeCategory: 'all',
        matches(title, content, tags = '') {
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            return (title.toLowerCase().includes(q) || content.toLowerCase().includes(q) || tags.toLowerCase().includes(q));
        }
    }" style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- ========================================================================= --}}
        {{-- 1. HERO HEADER DENGAN LIVE SEARCH BOX                                     --}}
        {{-- ========================================================================= --}}
        <div style="background: linear-gradient(135deg, #0f766e 0%, #115e59 40%, #065f46 100%);
            border: 1px solid rgba(20, 184, 166, 0.3);
            box-shadow: 0 10px 25px -5px rgba(15, 118, 110, 0.25);
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            padding: 1.5rem;
            color: #ffffff;">
            <div style="position: relative; z-index: 10; width: 100%;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 9999px; background: rgba(13, 148, 136, 0.25); border: 1px solid rgba(13, 148, 136, 0.4); color: #fef08a; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                    <x-heroicon-s-sparkles style="width: 1rem; height: 1rem; color: #f59e0b;" />
                    Dokumentasi Resmi & Pusat Edukasi
                </div>

                <h1 style="font-size: 1.85rem; font-weight: 800; letter-spacing: -0.025em; line-height: 1.2; margin: 0; color: #ffffff;">
                    Buku Panduan Operasional Wasilah AI
                </h1>

                <p style="font-size: 0.875rem; color: #cbd5e1; margin-top: 0.5rem; line-height: 1.6;">
                    Pelajari langkah demi langkah mengoperasikan asisten AI pintar, menghubungkan WhatsApp & Meta, melayani customer di OmniChat, hingga mengotomasi broadcast dan follow-up.
                </p>

                {{-- SEARCH BOX INPUT --}}
                <div style="position: relative; margin-top: 1.5rem;">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="🔍 Ketik topik pencarian (contoh: scan qr, website scraper, broadcast, follow-up, crm, lynk.id)..."
                        style="width: 100%; border-radius: 0.75rem; border: 1px solid #475569; background: rgba(15, 23, 42, 0.85); padding: 0.85rem 2.75rem 0.85rem 1rem; font-size: 0.875rem; color: white; outline: none; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2); transition: all 0.2s;"
                        onfocus="this.style.borderColor='#14b8a6'; this.style.boxShadow='0 0 0 3px rgba(20, 184, 166, 0.25)';"
                        onblur="this.style.borderColor='#475569'; this.style.boxShadow='inset 0 2px 4px rgba(0,0,0,0.2)';"
                    />
                    <button
                        x-show="searchQuery"
                        @click="searchQuery = ''"
                        style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; font-size: 0.75rem; font-weight: bold; cursor: pointer;">
                        ✕ Hapus
                    </button>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 2. MODUL SETUP CEPAT 5 MENIT (ONBOARDING CHECKLIST)                       --}}
        {{-- ========================================================================= --}}
        <div x-show="matches('Modul Setup Singkat', 'langkah awal setup whatsapp facebook instagram knowledge base omnichat broadcast', 'quickstart')"
             style="background: white; border-radius: 1rem; padding: 1.5rem; border: 1px solid rgba(128,128,128,0.2); box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; background: linear-gradient(135deg, #0d9488, #10b981); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: bold;">
                    🚀
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0;">Modul Setup Cepat (Mulai dalam 5 Menit)</h3>
                    <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Ikuti 4 langkah mudah ini untuk langsung mengaktifkan AI di bisnis Anda.</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                {{-- Step 1 --}}
                <div style="padding: 1.25rem; border-radius: 0.75rem; background: rgba(13, 148, 136, 0.05); border: 1px solid rgba(13, 148, 136, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 800; background: #0d9488; color: white; padding: 0.15rem 0.5rem; border-radius: 9999px;">LANGKAH 1</span>
                        <span style="font-size: 1.2rem;">📱</span>
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Hubungkan Saluran Chat</h4>
                    <p style="font-size: 0.8rem; color: #475569; margin: 0; line-height: 1.5;" class="dark:text-gray-300">
                        Buka menu <b>Saluran Komunikasi</b>. Scan QR WhatsApp kantor atau klik <b>Hubungkan Facebook / IG</b>.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div style="padding: 1.25rem; border-radius: 0.75rem; background: rgba(13, 148, 136, 0.05); border: 1px solid rgba(13, 148, 136, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 800; background: #0d9488; color: white; padding: 0.15rem 0.5rem; border-radius: 9999px;">LANGKAH 2</span>
                        <span style="font-size: 1.2rem;">🧠</span>
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Latih Pengetahuan AI</h4>
                    <p style="font-size: 0.8rem; color: #475569; margin: 0; line-height: 1.5;" class="dark:text-gray-300">
                        Buka menu <b>Knowledge Base</b>. Masukkan URL website toko Anda untuk di-scrape atau ketik FAQ produk.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div style="padding: 1.25rem; border-radius: 0.75rem; background: rgba(13, 148, 136, 0.05); border: 1px solid rgba(13, 148, 136, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 800; background: #0d9488; color: white; padding: 0.15rem 0.5rem; border-radius: 9999px;">LANGKAH 3</span>
                        <span style="font-size: 1.2rem;">💬</span>
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Buka OmniChat Live</h4>
                    <p style="font-size: 0.8rem; color: #475569; margin: 0; line-height: 1.5;" class="dark:text-gray-300">
                        Klik <b>OmniChat CRM</b> di bilah samping untuk memantau obrolan AI secara live dan melayani customer.
                    </p>
                </div>

                {{-- Step 4 --}}
                <div style="padding: 1.25rem; border-radius: 0.75rem; background: rgba(13, 148, 136, 0.05); border: 1px solid rgba(13, 148, 136, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 800; background: #0d9488; color: white; padding: 0.15rem 0.5rem; border-radius: 9999px;">LANGKAH 4</span>
                        <span style="font-size: 1.2rem;">📢</span>
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0;">Promosi & Follow-Up</h4>
                    <p style="font-size: 0.8rem; color: #475569; margin: 0; line-height: 1.5;" class="dark:text-gray-300">
                        Import kontak, kirim pesan broadcast anti-banned, dan pasang follow-up otomatis jangka panjang.
                    </p>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 3. DETAIL DOKUMENTASI LENGKAP FITUR OPERASIONAL                           --}}
        {{-- ========================================================================= --}}
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">

            {{-- 1. MODUL SALURAN KOMUNIKASI (CHANNELS) --}}
            <div x-show="matches('Saluran Komunikasi (Channels)', 'whatsapp scan qr code baileys facebook fanspage instagram meta bot toggle jam aktif kantor session id', 'channels wa fb ig')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(13, 148, 136, 0.08); border-bottom: 1px solid rgba(13, 148, 136, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">📶</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #0f766e;">1. Panduan Saluran Komunikasi (Channels)</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Integrasi WhatsApp Unofficial (VPS), Facebook Pages, dan Instagram Business.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #0d9488; margin: 0 0 0.25rem 0;">A. Menghubungkan WhatsApp (Baileys Engine):</h4>
                        <ol style="margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li>Buka menu <b>Saluran Komunikasi</b> $\rightarrow$ Klik <b>(+) Tambah Saluran</b>.</li>
                            <li>Pilih platform <b>WhatsApp (Unofficial Baileys)</b>, beri nama channel (contoh: <i>WA CS Utama</i>), dan isi <i>Session ID</i> unik (contoh: <code>kantor_pusat_wa</code>).</li>
                            <li>Klik <b>Simpan</b>. Di tabel saluran, klik tombol hijau <b>[Scan QR]</b>.</li>
                            <li>Buka aplikasi WhatsApp di HP Anda $\rightarrow$ <i>Perangkat Tertaut (Linked Devices)</i> $\rightarrow$ <i>Tautkan Perangkat</i> $\rightarrow$ Scan QR Code di layar monitor Anda.</li>
                            <li>Status akan otomatis berubah menjadi 🟢 <b>Terhubung</b> dalam hitungan detik.</li>
                        </ol>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #0d9488; margin: 0 0 0.25rem 0;">B. Menghubungkan Facebook & Instagram (Composio OAuth):</h4>
                        <ol style="margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li>Klik <b>(+) Tambah Saluran</b> $\rightarrow$ Pilih <b>Facebook Page</b> atau <b>Instagram Business</b> $\rightarrow$ Simpan.</li>
                            <li>Di baris tabel, klik tombol biru <b>[Hubungkan Facebook]</b> atau kuning <b>[Hubungkan Instagram]</b>.</li>
                            <li>Lakukan login resmi di popup otorisasi dan centang Halaman / Akun Instagram Bisnis yang ingin Anda hubungkan.</li>
                            <li>Sistem otomatis mendeteksi Page ID / Instagram ID Anda secara instan tanpa perlu repot memasukkan token manual!</li>
                        </ol>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #0d9488; margin: 0 0 0.25rem 0;">C. Pengaturan Jam Operasional Bot:</h4>
                        <p style="margin: 0;">
                            Anda bisa memilih apakah AI membalas <b>24 Jam Nonstop</b> atau <b>Hanya Aktif di Luar Jam Kerja CS (Malam & Hari Libur)</b>. Atur jam masuk (misal: 08:00) dan jam pulang (misal: 17:00). Di siang hari staf CS yang melayani, dan di malam hari AI otomatis mengambil alih agar tidak ada customer yang terabaikan!
                        </p>
                    </div>
                </div>
            </div>

            {{-- 2. MODUL KNOWLEDGE BASE & AI GEMINI --}}
            <div x-show="matches('Knowledge Base & Website Scraper (Otak AI)', 'materi pengetahuan ai gemini scraper url website katalog faq pintar vision struk bukti transfer prompt', 'knowledge ai')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(147, 51, 234, 0.08); border-bottom: 1px solid rgba(147, 51, 234, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">🧠</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #7e22ce;">2. Knowledge Base & Training AI Gemini 1.5 Flash</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Melatih materi pengetahuan kecerdasan buatan agar paham katalog, harga, dan SOP bisnis Anda.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #7e22ce; margin: 0 0 0.25rem 0;">A. Dynamic Website Scraper (Penyedot Otomatis Website):</h4>
                        <p style="margin: 0;">
                            Jika Anda memiliki website toko atau landing page produk, klik <b>(+) Tambah Knowledge Base</b> $\rightarrow$ Pilih tipe <i>Website URL</i> $\rightarrow$ Masukkan alamat URL (contoh: <code>https://tokoanda.com/katalog</code>) $\rightarrow$ Simpan. Sistem otomatis men-scrape seluruh teks dan menyimpannya ke memori AI. Jika website Anda di-update, cukup klik tombol <b>Sync Ulang</b> di tabel.
                        </p>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #7e22ce; margin: 0 0 0.25rem 0;">B. Input Manual Teks & FAQ:</h4>
                        <p style="margin: 0;">
                            Pilih tipe <i>Teks Manual</i> untuk menginput tanya-jawab umum, syarat & ketentuan, alamat kantor fisik, nomor rekening pembayaran resmi, serta alur pendaftaran customer.
                        </p>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #7e22ce; margin: 0 0 0.25rem 0;">C. Kemampuan AI Vision (Paham Gambar & Bukti Transfer):</h4>
                        <p style="margin: 0;">
                            AI Wasilah AI dilengkapi model Gemini Vision. Ketika customer mengirimkan foto produk di WhatsApp atau bukti transfer pembayaran, AI otomatis membaca isi gambar tersebut, mencocokkan nominal uang, dan memberikan konfirmasi yang tepat.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 3. MODUL OMNICHAT LIVE WORKSPACE --}}
            <div x-show="matches('OmniChat Live Workspace (Layar Kerja CS)', 'workspace crm live chat pemutar video mp4 pdf dokumen pipeline hot lead closing won mobile view fab drawer live chat', 'omnichat workspace')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(16, 185, 129, 0.08); border-bottom: 1px solid rgba(16, 185, 129, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">💻</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #047857;">3. OmniChat Live Workspace (Layar Kerja Sentral CS)</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Meja kerja 3-kolom modern untuk memantau obrolan AI secara real-time dan melayani customer.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #047857; margin: 0 0 0.25rem 0;">A. Struktur Layar 3-Kolom:</h4>
                        <ul style="margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li><b>Kolom Kiri (Daftar Chat):</b> Dilengkapi 5 Tab Filter (🌟 Semua, 💬 WA, 📘 FB, 📷 IG, 👥 Grup WA) serta kolom pencarian live untuk mencari nama/nomor HP/username.</li>
                            <li><b>Kolom Tengah (Ruang Obrolan):</b> Menampilkan riwayat chat realtime. Dilengkapi tombol switch <i>Bot AI ON/OFF</i>, pemutar video MP4 inline, tombol unduh dokumen PDF, dan baris kirim lampiran foto/file.</li>
                            <li><b>Kolom Kanan (Profil & Pipeline Leads):</b> Berisi status prospek (<i>Baru, Cold, Warm, Hot 🔥, Closing, Won 🎉</i>), ringkasan kebutuhan otomatis dari AI, dan widget kontrol follow-up.</li>
                        </ul>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #047857; margin: 0 0 0.25rem 0;">B. Penggunaan di Smartphone (Mobile Experience):</h4>
                        <p style="margin: 0;">
                            Di layar HP, saat Anda membuka obrolan chat, tombol melayang <b>✨ Summary Leads (FAB)</b> akan muncul di pojok kanan bawah. Klik tombol tersebut untuk membuka laci profil leads, merubah status pipeline closing, dan melihat catatan kebutuhan AI tanpa perlu berpindah halaman!
                        </p>
                    </div>
                </div>
            </div>

            {{-- 4. MODUL MANAJEMEN KONTAK & GRUP --}}
            <div x-show="matches('Kontak & Grup Kontak (Database Leads)', 'database leads kontak import grup wa buku telepon hp excel csv anti duplikasi', 'contacts leads')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(59, 130, 246, 0.08); border-bottom: 1px solid rgba(59, 130, 246, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">👥</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #1d4ed8;">4. Manajemen Kontak & Grup (Database Leads)</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Penyimpanan terpusat seluruh database prospek dari WhatsApp, Facebook, dan Instagram.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #1d4ed8; margin: 0 0 0.25rem 0;">A. Fitur Import Kontak Cerdas 1-Klik:</h4>
                        <p style="margin: 0;">
                            Buka menu <b>Grup Kontak</b> $\rightarrow$ Di tabel grup kontak, Anda dapat memanfaatkan 3 tombol import canggih:
                        </p>
                        <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li><b>Import Anggota Grup WA:</b> Menarik seluruh nomor HP anggota dari grup WhatsApp yang diikuti nomor Anda.</li>
                            <li><b>Import Buku Telepon HP:</b> Menyedot semua kontak telepon tersimpan dari aplikasi WhatsApp di HP Anda.</li>
                            <li><b>Import File Excel/CSV:</b> Unggah file spreadsheet kontak (sistem otomatis mengenali pemisah koma maupun titik-koma).</li>
                        </ul>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #1d4ed8; margin: 0 0 0.25rem 0;">B. Jaminan Anti-Duplikasi:</h4>
                        <p style="margin: 0;">
                            Database Wasilah AI memiliki kunci unik anti-duplikasi. Jika Anda mengimpor file yang berisi nomor yang sama berulang kali, sistem otomatis melewatinya sehingga kontak Anda tetap rapi dan tidak ada pesan ganda.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 5. MODUL BROADCAST MARKETING --}}
            <div x-show="matches('Broadcast WhatsApp Massal Anti-Banned', 'broadcast promosi massal template variabel name jitter jeda anti ban live progress bar', 'broadcast')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(245, 158, 11, 0.08); border-bottom: 1px solid rgba(245, 158, 11, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">📢</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #b45309;">5. Broadcast WhatsApp Massal (Anti-Banned Jitter Engine)</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Kirim ribuan pesan promosi dengan proteksi algoritma jeda acak agar nomor WA aman dari blokir.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #b45309; margin: 0 0 0.25rem 0;">A. Template dengan Variabel Personalisasi:</h4>
                        <p style="margin: 0;">
                            Di menu <b>Template Broadcast</b>, Anda bisa membuat draf pesan dengan menyisipkan variabel <code>@{{name}}</code> (misal: <i>"Halo Kak @{{name}}, kami ada promo spesial hari ini!"</i>). Sistem akan otomatis mengganti <code>@{{name}}</code> dengan nama asli masing-masing penerima. Anda juga bisa melampirkan foto brosur produk.
                        </p>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #b45309; margin: 0 0 0.25rem 0;">B. Mesin Pengirim Anti-Ban Jitter & Live Progress Bar:</h4>
                        <p style="margin: 0;">
                            Saat Anda membuat kampanye di menu <b>Broadcast Campaign</b>, antrean pesan dikirimkan ke VPS Baileys dengan jeda acak 5–12 detik per nomor (meniru ketikan manusia asli). Anda dapat melihat <b>Live Progress Bar (0% - 100%)</b> yang bergerak secara real-time di layar.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 6. MODUL FOLLOW-UP DRIP SEQUENCE --}}
            <div x-show="matches('Follow-Up Drip Sequence (Nurturing Otomatis)', 'follow up drip sequence berseri step 1 n smart ai prompt jeda stop on reply stop on won closing', 'followup drip')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(225, 29, 72, 0.08); border-bottom: 1px solid rgba(225, 29, 72, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">🎯</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #be123c;">6. Multi-Step Follow-Up Drip Sequence (Otomasi Closing)</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Rangkaian pesan otomatis jangka panjang untuk mem-follow up leads hingga closing.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #be123c; margin: 0 0 0.25rem 0;">A. Step 1 sampai N Tanpa Batas:</h4>
                        <p style="margin: 0;">
                            Buka menu <b>Follow-Up Sequence</b>. Anda bisa membuat rangkaian langkah berjadwal:
                        </p>
                        <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li><b>Langkah 1:</b> Dikirim 2 Jam setelah customer pertama kali chat (Sapaan hangat & konfirmasi kebutuhan).</li>
                            <li><b>Langkah 2:</b> Dikirim 1 Hari berikutnya (Kirimkan testimoni pelanggan atau video review produk).</li>
                            <li><b>Langkah 3:</b> Dikirim 3 Hari berikutnya (Pemberian voucher diskon khusus yang terbatas).</li>
                        </ul>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #be123c; margin: 0 0 0.25rem 0;">B. Pesan AI Gemini Smart Custom Prompt:</h4>
                        <p style="margin: 0;">
                            Selain template teks biasa, Anda bisa memilih tipe <b>AI Gemini Smart Message</b> dan menuliskan instruksi prompt (misal: <i>"Susun pesan persuasif yang ramah mengingatkan calon jamaah umroh tentang sisa kuota seat keberangkatan"</i>). AI akan merangkai kata-kata unik yang berbeda untuk setiap customer!
                        </p>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #be123c; margin: 0 0 0.25rem 0;">C. Smart Exit Guard (Otomatis Berhenti Saat Closing):</h4>
                        <p style="margin: 0;">
                            Sistem secara cerdas akan menjeda rangkaian pesan jika customer membalas chat (<b>Stop on Reply</b>), dan otomatis menghentikan total pengiriman jika status customer telah diubah menjadi <b>Won 🎉 (Closing)</b>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 7. MODUL LANGGANAN & PEMBAYARAN (SUBSCRIPTION) --}}
            <div x-show="matches('Langganan Kantor & Pembayaran (Subscription)', 'subscription perpanjang masa aktif paket 1 bulan 1 tahun lynk.id aktivasi otomatis free active expiring inactive', 'subscription lynkid')"
                 style="background: white; border-radius: 1rem; border: 1px solid rgba(128,128,128,0.2); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:bg-gray-900">

                <div style="padding: 1.25rem 1.5rem; background: rgba(79, 70, 229, 0.08); border-bottom: 1px solid rgba(79, 70, 229, 0.15); display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.35rem;">💳</span>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: #4338ca;">7. Langganan Kantor & Pembayaran Otomatis (Subscription)</h3>
                        <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Masa aktif per-kantor, pilihan paket Lynk.id, dan aktivasi instan tanpa ribet.</p>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem; line-height: 1.6; color: #334155;" class="dark:text-gray-300">
                    <div>
                        <h4 style="font-weight: 700; color: #4338ca; margin: 0 0 0.25rem 0;">A. 4 Status Masa Aktif Langganan:</h4>
                        <ul style="margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li>⚪ <b>Free:</b> Kantor baru dibuat (akses fitur operasional terkunci hingga paket diaktifkan).</li>
                            <li>🔵 <b>Active:</b> Langganan aktif dan seluruh fitur AI, WhatsApp, & Meta terbuka penuh.</li>
                            <li>⚠️ <b>Expiring:</b> Masa aktif tersisa $\le 7$ hari (muncul banner kuning pengingat perpanjangan).</li>
                            <li>🔴 <b>Inactive:</b> Masa aktif habis (menu operasional terkunci dan muncul banner merah).</li>
                        </ul>
                    </div>

                    <div style="border-top: 1px dashed rgba(128,128,128,0.2); padding-top: 0.75rem;">
                        <h4 style="font-weight: 700; color: #4338ca; margin: 0 0 0.25rem 0;">B. Cara Perpanjangan & Aktivasi Otomatis via Lynk.id:</h4>
                        <ol style="margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li>Buka menu <b>Langganan (Subscription)</b> $\rightarrow$ Klik tombol <b>Perpanjang Langganan</b>.</li>
                            <li>Pilih durasi paket: <b>1 Bulan (Rp 37.000)</b> atau <b>1 Tahun (Rp 300.000 - Hemat 32%)</b>.</li>
                            <li>Klik <i>Lanjut ke Pembayaran Lynk.id</i>.</li>
                            <li><b>PENTING:</b> Saat checkout di Lynk.id (QRIS / Transfer Bank), pastikan Anda memasukkan <b>alamat email yang sama dengan email akun Wasilah AI Anda</b>.</li>
                            <li>Seketika setelah pembayaran lunas terverifikasi, sistem Wasilah AI langsung memperpanjang masa aktif kantor Anda (+30 hari atau +365 hari) secara otomatis!</li>
                        </ol>
                    </div>
                </div>
            </div>

        </div>

        {{-- ========================================================================= --}}
        {{-- STATE KOSONG SAAT SEARCH TIDAK DITEMUKAN                                  --}}
        {{-- ========================================================================= --}}
        <div x-show="searchQuery && !matches('Setup', searchQuery) && !matches('Saluran', searchQuery) && !matches('Knowledge', searchQuery) && !matches('OmniChat', searchQuery) && !matches('Kontak', searchQuery) && !matches('Broadcast', searchQuery) && !matches('Follow-Up', searchQuery) && !matches('Langganan', searchQuery)"
             style="text-align: center; padding: 3rem 1.5rem; background: white; border-radius: 1rem; border: 1px dashed #cbd5e1;" class="dark:bg-gray-900 dark:border-gray-800">
            <x-heroicon-o-magnifying-glass style="width: 2.5rem; height: 2.5rem; margin: 0 auto 0.5rem auto; color: #94a3b8;" />
            <h4 style="font-weight: 700; font-size: 0.95rem; margin: 0; color: #475569;" class="dark:text-gray-300">Topik panduan tidak ditemukan</h4>
            <p style="font-size: 0.8rem; color: #94a3b8; margin: 0.25rem 0 0 0;">Coba gunakan kata kunci lain seperti <i>whatsapp, ai, broadcast, follow-up, crm, atau lynk.id</i>.</p>
        </div>

    </div>
</x-filament-panels::page>
