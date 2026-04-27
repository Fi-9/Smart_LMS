<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Member::query()->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $members = $query->withCount([
            'borrowings',
            'borrowings as active_borrowings_count' => function ($q) {
                $q->whereIn('status', ['borrowed', 'late']);
            },
        ])->paginate(20)->withQueryString();

        $stats = [
            'total' => Member::count(),
            'active' => Member::where('status', 'active')->count(),
            'siswa' => Member::where('type', 'siswa')->count(),
            'guru' => Member::where('type', 'guru')->count(),
        ];

        return view('members.index', [
            'members' => $members,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'type' => $request->input('type', ''),
            ],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:members,nis'],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', Rule::in(['siswa', 'guru', 'staff'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        Member::create($validated);

        return redirect()->route('members.index')
            ->with('success', "Anggota '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('members', 'nis')->ignore($member->id)],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', Rule::in(['siswa', 'guru', 'staff'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $member->update($validated);

        return redirect()->route('members.index')
            ->with('success', "Data anggota '{$member->name}' berhasil diperbarui.");
    }

    public function destroy(Member $member): RedirectResponse
    {
        $activeBorrowings = $member->activeBorrowings()->count();
        if ($activeBorrowings > 0) {
            return redirect()->route('members.index')
                ->with('error', "Anggota '{$member->name}' masih memiliki {$activeBorrowings} peminjaman aktif. Kembalikan dulu buku sebelum menghapus.");
        }

        $name = $member->name;
        $member->delete();

        return redirect()->route('members.index')
            ->with('success', "Anggota '{$name}' berhasil dihapus.");
    }

    /**
     * API endpoint for autocomplete in Borrowing form.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $members = Member::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('nis', 'like', "%{$query}%");
            })
            ->select('id', 'nis', 'name', 'class', 'type')
            ->limit(10)
            ->get()
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'nis' => $m->nis,
                'name' => $m->name,
                'class' => $m->class,
                'type' => $m->type,
                'label' => $m->display_label,
            ]);

        return response()->json($members);
    }

    /**
     * Show member profile with borrowing history.
     */
    public function show(Member $member): View
    {
        $member->loadCount([
            'borrowings',
            'borrowings as active_borrowings_count' => fn ($q) => $q->whereIn('status', ['borrowed', 'late']),
            'borrowings as late_borrowings_count' => fn ($q) => $q->where('status', 'late'),
        ]);

        $borrowingHistory = $member->borrowings()
            ->with('book')
            ->orderByDesc('borrowed_at')
            ->paginate(15);

        return view('members.show', [
            'member' => $member,
            'borrowingHistory' => $borrowingHistory,
        ]);
    }
}
