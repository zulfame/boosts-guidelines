---
paths:
  - 'app/Actions/**'
---

# Action

## Action untuk satu operasi bisnis
- Satu class untuk satu operasi, dinamai dengan kata kerja: `CreateInvoice`, `CancelSubscription`. Lokasinya `app/Actions/<Domain>/`, dengan satu method publik `handle()` dan argumen bertipe (model, DTO, atau array yang sudah divalidasi).
- Buat Action hanya jika memenuhi kriteria di `controllers.md`. Operasi sederhana tetap di controller.
- Action boleh memakai Service lewat constructor injection. Hindari `app()` atau `resolve()` di dalamnya.
- Action bawaan paket (misalnya `app/Actions/Fortify`) mengikuti kontrak dari paketnya.

## Action transaksional dan bebas urusan HTTP
- Bungkus beberapa operasi tulis dengan `DB::transaction()`.
- Job dan notifikasi yang bergantung pada data transaksi di-dispatch dengan `->afterCommit()`. Event yang di-dispatch di dalam transaksi mengimplementasikan `ShouldDispatchAfterCommit`.
- Jangan memakai `request()`, `session()`, `redirect()`, atau respons view di dalam Action, supaya Action bisa dipakai dari controller, komponen, job, command, dan test.
