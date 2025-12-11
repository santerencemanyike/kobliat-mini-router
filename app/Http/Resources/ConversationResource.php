<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'customer' => [
                'id' => $this->customer->id,
                'external_id' => $this->customer->external_id,
                'name' => $this->customer->name,
            ],
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'last_message' => optional($this->messages->sortByDesc('sent_at')->first()),
        ];
    }
}
