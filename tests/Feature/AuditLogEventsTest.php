<?php

use App\Enums\CashRegisterStatus;
use App\Models\AuditLog;
use App\Models\CashRegisterSession;
use App\Models\Sale;
use App\Models\User;
use App\Models\VoidRequest;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

function aleSession(User $user): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id' => $user->id,
        'opening_amount' => 1000,
        'opened_at' => now(),
        'status' => CashRegisterStatus::Open,
    ]);
}

it('logs a failed login without the password', function () {
    $user = User::factory()->create(['password' => bcrypt('right-password')]);

    Auth::attempt(['email' => $user->email, 'password' => 'wrong-password']);
    Auth::attempt(['email' => 'nobody@example.com', 'password' => 'whatever']);

    $logs = AuditLog::where('action', 'login_failed')->orderBy('id')->get();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]->user_id)->toBe($user->id)
        ->and($logs[0]->new_values)->toBe(['attempted_login' => $user->email, 'reason' => 'wrong password'])
        ->and($logs[1]->user_id)->toBeNull()
        ->and($logs[1]->new_values['reason'])->toBe('unknown account')
        ->and(json_encode($logs->toArray()))->not->toContain('wrong-password');
});

it('logs each login and logout exactly once', function () {
    $user = User::factory()->create(['password' => bcrypt('right-password')]);

    Auth::attempt(['email' => $user->email, 'password' => 'right-password']);
    Auth::logout();

    expect(AuditLog::where('action', 'login')->count())->toBe(1)
        ->and(AuditLog::where('action', 'logout')->count())->toBe(1);
});

it('logs receipt prints and flags reprints', function () {
    actingAs(User::factory()->create());
    $sale = Sale::factory()->paid()->create();

    get("/pos/print-receipt/{$sale->id}")->assertOk();
    get("/pos/print-receipt/{$sale->id}")->assertOk();

    $logs = AuditLog::where('action', 'printed_receipt')->where('auditable_id', $sale->id)->orderBy('id')->get();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]->new_values['reprint'])->toBeFalse()
        ->and($logs[1]->new_values['reprint'])->toBeTrue()
        ->and($logs[1]->new_values['print_number'])->toBe(2);
});

it('logs a cashier void request and its cancellation', function () {
    $cashier = User::factory()->create();
    actingAs($cashier);
    $session = aleSession($cashier);
    $sale = Sale::factory()->paid()->create(['cash_register_session_id' => $session->id]);

    postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'Double entry'])->assertOk();
    $voidRequest = VoidRequest::where('sale_id', $sale->id)->first();

    $requested = AuditLog::where('action', 'requested_void')->first();
    expect($requested->user_id)->toBe($cashier->id)
        ->and($requested->auditable_id)->toBe($voidRequest->id)
        ->and($requested->new_values['void_reason'])->toBe('Double entry');

    postJson("/pos/void-requests/{$voidRequest->id}/cancel")->assertOk();

    expect(AuditLog::where('action', 'cancelled_void_request')->where('auditable_id', $voidRequest->id)->exists())->toBeTrue();
});
