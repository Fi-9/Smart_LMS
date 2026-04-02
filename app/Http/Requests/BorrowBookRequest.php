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
            'borrower_name' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'borrower_name.required' => 'Nama peminjam wajib diisi.',
            'due_date.required' => 'Tanggal pengembalian wajib diisi.',
            'due_date.after' => 'Tanggal pengembalian harus setelah hari ini.',
        ];
    }
}
