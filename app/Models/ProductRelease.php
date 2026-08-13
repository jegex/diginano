<?php

namespace App\Models;

use Database\Factories\ProductReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $product_id
 * @property string $version
 * @property string|null $changelog
 * @property string|null $file_path
 * @property string|null $original_name
 *
 * @method static ProductReleaseFactory factory()
 */
#[Fillable(['product_id', 'version', 'changelog', 'file_path', 'original_name'])]
class ProductRelease extends Model
{
    /** @use HasFactory<ProductReleaseFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasFile(): bool
    {
        return $this->file_path !== null;
    }

    /**
     * The filename the customer receives when downloading this release.
     */
    public function downloadName(): string
    {
        return $this->original_name ?? 'release-'.$this->version.'.zip';
    }

    /**
     * Whether the release file physically exists on the configured disk.
     */
    public function fileExists(): bool
    {
        return $this->hasFile() && Storage::exists($this->file_path);
    }
}
