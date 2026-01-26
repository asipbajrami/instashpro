'use client';

import Image from 'next/image';
import Link from 'next/link';
import { Badge } from '@/components/ui/badge';
import { Product } from '@/lib/types';
import { formatPrice } from '@/lib/currency';

interface ProductCardProps {
  product: Product;
}

// Format date as "Jan 15" or "Jan 15, 2024" if different year
function formatPostDate(dateString: string | null): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const sameYear = date.getFullYear() === now.getFullYear();
  
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    ...(sameYear ? {} : { year: 'numeric' }),
  });
}

export function ProductCard({ product }: ProductCardProps) {
  const primaryImage = product.images.find((img) => img.is_primary) || product.images[0];
  // Use thumbnail (mid-quality) for grid view, fall back to primary image
  const thumbnailUrl = product.thumbnail_url || primaryImage?.url;
  const rawPrice = parseFloat(product.price) || 0;
  const rawDiscountPrice = product.discount_price ? parseFloat(product.discount_price) : null;

  // Normalize: if price is 0 but discount_price exists, use discount_price as the actual price
  const price = rawPrice > 0 ? rawPrice : (rawDiscountPrice || 0);
  const discountPrice = rawPrice > 0 ? rawDiscountPrice : null;

  const hasDiscount = discountPrice && discountPrice > 0 && price > discountPrice;
  const discountPercent = hasDiscount && price > 0
    ? Math.round(((price - discountPrice) / price) * 100)
    : 0;
  const currency = product.currency || 'ALL';
  const postDate = formatPostDate(product.published_at);

  return (
    <Link href={`/products/${product.id}`} className="group block h-full">
      <div className="overflow-hidden rounded-xl border bg-card transition-all hover:shadow-lg hover:border-primary/20 h-full flex flex-col">
        {/* Image - uses thumbnail (mid-quality) for faster loading */}
        <div className="relative aspect-square bg-muted overflow-hidden">
          {thumbnailUrl ? (
            <Image
              src={thumbnailUrl.startsWith('/') ? `http://localhost:8000${thumbnailUrl}` : thumbnailUrl}
              alt={product.name}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, (max-width: 1280px) 25vw, 20vw"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-muted-foreground text-sm">
              No image
            </div>
          )}
          {hasDiscount && (
            <Badge className="absolute top-2.5 left-2.5 bg-red-500 hover:bg-red-500 text-xs font-semibold px-2 py-1">
              -{discountPercent}%
            </Badge>
          )}
        </div>

        {/* Content */}
        <div className="p-3 flex flex-col flex-1">
          <h3 className="font-medium text-xs leading-tight line-clamp-2 min-h-[2rem] group-hover:text-primary transition-colors">
            {product.name}
          </h3>

          <div className="mt-2 flex items-baseline gap-1.5">
            {hasDiscount ? (
              <>
                <span className="text-sm md:text-base font-bold text-red-500">
                  {formatPrice(discountPrice, currency)}
                </span>
                <span className="text-[10px] md:text-xs text-muted-foreground line-through">
                  {formatPrice(price, currency)}
                </span>
              </>
            ) : (
              <span className="text-sm md:text-base font-bold">{formatPrice(price, currency)}</span>
            )}
          </div>

          <div className="mt-auto pt-1.5 flex items-center justify-between gap-2">
            {product.seller_username && (
              <p className="text-xs text-muted-foreground truncate">
                @{product.seller_username}
              </p>
            )}
            {postDate && (
              <p className="text-xs text-muted-foreground truncate text-right flex-shrink-0">
                {postDate}
              </p>
            )}
          </div>
        </div>
      </div>
    </Link>
  );
}
