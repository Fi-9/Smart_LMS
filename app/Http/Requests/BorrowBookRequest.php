<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BorrowBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'borrower_name' => ['required_without:member_id', 'nullable', 'string', 'max:255'],
            'due_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.exists' => 'Anggota tidak ditemukan.',
            'borrower_name.required_without' => 'Nama peminjam wajib diisi jika bukan anggota terdaftar.',
            'due_date.required' => 'Tanggal pengembalian wajib diisi.',
            'due_date.after' => 'Tanggal pengembalian harus setelah hari ini.',
        ];
    }
}
