# REST API Autentikasi & Akun — Lokamitra v1

Dokumen ini merangkum endpoint `/api/v1` untuk autentikasi, akun, pendaftaran, dokumen, dan verifikasi pengajuan. Implementasi mengikuti `spesifikasi_restful_api_akun_autentikasi_v1.md` dengan beberapa penyesuaian yang dijelaskan di bawah.

## Konvensi umum

- Base URL: `/api/v1`. Semua route memakai middleware `web` agar sesi dan CSRF Laravel tetap aktif.
- Header yang dikirim klien:
    ```http
    Accept: application/json
    Content-Type: application/json
    ```
    Untuk unggah berkas gunakan `multipart/form-data` dan biarkan browser menentukan boundary.
- Sudah login: sesi memakai cookie yang sama dengan halaman Inertia (`auth:web`).
- Format galat validasi (HTTP 422):
    ```json
    {
        "message": "The given data was invalid.",
        "errors": { "email": ["Alasan kesalahan."] }
    }
    ```
- Format galat lain: `{ "message": "..." }` dengan status 401 (belum login), 403 (role tidak diizinkan/akun belum aktif), 404, 415, 419 (CSRF).

### CSRF

`GET /api/v1/csrf-token` mengembalikan token sesi mentah:

```json
{ "token": "..." }
```

Laravel menerima token mentah melalui header `X-CSRF-TOKEN`. Header `X-XSRF-TOKEN` juga didukung, tetapi nilainya harus token **terenkripsi** yang dibaca dari cookie `XSRF-TOKEN` (Laravel akan mendekripsinya). Mengirim token mentah dari endpoint lewat `X-XSRF-TOKEN` akan menghasilkan 419.

`resources/js/lib/api.ts` sudah menerapkan strategi tersebut: memakai `X-XSRF-TOKEN` dari cookie bila tersedia, `X-CSRF-TOKEN` dari meta/endpoint sebagai fallback, dan mencoba ulang sekali setelah memperbarui token jika menerima 419.

### Nilai yang dinormalisasi

| Konsep            | Nilai di request                                                                                                                                               | Nilai di response                                                                                                                          |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Role              | `eo`, `vendor`, `komunitas`, `pemda`, `akademisi`, `media`, `pengunjung`; alias `vendor_umkm`, `komunitas_lokal`, `pemerintah_daerah` diterima saat registrasi | Nilai database + `role_label`. Pada `GET /api/v1/admin/pengajuan` field `data[].role` memakai alias spec                                   |
| Status verifikasi | `pending`, `verified`, `rejected` juga diterima sebagai filter admin                                                                                           | `pending` (`menunggu`), `verified` (`aktif`), `rejected` (`ditolak`) lewat `status_verifikasi`; `status_akun` tetap memakai nilai database |

---

## Modul 1 — Login & Logout

### `POST /api/v1/login`

Guest. Memakai pipeline Fortify.

```json
{ "email": "eo@lokamitra.id", "password": "password123", "remember": true }
```

Respons `200`:

```json
{
    "message": "Login berhasil.",
    "data": {
        "id": 1,
        "name": "Budi Santoso",
        "email": "eo@lokamitra.id",
        "role": "eo",
        "status_verifikasi": "verified",
        "role_label": "Event Organizer (EO)",
        "status_akun": "aktif"
    },
    "redirect": "/dashboard"
}
```

Field `role_label`, `status_akun`, dan `redirect` adalah tambahan implementasi untuk frontend web; lima field pertama sesuai kontrak minimum spec. `redirect` ditentukan `App\Http\Responses\LoginResponse` berdasarkan role.

### `POST /api/v1/logout`

Auth. Respons `200`: `{ "message": "Logout berhasil." }`.

---

## Modul 2 — Registrasi

### `POST /api/v1/register`

Guest. `multipart/form-data` bila mengunggah dokumen. Memakai `RegisterRequest` penuh: `name`, `email`, `nomor_telepon`, `password`, `password_confirmation`, `role`, `persetujuan`, field profil sesuai role, dan `documents.<key>` berupa PDF/JPG/JPEG/PNG maksimal 10 MB.

