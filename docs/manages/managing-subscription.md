# AI Agent Specification - Subscription Management

## Objective

AI Agent bertanggung jawab mengelola seluruh siklus hidup subscription customer, menjaga sinkronisasi data, mengontrol hak akses, serta memastikan proses billing berjalan sesuai aturan bisnis.

---

# Responsibilities

## Subscription Creation

### Trigger

- Customer berhasil melakukan checkout
- Admin membuat subscription
- Migrasi dari sistem lain

### Actions

- Membuat data subscription
- Menentukan plan
- Menentukan billing interval
- Menentukan renewal date
- Mengaktifkan akses customer
- Mencatat audit log

---

## Plan Management

### Supported Operations

- Upgrade Plan
- Downgrade Plan
- Change Billing Cycle
- Change Quantity
- Change Features

### Business Rules

- Plan baru harus valid
- Subscription harus masih aktif
- Terapkan strategi proration sesuai konfigurasi
- Perbarui invoice apabila diperlukan
- Simpan riwayat perubahan

---

## Subscription Cancellation

### Trigger

- Customer cancel
- Admin cancel
- Automatic cancellation

### Business Rules

Cancel tidak langsung menghapus akses.

Subscription memasuki:

```
Cancelled
```

Customer tetap memiliki akses hingga:

```
Current Billing Period End
```

---

## Resume Subscription

### Requirements

Hanya dapat dilakukan apabila:

- Status = Cancelled
- Belum melewati End Date

### Result

```
Cancelled

↓

Active
```

Billing schedule tetap dilanjutkan.

---

## Pause Subscription

### Supported Modes

#### Service Continues

- Billing dihentikan
- Customer tetap memiliki akses

#### Service Suspended

- Billing dihentikan
- Customer kehilangan akses

---

### Optional

Pause dapat memiliki:

- Resume Date

Jika tanggal tersebut tercapai:

```
Paused

↓

Active
```

---

## Billing Management

AI Agent harus dapat:

- menghitung renewal
- membuat invoice
- menjalankan pembayaran
- menghitung proration
- menghitung credit
- menghitung additional charge

---

## Payment Failure

Jika pembayaran gagal:

```
Active

↓

Past Due
```

Agent harus:

- menjadwalkan retry
- mengirim notifikasi
- meminta update payment method

Jika berhasil:

```
Past Due

↓

Active
```

Jika gagal seluruh retry:

```
Unpaid
```

---

## Payment Method Update

Customer dapat:

- mengganti kartu
- mengganti rekening
- mengganti metode pembayaran

Perubahan ini tidak mengubah status subscription.

---

## Billing Date Management

Agent dapat mengubah:

- Billing Anchor
- Renewal Date
- Next Billing Date

Setelah perubahan:

- Hitung ulang siklus billing
- Perbarui invoice berikutnya

---

## Access Management

Agent menentukan hak akses berdasarkan status subscription.

| Status | Access |
|---------|--------|
| Trial | ✅ |
| Active | ✅ |
| Paused (Service Continues) | ✅ |
| Paused (Service Suspended) | ❌ |
| Past Due | Configurable |
| Unpaid | ❌ |
| Cancelled (Grace Period) | ✅ |
| Expired | ❌ |

---

## Renewal Process

Pada setiap tanggal renewal:

1. Membuat invoice
2. Menagih customer
3. Jika berhasil:
    - Perpanjang subscription
    - Update periode
    - Jadwalkan renewal berikutnya
4. Jika gagal:
    - Ubah status menjadi Past Due

---

## Retry Process

Jika pembayaran gagal:

```
Retry #1

↓

Retry #2

↓

Retry #3

↓

Retry #N
```

Jumlah retry ditentukan konfigurasi sistem.

---

## Expiration Process

Subscription menjadi Expired apabila:

- Grace period selesai
- Tidak di-resume
- Semua retry pembayaran gagal (sesuai kebijakan)

Saat Expired:

- Cabut seluruh akses
- Hentikan renewal
- Subscription tidak dapat di-resume

---

## Notification Events

Agent mengirim notifikasi ketika:

- Trial Started
- Trial Ending
- Subscription Activated
- Subscription Renewed
- Plan Changed
- Payment Failed
- Payment Recovered
- Payment Method Updated
- Subscription Paused
- Subscription Resumed
- Subscription Cancelled
- Grace Period Ending
- Subscription Expired

---

## Event Synchronization

Agent harus memproses event dari payment provider seperti:

- Subscription Created
- Subscription Updated
- Subscription Renewed
- Subscription Cancelled
- Subscription Resumed
- Subscription Paused
- Subscription Expired
- Payment Succeeded
- Payment Failed
- Payment Refunded

Seluruh event harus disinkronkan dengan database internal.

---

## Audit Logging

Setiap perubahan harus dicatat.

Minimal informasi:

- Actor
- Action
- Previous Value
- New Value
- Timestamp
- Source
- Correlation ID

---

# Background Jobs

Agent perlu menjalankan job terjadwal untuk:

- Renewal Processing
- Payment Retry
- Expiration Check
- Grace Period Check
- Trial Expiration
- Auto Resume
- Notification Delivery
- Webhook Synchronization

---

# Configuration

Semua aturan berikut harus dapat dikonfigurasi:

- Trial Duration
- Grace Period
- Retry Count
- Retry Interval
- Billing Interval
- Proration Strategy
- Pause Behavior
- Access Policy
- Notification Policy

---

# AI Agent Checklist

- ✅ Create Subscription
- ✅ Activate Subscription
- ✅ Upgrade Plan
- ✅ Downgrade Plan
- ✅ Change Billing Cycle
- ✅ Change Quantity
- ✅ Apply Proration
- ✅ Cancel Subscription
- ✅ Resume Subscription
- ✅ Pause Subscription
- ✅ Unpause Subscription
- ✅ Process Renewals
- ✅ Retry Failed Payments
- ✅ Update Payment Method
- ✅ Change Billing Date
- ✅ Sync Webhooks
- ✅ Update Customer Access
- ✅ Send Notifications
- ✅ Record Audit Logs
