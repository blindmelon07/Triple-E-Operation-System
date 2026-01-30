<?php

namespace App\Traits;

use App\Models\AuditLog;
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
            return "{$className}: {$model->getAttribute('name')}";
        }
        if ($model->getAttribute('reference_number')) {
            return "{$className} {$model->getAttribute('reference_number')}";
        }
        if ($model->getAttribute('quotation_number')) {
            return "{$className} {$model->getAttribute('quotation_number')}";
        }
        if ($model->getAttribute('plate_number')) {
            return "{$className} {$model->getAttribute('plate_number')}";
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
