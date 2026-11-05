<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function store(ContactRequest $request): JsonResponse
    {
        // Honeypot check: if hidden field was filled by a bot, reject
        if ($request->filled('website')) {
            throw ValidationException::withMessages([
                'message' => ['Automated spam submission blocked.'],
            ]);
        }

        // Minimum interaction time check (1.5 seconds) to block instant bot POSTs
        $renderedAt = (int) $request->input('_rendered_at', 0);
        if ($renderedAt > 0 && ((int) (microtime(true) * 1000) - $renderedAt) < 1500) {
            throw ValidationException::withMessages([
                'message' => ['Submission was too fast. Please wait a moment and try again.'],
            ]);
        }

        $data = $request->safe()->only(['name', 'email', 'subject', 'message']);
        $data['status'] = 'unread';
        $data['ip_address'] = $request->ip();

        $message = ContactMessage::create($data);

        return response()->json([
            'message' => 'Thank you for reaching out! Your message has been received.',
            'data'    => [
                'id'         => $message->id,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }
}
