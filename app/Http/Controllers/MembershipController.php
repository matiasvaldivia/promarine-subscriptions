<?php

namespace App\Http\Controllers;

use App\Mail\MembershipRequested;
use App\Models\MembershipSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class MembershipController extends Controller
{
    public function show()
    {
        return view('membership.show');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
            'community_updates' => 'sometimes|boolean',
            'consent_terms' => 'accepted',
        ], [
            'consent_terms.accepted' => 'Necesitamos tu aceptación para registrar la membresía.',
        ]);

        $email = mb_strtolower(trim($data['email']));
        $membership = MembershipSubscription::query()->firstOrNew([
            'email' => $email,
            'status' => 'pending_confirmation',
        ]);

        if (! $membership->exists) {
            $membership->uuid = (string) Str::uuid();
        }

        $membership->fill([
            'name' => trim($data['name']),
            'email' => $email,
            'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
            'billing_period' => 'annual',
            'status' => 'pending_confirmation',
            'benefits_json' => ['member_discounts', 'priority_deliveries', 'exclusive_information'],
            'community_updates' => ! empty($data['community_updates']),
            'consent_terms_at' => now(),
            'is_mock' => true,
            'source' => 'standalone_membership',
        ])->save();

        $mailSent = false;
        try {
            Mail::to($membership->email)->send(new MembershipRequested($membership));
            $mailSent = true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar la confirmación de membresía.', [
                'membership_uuid' => $membership->uuid,
                'exception' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('membership.confirmation', ['membership' => $membership->uuid, 'email' => $mailSent ? 'sent' : 'pending']);
    }

    public function confirmation(MembershipSubscription $membership)
    {
        abort_unless($membership->is_mock && $membership->source === 'standalone_membership', 404);

        return view('membership.confirmation', [
            'membership' => $membership,
            'mailSent' => request('email') === 'sent',
        ]);
    }
}
