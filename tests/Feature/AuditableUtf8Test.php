<?php

use App\Models\AuditLog;
use App\Models\Product;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('logs an audit entry even when the model has malformed UTF-8 in a text field', function () {
    // A lone 0xE9 byte is valid Latin-1 ("é") but invalid UTF-8. Before the
    // Auditable fix, this create() would throw JsonEncodingException from
    // deep inside the `created` model event (old_values/new_values are
    // JSON-cast columns), taking the whole save down with it.
    $product = Product::factory()->create(['name' => "Caf\xE9 Chairs"]);

    expect($product->exists)->toBeTrue();

    $log = AuditLog::where('auditable_type', Product::class)
        ->where('auditable_id', $product->id)
        ->where('action', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect(mb_check_encoding($log->auditable_label, 'UTF-8'))->toBeTrue();
    expect(json_encode($log->new_values))->not->toBeFalse();
});

it('logs an update audit entry when the changed value has malformed UTF-8', function () {
    $product = Product::factory()->create(['name' => 'Plain Chairs']);

    $product->update(['name' => "Caf\xE9 Chairs Deluxe"]);

    $log = AuditLog::where('auditable_type', Product::class)
        ->where('auditable_id', $product->id)
        ->where('action', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect(json_encode($log->new_values))->not->toBeFalse();
});
