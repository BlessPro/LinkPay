<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OgImageService
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;
    private const PRODUCT_OG_VERSION = 'v2';

    public function generateSeller(SellerProfile $profile): void
    {
        if (! $profile->public_slug) {
            return;
        }

        $title = $profile->business_name ?: 'Seller';
        $subtitle = 'Browse products and contact on WhatsApp';

        $imagePath = $this->bestSellerImagePath($profile);
        $jpg = $this->renderJpeg($title, $subtitle, null, $imagePath);

        if ($jpg) {
            $this->storePublicJpeg('og/sellers/'.$profile->public_slug.'.jpg', $jpg);
        }
    }

    public function generateProduct(Product $product): void
    {
        if (! $product->id) {
            return;
        }

        $sellerName = $product->user?->sellerProfile?->business_name ?: ($product->user?->name ?: 'Seller');
        $title = $product->name ?: 'Product';
        $subtitle = $sellerName;
        $price = (string) $product->price;

        $imagePath = $product->image_path ? $this->publicDiskAbsolutePath($product->image_path) : null;
        $jpg = $this->renderProductJpeg($title, $subtitle, $price, $imagePath);

        if ($jpg) {
            $this->storePublicJpeg($this->publicProductOgPath($product->id), $jpg);
        }
    }

    public function generateInvoice(Invoice $invoice): void
    {
        if (! $invoice->id) {
            return;
        }

        $sellerName = $invoice->user?->sellerProfile?->business_name ?: ($invoice->user?->name ?: 'Seller');
        $title = $invoice->title ?: 'Invoice';
        $subtitle = $sellerName;
        $price = (string) $invoice->amountDue();

        $imagePath = $invoice->image_path ? $this->publicDiskAbsolutePath($invoice->image_path) : null;
        $jpg = $this->renderJpeg($title, $subtitle, $price, $imagePath);

        if ($jpg) {
            $this->storePublicJpeg('og/invoices/'.$invoice->id.'.jpg', $jpg);
        }
    }

    public function publicSellerOgPath(string $publicSlug): string
    {
        return 'og/sellers/'.$publicSlug.'.jpg';
    }

    public function publicProductOgPath(int|string $productId): string
    {
        return 'og/products/'.$productId.'-'.self::PRODUCT_OG_VERSION.'.jpg';
    }

    public function publicInvoiceOgPath(string $invoiceId): string
    {
        return 'og/invoices/'.$invoiceId.'.jpg';
    }

    private function storePublicJpeg(string $path, string $binary): void
    {
        Storage::disk('public')->put($path, $binary);
    }

    private function publicDiskAbsolutePath(string $relative): ?string
    {
        $absolute = storage_path('app/public/'.$relative);
        return is_file($absolute) ? $absolute : null;
    }

    private function bestSellerImagePath(SellerProfile $profile): ?string
    {
        try {
            $product = $profile->user?->products()
                ?->where('is_active', true)
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->latest()
                ->first();

            if ($product?->image_path) {
                return $this->publicDiskAbsolutePath($product->image_path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    /**
     * Render a 1200x630 PNG.
     */
    private function renderJpeg(string $title, string $subtitle, ?string $price, ?string $photoPath): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if (! $img) {
            return null;
        }

        // Background gradient.
        $top = [245, 250, 255];
        $bottom = [236, 253, 245];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / max(1, (self::HEIGHT - 1));
            $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $t);
            $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $t);
            $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $t);
            $col = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $col);
        }

        // Right-side photo panel (if available).
        if ($photoPath) {
            $photo = $this->loadImage($photoPath);
            if ($photo) {
                $panelX = 720;
                $panelW = 420;
                $panelH = 520;
                $panelY = 55;
                $this->drawImageCover($img, $photo, $panelX, $panelY, $panelW, $panelH);
                imagedestroy($photo);
            }
        }

        // Text panel.
        $panelBg = imagecolorallocatealpha($img, 255, 255, 255, 25);
        imagefilledrectangle($img, 60, 70, 690, 560, $panelBg);

        // Brand chip.
        $chipBg = imagecolorallocatealpha($img, 16, 185, 129, 10);
        imagefilledrectangle($img, 90, 105, 240, 145, $chipBg);
        $chipText = imagecolorallocate($img, 6, 95, 70);
        $this->drawText($img, '8Kommerce', 100, 135, 18, $chipText);

        $titleColor = imagecolorallocate($img, 15, 23, 42);
        $muted = imagecolorallocate($img, 71, 85, 105);
        $accent = imagecolorallocate($img, 16, 185, 129);

        $font = $this->fontPath();
        $title = trim($title);
        $subtitle = trim($subtitle);

        if ($font) {
            $titleLines = $this->wrapTtf($title, $font, 46, 560);
            $y = 220;
            foreach ($titleLines as $line) {
                imagettftext($img, 46, 0, 90, $y, $titleColor, $font, $line);
                $y += 58;
                if ($y > 400) {
                    break;
                }
            }

            imagettftext($img, 22, 0, 92, 440, $muted, $font, $subtitle);

            if ($price !== null && $price !== '') {
                $currency = (string) config('services.paystack.currency', 'GHS');
                imagettftext($img, 28, 0, 92, 505, $accent, $font, $currency.' '.number_format((float) $price, 2, '.', ','));
            }
        } else {
            // Fallback (uglier): built-in font.
            imagestring($img, 5, 90, 210, $this->truncate($title, 48), $titleColor);
            imagestring($img, 4, 92, 300, $this->truncate($subtitle, 60), $muted);
        }

        ob_start();
        imagejpeg($img, null, 88);
        $jpg = ob_get_clean();

        imagedestroy($img);

        return is_string($jpg) ? $jpg : null;
    }

    /**
     * Product-focused OG layout:
     * - large product image area
     * - title
     * - prominent price directly under title
     */
    private function renderProductJpeg(string $title, string $subtitle, ?string $price, ?string $photoPath): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if (! $img) {
            return null;
        }

        $top = [243, 248, 255];
        $bottom = [224, 244, 236];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / max(1, (self::HEIGHT - 1));
            $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $t);
            $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $t);
            $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $t);
            $col = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $col);
        }

        // Main image card (largest element in OG).
        $cardX = 60;
        $cardY = 42;
        $cardW = 1080;
        $cardH = 410;
        $cardBg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $cardBg);

        if ($photoPath) {
            $photo = $this->loadImage($photoPath);
            if ($photo) {
                // Keep full source image visible (no trimming/cropping).
                $this->drawImageContain($img, $photo, $cardX, $cardY, $cardW, $cardH);
                imagedestroy($photo);
            }
        }

        $titleColor = imagecolorallocate($img, 15, 23, 42);
        $muted = imagecolorallocate($img, 71, 85, 105);
        $accent = imagecolorallocate($img, 5, 150, 105);
        $chipBg = imagecolorallocatealpha($img, 16, 185, 129, 22);
        $chipText = imagecolorallocate($img, 6, 95, 70);
        $font = $this->fontPath();

        // Brand chip and title block under the image.
        imagefilledrectangle($img, 70, 475, 250, 515, $chipBg);
        $this->drawText($img, '8Kommerce', 82, 503, 18, $chipText);

        $title = trim($title);
        $subtitle = trim($subtitle);
        if ($font) {
            $titleLines = $this->wrapTtf($title, $font, 42, 1030);
            $y = 560;
            foreach (array_slice($titleLines, 0, 1) as $line) {
                imagettftext($img, 42, 0, 70, $y, $titleColor, $font, $line);
            }

            if ($price !== null && $price !== '') {
                $currency = (string) config('services.paystack.currency', 'GHS');
                imagettftext($img, 34, 0, 70, 606, $accent, $font, $currency.' '.number_format((float) $price, 2, '.', ','));
            }

            imagettftext($img, 19, 0, 430, 606, $muted, $font, $subtitle);
        } else {
            imagestring($img, 5, 70, 535, $this->truncate($title, 48), $titleColor);
            if ($price !== null && $price !== '') {
                imagestring($img, 5, 70, 570, ((string) config('services.paystack.currency', 'GHS')).' '.number_format((float) $price, 2, '.', ','), $accent);
            }
            imagestring($img, 3, 430, 575, $this->truncate($subtitle, 40), $muted);
        }

        ob_start();
        imagejpeg($img, null, 90);
        $jpg = ob_get_clean();
        imagedestroy($img);

        return is_string($jpg) ? $jpg : null;
    }

    private function drawText($img, string $text, int $x, int $y, int $size, int $color): void
    {
        $font = $this->fontPath();
        if ($font) {
            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
            return;
        }

        imagestring($img, 4, $x, $y - 16, $text, $color);
    }

    private function fontPath(): ?string
    {
        $candidates = [
            // Docker/Linux (when fonts are installed).
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            // Windows dev machine.
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function wrapTtf(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $test = $line === '' ? $word : ($line.' '.$word);
            $box = imagettfbbox($size, 0, $font, $test);
            $width = $box ? abs($box[2] - $box[0]) : 0;

            if ($width > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
                continue;
            }

            $line = $test;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, max(0, $max - 3)).'...';
    }

    private function loadImage(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        try {
            return match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($path),
                'png' => @imagecreatefrompng($path),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning('OG image photo load failed', ['path' => $path, 'message' => $e->getMessage()]);
            return null;
        }
    }

    private function drawImageCover($dst, $src, int $x, int $y, int $w, int $h): void
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            return;
        }

        $srcRatio = $srcW / $srcH;
        $dstRatio = $w / $h;

        if ($srcRatio > $dstRatio) {
            // Source wider: crop width.
            $newH = $srcH;
            $newW = (int) round($srcH * $dstRatio);
            $srcX = (int) round(($srcW - $newW) / 2);
            $srcY = 0;
        } else {
            // Source taller: crop height.
            $newW = $srcW;
            $newH = (int) round($srcW / $dstRatio);
            $srcX = 0;
            $srcY = (int) round(($srcH - $newH) / 2);
        }

        imagecopyresampled($dst, $src, $x, $y, $srcX, $srcY, $w, $h, $newW, $newH);
    }

    private function drawImageContain($dst, $src, int $x, int $y, int $w, int $h): void
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            return;
        }

        $scale = min($w / $srcW, $h / $srcH);
        $drawW = max(1, (int) floor($srcW * $scale));
        $drawH = max(1, (int) floor($srcH * $scale));
        $drawX = $x + (int) floor(($w - $drawW) / 2);
        $drawY = $y + (int) floor(($h - $drawH) / 2);

        imagecopyresampled($dst, $src, $drawX, $drawY, 0, 0, $drawW, $drawH, $srcW, $srcH);
    }
}