Alias role spec diterima pada input: `vendor_umkm`, `komunitas_lokal`, `pemerintah_daerah`. Field ringkas `kategori`/`alamat` pada contoh spec tidak cukup untuk validasi aplikasi, sehingga dalam praktiknya tetap memakai field profil DB seperti `nama_komunitas`, `bidang_kegiatan`, `alamat_kantor`, dll.

Respons `201`:

```json
{
    "message": "Registrasi berhasil. Menunggu verifikasi Admin.",
    "data": {
        "id": 12,
        "name": "...",
        "email": "...",
        "role": "komunitas",
        "status_verifikasi": "pending",
        "...": "..."
    }
}
```

Pengunjung langsung aktif dengan message `Registrasi berhasil. Akun Anda sudah aktif, silakan login.`

Dokumen disimpan privat di disk `local` pada `dokumen-persyaratan/{akun_id}`; tidak ada URL `/storage` publik yang dikembalikan.

---

## Modul 3 — Lupa & Reset Kata Sandi

### `POST /api/v1/forgot-password`

Guest. `{ "email": "eo@lokamitra.id" }`

Respons `200`: `{ "message": "Tautan reset kata sandi telah dikirim ke email Anda." }`

### `POST /api/v1/reset-password`

Guest.

```json
{
    "token": "...",
    "email": "eo@lokamitra.id",
    "password": "PasswordBaru123!",
    "password_confirmation": "PasswordBaru123!"
}
```

Respons `200`: `{ "message": "Kata sandi berhasil diperbarui." }`

Halaman GET `/forgot-password` dan `/reset-password/{token}` tetap tersedia untuk merender formulir.

---

## Modul 4 — Akun & Profil

### `GET /api/v1/me`

Auth. Respons `200`: `{ "data": UserResource }`.

### `PATCH /api/v1/me`

Auth. `{ "name": "...", "email": "..." }` → `200` dengan `data` + `message: "Profil berhasil diperbarui."`. Email baru menandai `email_verified_at` null.

### `PUT /api/v1/me/password`

Auth, throttle `6,1`.

```json
{ "current_password": "...", "password": "...", "password_confirmation": "..." }
```

Respons `200`: `{ "message": "Password berhasil diperbarui." }`.

### `GET /api/v1/me/role-profile`

Auth + role mitra. Respons `200`: `data` berisi `role`, `role_label`, dan `fields`.

### `PUT /api/v1/me/role-profile`

Auth + role mitra. Respons `200`: `data` + `message: "Profil peran berhasil diperbarui."`. Status verifikasi tidak dapat diubah pemilik akun.

### `DELETE /api/v1/profile`

Auth. `{ "password": "kata-sandi-saat-ini" }`

Respons `200`: `{ "message": "Akun Anda berhasil dihapus secara permanen." }`. Sesi dihapus dan akun beserta data terkait dihapus.

---

## Modul 5 — Unggah Dokumen Persyaratan Mitra

### `POST /api/v1/role-profile/dokumen`

Auth + role mitra. `multipart/form-data`.

| Field           | Aturan                                 |
| --------------- | -------------------------------------- |
| `jenis_dokumen` | wajib, string, maksimal 255 karakter   |
| `file_dokumen`  | wajib, PDF/JPG/JPEG/PNG, maksimal 5 MB |

Respons `201`:

```json
{
    "message": "Dokumen persyaratan berhasil diunggah.",
    "data": {
        "id": 45,
        "jenis_dokumen": "SK_KOMUNITAS",
        "file_url": "/api/v1/role-profile/dokumen/45/download"
    }
}
```

`file_url` sengaja mengarah ke endpoint unduh terautentikasi, bukan `/storage` publik, karena dokumen bersifat privat.

### `GET /api/v1/role-profile/dokumen/{dokumen}/download`

Auth + role mitra. Hanya pemilik dokumen yang dapat mengunduh; akun lain mendapat 404. Memakai `PendaftaranService::simpanDokumen()` agar logika penyimpanan sama dengan saat registrasi.

