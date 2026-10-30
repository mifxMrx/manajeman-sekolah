@extends('layouts.admin')

@section('header', 'Jadwal Mengajar Guru')

@section('content')
<div class="space-y-6" 
    x-data="jadwalTemplate()"
    data-store-url="{{ route('admin.kurikulum.jadwal.template.store') }}"
    data-csrf="{{ csrf_token() }}">


    {{-- Arsitektur Template UI --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Manajemen Jadwal Kelas
                </h2>
                <p class="text-xs text-gray-500 mt-1">Pilih kelas untuk melihat dan mengatur jadwalnya. Klik "Auto-Generate Waktu" untuk membuat slot waktu kosong.</p>
            </div>
        </div>

        <div class="mb-5">
            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Pilih Kelas:</label>
            <div class="relative max-w-sm">
                <select x-model="selectedClassroom" @change="loadSchedule" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 font-bold shadow-sm appearance-none">
                    <option value="" disabled selected>-- Pilih Kelas --</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->grade }} - {{ $classroom->name }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end bg-gray-50/50 p-4 rounded-xl border border-gray-100">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wider">Bel Masuk Pertama</label>
                <div class="relative">
                    <input type="time" x-model="templateStartTime" class="w-full bg-white border border-gray-200 text-gray-800 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 shadow-sm font-bold">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wider">Durasi JP Standar (Min)</label>
                <input type="number" x-model="templateDuration" class="w-full bg-white border border-gray-200 text-gray-800 text-sm rounded-lg text-center focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 shadow-sm font-bold">
            </div>
            <div>
                <button @click="generateSlots" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2.5 px-4 rounded-lg shadow-md transition-colors flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Auto-Generate Waktu
                </button>
            </div>
        </div>
    </div>

    {{-- Timeline Sesi Harian UI --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-900">Timeline Sesi Harian</h2>
            <button @click="showHolidayModal = true" class="inline-flex items-center text-xs font-semibold border px-3 py-1.5 rounded-lg transition-colors text-red-600 border-red-200 bg-red-50 hover:bg-red-100">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                KELOLA LIBUR NASIONAL
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 mb-6">
            <template x-for="day in ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']" :key="day">
                <button @click="activeTab = day"
                        :class="activeTab === day 
                            ? 'bg-indigo-600 text-white shadow-md' 
                            : (day === 'Minggu' ? 'bg-red-50 text-red-500 border border-red-100' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50')"
                        class="px-5 py-2 rounded-xl text-sm font-medium transition-all duration-200 flex items-center justify-center">
                    <span x-text="day"></span>
                    <span x-show="day === 'Minggu'" class="ml-1 opacity-70">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </span>
                </button>
            </template>
        </div>

        {{-- Tab Content --}}
        <div class="border border-gray-100 bg-gray-50/50 rounded-2xl p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase" x-text="'SLOT HARI ' + activeTab"></h3>
                    <div class="flex space-x-2">
                        <div class="relative">
                            <select class="appearance-none bg-white border border-gray-200 text-gray-700 text-xs py-1.5 px-3 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium">
                                <option>-- COPY DR HARI --</option>
                                <option>Senin</option>
                                <option>Selasa</option>
                                <option>Rabu</option>
                                <option>Kamis</option>
                                <option>Jumat</option>
                                <option>Sabtu</option>
                                <option>Minggu</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <button class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded-lg flex items-center font-medium hover:bg-gray-700 transition shadow-sm">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            PASTE
                        </button>
                    </div>
                </div>

                {{-- Dynamic Slots --}}
                <div class="space-y-3 mb-3">
                    <template x-for="(slot, index) in dailySlots[activeTab]" :key="slot.id">
                        <div class="flex items-center gap-4 bg-white border border-gray-200 rounded-xl p-3 shadow-sm hover:border-indigo-300 transition-colors" :class="slot.isEmpty ? 'opacity-50 grayscale' : ''">
                            
                            {{-- Number indicator --}}
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm" x-text="slot.index"></div>
                            
                            {{-- Time inputs --}}
                            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <input type="time" x-model="slot.start" class="border-0 bg-transparent p-0 focus:ring-0 text-sm font-semibold w-auto min-w-[90px]">
                                <span class="text-gray-400">-</span>
                                <input type="time" x-model="slot.end" class="border-0 bg-transparent p-0 focus:ring-0 text-sm font-semibold w-auto min-w-[90px]">
                            </div>

                            {{-- Duration --}}
                            <div class="flex items-center gap-2 border-l border-gray-200 pl-4">
                                <span class="text-xs font-bold text-gray-500 uppercase">Durasi:</span>
                                <input type="number" x-model="slot.duration" class="w-14 text-center text-sm font-bold bg-gray-50 border border-gray-200 rounded px-1 py-0.5">
                                <span class="text-xs text-gray-500">Min</span>
                            </div>

                            {{-- Teacher / Subject Dropdown --}}
                            <div class="flex-grow border-l border-gray-200 pl-4 relative">
                                <select x-model="slot.teacher_id" :disabled="slot.isEmpty" class="w-full appearance-none bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm py-2 px-3 pr-8 font-medium">
                                    <option value="">Pembelajaran (Pilih Guru)</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>

                            {{-- Checkbox Kosongkan --}}
                            <label class="flex items-center gap-2 cursor-pointer border-l border-gray-200 pl-4">
                                <input type="checkbox" x-model="slot.isEmpty" class="form-checkbox h-4 w-4 text-gray-400 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-xs font-semibold text-gray-500">Kosongkan</span>
                            </label>

                            {{-- Delete Button --}}
                            <button @click="slotToDelete = index" class="text-gray-300 hover:text-red-500 transition-colors ml-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Empty State Slot --}}
                <div x-show="dailySlots[activeTab].length === 0" class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center bg-white mb-3">
                    <p class="text-xs font-bold text-gray-400 tracking-wider">SLOT WAKTU KOSONG. MULAI TAMBAHKAN SESI BARU ATAU AUTO-GENERATE.</p>
                </div>

                {{-- Add New Slot Button --}}
                <button @click="addSlot" class="w-full border-2 border-dashed border-indigo-200 bg-indigo-50/50 text-indigo-600 rounded-xl p-3 text-sm font-bold hover:bg-indigo-50 transition-colors flex items-center justify-center">
                    <span class="mr-1 text-lg leading-none">+</span> TAMBAH SLOT BARU
                </button>
        </div>
    </div>
    
    {{-- Save Global Template Button --}}
    <div class="flex justify-end mt-4">
        <button @click="saveTemplate" :disabled="isSaving" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center" :class="{ 'opacity-70 cursor-wait': isSaving }">
            <svg x-show="isSaving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Seluruh Jadwal'"></span>
        </button>
    </div>
    {{-- Modal Kelola Libur Nasional --}}
    <div x-show="showHolidayModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity" style="display: none;">
        <div @click.away="showHolidayModal = false" class="relative w-full max-w-2xl p-6 bg-white rounded-2xl shadow-xl border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Kelola Libur Nasional</h3>
                <button @click="showHolidayModal = false" class="text-gray-400 hover:text-gray-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="{{ route('admin.kurikulum.libur.store') }}" method="POST" class="mb-6 flex gap-2">
                @csrf
                <div class="w-1/3">
                    <input type="date" name="date" required class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 shadow-sm font-medium">
                </div>
                <div class="flex-grow">
                    <input type="text" name="description" placeholder="Keterangan libur..." required class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 shadow-sm font-medium">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded-lg shadow-md transition-colors">
                    Tambah
                </button>
            </form>

            <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $hol)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ \Carbon\Carbon::parse($hol->date)->translatedFormat('d F Y') }}</td>
                                <td class="px-4 py-3">{{ $hol->description }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('admin.kurikulum.libur.destroy', $hol->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Hapus hari libur ini?')" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">Belum ada hari libur yang ditambahkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="slotToDelete !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity" style="display: none;">
        <div @click.away="slotToDelete = null" class="relative w-full max-w-sm p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Slot Waktu?</h3>
            <p class="text-sm text-gray-500 mb-6">Slot ini akan dihapus dari jadwal hari ini. Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-center space-x-3">
                <button @click="slotToDelete = null" type="button" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:outline-none transition-colors w-full">Batal</button>
                <button @click="executeDelete" type="button" class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 border border-transparent rounded-xl hover:bg-red-700 focus:outline-none shadow-sm shadow-red-600/30 transition-colors w-full">Ya, Hapus!</button>
            </div>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-5 right-5 z-50 flex items-center p-4 mb-4 text-sm text-gray-500 bg-white rounded-lg shadow-xl border border-gray-100" 
         role="alert" style="display: none;">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg" 
             :class="toast.type === 'success' ? 'text-green-500 bg-green-100' : 'text-red-500 bg-red-100'">
            <svg x-show="toast.type === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
            <svg x-show="toast.type === 'error'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </div>
        <div class="ms-3 text-sm font-medium text-gray-700" x-text="toast.message"></div>
        <button @click="toast.show = false" type="button" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 ml-4">
            <span class="sr-only">Close</span>
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
        </button>
    </div>
</div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('jadwalTemplate', () => ({
        showEditModal: false,
        editData: {},
        activeTab: 'Senin',
        templateStartTime: '07:00',
        templateDuration: 40,
        showHolidayModal: false,
        toast: { show: false, message: '', type: 'success' },
        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },
        
        schedulesByClassroom: @json($schedulesByClassroom),
        selectedClassroom: '',
        dailySlots: {
            Senin: [], Selasa: [], Rabu: [], Kamis: [], Jumat: [], Sabtu: [], Minggu: []
        },

        loadSchedule() {
            if (this.selectedClassroom && this.schedulesByClassroom[this.selectedClassroom]) {
                this.dailySlots = JSON.parse(JSON.stringify(this.schedulesByClassroom[this.selectedClassroom]));
            } else {
                this.dailySlots = {
                    Senin: [], Selasa: [], Rabu: [], Kamis: [], Jumat: [], Sabtu: [], Minggu: []
                };
            }
        },

        generateSlots() {
            let currentStart = this.templateStartTime;
            let newSlots = [];
            for (let i = 1; i <= 8; i++) {
                let [hours, minutes] = currentStart.split(':').map(Number);
                let date = new Date(2000, 0, 1, hours, minutes);
                date.setMinutes(date.getMinutes() + parseInt(this.templateDuration));
                let endHours = String(date.getHours()).padStart(2, '0');
                let endMinutes = String(date.getMinutes()).padStart(2, '0');
                let endStr = `${endHours}:${endMinutes}`;
                newSlots.push({
                    id: Date.now() + i,
                    index: i,
                    start: currentStart,
                    end: endStr,
                    duration: parseInt(this.templateDuration),
                    teacher_id: '',
                    isEmpty: false
                });
                currentStart = endStr;
            }
            this.dailySlots[this.activeTab] = newSlots;
        },
        addSlot() {
            let day = this.activeTab;
            let lastSlot = this.dailySlots[day].length > 0 ? this.dailySlots[day][this.dailySlots[day].length - 1] : null;
            let start = lastSlot ? lastSlot.end : '07:00';
            let [hours, minutes] = start.split(':').map(Number);
            let date = new Date(2000, 0, 1, hours, minutes);
            date.setMinutes(date.getMinutes() + parseInt(this.templateDuration));
            let endHours = String(date.getHours()).padStart(2, '0');
            let endMinutes = String(date.getMinutes()).padStart(2, '0');
            this.dailySlots[day].push({
                id: Date.now(),
                index: this.dailySlots[day].length + 1,
                start: start,
                end: `${endHours}:${endMinutes}`,
                duration: parseInt(this.templateDuration),
                teacher_id: '',
                isEmpty: false
            });
        },
        slotToDelete: null,
        executeDelete() {
            if (this.slotToDelete !== null) {
                this.dailySlots[this.activeTab].splice(this.slotToDelete, 1);
                this.dailySlots[this.activeTab].forEach((slot, i) => { slot.index = i + 1; });
                this.slotToDelete = null;
                this.showToast('Slot berhasil dihapus.', 'success');
                // Auto-save jika sudah ada kelas terpilih
                if (this.selectedClassroom) {
                    setTimeout(() => this.saveTemplate(true), 500);
                }
            }
        },
        isSaving: false,
        saveTemplate(silent = false) {
            if (!this.selectedClassroom) {
                if (!silent) this.showToast('Silakan pilih kelas terlebih dahulu!', 'error');
                return;
            }
            let hasSlots = false;
            for (const day in this.dailySlots) {
                if (this.dailySlots[day].length > 0) hasSlots = true;
            }
            if (!hasSlots && !silent) {
                this.showToast('Belum ada slot waktu yang dibuat! Klik Auto-Generate Waktu terlebih dahulu.', 'error');
                return;
            }
            let payloadSlots = {};
            for (const day in this.dailySlots) {
                payloadSlots[day] = this.dailySlots[day];
            }
            this.isSaving = true;
            fetch(window._jadwalStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window._jadwalCsrf
                },
                body: JSON.stringify({
                    classrooms: [this.selectedClassroom],
                    slots: payloadSlots
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                this.isSaving = false;
                if (data.success) {
                    if (!silent) this.showToast(data.message, 'success');
                    // Perbarui master data di frontend
                    this.schedulesByClassroom[this.selectedClassroom] = JSON.parse(JSON.stringify(this.dailySlots));
                } else {
                    this.showToast('Gagal: ' + data.message, 'error');
                }
            })
            .catch(error => {
                this.isSaving = false;
                console.error('Save error:', error);
                this.showToast('Terjadi kesalahan: ' + error.message, 'error');
            });
        }
    }));
});

window._jadwalStoreUrl = "{{ route('admin.kurikulum.jadwal.template.store') }}";
window._jadwalCsrf = "{{ csrf_token() }}";
</script>
@endsection
