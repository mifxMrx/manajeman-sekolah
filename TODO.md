- [ ] Buat form/modal tambah jadwal di `resources/views/kurikulum/jadwal.blade.php` (field: day, start_time, end_time, teacher_id, classroom_id)
- [ ] Tambahkan route POST simpan jadwal di `routes/web.php` (admin.kurikulum.jadwal.store)
- [ ] Tambahkan method `storeJadwal()` di `app/Http/Controllers/KurikulumController.php`
- [ ] Terapkan validasi input dan tolak duplikat sesuai aturan: kombinasi `day + start_time + end_time + teacher_id` harus unik
- [ ] Pastikan relasi `teacher` dan `classroom` terset dan tampilan tabel tetap berjalan
- [x] Lakukan testing manual: tambah jadwal sukses, tambah duplikat ditolak, reload halaman menampilkan jadwal baru

- [ ] Buat form/modal tambah jadwal di `resources/views/kurikulum/jadwal.blade.php` (field: day, start_time, end_time, teacher_id, classroom_id)
- [ ] Tambahkan route POST simpan jadwal di `routes/web.php` (admin.kurikulum.jadwal.store)
- [ ] Tambahkan method `storeJadwal()` di `app/Http/Controllers/KurikulumController.php`
- [ ] Terapkan validasi input dan tolak duplikat sesuai aturan: kombinasi `day + start_time + end_time + teacher_id` harus unik
- [ ] Pastikan relasi `teacher` dan `classroom` terset dan tampilan tabel tetap berjalan
- [x] Lakukan testing manual: tambah jadwal sukses, tambah duplikat ditolak, reload halaman menampilkan jadwal baru

