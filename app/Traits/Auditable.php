<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Support\Utf8;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::logAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $oldValues = $model->getOriginal();
            $newValues = $model->getChanges();

            $meaningfulChanges = collect($newValues)->except(['updated_at'])->toArray();
            if (empty($meaningfulChanges)) {
                return;
            }

            $relevantOldValues = collect($oldValues)
                ->only(array_keys($meaningfulChanges))
                ->toArray();

            static::logAudit($model, 'updated', $relevantOldValues, $meaningfulChanges);
        });

        static::deleted(function (Model $model) {
            static::logAudit($model, 'deleted', $model->getOriginal(), []);
        });
    }

    protected static function logAudit(Model $model, string $action, array $oldValues, array $newValues): void
    {
        $excludedFields = static::getAuditExcludedFields();
        $oldValues = collect($oldValues)->except($excludedFields)->toArray();
        $newValues = collect($newValues)->except($excludedFields)->toArray();

        // A field can carry invalid UTF-8 bytes (old imports, pasted-in text)
        // without Eloquent ever complaining — but the old_values/new_values
        // columns below are JSON-cast, and json_encode() throws on invalid
        // UTF-8. Sanitize first so a pre-existing bad byte in, say, a
        // product name can't block every future create/update/delete of
        // that record with a 500.
        $oldValues = Utf8::cleanArray($oldValues);
        $newValues = Utf8::cleanArray($newValues);

        $user = auth()->user();

        AuditLog::create([
            'user_id'         => $user?->id,
            'user_name'       => $user?->name,
            'action'          => $action,
            'auditable_type'  => $model->getMorphClass(),
            'auditable_id'    => $model->getKey(),
            'auditable_label' => static::getAuditLabel($model),
            'old_values'      => !empty($oldValues) ? $oldValues : null,
            'new_values'      => !empty($newValues) ? $newValues : null,
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
        ]);
    }

    protected static function getAuditLabel(Model $model): string
    {
        $className = class_basename($model);

        if ($model->getAttribute('name')) {
            return "{$className}: ".Utf8::clean($model->getAttribute('name'));
        }
        if ($model->getAttribute('reference_number')) {
            return "{$className} ".Utf8::clean($model->getAttribute('reference_number'));
        }
        if ($model->getAttribute('quotation_number')) {
            return "{$className} ".Utf8::clean($model->getAttribute('quotation_number'));
        }
        if ($model->getAttribute('plate_number')) {
            return "{$className} ".Utf8::clean($model->getAttribute('plate_number'));
        }

        return "{$className} #{$model->getKey()}";
    }

    protected static function getAuditExcludedFields(): array
    {
        return ['password', 'remember_token'];
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
