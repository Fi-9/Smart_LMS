<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 30)->unique()->comment('Nomor Induk Siswa / NIP guru');
            $table->string('name');
            $table->string('class', 50)->nullable()->comment('Kelas atau jabatan (XII RPL, Guru, dll)');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable()->comment('Path ke foto profil di storage');
            $table->enum('type', ['siswa', 'guru', 'staff'])->default('siswa');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('class');
            $table->index('status');
            $table->index('type');
        });

        // Add member_id FK to borrowings table (nullable for backward compat)
        Schema::table('borrowings', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('book_id')->constrained('members')->nullOnDelete();
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropIndex(['member_id']);
            $table->dropColumn('member_id');
        });

        Schema::dropIfExists('members');
    }
};
