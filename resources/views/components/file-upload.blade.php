@props(['name' => 'image', 'value' => null, 'required' => false])

<div x-data="{ 
    fileName: '{{ $value ? basename($value) : '' }}',
    fileSize: '{{ $value ? 'Foto Tersimpan' : '' }}',
    preview: '{{ $value ? asset('storage/' . $value) : '' }}',
    hasFile: {{ $value ? 'true' : 'false' }}
}" 
     class="space-y-2">
    
    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
        Upload Bukti Transaksi <span class="text-slate-400 font-normal lowercase">(opsional)</span>
    </label>

    <!-- Tombol Pilihan -->
    <div class="grid grid-cols-2 gap-3">
        <button type="button" 
                onclick="document.getElementById('fileInput-{{ $name }}').click()"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 dark:bg-navy-800 hover:bg-slate-200 dark:hover:bg-navy-700 text-slate-600 dark:text-white/70 rounded-xl border border-slate-200 dark:border-navy-700 font-semibold text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Galeri
        </button>
        
        <button type="button" 
                onclick="captureFromCamera('fileInput-{{ $name }}')"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-blue-50 dark:bg-navy-400/10 hover:bg-blue-100 dark:hover:bg-navy-400/20 text-blue-600 dark:text-navy-400 rounded-xl border border-blue-200 dark:border-navy-400/20 font-semibold text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Kamera
        </button>
    </div>

    <!-- Hidden file input (always accessible for Galeri/Kamera buttons) -->
    <input type="file" 
           name="{{ $name }}" 
           id="fileInput-{{ $name }}" 
           accept="image/*"
           capture="environment"
           class="hidden"
           @change="handleFileSelect($event, '{{ $name }}')"
           {{ $required ? 'required' : '' }}>

    <!-- Drop Zone (desktop only) -->
    <div id="drop-zone-{{ $name }}" 
         class="relative border-2 border-dashed border-slate-200 dark:border-navy-800 hover:border-blue-400 dark:hover:border-blue-500 rounded-2xl p-6 text-center bg-slate-50/50 dark:bg-navy-800/40 hover:bg-slate-50 dark:hover:bg-navy-800/50 transition cursor-pointer hidden md:block"
         @dragover.prevent
         @drop.prevent="handleDrop($event, '{{ $name }}')">

        <!-- Placeholder -->
        <div x-show="!hasFile && !preview" class="space-y-2">
            <div class="w-12 h-12 mx-auto bg-blue-50 dark:bg-navy-400/10 text-blue-500 dark:text-navy-400 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-white/80">
                <span class="text-blue-600 dark:text-navy-400">Klik</span> atau tarik gambar ke sini
            </p>
            <p class="text-xs text-slate-400 dark:text-white/50">PNG, JPG, JPEG hingga 2MB</p>
        </div>

        <!-- Preview -->
        <div x-show="hasFile || preview" 
             class="flex items-center justify-between p-3 bg-white dark:bg-navy-900 rounded-xl border border-slate-200 dark:border-navy-800">
            <div class="flex items-center gap-3 min-w-0">
                <img :src="preview" alt="Preview" class="w-12 h-12 rounded-lg object-cover border border-slate-100 dark:border-navy-800 flex-shrink-0">
                <div class="text-left min-w-0">
                    <p class="text-xs font-bold text-slate-800 dark:text-white/90 truncate" x-text="fileName || 'Foto tersimpan'"></p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold" x-text="fileSize || 'Tersimpan'"></p>
                </div>
            </div>
            <button type="button" 
                    @click="removeFile('{{ $name }}')" 
                    class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function captureFromCamera(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.setAttribute('capture', 'environment');
        input.click();
    }
}

function handleFileSelect(event, name) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const component = document.querySelector(`[x-data]`);
            if (component && component.__x) {
                component.__x.$data.fileName = file.name;
                component.__x.$data.fileSize = (file.size / 1024).toFixed(1) + ' KB';
                component.__x.$data.preview = e.target.result;
                component.__x.$data.hasFile = true;
            }
        };
        reader.readAsDataURL(file);
    }
}

function removeFile(name) {
    const input = document.getElementById('fileInput-' + name);
    if (input) {
        input.value = '';
        const component = document.querySelector(`[x-data]`);
        if (component && component.__x) {
            component.__x.$data.fileName = '';
            component.__x.$data.fileSize = '';
            component.__x.$data.preview = '';
            component.__x.$data.hasFile = false;
        }
    }
}

function handleDrop(event, name) {
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const input = document.getElementById('fileInput-' + name);
        if (input) {
            input.files = files;
            handleFileSelect({ target: input }, name);
        }
    }
}
</script>