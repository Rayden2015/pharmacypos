<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreDirectMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canUseTenantCommunications() ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $recipient = User::query()->find($this->input('recipient_id'));
            $user = $this->user();
            if (! $recipient || ! $user) {
                return;
            }
            if ($recipient->isSuperAdmin() || ! $recipient->company_id) {
                $validator->errors()->add('recipient_id', 'Invalid recipient.');

                return;
            }
            if ((int) $recipient->company_id !== (int) $user->company_id) {
                $validator->errors()->add('recipient_id', 'You can only message users in your organization.');
            }
            if ((int) $recipient->id === (int) $user->id) {
                $validator->errors()->add('recipient_id', 'Choose someone else to message.');
            }
        });
    }
}
