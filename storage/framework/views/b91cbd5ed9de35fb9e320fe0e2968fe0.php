<?php $__env->startSection('title', 'Smart Scanner'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .scan-root { max-width: 500px; margin: 0 auto; }
    .hidden { display: none !important; }
    .cam-container { position: relative; border-radius: 1.25rem; overflow: hidden; background: #000; }
    .cam-view { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; }
    .cam-overlay { position: absolute; inset: 0; border: 3px dashed rgba(255,255,255,.35); border-radius: 1.25rem; pointer-events: none; transition: border-color 0.3s; }
    .cam-overlay.scanning { border-color: rgba(16,185,129,.6); border-style: solid; }
    .cam-overlay.captured { border-color: #10b981; border-style: solid; animation: flash .4s; }
    @keyframes flash { 0%,100% { opacity: 1 } 50% { opacity: .2 } }
    @keyframes pulse { 0%,100% { opacity: 1 } 50% { opacity: .5 } }
    @keyframes spin { to { transform: rotate(360deg) } }
    .pulse { animation: pulse 2s infinite; }
    .spinner { animation: spin .8s linear infinite; }
    .btn-camera-control { position: absolute; z-index: 10; background: rgba(0,0,0,.55); backdrop-filter: blur(6px); color: #fff; border: 1px solid rgba(255,255,255,.2); }
    .preview-thumb { width: 56px; height: 74px; object-fit: cover; border-radius: .5rem; border: 2px solid #e5e7eb; }
    .preview-thumb.active { border-color: #10b981; }
    .countdown-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.5); border-radius: 1.25rem; pointer-events: none; }
    .countdown-num { font-size: 5rem; font-weight: 900; color: #10b981; text-shadow: 0 0 30px rgba(16,185,129,.5); }
    .cover-result { width: 100px; height: 133px; object-fit: cover; border-radius: .75rem; border: 2px solid #e5e7eb; }
    .cover-result.has-image { border-color: #10b981; }
</style>

<div class="scan-root">
    
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-400">📊 Scanned Today</p>
            <p class="text-2xl font-black text-gray-900" id="stat-today"><?php echo e($todayCount); ?></p>
        </div>
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-400">📥 Inbox Pending</p>
            <p class="text-2xl font-black text-gray-900" id="stat-inbox">-</p>
        </div>
    </div>

    
    <div id="session-bar" class="rounded-2xl bg-primary-50 border border-primary-100 p-3 mb-4 <?php if(!$activeSession): ?> hidden <?php endif; ?>">
        <div class="flex items-center gap-3">
            <span><span class="inline-flex h-3 w-3 rounded-full bg-green-500 pulse"></span></span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-primary-900 truncate" id="session-label"><?php if($activeSession): ?> Sesi: <?php echo e($activeSession->operator_name); ?> <?php endif; ?></p>
                <p class="text-xs text-primary-600">Buku: <strong id="session-count"><?php echo e($activeSession->book_count ?? 0); ?></strong></p>
            </div>
            <button onclick="endSession()" class="text-xs font-medium text-primary-600 hover:text-primary-800 border border-primary-300 rounded-lg px-3 py-1.5">Selesai</button>
        </div>
    </div>

    
    <div id="screen-start" class="<?php if($activeSession): ?> hidden <?php endif; ?>">
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100 text-center">
            <p class="text-5xl mb-3">📷</p>
            <h2 class="text-lg font-bold text-gray-900">Siap Scan Buku</h2>
            <p class="text-sm text-gray-500 mt-1 mb-5">Masukkan nama operator lalu pilih metode scan</p>
            <div class="mb-4">
                <input type="text" id="op-name" maxlength="100" placeholder="Nama operator..." autocomplete="off"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
            </div>
            <p id="op-error" class="text-xs text-red-500 mb-3 hidden"></p>
            <button onclick="startSession()" class="w-full rounded-2xl bg-primary-700 px-6 py-3.5 text-sm font-bold text-white hover:bg-primary-800 transition active:scale-[0.98]">
                🟢 Mulai Scan
            </button>
        </div>
    </div>

    
    <div id="screen-mode" class="hidden">
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
            <h2 class="text-base font-bold text-gray-900 mb-4">Pilih Metode Scan</h2>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="startCamera('gemini')" class="rounded-xl border-2 border-primary-200 bg-primary-50 p-5 text-left hover:border-primary-500 transition active:scale-[0.98]">
                    <span class="text-3xl">📷</span>
                    <p class="mt-2 text-sm font-bold text-gray-900">Kamera</p>
                    <p class="mt-1 text-xs text-gray-500">Auto-capture cover buku</p>
                </button>
                <button onclick="showIsbn()" class="rounded-xl border-2 border-gray-200 bg-gray-50 p-5 text-left hover:border-primary-500 transition active:scale-[0.98]">
                    <span class="text-3xl">🔢</span>
                    <p class="mt-2 text-sm font-bold text-gray-900">ISBN</p>
                    <p class="mt-1 text-xs text-gray-500">Input / scan barcode</p>
                </button>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Pipeline AI</p>
                <p class="text-xs text-gray-500">Mode otomatis: Gemini + OCR + Catalog Enrichment</p>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>Sesi aktif — scan berikutnya</span>
                    <button onclick="endSession()" class="text-red-400 hover:text-red-600">Akhiri sesi</button>
                </div>
            </div>
        </div>

        
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100 mt-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-gray-900">📊 Progress Sesi Scan</h3>
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            </div>

            
            <div class="grid grid-cols-5 gap-1.5 mb-4 text-center">
                <div id="queue-stat-total-card" onclick="setQueueFilter('all')" class="rounded-xl bg-gray-50 p-2 border border-gray-100 cursor-pointer hover:shadow transition duration-200 ring-2 ring-primary-500 shadow-sm">
                    <p class="text-[10px] text-gray-400 font-medium">Total</p>
                    <p class="text-sm font-black text-gray-800" id="queue-stat-total">0</p>
                </div>
                <div id="queue-stat-waiting-card" onclick="setQueueFilter('waiting')" class="rounded-xl bg-yellow-50 p-2 border border-yellow-100 cursor-pointer hover:shadow transition duration-200">
                    <p class="text-[10px] text-yellow-600 font-medium">Antre</p>
                    <p class="text-sm font-black text-yellow-700" id="queue-stat-waiting">0</p>
                </div>
                <div id="queue-stat-processing-card" onclick="setQueueFilter('processing')" class="rounded-xl bg-blue-50 p-2 border border-blue-100 cursor-pointer hover:shadow transition duration-200">
                    <p class="text-[10px] text-blue-600 font-medium">Proses</p>
                    <p class="text-sm font-black text-blue-700" id="queue-stat-processing">0</p>
                </div>
                <div id="queue-stat-completed-card" onclick="setQueueFilter('completed')" class="rounded-xl bg-green-50 p-2 border border-green-100 cursor-pointer hover:shadow transition duration-200">
                    <p class="text-[10px] text-green-600 font-medium">Berhasil</p>
                    <p class="text-sm font-black text-green-700" id="queue-stat-completed">0</p>
                </div>
                <div id="queue-stat-failed-card" onclick="setQueueFilter('failed')" class="rounded-xl bg-red-50 p-2 border border-red-100 cursor-pointer hover:shadow transition duration-200">
                    <p class="text-[10px] text-red-600 font-medium">Gagal</p>
                    <p class="text-sm font-black text-red-700" id="queue-stat-failed">0</p>
                </div>
            </div>

            
            <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Antrean Pemrosesan</h4>
            <div class="space-y-2 max-h-60 overflow-y-auto pr-1" id="queue-list-container">
                <p class="text-xs text-gray-400 text-center py-4">Belum ada antrean scan hari ini.</p>
            </div>
        </div>
    </div>

    
    <div id="screen-camera" class="hidden">
        <div class="rounded-2xl bg-white shadow-sm border border-gray-100 overflow-hidden">
            
            <div class="flex justify-between items-center p-4 pb-2">
                <h2 class="text-base font-bold text-gray-900" id="cam-title">📷 Cover Depan</h2>
                <span class="text-xs bg-gray-100 px-2 py-1 rounded-full text-gray-500" id="cam-step">1/2</span>
            </div>

            
            <div class="cam-container mx-4 rounded-xl">
                <video id="cam-video" class="cam-view" autoplay playsinline muted></video>
                <div id="cam-overlay" class="cam-overlay"></div>
                
                <div id="countdown" class="countdown-overlay hidden">
                    <span class="countdown-num" id="countdown-num">3</span>
                </div>
                
                <button onclick="toggleCamera()" class="btn-camera-control top-3 right-3 rounded-full p-2.5 text-sm" title="Tukar kamera">
                    🔄
                </button>
                
                <button onclick="toggleFlash()" class="btn-camera-control top-3 left-3 rounded-full p-2.5 text-sm" title="Flash" id="btn-flash">
                    ⚡
                </button>
                
                <div id="barcode-indicator" class="hidden absolute bottom-3 left-1/2 -translate-x-1/2 bg-green-500/90 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur">
                    ✅ ISBN Terdeteksi!
                </div>
            </div>

            
            <div class="flex gap-3 p-4" id="preview-strip">
                <div class="flex flex-col items-center gap-1" id="preview-front-wrapper">
                    <div class="preview-thumb flex items-center justify-center bg-gray-100 text-gray-300 text-xs" id="preview-front">
                        <span>Cover<br>Depan</span>
                    </div>
                    <span class="text-[10px] text-gray-400" id="label-front">Depan</span>
                </div>
                <div class="flex flex-col items-center gap-1" id="preview-back-wrapper">
                    <div class="preview-thumb flex items-center justify-center bg-gray-100 text-gray-300 text-xs" id="preview-back">
                        <span>Cover<br>Blkg</span>
                    </div>
                    <span class="text-[10px] text-gray-400" id="label-back">Belakang</span>
                </div>
            </div>

            
            <div class="flex gap-2 px-4 pb-4">
                <button onclick="manualCapture()" id="btn-capture" class="flex-1 rounded-xl bg-primary-700 px-5 py-3.5 text-sm font-bold text-white hover:bg-primary-800 active:scale-[0.98] transition">
                    📸 Ambil Manual
                </button>
                <button onclick="backToMode()" class="rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-500 hover:bg-gray-50">
                    ←
                </button>
            </div>
            <p class="text-center text-xs text-gray-400 pb-4" id="cam-hint">Arahkan kamera ke cover — foto akan otomatis diambil</p>
        </div>
        <canvas id="cam-canvas" class="hidden"></canvas>
    </div>

    
    <div id="screen-isbn" class="hidden">
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
            <h2 class="text-base font-bold text-gray-900 mb-3">🔢 Input ISBN</h2>
            <p class="text-sm text-gray-500 mb-3">Masukkan nomor ISBN buku (10 atau 13 digit)</p>
            <div class="flex gap-2 mb-3">
                <input type="text" id="isbn-input" maxlength="20" placeholder="978-..." inputmode="numeric"
                    class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3.5 text-lg tracking-wider focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
                <button onclick="lookupIsbn()" class="rounded-xl bg-primary-700 px-6 py-3.5 text-sm font-bold text-white hover:bg-primary-800 active:scale-[0.98]">Cari</button>
            </div>
            <p class="text-xs text-gray-400 mb-3">Arahkan barcode ISBN ke kamera untuk auto-fill</p>
            
            <div id="isbn-scanner" class="w-full rounded-xl overflow-hidden mb-3" style="max-height:160px;"></div>
            <button onclick="backToMode()" class="text-sm text-gray-400 hover:text-gray-600">← Kembali</button>
        </div>
    </div>

    
    <div id="screen-processing" class="hidden">
        <div class="rounded-2xl bg-white p-8 shadow-sm border border-gray-100 text-center">
            <div class="spinner mx-auto mb-5 h-14 w-14 rounded-full border-[5px] border-primary-100 border-t-primary-600"></div>
            <h2 class="text-base font-bold text-gray-900" id="proc-title">🔍 Memproses gambar...</h2>
            <p class="text-sm text-gray-500 mt-2" id="proc-detail">Mengirim ke Gemini Vision AI</p>
            <div class="mt-4 flex justify-center gap-2" id="proc-previews"></div>
        </div>
    </div>

    
    <div id="screen-result" class="hidden">
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
            <h2 class="text-base font-bold text-gray-900 mb-4">✅ Hasil Scan</h2>

            
            <div class="flex gap-4 mb-4 p-3 bg-gray-50 rounded-xl">
                <img id="r-cover" class="cover-result bg-gray-200" src="" alt="Cover" style="display:none">
                <div class="cover-result flex items-center justify-center bg-gray-200 text-gray-400 text-xs text-center" id="r-cover-placeholder">
                    <span>Cover<br>Belum<br>Tersedia</span>
                </div>
                <div class="flex-1 text-sm">
                    <p class="font-bold text-gray-900" id="r-title-preview">-</p>
                    <p class="text-gray-500 text-xs mt-1">oleh <span id="r-author-preview">-</span></p>
                    <div class="mt-2 flex items-center gap-2">
                        <span id="r-source-badge" class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-bold text-blue-700">-</span>
                        <span id="r-confidence" class="text-[10px] text-gray-400"></span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Judul <span class="text-red-400">*</span></label>
                    <input type="text" id="r-title" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="Judul buku">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-bold text-gray-400 uppercase mb-1">Penulis</label>
                        <input type="text" id="r-author" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="Penulis">
                    </div>
                    <div><label class="block text-xs font-bold text-gray-400 uppercase mb-1">ISBN</label>
                        <input type="text" id="r-isbn" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="978-...">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-bold text-gray-400 uppercase mb-1">Penerbit</label>
                        <input type="text" id="r-publisher" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="Penerbit">
                    </div>
                    <div><label class="block text-xs font-bold text-gray-400 uppercase mb-1">Tahun</label>
                        <input type="number" id="r-year" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="2024">
                    </div>
                </div>
                <div><label class="block text-xs font-bold text-gray-400 uppercase mb-1">Kategori</label>
                    <input type="text" id="r-category" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="Fiksi, Teknologi...">
                </div>
                <div><label class="block text-xs font-bold text-gray-400 uppercase mb-1">Deskripsi</label>
                    <textarea id="r-description" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none" placeholder="Deskripsi buku..."></textarea>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button onclick="rescan()" class="rounded-xl border border-gray-300 px-5 py-3 text-sm text-gray-600 hover:bg-gray-50 active:scale-[0.98]">
                    🔄 Scan Ulang
                </button>
                <button onclick="saveToInbox()" class="flex-1 rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white hover:bg-green-700 active:scale-[0.98] transition">
                    ✅ Simpan ke Inbox
                </button>
            </div>
        </div>
    </div>

    
    <div id="screen-done" class="hidden">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-6 text-center shadow-sm">
            <p class="text-5xl mb-3">✅</p>
            <h2 class="text-lg font-bold text-emerald-800">Tersimpan ke Inbox!</h2>
            <p class="text-sm text-emerald-600 mt-1">Buku masuk ke tab <strong>Review</strong> dashboard admin</p>
            <button onclick="scanAgain()" class="mt-5 rounded-xl bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white hover:bg-emerald-700 active:scale-[0.98] w-full transition">
                📸 Scan Buku Lagi
            </button>
        </div>
    </div>

    
    <div id="screen-duplicate-warning" class="hidden">
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 text-center shadow-sm">
            <p class="text-5xl mb-3">⚠️</p>
            <h2 class="text-lg font-bold text-amber-800">Kemungkinan Duplikat</h2>
            <p class="text-sm text-amber-600 mt-1 mb-5" id="duplicate-warning-text">
                Buku ini kemungkinan sudah pernah dipindai dalam sesi ini.
            </p>
            <div class="flex gap-3">
                <button onclick="confirmDuplicateForce()" class="flex-1 rounded-xl bg-amber-600 px-4 py-3.5 text-sm font-bold text-white hover:bg-amber-700 active:scale-[0.98] transition">
                    Lanjutkan Scan
                </button>
                <button onclick="cancelDuplicate()" class="flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3.5 text-sm font-bold text-gray-600 hover:bg-gray-50 active:scale-[0.98] transition">
                    Batalkan
                </button>
            </div>
        </div>
    </div>

    
    <div id="job-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-xl border border-gray-100 animate-fade-in">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-base font-bold text-gray-900" id="modal-job-title">Detail Hasil Antrean</h3>
                <button onclick="closeJobModal()" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
            </div>

            
            <div class="space-y-4">
                
                <div class="flex gap-4 p-3 bg-gray-50 rounded-xl">
                    <img id="modal-cover" class="w-20 h-26 object-cover rounded-lg bg-gray-200 border border-gray-100" src="" alt="Cover">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-900 text-sm truncate" id="modal-book-title">-</p>
                        <p class="text-gray-500 text-xs mt-0.5 truncate">oleh <span id="modal-book-author">-</span></p>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span id="modal-status-badge" class="rounded-full px-2.5 py-0.5 text-[10px] font-bold">-</span>
                            <span id="modal-confidence" class="text-[10px] text-gray-400 font-semibold"></span>
                        </div>
                    </div>
                </div>

                
                <div class="space-y-2 text-xs" id="modal-metadata-section">
                    <div class="grid grid-cols-3 border-b border-gray-100 py-1.5">
                        <span class="text-gray-400 font-medium">ISBN</span>
                        <span class="col-span-2 text-gray-800 font-semibold" id="modal-isbn">-</span>
                    </div>
                    <div class="grid grid-cols-3 border-b border-gray-100 py-1.5">
                        <span class="text-gray-400 font-medium">Penerbit</span>
                        <span class="col-span-2 text-gray-800" id="modal-publisher">-</span>
                    </div>
                    <div class="grid grid-cols-3 border-b border-gray-100 py-1.5">
                        <span class="text-gray-400 font-medium">Tahun Terbit</span>
                        <span class="col-span-2 text-gray-800" id="modal-year">-</span>
                    </div>
                    <div class="grid grid-cols-3 border-b border-gray-100 py-1.5">
                        <span class="text-gray-400 font-medium">Kategori</span>
                        <span class="col-span-2 text-gray-800" id="modal-category">-</span>
                    </div>
                    <div class="pt-2">
                        <span class="text-gray-400 font-medium block mb-1">Deskripsi</span>
                        <p class="text-gray-600 bg-gray-50 p-2.5 rounded-xl border border-gray-100 max-h-32 overflow-y-auto leading-relaxed" id="modal-description">-</p>
                    </div>
                </div>

                
                <div id="modal-pipeline-section" class="p-3 bg-gray-50 border border-gray-100 rounded-xl text-xs space-y-2">
                    <span class="text-gray-500 font-bold block mb-1">Status Alur Pipeline:</span>
                    <div id="modal-pipeline-stages" class="space-y-1">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                
                <div id="modal-error-section" class="hidden p-3 bg-red-50 border border-red-100 rounded-xl text-xs">
                    <span class="text-red-700 font-bold block mb-1">⚠️ Gagal diproses:</span>
                    <p class="text-red-600 font-medium whitespace-pre-wrap" id="modal-error-message">-</p>
                </div>
            </div>

            
            <div class="mt-5 flex gap-2">
                <button id="modal-retry-btn" onclick="retryJob()" class="hidden flex-1 rounded-xl bg-primary-700 py-2.5 text-xs font-bold text-white hover:bg-primary-800 active:scale-[0.98] transition">
                    🔄 Coba Lagi
                </button>
                <button id="modal-manual-btn" onclick="modalToManual()" class="hidden flex-1 rounded-xl bg-green-600 py-2.5 text-xs font-bold text-white hover:bg-green-700 active:scale-[0.98] transition">
                    📝 Isi Manual
                </button>
                <button onclick="closeJobModal()" class="flex-1 rounded-xl border border-gray-300 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 active:scale-[0.98] transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    // ═══════════════════════════════════
    // STATE
    // ═══════════════════════════════════
    let sessionId = <?php echo e($activeSession ? $activeSession->id : 'null'); ?>;
    let currentStream = null;
    let facingMode = 'environment'; // 'environment' = back cam, 'user' = front cam
    let flashOn = false;
    let frontBlob = null, backBlob = null, frontDataUrl = null, backDataUrl = null;
    let lastFrontBlob = null, lastBackBlob = null;
    let captureTarget = 'front'; // 'front' | 'back'
    let currentCoverUrl = null;
    let autoCaptureActive = false;
    let countdownTimer = null;
    let stabilityCheck = null;
    let lastFrameData = null;
    let barcodeDetector = null;
    let html5QrReader = null;

    let pollInterval = null;
    let lastJobsList = [];
    let currentRetryJobId = null;
    let currentInboxId = null;
    let currentFilter = 'all'; // 'all' | 'waiting' | 'processing' | 'completed' | 'failed'

    const $ = id => document.getElementById(id);
    const screens = ['screen-start','screen-mode','screen-camera','screen-isbn','screen-processing','screen-result','screen-done','screen-duplicate-warning'];
    function showScreen(id) { screens.forEach(s => { if($(s)) $(s).classList.add('hidden'); }); const el = $(id); if(el) el.classList.remove('hidden'); }
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function formatSourceLabel(src) {
        const sourceLabel = src || 'gemini_vision';
        if (sourceLabel === 'google_books+openlibrary' || sourceLabel === 'google_books+openlibrary_gemini') {
            return '✓ Gemini + Google Books + Open Library';
        } else if (sourceLabel === 'google_books' || sourceLabel === 'google_books_gemini') {
            return '✓ Gemini + Google Books';
        } else if (sourceLabel === 'openlibrary' || sourceLabel === 'openlibrary_gemini') {
            return '✓ Gemini + Open Library';
        } else if (sourceLabel === 'gemini_vision' || sourceLabel === 'gemini_gemini') {
            return '⚠ Gemini Only';
        } else if (sourceLabel === 'cache') {
            return '⚡ Cache Hit';
        }
        return sourceLabel;
    }


    async function api(url, body, method='POST') {
        const opts = { method, headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf} };
        if (body instanceof FormData) opts.body = body;
        else if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const r = await fetch(url, opts);
        if (!r.ok) throw new Error((await r.json()).message || await r.text());
        return r.json();
    }

    function showSuccessQueued(queueNumber) {
        document.querySelector('#screen-done h2').textContent = 'Masuk Antrean!';
        document.querySelector('#screen-done p').innerHTML = `Buku Anda masuk antrean <strong>#${queueNumber}</strong> dan sedang diproses di latar belakang oleh AI.`;
        showScreen('screen-done');
    }

    function showSuccessSaved() {
        document.querySelector('#screen-done h2').textContent = 'Tersimpan ke Inbox!';
        document.querySelector('#screen-done p').innerHTML = 'Buku masuk ke tab <strong>Review</strong> dashboard admin';
        showScreen('screen-done');
    }

    // ═══════════════════════════════════
    // SESSION
    // ═══════════════════════════════════
    <?php if($activeSession): ?>
        showScreen('screen-mode'); fetchStats(); startQueuePolling();
    <?php endif; ?>

    window.startSession = async () => {
        const name = $('op-name').value.trim();
        if (!name) { $('op-error').textContent = 'Nama operator wajib diisi'; $('op-error').classList.remove('hidden'); return; }
        $('op-error').classList.add('hidden');
        try {
            const d = await api('/book-scanner/start', { operator_name: name });
            sessionId = d.session_id;
            $('session-label').textContent = 'Sesi: ' + d.operator_name;
            $('session-count').textContent = '0';
            $('session-bar').classList.remove('hidden');
            showScreen('screen-mode');
            fetchStats();
            startQueuePolling();
        } catch(e) { $('op-error').textContent = e.message; $('op-error').classList.remove('hidden'); }
    };

    window.endSession = async () => {
        await api('/book-scanner/end').catch(()=>{});
        sessionId = null; stopCamera(); stopAutoCapture(); stopQueuePolling();
        $('session-bar').classList.add('hidden');
        showScreen('screen-start');
    };

    // ═══════════════════════════════════
    // MODE
    // ═══════════════════════════════════
    window.backToMode = () => { stopCamera(); stopAutoCapture(); showScreen('screen-mode'); startQueuePolling(); };
    window.scanAgain = () => { resetScanState(); showScreen('screen-mode'); fetchStats(); startQueuePolling(); };
    window.rescan = () => { resetScanState(); showScreen('screen-mode'); startQueuePolling(); };

    function resetScanState() {
        frontBlob = null; backBlob = null; frontDataUrl = null; backDataUrl = null;
        lastFrontBlob = null; lastBackBlob = null;
        captureTarget = 'front';
        currentCoverUrl = null;
        currentInboxId = null;
        $('preview-front').innerHTML = '<span>Cover<br>Depan</span>';
        $('preview-front').classList.remove('active');
        $('preview-back').innerHTML = '<span>Cover<br>Blkg</span>';
        $('preview-back').classList.remove('active');
        $('r-cover').style.display = 'none';
        $('r-cover-placeholder').style.display = 'flex';
        
        const rescanBtn = document.querySelector('#screen-result button[onclick^="rescan"]');
        if (rescanBtn) {
            rescanBtn.innerHTML = '🔄 Scan Ulang';
            rescanBtn.setAttribute('onclick', 'rescan()');
        }
    }

    // ═══════════════════════════════════
    // CAMERA
    // ═══════════════════════════════════
    window.startCamera = () => {
        resetScanState();
        captureTarget = 'front';
        $('cam-title').textContent = '📷 Cover Depan';
        $('cam-step').textContent = '1/2';
        $('cam-hint').textContent = 'Arahkan kamera ke cover depan — foto otomatis';
        $('btn-capture').textContent = '📸 Ambil Manual';
        showScreen('screen-camera');
        stopQueuePolling();
        startStream();
    };

    async function startStream() {
        stopCamera();
        try {
            const constraints = {
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1920 },
                    height: { ideal: 1440 }
                },
                audio: false
            };
            currentStream = await navigator.mediaDevices.getUserMedia(constraints);

            // Enable flash if back camera
            const track = currentStream.getVideoTracks()[0];
            if (track && 'torch' in track.getCapabilities()) {
                $('btn-flash').style.display = 'block';
            }

            $('cam-video').srcObject = currentStream;
            await $('cam-video').play();

            // Start auto-capture after camera is ready
            setTimeout(() => startAutoCapture(), 800);
        } catch(e) {
            alert('Kamera tidak tersedia: ' + e.message);
            showScreen('screen-mode');
        }
    }

    window.stopCamera = () => {
        stopAutoCapture();
        if (currentStream) {
            currentStream.getTracks().forEach(t => t.stop());
            currentStream = null;
        }
    };

    window.toggleCamera = () => {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        stopAutoCapture();
        startStream();
    };

    window.toggleFlash = async () => {
        if (!currentStream) return;
        const track = currentStream.getVideoTracks()[0];
        if (!track) return;
        try {
            flashOn = !flashOn;
            await track.applyConstraints({ advanced: [{ torch: flashOn }] });
            $('btn-flash').textContent = flashOn ? '🔆' : '⚡';
        } catch(e) { /* Flash not supported */ }
    };

    // ═══════════════════════════════════
    // AUTO-CAPTURE
    // ═══════════════════════════════════
    function startAutoCapture() {
        autoCaptureActive = true;
        $('cam-overlay').classList.add('scanning');

        // Try BarcodeDetector first
        if ('BarcodeDetector' in window) {
            try {
                barcodeDetector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e'] });
                detectBarcodeLoop();
                return;
            } catch(e) { /* fall through to stability */ }
        }

        // Fallback: stability-based countdown
        startStabilityDetection();
    }

    function stopAutoCapture() {
        autoCaptureActive = false;
        if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
        if (stabilityCheck) { clearInterval(stabilityCheck); stabilityCheck = null; }
        $('countdown').classList.add('hidden');
        $('cam-overlay').classList.remove('scanning');
        $('barcode-indicator').classList.add('hidden');
        barcodeDetector = null;
    }

    // ── Barcode Detection ──
    async function detectBarcodeLoop() {
        if (!autoCaptureActive || !barcodeDetector) return;
        try {
            const video = $('cam-video');
            if (video.readyState < 2) { setTimeout(detectBarcodeLoop, 300); return; }

            const barcodes = await barcodeDetector.detect(video);
            if (barcodes.length > 0) {
                const value = barcodes[0].rawValue.replace(/[^0-9Xx]/g, '');
                if (value.length >= 10 && value.length <= 13) {
                    // ISBN detected! Auto-capture
                    $('barcode-indicator').classList.remove('hidden');
                    $('cam-overlay').classList.add('captured');
                    setTimeout(() => {
                        $('barcode-indicator').classList.add('hidden');
                        doCapture();
                    }, 600);
                    return;
                }
            }
        } catch(e) { /* ignore */ }
        setTimeout(detectBarcodeLoop, 400);
    }

    // ── Stability Detection ──
    function startStabilityDetection() {
        let stableFrames = 0;
        const requiredStableFrames = 30; // ~1.5s at 50ms interval
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 120; canvas.height = 90; // small for fast comparison

        stabilityCheck = setInterval(() => {
            if (!autoCaptureActive) return;
            const video = $('cam-video');
            if (video.readyState < 2) { stableFrames = 0; return; }

            ctx.drawImage(video, 0, 0, 120, 90);
            const current = ctx.getImageData(0, 0, 120, 90).data;
            const hash = simpleHash(current);

            if (lastFrameData === hash) {
                stableFrames++;
                if (stableFrames === 20) startCountdown(); // Show countdown
                if (stableFrames >= requiredStableFrames) {
                    $('cam-overlay').classList.add('captured');
                    setTimeout(doCapture, 300);
                }
            } else {
                stableFrames = 0;
                $('countdown').classList.add('hidden');
                $('cam-overlay').classList.remove('scanning');
                $('cam-overlay').classList.add('scanning');
            }
            lastFrameData = hash;
        }, 50);
    }

    function simpleHash(data) {
        let h = 0;
        for (let i = 0; i < data.length; i += 16) h = ((h << 5) - h + data[i]) | 0;
        return h;
    }

    function startCountdown() {
        let count = 3;
        $('countdown').classList.remove('hidden');
        $('countdown-num').textContent = count;
        if (countdownTimer) clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            count--;
            if (count <= 0) { clearInterval(countdownTimer); countdownTimer = null; }
            else $('countdown-num').textContent = count;
        }, 600);
    }

    // ═══════════════════════════════════
    // CAPTURE
    // ═══════════════════════════════════
    window.manualCapture = () => {
        stopAutoCapture();
        $('cam-overlay').classList.add('captured');
        setTimeout(doCapture, 200);
    };

    function doCapture() {
        stopAutoCapture();
        $('countdown').classList.add('hidden');
        const video = $('cam-video');
        const canvas = $('cam-canvas');
        canvas.width = video.videoWidth || 1920;
        canvas.height = video.videoHeight || 1440;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

        canvas.toBlob(blob => {
            if (captureTarget === 'front') {
                frontBlob = blob;
                frontDataUrl = dataUrl;
                updatePreview('preview-front', dataUrl, 'Depan ✅');
                // Move to back cover
                captureTarget = 'back';
                $('cam-title').textContent = '📷 Cover Belakang';
                $('cam-step').textContent = '2/2';
                $('cam-hint').textContent = 'Arahkan ke cover belakang (opsional)';
                $('btn-capture').textContent = '📸 Ambil / Lewati';
                $('cam-overlay').classList.remove('captured');
                lastFrameData = null;
                setTimeout(() => {
                    if (autoCaptureActive) return;
                    startAutoCapture();
                }, 500);
            } else {
                backBlob = blob;
                backDataUrl = dataUrl;
                updatePreview('preview-back', dataUrl, 'Blkg ✅');
                stopCamera();
                processImages();
            }
        }, 'image/jpeg', 0.85);
    }

    // Override manual capture for back → skip goes to processing
    const origManualCapture = window.manualCapture;
    window.manualCapture = function() {
        stopAutoCapture();
        if (captureTarget === 'back') {
            // "Ambil / Lewati" → skip back, go to processing
            stopCamera();
            processImages();
            return;
        }
        $('cam-overlay').classList.add('captured');
        setTimeout(doCapture, 200);
    };

    function updatePreview(elId, dataUrl, label) {
        const el = $(elId);
        el.innerHTML = `<img src="${dataUrl}" class="w-full h-full object-cover rounded-md">`;
        el.classList.add('active');
    }

    // ═══════════════════════════════════
    // ISBN
    // ═══════════════════════════════════
    window.showIsbn = () => {
        showScreen('screen-isbn');
        $('isbn-input').focus();
        initIsbnScanner();
    };

    function initIsbnScanner() {
        if (typeof Html5Qrcode === 'undefined') return;
        if (html5QrReader) return;
        try {
            html5QrReader = new Html5Qrcode('isbn-scanner');
            html5QrReader.start(
                { facingMode: 'environment' },
                { fps: 8, qrbox: { width: 280, height: 80 } },
                text => {
                    const isbn = text.replace(/[^0-9Xx]/g, '');
                    if (isbn.length >= 10 && isbn.length <= 13) {
                        $('isbn-input').value = isbn;
                        html5QrReader.stop().then(() => { html5QrReader = null; lookupIsbn(); });
                    }
                },
                () => {}
            ).catch(() => { html5QrReader = null; });
        } catch(e) { html5QrReader = null; }
    }

    window.lookupIsbn = async () => {
        const isbn = $('isbn-input').value.trim().replace(/[^0-9Xx]/g, '');
        if (!isbn) return;
        if (html5QrReader) { html5QrReader.stop().catch(()=>{}); html5QrReader = null; }
        showScreen('screen-processing');
        $('proc-title').textContent = '🔍 Mencari ISBN...';
        $('proc-detail').textContent = 'Google Books → OpenLibrary';
        try {
            const r = await api('/book-scanner/isbn', { isbn });
            if (r.found) showResult(r.book, r.source);
            else showResult({ isbn, title: 'ISBN: ' + isbn }, 'manual');
        } catch(e) { alert('Lookup gagal: ' + e.message); backToMode(); }
    };

    // ═══════════════════════════════════
    // PROCESSING
    // ═══════════════════════════════════
    window.processImages = async (force = false) => {
        if (!frontBlob && !lastFrontBlob) { alert('Cover depan wajib difoto'); return startCamera(); }
        
        if (frontBlob) lastFrontBlob = frontBlob;
        if (backBlob) lastBackBlob = backBlob;

        showScreen('screen-processing');
        $('proc-title').textContent = '📤 Mengunggah...';
        $('proc-detail').textContent = 'Menyimpan cover & memasukkan ke antrean';

        const form = new FormData();
        form.append('front', lastFrontBlob, 'front.jpg');
        if (lastBackBlob) form.append('back', lastBackBlob, 'back.jpg');

        if (force) {
            form.append('force', 'true');
        }

        const prevEl = $('proc-previews');
        prevEl.innerHTML = '';
        if (frontDataUrl) prevEl.innerHTML += `<img src="${frontDataUrl}" class="preview-thumb">`;
        if (backDataUrl) prevEl.innerHTML += `<img src="${backDataUrl}" class="preview-thumb">`;

        try {
            const r = await api('/book-scanner/enqueue', form);
            if (r.warning === 'duplicate_detected') {
                showScreen('screen-duplicate-warning');
            } else if (r.queued) {
                const c = parseInt($('session-count').textContent || '0');
                $('session-count').textContent = c + 1;
                resetScanState();
                showSuccessQueued(r.queue_number);
                fetchStats();
            } else {
                throw new Error(r.error || 'Gagal memasukkan antrean');
            }
        } catch(e) {
            alert('Gagal mengantrekan scan: ' + e.message);
            backToMode();
        }
    };

    // ═══════════════════════════════════
    // RESULT
    // ═══════════════════════════════════
    function showResult(book, source) {
        populateFields(book);
        $('r-source-badge').textContent = formatSourceLabel(source);
        showScreen('screen-result');
    }

    function populateFields(b) {
        $('r-title').value = b.title || '';
        $('r-author').value = b.author || '';
        $('r-isbn').value = b.isbn || '';
        $('r-publisher').value = b.publisher || '';
        $('r-year').value = b.published_year || '';
        $('r-category').value = b.category || '';
        $('r-description').value = b.description || '';
        currentCoverUrl = b.cover_url || null;

        // Preview text
        $('r-title-preview').textContent = b.title || '(Belum ada judul)';
        $('r-author-preview').textContent = b.author || '-';

        // Cover image: prefer external URL, fallback to front cover
        const coverUrl = b.cover_url;
        if (coverUrl) {
            $('r-cover').src = coverUrl;
            $('r-cover').style.display = 'block';
            $('r-cover-placeholder').style.display = 'none';
            $('r-cover').classList.add('has-image');
        } else if (frontDataUrl) {
            $('r-cover').src = frontDataUrl;
            $('r-cover').style.display = 'block';
            $('r-cover-placeholder').style.display = 'none';
            $('r-cover').classList.add('has-image');
        } else {
            $('r-cover').style.display = 'none';
            $('r-cover-placeholder').style.display = 'flex';
        }
    }

    // ═══════════════════════════════════
    // SAVE
    // ═══════════════════════════════════
    window.saveToInbox = async () => {
        const title = $('r-title').value.trim();
        if (!title) { alert('Judul wajib diisi'); return; }
        const data = {
            title, author: $('r-author').value, isbn: $('r-isbn').value,
            publisher: $('r-publisher').value,
            published_year: $('r-year').value ? parseInt($('r-year').value) : null,
            category: $('r-category').value,
            description: $('r-description').value,
            cover_url: currentCoverUrl,
            source: $('r-source-badge').textContent,
            inbox_id: currentInboxId,
        };
        try {
            await api('/book-scanner/save-inbox', data);
            if (!currentInboxId) {
                const c = parseInt($('session-count').textContent || '0');
                $('session-count').textContent = c + 1;
            }
            resetScanState();
            showSuccessSaved();
            fetchStats();
        } catch(e) { alert('Gagal: ' + e.message); }
    };

    // ═══════════════════════════════════
    // STATS & POLLING
    // ═══════════════════════════════════
    async function fetchStats() {
        try {
            const d = await api('/book-scanner/stats', null, 'GET');
            $('stat-today').textContent = d.total_books || 0;
            $('stat-inbox').textContent = d.inbox_today || 0;
        } catch(e) {}
    }

    window.confirmDuplicateForce = () => {
        window.processImages(true);
    };

    window.cancelDuplicate = () => {
        resetScanState();
        showScreen('screen-mode');
        startQueuePolling();
    };

    window.openJobModal = (jobId) => {
        const job = lastJobsList.find(j => j.id === jobId);
        if (!job) return;

        // Populate modal fields
        $('modal-job-title').textContent = `Detail Antrean #${jobId}`;
        
        // Status text & badge mapping
        const badge = $('modal-status-badge');
        badge.className = 'rounded-full px-2.5 py-0.5 text-[10px] font-bold';
        
        let statusLabel = '';
        let statusClass = '';
        
        if (job.status === 'waiting') {
            statusLabel = 'Mengantre';
            statusClass = 'bg-yellow-100 text-yellow-700';
        } else if (job.status === 'processing') {
            statusLabel = 'Memproses...';
            statusClass = 'bg-blue-100 text-blue-700';
        } else if (job.status === 'completed') {
            statusLabel = job.inbox_status === 'approved' ? 'Selesai (Auto-Approve)' : 'Selesai (Butuh Review)';
            statusClass = job.inbox_status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';
        } else if (job.status === 'failed') {
            statusLabel = 'Gagal';
            statusClass = 'bg-red-100 text-red-700';
        }

        badge.textContent = statusLabel;
        badge.classList.add(...statusClass.split(' '));

        // Cover image
        const coverUrl = job.front_cover_path ? '/storage/' + job.front_cover_path : '';
        $('modal-cover').src = coverUrl;

        // Title & Author & Details
        if (job.status === 'completed') {
            $('modal-book-title').textContent = job.book_title || 'Selesai';
            $('modal-book-author').textContent = job.book_author || '-';
            $('modal-isbn').textContent = job.book_isbn || '-';
            $('modal-publisher').textContent = job.book_publisher || '-';
            $('modal-year').textContent = job.book_year || '-';
            $('modal-category').textContent = job.book_category || '-';
            $('modal-description').textContent = job.book_description || '(Tidak ada deskripsi)';
            $('modal-confidence').textContent = `(${job.confidence_score}%)`;
            $('modal-confidence').classList.remove('hidden');
            $('modal-metadata-section').classList.remove('hidden');
        } else {
            $('modal-book-title').textContent = 'Buku dalam antrean';
            $('modal-book-author').textContent = '-';
            $('modal-isbn').textContent = '-';
            $('modal-publisher').textContent = '-';
            $('modal-year').textContent = '-';
            $('modal-category').textContent = '-';
            $('modal-description').textContent = '-';
            $('modal-confidence').textContent = '';
            $('modal-confidence').classList.add('hidden');
            $('modal-metadata-section').classList.add('hidden');
        }

        // Render pipeline stages
        const stagesList = [
            { key: 'identification', label: '1. Identifikasi (Gemini + OCR)' },
            { key: 'lookup', label: '2. Pencarian Katalog (API)' },
            { key: 'enrichment', label: '3. Penggabungan & Confidence' },
            { key: 'fallback', label: '4. Pengisian Fallback Data' },
            { key: 'inbox', label: '5. Penyelesaian & Inbox Staging' }
        ];

        let stagesHtml = '';
        let currentReached = false;

        stagesList.forEach(stage => {
            let icon = '⬜';
            let labelClass = 'text-gray-400';
            let durationStr = '';
            
            if (job.pipeline_metrics && job.pipeline_metrics[stage.key]) {
                durationStr = ` (${(job.pipeline_metrics[stage.key] / 1000).toFixed(1)}s)`;
            }

            if (job.status === 'completed') {
                icon = '✅';
                labelClass = 'text-green-700 font-medium';
            } else if (job.status === 'waiting') {
                icon = '⬜';
                labelClass = 'text-gray-400';
            } else {
                // job.status is 'processing' or 'failed'
                if (stage.key === job.current_stage) {
                    if (job.status === 'failed') {
                        icon = '❌';
                        labelClass = 'text-red-700 font-bold';
                    } else {
                        icon = '⏳';
                        labelClass = 'text-blue-700 font-bold animate-pulse';
                    }
                    currentReached = true;
                } else if (!currentReached) {
                    icon = '✅';
                    labelClass = 'text-gray-700 font-medium';
                } else {
                    icon = '⬜';
                    labelClass = 'text-gray-400';
                }
            }
            
            stagesHtml += `
            <div class="flex items-center justify-between py-1 border-b border-gray-50 last:border-0">
                <div class="flex items-center gap-2">
                    <span>${icon}</span>
                    <span class="${labelClass}">${stage.label}</span>
                </div>
                <span class="text-[10px] text-gray-400 font-semibold">${durationStr}</span>
            </div>`;
        });
        $('modal-pipeline-stages').innerHTML = stagesHtml;

        // Error section
        if (job.status === 'failed') {
            $('modal-error-section').classList.remove('hidden');
            $('modal-error-message').textContent = job.error_message || 'Kesalahan internal tidak diketahui.';
            $('modal-retry-btn').classList.remove('hidden');
            $('modal-manual-btn').classList.remove('hidden');
            currentRetryJobId = jobId;
        } else {
            $('modal-error-section').classList.add('hidden');
            $('modal-retry-btn').classList.add('hidden');
            $('modal-manual-btn').classList.add('hidden');
            currentRetryJobId = null;
        }

        $('job-detail-modal').classList.remove('hidden');
    };

    window.closeJobModal = () => {
        $('job-detail-modal').classList.add('hidden');
        currentRetryJobId = null;
    };

    window.retryJob = async () => {
        if (!currentRetryJobId) return;
        const jobId = currentRetryJobId;
        const retryBtn = $('modal-retry-btn');
        retryBtn.disabled = true;
        retryBtn.textContent = '⏳ Memproses...';
        try {
            const r = await api(`/book-scanner/retry/${jobId}`, null, 'POST');
            if (r.success) {
                closeJobModal();
                pollQueueStatus();
            } else {
                alert('Gagal mengirim ulang: ' + (r.error || 'Kesalahan internal.'));
                retryBtn.disabled = false;
                retryBtn.textContent = '🔄 Coba Lagi';
            }
        } catch (e) {
            alert('Gagal mengirim ulang: ' + e.message);
            retryBtn.disabled = false;
            retryBtn.textContent = '🔄 Coba Lagi';
        }
    };

    function startQueuePolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollQueueStatus();
        pollInterval = setInterval(pollQueueStatus, 3000);
    }

    function stopQueuePolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    async function pollQueueStatus() {
        if (!sessionId) return;
        try {
            const d = await api('/book-scanner/queue-status', null, 'GET');
            
            // Save to state
            lastJobsList = d.jobs || [];

            // Update Stats Bar / Summary
            $('queue-stat-total').textContent = d.session.total_books || 0;
            $('queue-stat-waiting').textContent = d.session.waiting_count || 0;
            $('queue-stat-processing').textContent = d.session.processing_count || 0;
            $('queue-stat-completed').textContent = d.session.completed_count || 0;
            $('queue-stat-failed').textContent = d.session.failed_count || 0;

            if ($('session-count')) {
                $('session-count').textContent = d.session.completed_count || 0;
            }
            
            renderQueueList();
        } catch (e) {
            console.error('Queue poll failed', e);
        }
    }

    function renderQueueList() {
        const container = $('queue-list-container');
        if (!container) return;

        let filteredJobs = lastJobsList;
        if (currentFilter !== 'all') {
            filteredJobs = lastJobsList.filter(job => job.status === currentFilter);
        }

        if (filteredJobs.length === 0) {
            let statusText = 'antrean scan';
            if (currentFilter === 'waiting') statusText = 'antrean yang mengantre';
            if (currentFilter === 'processing') statusText = 'antrean yang sedang diproses';
            if (currentFilter === 'completed') statusText = 'antrean yang berhasil';
            if (currentFilter === 'failed') statusText = 'antrean yang gagal';
            container.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Tidak ada ${statusText} saat ini.</p>`;
            return;
        }

        let html = '';
        filteredJobs.forEach(job => {
            const coverSrc = job.front_cover_path ? '/storage/' + job.front_cover_path : '';
            
            if (job.status === 'waiting') {
                html += `
                <div onclick="openJobDetails(${job.id})" class="flex items-center gap-3 p-2 bg-gray-50 border border-gray-100 rounded-xl cursor-pointer hover:bg-gray-100 transition duration-200 animate-fade-in">
                    <img src="${coverSrc}" class="w-8 h-10 object-cover rounded bg-gray-200">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-gray-700 truncate">Mengantre</p>
                            <span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-semibold">Antrean #${job.queue_number}</span>
                        </div>
                        <p class="text-[10px] text-gray-400 font-medium">Prioritas: ${job.priority || 'normal'} (Klik detail)</p>
                    </div>
                </div>`;
            } else if (job.status === 'processing') {
                let stageText = 'Sedang Diproses AI...';
                let stageLabel = 'Mengekstrak sinopsis & metadata';
                if (job.current_stage === 'identification') {
                    stageText = 'Stage 1/5: Identifikasi Buku';
                    stageLabel = 'Mengekstrak teks & visual cover...';
                } else if (job.current_stage === 'lookup') {
                    stageText = 'Stage 2/5: Mencari Katalog';
                    stageLabel = 'Mencari di Google Books & OpenLibrary...';
                } else if (job.current_stage === 'enrichment') {
                    stageText = 'Stage 3/5: Penggabungan & Confidence';
                    stageLabel = 'Menghitung tingkat akurasi data...';
                } else if (job.current_stage === 'fallback') {
                    stageText = 'Stage 4/5: Pengisian Fallback';
                    stageLabel = 'Mengisi field data kosong...';
                } else if (job.current_stage === 'inbox') {
                    stageText = 'Stage 5/5: Menyimpan ke Inbox';
                    stageLabel = 'Buku sedang dikirim ke inbox review...';
                }

                html += `
                <div onclick="openJobDetails(${job.id})" class="flex items-center gap-3 p-2 bg-blue-50/50 border border-blue-100 rounded-xl cursor-pointer hover:bg-blue-100/70 transition duration-200 animate-fade-in">
                    <img src="${coverSrc}" class="w-8 h-10 object-cover rounded bg-gray-200 animate-pulse">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-blue-700 truncate">${stageText}</p>
                            <span class="flex items-center gap-1 text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold animate-pulse">
                                Proses
                            </span>
                        </div>
                        <p class="text-[10px] text-blue-500 font-medium truncate">${stageLabel} (Klik detail)</p>
                    </div>
                </div>`;
            } else if (job.status === 'completed') {
                const statusBadge = job.inbox_status === 'approved' 
                    ? '<span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">Auto-Approve</span>' 
                    : '<span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-semibold">Butuh Review</span>';
                
                html += `
                <div onclick="openJobDetails(${job.id})" class="flex items-center gap-3 p-2 bg-green-50/20 border border-green-100 rounded-xl cursor-pointer hover:bg-green-50/40 transition duration-200 animate-fade-in">
                    <img src="${coverSrc}" class="w-8 h-10 object-cover rounded bg-gray-200">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-gray-800 truncate" title="${job.book_title || 'Selesai'}">${job.book_title || 'Selesai diproses'}</p>
                            ${statusBadge}
                        </div>
                        <p class="text-[10px] text-gray-500 truncate">oleh ${job.book_author || '-'} (Klik detail)</p>
                    </div>
                </div>`;
            } else if (job.status === 'failed') {
                const errorMsg = job.error_message || 'Kesalahan internal';
                html += `
                <div onclick="openJobDetails(${job.id})" class="flex items-center gap-3 p-2 bg-red-50/50 border border-red-100 rounded-xl cursor-pointer hover:bg-red-100/70 transition duration-200 animate-fade-in">
                    <img src="${coverSrc}" class="w-8 h-10 object-cover rounded bg-gray-200 opacity-60">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-red-700 truncate">Gagal diproses</p>
                            <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold">Gagal</span>
                        </div>
                        <p class="text-[10px] text-red-500 truncate" title="${errorMsg}">${errorMsg} (Klik detail)</p>
                    </div>
                </div>`;
            }
        });
        container.innerHTML = html;
    }

    window.setQueueFilter = (filter) => {
        currentFilter = filter;
        const filterCards = {
            'all': 'queue-stat-total-card',
            'waiting': 'queue-stat-waiting-card',
            'processing': 'queue-stat-processing-card',
            'completed': 'queue-stat-completed-card',
            'failed': 'queue-stat-failed-card'
        };
        
        Object.keys(filterCards).forEach(key => {
            const el = $(filterCards[key]);
            if (el) {
                el.classList.remove('ring-2', 'ring-primary-500', 'shadow-md');
            }
        });
        
        const activeEl = $(filterCards[filter]);
        if (activeEl) {
            activeEl.classList.add('ring-2', 'ring-primary-500', 'shadow-md');
        }
        
        renderQueueList();
    };

    window.openJobDetails = async (jobId) => {
        await pollQueueStatus(); // Refresh data dulu agar status selalu akurat
        const job = lastJobsList.find(j => j.id === jobId);
        if (!job) return;

        if (job.status === 'completed') {
            stopQueuePolling();
            
            // Set active editing state
            currentInboxId = job.inbox_id || null;
            
            // Populate form fields
            $('r-title').value = job.book_title || '';
            $('r-author').value = job.book_author || '';
            $('r-isbn').value = job.book_isbn || '';
            $('r-publisher').value = job.book_publisher || '';
            $('r-year').value = job.book_year || '';
            $('r-category').value = job.book_category || '';
            $('r-description').value = job.book_description || '';
            currentCoverUrl = null;

            // Preview text
            $('r-title-preview').textContent = job.book_title || '(Belum ada judul)';
            $('r-author-preview').textContent = job.book_author || '-';

            // Cover preview using local storage file path
            if (job.front_cover_path) {
                const coverPath = '/storage/' + job.front_cover_path;
                $('r-cover').src = coverPath;
                $('r-cover').style.display = 'block';
                $('r-cover-placeholder').style.display = 'none';
                $('r-cover').classList.add('has-image');
            } else {
                $('r-cover').style.display = 'none';
                $('r-cover-placeholder').style.display = 'flex';
            }

            // Set badge & confidence text
            $('r-source-badge').textContent = formatSourceLabel(job.source);
            $('r-confidence').textContent = job.confidence_score ? `(${job.confidence_score}%)` : '';
            
            // Update rescan button to be "← Kembali"
            const rescanBtn = document.querySelector('#screen-result button[onclick^="rescan"]');
            if (rescanBtn) {
                rescanBtn.innerHTML = '← Kembali';
                rescanBtn.setAttribute('onclick', 'backToMode()');
            }

            showScreen('screen-result');
        } else {
            // For waiting, processing, failed: show details modal
            openJobModal(jobId);
        }
    };

    window.modalToManual = () => {
        if (!currentRetryJobId) return;
        const job = lastJobsList.find(j => j.id === currentRetryJobId);
        closeJobModal();
        if (!job) return;

        stopQueuePolling();
        
        // Treat as manual entry
        currentInboxId = null;
        
        $('r-title').value = '';
        $('r-author').value = '';
        $('r-isbn').value = '';
        $('r-publisher').value = '';
        $('r-year').value = '';
        $('r-category').value = '';
        $('r-description').value = '';
        currentCoverUrl = null;

        // Cover preview
        if (job.front_cover_path) {
            const coverPath = '/storage/' + job.front_cover_path;
            $('r-cover').src = coverPath;
            $('r-cover').style.display = 'block';
            $('r-cover-placeholder').style.display = 'none';
            $('r-cover').classList.add('has-image');
        } else {
            $('r-cover').style.display = 'none';
            $('r-cover-placeholder').style.display = 'flex';
        }

        $('r-title-preview').textContent = '(Belum ada judul)';
        $('r-author-preview').textContent = '-';
        $('r-source-badge').textContent = 'manual';
        $('r-confidence').textContent = '';

        // Update rescan button to be "← Kembali"
        const rescanBtn = document.querySelector('#screen-result button[onclick^="rescan"]');
        if (rescanBtn) {
            rescanBtn.innerHTML = '← Kembali';
            rescanBtn.setAttribute('onclick', 'backToMode()');
        }

        showScreen('screen-result');
    };

    window.addEventListener('beforeunload', () => { stopCamera(); stopQueuePolling(); if(html5QrReader) html5QrReader.stop().catch(()=>{}); });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.scanner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Downloads\Smart_LMS\resources\views/scanner/mobile-scan.blade.php ENDPATH**/ ?>