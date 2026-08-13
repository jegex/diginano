<?php

namespace App\Livewire;

use App\Models\License;
use App\Models\ProductRelease;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class DownloadCenter extends Component
{
    public function download(int $releaseId): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $release = ProductRelease::with('product')->findOrFail($releaseId);
        abort_unless($release->fileExists(), 422, 'Berkas rilis belum tersedia.');

        $license = $this->usableLicenseFor($release->product_id);
        abort_unless($license !== null, 403, 'Anda tidak memiliki lisensi aktif untuk produk ini.');

        $license->downloads()->create([
            'product_id' => $release->product_id,
            'release_id' => $release->id,
            'downloaded_at' => now(),
        ]);

        return Storage::disk('local')->download($release->file_path, $release->downloadName());
    }

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user !== null, 403);

        $entries = $this->usableLicenses()
            ->groupBy('product_id')
            ->map(function (Collection $licenses) {
                $product = $licenses->first()->product;

                return [
                    'product' => $product,
                    'licenses' => $licenses,
                    'latestRelease' => $product->releases->first(),
                ];
            })
            ->values();

        return view('livewire.download-center', [
            'entries' => $entries,
        ]);
    }

    /**
     * @return Collection<int, License>
     */
    private function usableLicenses(): Collection
    {
        return auth()->user()->licenses()
            ->with([
                'plan',
                'subscription',
                'product.releases' => fn ($query) => $query->latest('id'),
            ])
            ->get()
            ->filter(fn (License $license) => $license->isUsable())
            ->values();
    }

    private function usableLicenseFor(int $productId): ?License
    {
        return $this->usableLicenses()
            ->first(fn (License $license) => $license->product_id === $productId);
    }
}
