<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\SyncDjenBarRegistration;
use App\Jobs\SyncFolderDataJud;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Folder;
use App\Models\IntegrationSyncRun;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\InvoiceReminderRule;
use App\Models\Organization;
use App\Services\Folders\SyncFolderWithDataJud;
use App\Services\Publications\ImportDjenPublications;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BackgroundOrganizationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_datajud_jobs_switch_organization_and_restore_the_caller_after_success_and_failure(): void
    {
        $context = app(CurrentOrganization::class);
        $caller = Organization::factory()->create();
        $target = Organization::factory()->create();
        $folder = Folder::factory()->for($target)->create(['datajud_monitoring_enabled' => true]);
        $context->set($caller);
        $service = Mockery::mock(SyncFolderWithDataJud::class);
        $service->shouldReceive('execute')->twice()->andReturnUsing(function ($received) use ($context, $target, $folder) {
            $this->assertSame($target->id, $context->id());
            $this->assertSame([$folder->id], Folder::pluck('id')->all());
            $this->assertSame($folder->id, $received->id);
            static $calls = 0;
            if (++$calls === 2) {
                throw new RuntimeException('provider unavailable');
            }

            return ['movements_seen' => 0, 'movements_imported' => 0];
        });
        $job = new SyncFolderDataJud($folder->id, '2026-09-16');
        $job->handle($service);
        $this->assertSame($caller->id, $context->id());
        try {
            $job->handle($service);
            $this->fail('Provider exception must propagate for retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('provider unavailable', $exception->getMessage());
        }
        $this->assertSame($caller->id, $context->id());
        $this->assertDatabaseHas('integration_sync_runs', ['organization_id' => $target->id, 'status' => 'failed']);
    }

    public function test_datajud_skips_inactive_organization_and_disabled_monitoring(): void
    {
        $organization = Organization::factory()->inactive()->create();
        $folder = Folder::factory()->for($organization)->create(['datajud_monitoring_enabled' => true]);
        $service = Mockery::mock(SyncFolderWithDataJud::class);
        $service->shouldNotReceive('execute');
        (new SyncFolderDataJud($folder->id, '2026-09-16'))->handle($service);
        $organization->update(['status' => 'active']);
        $folder->update(['datajud_monitoring_enabled' => false]);
        (new SyncFolderDataJud($folder->id, '2026-09-16'))->handle($service);
        $this->assertDatabaseCount('integration_sync_runs', 0);
        $this->assertFalse(app(CurrentOrganization::class)->has());
    }

    public function test_djen_job_restores_context_on_success_and_failure_and_skips_inactive_organization(): void
    {
        $context = app(CurrentOrganization::class);
        $caller = Organization::factory()->create();
        $target = Organization::factory()->create();
        $registration = $target->monitoredBarRegistrations()->create(['lawyer_name' => 'Teste', 'bar_number' => '12345', 'state' => 'RS']);
        $context->set($caller);
        $service = Mockery::mock(ImportDjenPublications::class);
        $service->shouldReceive('handle')->twice()->andReturnUsing(function () use ($context, $target) {
            $this->assertSame($target->id, $context->id());
            static $calls = 0;
            if (++$calls === 2) {
                throw new RuntimeException('DJEN unavailable');
            }

            return new IntegrationSyncRun;
        });
        $job = new SyncDjenBarRegistration($registration->id, '2026-09-15', '2026-09-16');
        $job->handle($service);
        $this->assertSame($caller->id, $context->id());
        try {
            $job->handle($service);
            $this->fail('Provider exception must propagate.');
        } catch (RuntimeException) {
            $this->assertSame($caller->id, $context->id());
        }
        $target->update(['status' => 'inactive']);
        $job->handle($service);
        $this->assertSame($caller->id, $context->id());
    }

    public function test_generation_and_delivery_keep_invoices_in_their_organization_and_restore_caller(): void
    {
        Mail::fake();
        $first = $this->billingFixture();
        $second = $this->billingFixture();
        $inactive = $this->billingFixture();
        $inactive['organization']->update(['status' => 'inactive']);
        $context = app(CurrentOrganization::class);
        $caller = Organization::factory()->create();
        $context->set($caller);

        $this->artisan('finance:generate-reminders')->assertSuccessful();
        $this->artisan('finance:generate-reminders')->assertSuccessful();
        $this->assertSame($caller->id, $context->id());
        $this->assertDatabaseCount('invoice_reminders', 2);
        foreach ([$first, $second] as $fixture) {
            $this->assertDatabaseHas('invoice_reminders', [
                'organization_id' => $fixture['organization']->id,
                'invoice_id' => $fixture['invoice']->id,
                'recipient' => $fixture['client']->email,
            ]);
        }
        $this->artisan('finance:send-reminders')->assertSuccessful();
        $this->assertSame($caller->id, $context->id());
        Mail::assertSent(InvoiceReminderMail::class, 2);
        foreach ([$first, $second] as $fixture) {
            Mail::assertSent(InvoiceReminderMail::class, fn ($mail) => $mail->invoice->id === $fixture['invoice']->id && $mail->hasTo($fixture['client']->email));
        }
    }

    public function test_generation_restores_context_if_invoice_processing_throws(): void
    {
        $fixture = $this->billingFixture();
        $context = app(CurrentOrganization::class);
        $caller = Organization::factory()->create();
        $context->set($caller);
        $event = 'eloquent.retrieved: '.Invoice::class;
        Event::listen($event, function () use ($context, $fixture) {
            $this->assertSame($fixture['organization']->id, $context->id());
            throw new RuntimeException('simulated query processing failure');
        });
        try {
            Artisan::call('finance:generate-reminders');
            $this->fail('Processing exception must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated query processing failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame($caller->id, $context->id());
    }

    public function test_mail_failure_does_not_leak_context_into_the_next_reminder(): void
    {
        $first = $this->billingFixture();
        $second = $this->billingFixture();
        $this->artisan('finance:generate-reminders')->assertSuccessful();
        $context = app(CurrentOrganization::class);
        Mail::shouldReceive('to')->twice()->andReturnSelf();
        Mail::shouldReceive('send')->twice()->andReturnUsing(function ($mail) use ($context, $first, $second) {
            $this->assertSame($mail->invoice->organization_id, $context->id());
            if ($mail->invoice->id === $first['invoice']->id) {
                throw new RuntimeException('mail unavailable');
            }
            $this->assertSame($second['organization']->id, $context->id());
        });
        $this->artisan('finance:send-reminders')->assertSuccessful();
        $this->assertFalse($context->has());
        $this->assertDatabaseHas('invoice_reminders', ['invoice_id' => $first['invoice']->id, 'status' => 'failed']);
        $this->assertDatabaseHas('invoice_reminders', ['invoice_id' => $second['invoice']->id, 'status' => 'sent']);
    }

    public function test_delivery_rejects_cross_organization_invoice_and_skips_inactive_organization(): void
    {
        Mail::fake();
        $first = $this->billingFixture();
        $second = $this->billingFixture();
        $this->artisan('finance:generate-reminders')->assertSuccessful();
        $firstReminder = InvoiceReminder::where('invoice_id', $first['invoice']->id)->firstOrFail();
        // Corrupt an existing relation to ensure delivery also checks ownership.
        $firstReminder->update(['invoice_id' => $second['invoice']->id]);
        $second['organization']->update(['status' => 'inactive']);
        $this->artisan('finance:send-reminders')->assertSuccessful();
        Mail::assertNothingSent();
        $this->assertSame('failed', $firstReminder->fresh()->status);
        $this->assertDatabaseHas('invoice_reminders', ['organization_id' => $second['organization']->id, 'status' => 'scheduled']);
        $this->assertFalse(app(CurrentOrganization::class)->has());
    }

    private function billingFixture(): array
    {
        $organization = Organization::factory()->create();
        $client = Client::factory()->for($organization)->create(['email' => 'client'.$organization->id.'@example.test']);
        $invoice = Invoice::create([
            'organization_id' => $organization->id, 'client_id' => $client->id,
            'number' => 'TEST-'.$organization->id, 'charge_identifier' => 'TEST-'.$organization->id,
            'due_on' => today()->subDays(3), 'status' => 'open',
            'subtotal_cents' => 10000, 'total_cents' => 10000, 'balance_cents' => 10000,
        ]);
        InvoiceReminderRule::create([
            'organization_id' => $organization->id, 'name' => 'Regra de teste',
            'days_after_due' => 1, 'subject' => 'Aviso {cobranca}',
            'message' => 'Olá, {cliente}. Vencimento: {vencimento}.', 'active' => true,
        ]);

        return compact('organization', 'client', 'invoice');
    }
}
