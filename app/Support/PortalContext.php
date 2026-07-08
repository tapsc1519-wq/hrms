<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortalContext
{
    public function __construct(
        public readonly string $host,
        public readonly bool $isPlatform,
        public readonly ?string $productKey,
        public readonly string $brandName,
        public readonly string $loginTitle,
        public readonly string $loginSubtitle,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $host = Str::lower($request->getHost());
        $platformDomain = Str::lower((string) config('niyantron.platform_domain'));
        $products = config('niyantron.products', []);
        $productKey = null;

        foreach ($products as $key => $product) {
            if ($host === Str::lower((string) ($product['domain'] ?? ''))) {
                $productKey = $key;
                break;
            }
        }

        $isPlatform = $platformDomain !== '' && $host === $platformDomain;
        $product = $products[$productKey ?: config('niyantron.default_product')] ?? [];

        return new self(
            host: $host,
            isPlatform: $isPlatform,
            productKey: $productKey,
            brandName: $isPlatform ? 'Niyantron Platform' : (string) ($product['name'] ?? 'OpsBridge'),
            loginTitle: $isPlatform ? 'Niyantron control center' : (string) ($product['login_title'] ?? 'Welcome back'),
            loginSubtitle: $isPlatform
                ? 'Manage products, subscriptions, partners and platform operations.'
                : (string) ($product['login_subtitle'] ?? 'Access your workspace.'),
        );
    }

    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'is_platform' => $this->isPlatform,
            'product_key' => $this->productKey,
            'brand_name' => $this->brandName,
            'login_title' => $this->loginTitle,
            'login_subtitle' => $this->loginSubtitle,
        ];
    }
}
