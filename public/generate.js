const XLSX = require('xlsx');

// Template Siswa
const wbSiswa = XLSX.utils.book_new();
const wsSiswa = XLSX.utils.aoa_to_sheet([
    ['NISN', 'Nama Lengkap', 'Kelas'],
    ['1234567890', 'Ahmad Budi', 'X IPA 1'],
    ['0987654321', 'Siti Aminah', 'X IPA 2']
]);
XLSX.utils.book_append_sheet(wbSiswa, wsSiswa, "Data Siswa");
XLSX.writeFile(wbSiswa, "templates/template_siswa.xlsx");

// Template Guru
const wbGuru = XLSX.utils.book_new();
const wsGuru = XLSX.utils.aoa_to_sheet([
    ['NIP', 'Nama Lengkap', 'Mata Pelajaran'],
    ['198001012010011001', 'Budi Santoso', 'Matematika'],
    ['198202022012022002', 'Siti Aminah', 'Bahasa Inggris']
]);
XLSX.utils.book_append_sheet(wbGuru, wsGuru, "Data Guru");
XLSX.writeFile(wbGuru, "templates/template_guru.xlsx");

console.log("Templates created successfully!");
