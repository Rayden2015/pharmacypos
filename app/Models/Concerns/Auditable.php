<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            if ($model instanceof AuditLog) {
                return;
            }

            Audit::record(
                static::class.'@created',
                null,
                $model->auditSnapshot(),
                static::class,
                (int) $model->getKey(),
                null,
                ['audit_channel' => 'model']
            );
        });

        static::updated(function (Model $model) {
            if ($model instanceof AuditLog) {
                return;
            }

            $changeKeys = array_keys($model->getChanges());
            $changeKeys = array_values(array_filter($changeKeys, static function ($key) {
                return $key !== 'updated_at';
            }));
            if ($changeKeys === []) {
                return;
            }

            $exclude = $model->auditExcludeKeys();
            $old = [];
            $new = [];
            foreach ($changeKeys as $key) {
                if (in_array($key, $exclude, true)) {
                    $old[$key] = '[redacted]';
                    $new[$key] = '[redacted]';

                    continue;
                }
                $old[$key] = Audit::sanitizeValue($model->getRawOriginal($key));
                $new[$key] = Audit::sanitizeValue($model->getAttribute($key));
            }

            Audit::record(
                static::class.'@updated',
                $old,
                $new,
                static::class,
                (int) $model->getKey(),
                null,
                ['audit_channel' => 'model']
            );
        });

        static::deleting(function (Model $model) {
            if ($model instanceof AuditLog) {
                return;
            }

            Audit::record(
                static::class.'@deleted',
                $model->auditSnapshot(),
                null,
                static::class,
                (int) $model->getKey(),
                null,
                ['audit_channel' => 'model']
            );
        });
    }

    /**
     * Extra attribute names to redact (merged with defaults).
     *
     * @var array<int, string>
     */
    protected $auditExclude = [];

    /**
     * @return array<int, string>
     */
    public function auditExcludeKeys(): array
    {
        return array_values(array_unique(array_merge(
            ['password', 'remember_token', 'confirm_password'],
            $this->auditExclude ?? []
        )));
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        $out = [];
        foreach ($this->getAttributes() as $key => $_) {
            if (in_array($key, $this->auditExcludeKeys(), true)) {
                $out[$key] = '[redacted]';

                continue;
            }
            $out[$key] = Audit::sanitizeValue($this->getAttribute($key));
        }

        return $out;
    }
}
