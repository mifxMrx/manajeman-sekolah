@extends('layouts.admin')

@section('header', 'Pengaturan Aplikasi')

@section('content')
<div class="space-y-8 animate-fade-in-up">
    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden max-w-4xl">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6 text-white">
            <h2 class="text-2xl font-bold">Identitas Sekolah</h2>
            <p class="text-blue-100 mt-1">Ubah nama dan logo yang ditampilkan di aplikasi Anda.</p>
        </div>
        
        <div class="p-8">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" x-data="imagePreview()">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left: Form Fields -->
                    <div class="space-y-6">
                        <div>
                            <label for="app_name" class="block text-sm font-semibold text-gray-700 mb-2">Nama Sekolah / Institusi</label>
                            <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $appName) }}" required
                                class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm py-2.5 px-4 text-gray-700 font-medium bg-gray-50/50">
                            @error('app_name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="app_logo" class="block text-sm font-semibold text-gray-700 mb-2">Ganti Logo Aplikasi</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-50 hover:border-indigo-400 transition-colors cursor-pointer relative" @click="$refs.fileInput.click()">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <span class="relative cursor-pointer bg-transparent rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Unggah file logo</span>
                                            <input type="file" name="app_logo" id="app_logo" accept="image/*" class="sr-only" x-ref="fileInput" @change="previewImage($event)">
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, SVG maksimal 2MB</p>
                                </div>
                            </div>
                            @error('app_logo')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right: Preview -->
                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 flex flex-col items-center justify-center">
                        <p class="text-sm font-semibold text-gray-500 mb-6 uppercase tracking-wider">Pratinjau Sidebar</p>
                        
                        <div class="bg-gradient-to-b from-blue-700 to-blue-500 w-64 rounded-xl shadow-lg overflow-hidden border border-blue-400">
                            <div class="h-16 flex items-center px-6 border-b border-white/20 bg-blue-800/20">
                                <div class="flex items-center space-x-3 w-full">
                                    <img :src="imageUrl || '{{ asset('images/logo.png?v=' . time()) }}'" alt="Logo" class="w-8 h-8 object-contain rounded-md shadow-sm bg-white p-0.5">
                                    <span class="text-lg font-bold text-white tracking-wider drop-shadow-md truncate" x-text="document.getElementById('app_name').value || 'SMP IDM'" x-effect="$el.innerText = document.getElementById('app_name').value || 'SMP IDM'"></span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3 opacity-50">
                                <div class="h-4 bg-white/20 rounded w-3/4"></div>
                                <div class="h-4 bg-white/20 rounded w-full"></div>
                                <div class="h-4 bg-white/20 rounded w-5/6"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all hover:shadow-lg hover:-translate-y-0.5 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function imagePreview() {
        return {
            imageUrl: '',
            previewImage(event) {
                const file = event.target.files[0];
                if (file) {
                    this.imageUrl = URL.createObjectURL(file);
                }
            }
        }
    }
    
    // Live update preview text
    document.getElementById('app_name').addEventListener('input', function(e) {
        // Alpine x-effect handles this, but just in case
        document.querySelector('[x-text]').innerText = e.target.value || 'SMP IDM';
    });
</script>
@endsection
