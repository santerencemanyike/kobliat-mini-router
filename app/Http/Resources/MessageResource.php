<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'direction' => $this->direction,
            'body' => $this->body,
            'channel' => $this->channel,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
        ];
    }
}
