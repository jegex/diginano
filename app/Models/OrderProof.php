<?php

namespace App\Models;

use Database\Factories\OrderProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string $file_path
 * @property string $original_name
 * @property Carbon $submitted_at
 *
 * @method static OrderProofFactory factory()
 */
#[Fillable(['order_id', 'file_path', 'original_name', 'submitted_at'])]
class OrderProof extends Model
{
    /** @use HasFactory<OrderProofFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
