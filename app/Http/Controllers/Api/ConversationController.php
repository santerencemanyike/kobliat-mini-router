<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Customer;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Http\Requests\ReplyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        // Server-side DataTables support
        $draw = intval($request->get('draw', 0));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 15));
        $search = $request->input('search.value');

        $query = Conversation::query()->with('customer')
            ->withCount('messages');

          // join customers for searching/sorting by customer fields
          // don't override the select — keep withCount('messages') column
          $query->leftJoin('customers', 'customers.id', '=', 'conversations.customer_id');

        $recordsTotal = Conversation::count();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('conversations.id', 'like', "%{$search}%")
                  ->orWhere('customers.external_id', 'like', "%{$search}%")
                  ->orWhere('conversations.status', 'like', "%{$search}%");
            });
        }

        // ordering
        if ($request->has('order')) {
            $order = $request->get('order')[0] ?? null;
            $colIdx = $order['column'] ?? null;
            $dir = $order['dir'] ?? 'desc';
            $columns = $request->get('columns');
            $colName = $columns[$colIdx]['data'] ?? null;

            switch ($colName) {
                case 'id':
                    $query->orderBy('conversations.id', $dir);
                    break;
                case 'customer.external_id':
                    $query->orderBy('customers.external_id', $dir);
                    break;
                case 'status':
                    $query->orderBy('conversations.status', $dir);
                    break;
                case 'messages_count':
                    $query->orderBy('messages_count', $dir);
                    break;
                default:
                    $query->orderBy('conversations.updated_at', $dir);
            }
        } else {
            $query->orderBy('conversations.updated_at', 'desc');
        }

        $recordsFiltered = $query->count();

        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function ($c) {
            // load last message (efficient enough for small sets)
            $last = Message::where('conversation_id', $c->id)->orderBy('sent_at', 'desc')->first();
            return [
                'id' => $c->id,
                'customer' => [
                    'id' => optional($c->customer)->id,
                    'external_id' => optional($c->customer)->external_id,
                ],
                'last_message' => $last ? ['body' => $last->body, 'sent_at' => $last->sent_at] : null,
                'status' => $c->status,
                // compute messages_count explicitly to avoid issues with joins
                'messages_count' => Message::where('conversation_id', $c->id)->count(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $conversation = Conversation::with(['customer', 'messages'])->findOrFail($id);

        return new ConversationResource($conversation->load('messages'));
    }

    public function reply(ReplyRequest $request, $id)
    {
        $conversation = Conversation::findOrFail($id);

        $data = $request->validated();

        $sentAt = isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : now();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'body' => $data['body'],
            'channel' => $data['channel'] ?? null,
            'sent_at' => $sentAt,
        ]);

        return new MessageResource($message);
    }

    // UI-safe reply: called from the admin UI (no client token required)
    public function replyFromUi(ReplyRequest $request, $id)
    {
        // reuse the same reply logic; server trusts this internal endpoint
        return $this->reply($request, $id);
    }

    // Delete a customer and all related conversations/messages
    public function deleteCustomer($id)
    {
        $customer = Customer::findOrFail($id);

        // collect conversation ids
        $convIds = Conversation::where('customer_id', $customer->id)->pluck('id')->toArray();

        if (!empty($convIds)) {
            Message::whereIn('conversation_id', $convIds)->delete();
            Conversation::whereIn('id', $convIds)->delete();
        }

        $customer->delete();

        return response()->json(null, 204);
    }
}
