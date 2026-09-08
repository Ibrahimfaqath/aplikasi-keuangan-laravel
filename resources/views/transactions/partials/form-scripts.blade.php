{{-- Script form transaksi: voice input + upload preview + sinkron kategori.
     Variabel opsional: $transaction (?Transaction) untuk preview gambar lama saat edit. --}}
<script>
function voiceInput() {
    return {
        recording: false,
        voiceResult: '',
        recognition: null,
        silenceTimer: null,

        toggleVoice() {
            if (this.recording) {
                clearTimeout(this.silenceTimer);
                this.recognition?.stop();
                this.recording = false;
                return;
            }
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                alert('Browser kamu tidak mendukung voice input. Gunakan Chrome atau Edge.');
                return;
            }
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'id-ID';
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            const self = this;
            this.recognition.onresult = function(e) {
                let transcript = '';
                for (let i = 0; i < e.results.length; i++) {
                    transcript += e.results[i][0].transcript;
                }
                self.voiceResult = transcript;
                clearTimeout(self.silenceTimer);
                self.silenceTimer = setTimeout(() => {
                    self.recognition?.stop();
                }, 1200);
                if (e.results[e.results.length - 1].isFinal) {
                    self.parseText(transcript);
                }
            };
            this.recognition.onerror = function() { self.recording = false; };
            this.recognition.onend = function() { self.recording = false; };
            this.recognition.start();
            this.recording = true;
        },

        async parseText(text) {
            try {
                const res = await fetch('/transactions/parse-voice', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ text: text }),
                });
                const json = await res.json();
                if (json.data) {
                    if (json.data.title) document.getElementById('title').value = json.data.title;
                    if (json.data.amount) document.getElementById('amount').value = json.data.amount;
                    if (json.data.type) {
                        const radio = document.querySelector('input[name=type][value=' + json.data.type + ']');
                        if (radio) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                    if (json.data.category) {
                        const chip = document.querySelector('.cat-chip[data-category="' + CSS.escape(json.data.category) + '"]');
                        if (chip) chip.click();
                    }
                }
            } catch (err) {
                console.error('Voice parse error:', err);
            }
        }
    };
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const dropZone = document.getElementById('dropZone');
    const placeholder = document.getElementById('uploadPlaceholder');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeBtn = document.getElementById('removeFileBtn');
    const btnGallery = document.getElementById('btnGallery');
    const btnCamera = document.getElementById('btnCamera');

    @if(isset($transaction) && $transaction->image)
        const existingImage = "{{ asset('storage/' . $transaction->image) }}";
        const existingName = "{{ basename($transaction->image) }}";
        imagePreview.src = existingImage;
        fileName.textContent = existingName;
        fileSize.textContent = 'Foto tersimpan';
        placeholder.classList.add('hidden');
        dropZone.classList.add('hidden');
        previewContainer.classList.remove('hidden');
    @endif

    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            placeholder.classList.add('hidden');
            dropZone.classList.add('hidden');
            previewContainer.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    function resetUpload() {
        fileInput.value = '';
        @if(isset($transaction) && $transaction->image)
            imagePreview.src = "{{ asset('storage/' . $transaction->image) }}";
            fileName.textContent = "{{ basename($transaction->image) }}";
            fileSize.textContent = 'Foto tersimpan';
            placeholder.classList.add('hidden');
            dropZone.classList.add('hidden');
            previewContainer.classList.remove('hidden');
        @else
            imagePreview.src = '#';
            fileName.textContent = '';
            fileSize.textContent = '';
            placeholder.classList.remove('hidden');
            dropZone.classList.remove('hidden');
            previewContainer.classList.add('hidden');
        @endif
    }

    fileInput.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            showPreview(file);
        }
    });

    function setActiveBtn(el) {
        document.querySelectorAll('.btn-upload').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
    }

    btnGallery.addEventListener('click', function(e) {
        e.preventDefault();
        fileInput.removeAttribute('capture');
        fileInput.click();
        setActiveBtn(this);
    });

    btnCamera.addEventListener('click', function(e) {
        e.preventDefault();
        fileInput.setAttribute('capture', 'environment');
        fileInput.click();
        setActiveBtn(this);
    });

    removeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        resetUpload();
    });

    const typeRadios = document.querySelectorAll('input[name="type"]');
    const categoryInput = document.getElementById('category');
    const catIncome = document.getElementById('cat-income');
    const catExpense = document.getElementById('cat-expense');
    const allChips = document.querySelectorAll('.cat-chip');

    allChips.forEach(chip => {
        chip.addEventListener('click', function() {
            allChips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            if (categoryInput) categoryInput.value = this.dataset.category;
        });
    });

    function syncCategoryGroups() {
        const isIncome = document.querySelector('input[name="type"]:checked')?.value === 'income';
        if (catIncome) catIncome.style.display = isIncome ? '' : 'none';
        if (catExpense) catExpense.style.display = isIncome ? 'none' : '';
        const valid = Array.from(allChips).some(c =>
            c.dataset.category === (categoryInput?.value || '') &&
            c.closest(isIncome ? '#cat-income' : '#cat-expense')
        );
        if (!valid && categoryInput?.value) {
            categoryInput.value = '';
            allChips.forEach(c => c.classList.remove('active'));
        }
    }
    typeRadios.forEach(r => r.addEventListener('change', syncCategoryGroups));
    syncCategoryGroups();

    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-neutral-900', 'bg-neutral-100');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-neutral-900', 'bg-neutral-100');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-neutral-900', 'bg-neutral-100');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (file.type.startsWith('image/')) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                showPreview(file);
            }
        }
    });
});
</script>
