---
paths:
  - 'app/Notifications/**'
  - 'app/Mail/**'
---

# Notifikasi dan email

## Notifikasi dan email dikirim lewat antrean setelah commit
- Notifikasi dan Mailable yang memakai layanan luar (email, SMS, Slack) mengimplementasikan `ShouldQueue`, kecuali hasil pengirimannya harus diketahui saat itu juga.
- Yang dikirim dari dalam transaksi memakai `afterCommit()`, supaya tidak ada yang terkirim untuk data yang batal disimpan.
- Teks untuk pengguna memakai string terjemahan `__()`.
- Untuk penerima yang bukan user, gunakan on-demand notification (`Notification::route()`), bukan model palsu.
- Jangan menaruh secret atau kode sekali pakai di log.
- Uji isi pesan dan cara pengirimannya secara terpisah dengan `Notification::fake()` atau `Mail::fake()`.
