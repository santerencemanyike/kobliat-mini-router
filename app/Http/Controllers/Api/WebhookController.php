<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WebhookController extends Controller
{
    public function receive(Request $request)
    {
        $data = $request->validate([
            'external_user_id' => 'required|string',
            'body' => 'required|string',
            'channel' => 'nullable|string',
            'sent_at' => 'nullable|date',
        ]);

        $customer = Customer::firstOrCreate(
            ['external_id' => $data['external_user_id']],
            ['name' => null]
        );

        $conversation = Conversation::where('customer_id', $customer->id)
            ->where('status', 'open')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create(['customer_id' => $customer->id, 'status' => 'open']);
        }

        $sentAt = isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : now();

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'body' => $data['body'],
            'channel' => $data['channel'] ?? null,
            'sent_at' => $sentAt,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