---

## Modul 6 — Verifikasi Pengajuan oleh Admin

Semua endpoint berikut membutuhkan auth + role `admin`.

### `GET /api/v1/admin/pengajuan?status=pending`

Menerima `status`: `pending`, `verified`, `rejected`, `semua` (nilai database `menunggu`, `aktif`, `ditolak` juga tetap diterima), serta `role`, `q`, `page`.

Respons `200` memuat field spec `data` dan field tambahan untuk UI existing:

```json
{
    "data": [
        {
            "id": 12,
            "user_name": "Komunitas Kreatif Banyumas",
            "role": "komunitas_lokal",
            "status_verifikasi": "pending",
            "created_at": "2026-11-17T06:00:00Z"
        }
    ],
    "pengajuan": { "data": ["..."], "links": ["..."], "total": 12 },
    "ringkasan": { "menunggu": 5, "aktif": 6, "ditolak": 1 },
    "filter": {
        "status": "menunggu",
        "status_api": "pending",
        "role": "semua",
        "q": ""
    },
    "roles": [{ "value": "eo", "label": "Event Organizer (EO)" }]
}
```

### `PATCH /api/v1/admin/pengajuan/{id}`

```json
{
    "status": "verified",
    "catatan": "Dokumen legalitas lengkap dan telah diverifikasi Admin."
}
```

`status`: `verified` atau `rejected`. `catatan` wajib minimal 5 karakter jika `rejected`, opsional jika `verified`.

Respons `200`:

```json
{
    "message": "Status verifikasi akun mitra berhasil diperbarui.",
    "data": { "id": 12, "status_verifikasi": "verified" }
}
```

Logika bisnis tetap memanggil `VerifikasiPendaftaranService::setujuiAkun()` / `tolakAkun()`. Jika syarat persetujuan belum terpenuhi: 422 dengan `errors.verifikasi`.

### Endpoint tambahan yang tetap dipertahankan

Endpoint ini dipakai UI detail dan tidak dihapus agar tidak merusak klien existing:

- `POST /api/v1/admin/pengajuan/{id}/approve`
- `POST /api/v1/admin/pengajuan/{id}/reject`
- `POST /api/v1/admin/pengajuan/{id}/documents/{dokumen}/review`
- `GET /api/v1/admin/pengajuan/{id}/documents/{dokumen}/preview`
- `GET /api/v1/admin/pengajuan/{id}/documents/{dokumen}/download`

`preview_url`, `download_url`, dan `file_url` pada detail pengajuan mengarah ke endpoint privat terautentikasi, bukan file publik.

---

## Pengecualian spesifikasi

- **2FA** (`/api/v1/two-factor/*`) dan **konfirmasi password** (`/api/v1/confirm-password`) sengaja tidak diimplementasikan sesuai keputusan sebelumnya. Halaman/route web lama untuk keduanya juga tidak dipakai.
- Respons login, register, dan daftar admin memiliki field tambahan di luar contoh spec untuk kebutuhan frontend existing.
- `file_url` memakai endpoint unduh privat, bukan contoh `/storage/...`.
- Register tetap memakai `RegisterRequest` penuh dan field profil DB, bukan hanya `kategori`/`alamat`.

## Catatan implementasi

- Endpoint transaksi lama di `routes/web.php` (`POST /login`, `/logout`, `/register`, `/forgot-password`, `/reset-password`, `PATCH /settings/profile`, `DELETE /settings/profile`, `PUT /settings/password`, `PUT /profil-peran`, dan POST verifikasi admin) sudah dihapus. Route GET halaman tetap ada agar Inertia merender UI.
- Logika bisnis tidak digandakan: Fortify pipeline/action, `RegisterRequest`, `ProfileDeleteRequest`, `VerifikasiPendaftaranService`, `RoleProfileService`, `PendaftaranService`, dan `PengajuanService` dipakai bersama.
- Form request baru: `DokumenUploadRequest` dan `PengajuanVerifikasiRequest`.
