<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactMessageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ContactMessage::latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $messages = $query->paginate($request->input('per_page', 20));

        return ContactMessageResource::collection($messages);
    }

    public function show(ContactMessage $message): ContactMessageResource
    {
        if ($message->status === 'unread') {
            $message->update(['status' => 'read']);
        }

        return new ContactMessageResource($message);
    }

    public function update(UpdateContactMessageRequest $request, ContactMessage $message): ContactMessageResource
    {
        $message->update($request->validated());
        return new ContactMessageResource($message);
    }

    public function destroy(ContactMessage $message): JsonResponse
    {
        $message->delete();

        return response()->json([
            'message' => 'Message deleted successfully',
        ]);
    }
}
