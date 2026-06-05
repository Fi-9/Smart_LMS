<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BookInbox;
use App\Models\ScanJob;
use App\Models\ScanPipelineLog;
use App\Models\ScanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ObservabilityDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test admin role has access, and staff is forbidden from accessing dashboard.
     */
    public function test_access_gate_restrictions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $staff = User::factory()->create(['role' => UserRole::STAFF->value]);

        // Unauthenticated guest is redirected
        $this->get(route('admin.observability.index'))->assertRedirect(route('login'));
        $this->get(route('admin.observability.stats'))->assertRedirect(route('login'));
        $this->get(route('admin.observability.providers'))->assertRedirect(route('login'));

        // Staff is forbidden (403)
        $this->actingAs($staff)->get(route('admin.observability.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.observability.stats'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.observability.providers'))->assertForbidden();

        // Admin is allowed (200)
        $this->actingAs($admin)->get(route('admin.observability.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.observability.stats'))->assertOk();
        $this->actingAs($admin)->get(route('admin.observability.providers'))->assertOk();
    }

    /**
     * Test calculation and aggregation of observability metrics.
     */
    public function test_metrics_calculation_accuracy(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $session = ScanSession::create([
            'user_id' => $admin->id,
            'operator_name' => 'Test Admin',
            'started_at' => now(),
        ]);

        // Create completed jobs
        ScanJob::create([
            'scan_session_id' => $session->id,
            'front_cover_path' => 'covers/front.jpg',
            'status' => 'completed',
            'current_stage' => 'completed',
            'stage_status' => 'completed',
            'pipeline_metrics' => [
                'identification' => 1500,
                'lookup' => 2000,
                'enrichment' => 500,
                'fallback' => 100,
                'inbox' => 400
            ],
            'confidence_score' => 95,
        ]);

        ScanJob::create([
            'scan_session_id' => $session->id,
            'front_cover_path' => 'covers/front2.jpg',
            'status' => 'completed',
            'current_stage' => 'completed',
            'stage_status' => 'completed',
            'pipeline_metrics' => [
                'identification' => 2500,
                'lookup' => 1000,
                'enrichment' => 500,
                'fallback' => 200,
                'inbox' => 600
            ],
            'confidence_score' => 85,
        ]);

        // Create failed jobs
        ScanJob::create([
            'scan_session_id' => $session->id,
            'front_cover_path' => 'covers/front3.jpg',
            'status' => 'failed',
            'current_stage' => 'lookup',
            'stage_status' => 'failed',
            'error_message' => 'Rate limit exceeded',
        ]);

        // Create book inbox records
        BookInbox::create([
            'scan_session_id' => $session->id,
            'scanned_by' => $admin->id,
            'title' => 'Test Book 1',
            'author' => 'Test Author',
            'source' => 'cache',
            'confidence_score' => 95,
            'status' => 'approved',
        ]);

        BookInbox::create([
            'scan_session_id' => $session->id,
            'scanned_by' => $admin->id,
            'title' => 'Test Book 2',
            'author' => 'Test Author',
            'source' => 'google_books',
            'confidence_score' => 85,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.observability.stats', ['range' => 'today']));
        $response->assertOk();

        // 2 completed, 1 failed = 3 total
        $response->assertJsonPath('total_scans', 3);
        
        // Success rate = (2 completed / 3 total) * 100 = 66.7%
        $response->assertJsonPath('success_rate', 66.7);

        // Latency calculations:
        // identification: (1500 + 2500)/2 = 2000ms
        // lookup: (2000 + 1000)/2 = 1500ms
        // total: 1500+2000+500+100+400 = 4500ms for job 1, 2500+1000+500+200+600 = 4800ms for job 2. Avg total = 4650ms.
        $response->assertJsonPath('avg_latency.identification', 2000);
        $response->assertJsonPath('avg_latency.lookup', 1500);
        $response->assertJsonPath('avg_latency.total', 4650);

        // Failure distribution: 1 failure at 'lookup' stage
        $response->assertJsonPath('failure_distribution.lookup', 1);
        $response->assertJsonPath('failure_distribution.identification', 0);

        // API failures: Rate limit should trigger +1
        $response->assertJsonPath('api_failures', 1);

        // Cache hit rate: 1 cache out of 2 completed in inbox = 50.0%
        $this->assertEquals(50.0, $response->json('cache_hit_rate'));

        // Average confidence score: (95 + 85) / 2 = 90.0
        $this->assertEquals(90.0, $response->json('avg_confidence'));
    }

    /**
     * Test provider health status page logic.
     */
    public function test_provider_health_status_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        // Log pipeline calls
        ScanPipelineLog::create([
            'scan_id' => '1',
            'provider' => 'Gemini',
            'duration_ms' => 1200,
            'status' => 'success',
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        ScanPipelineLog::create([
            'scan_id' => '2',
            'provider' => 'Gemini',
            'duration_ms' => 1400,
            'status' => 'failed',
            'error' => 'Rate limit',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        ScanPipelineLog::create([
            'scan_id' => '3',
            'provider' => 'Tavily',
            'duration_ms' => 800,
            'status' => 'success',
            'created_at' => Carbon::now()->subMinutes(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.observability.providers'));
        $response->assertOk();

        // Check if provider stats are rendered
        $response->assertSee('Gemini');
        $response->assertSee('Tavily');
        
        // Gemini: 1 success, 1 fail = 50% success rate
        $response->assertSee('50%');
        
        // Tavily: 1 success, 0 fail = 100% success rate
        $response->assertSee('100%');
    }

    /**
     * Test the observability retention pruning command.
     */
    public function test_pruning_data_retention(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $session = ScanSession::create([
            'user_id' => $admin->id,
            'operator_name' => 'Operator',
            'started_at' => now(),
        ]);

        // Old records (to be pruned)
        $oldJob = ScanJob::create([
            'scan_session_id' => $session->id,
            'front_cover_path' => 'old_front.jpg',
            'status' => 'completed',
        ]);
        DB::table('scan_jobs')->where('id', $oldJob->id)->update(['created_at' => Carbon::now()->subDays(95)]);

        $oldLog = ScanPipelineLog::create([
            'scan_id' => '99',
            'provider' => 'GoogleBooks',
            'duration_ms' => 1500,
            'status' => 'success',
        ]);
        DB::table('scan_pipeline_logs')->where('id', $oldLog->id)->update(['created_at' => Carbon::now()->subDays(35)]);

        // New records (to be kept)
        $newJob = ScanJob::create([
            'scan_session_id' => $session->id,
            'front_cover_path' => 'new_front.jpg',
            'status' => 'completed',
        ]);
        DB::table('scan_jobs')->where('id', $newJob->id)->update(['created_at' => Carbon::now()->subDays(5)]);

        $newLog = ScanPipelineLog::create([
            'scan_id' => '100',
            'provider' => 'GoogleBooks',
            'duration_ms' => 1500,
            'status' => 'success',
        ]);
        DB::table('scan_pipeline_logs')->where('id', $newLog->id)->update(['created_at' => Carbon::now()->subDays(5)]);

        // Book Inbox (MUST NEVER BE DELETED!)
        $oldInbox = BookInbox::create([
            'scan_session_id' => $session->id,
            'scanned_by' => $admin->id,
            'title' => 'Important Archive',
            'author' => 'Author',
            'source' => 'google_books',
            'confidence_score' => 90,
            'status' => 'approved',
        ]);
        DB::table('book_inbox')->where('id', $oldInbox->id)->update(['created_at' => Carbon::now()->subDays(100)]);

        // Execute prune command
        $exitCode = Artisan::call('library:prune-observability');
        $this->assertEquals(0, $exitCode);

        // Assert old records are deleted
        $this->assertDatabaseMissing('scan_jobs', ['id' => $oldJob->id]);
        $this->assertDatabaseMissing('scan_pipeline_logs', ['id' => $oldLog->id]);

        // Assert new records are kept
        $this->assertDatabaseHas('scan_jobs', ['id' => $newJob->id]);
        $this->assertDatabaseHas('scan_pipeline_logs', ['id' => $newLog->id]);

        // Assert book inbox is not touched
        $this->assertDatabaseHas('book_inbox', ['id' => $oldInbox->id]);
    }
}
