<?php

namespace App\Http\Requests;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canPublishAnnouncements() ?? false;
    }

    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['tenant', 'site'])],
            'site_id' => [
                'nullable',
                'integer',
                'exists:sites,id',
            ],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var User $user */
            $user = $this->user();
            if (! $user) {
                return;
            }

            if ($this->input('scope') === 'tenant') {
                if (! $user->isTenantAdmin()) {
                    $validator->errors()->add('scope', 'Only tenant administrators can post organization-wide announcements.');

                    return;
                }

                return;
            }

            // Site scope
            $siteId = $this->input('site_id');
            if (! $siteId) {
                $validator->errors()->add('site_id', 'Choose a branch for this announcement.');

                return;
            }

            $site = Site::query()->find($siteId);
            if (! $site || (int) $site->company_id !== (int) $user->company_id) {
                $validator->errors()->add('site_id', 'Invalid branch.');

                return;
            }

            if ($user->isBranchAnnouncementAuthor() && ! $user->isTenantAdmin()) {
                $effective = (int) ($user->site_id ?? Site::defaultId());
                if ((int) $site->id !== $effective) {
                    $validator->errors()->add('site_id', 'You can only post to your own branch.');
                }
            }
        });
    }
}
