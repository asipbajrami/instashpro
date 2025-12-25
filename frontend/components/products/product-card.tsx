'use client';

import Image from 'next/image';
import Link from 'next/link';
import { Badge } from '@/components/ui/badge';
import { Product } from '@/lib/types';
import { formatPrice } from '@/lib/currency';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const primaryImage = product.images.find((img) => img.is_primary) || product.images[0];
  const hasDiscount = product.discount_price && parseFloat(product.discount_price) > 0;
  const price = parseFloat(product.price);
  const discountPrice = hasDiscount ? parseFloat(product.discount_price) : null;
  const discountPercent = hasDiscount ? Math.round(((price - (discountPrice || 0)) / price) * 100) : 0;
  const currency = product.currency || 'ALL';

  return (
    <Link href={`/products/${product.id}`} className="group block h-full">
      <div className="overflow-hidden rounded-xl border bg-card transition-all hover:shadow-lg hover:border-primary/20 h-full flex flex-col">
        {/* Image */}
        <div className="relative aspect-square bg-muted overflow-hidden">
          {primaryImage ? (
            <Image
              src={primaryImage.url.startsWith('/') ? `http://localhost:8000${primaryImage.url}` : primaryImage.url}
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
        <div className="p-4 flex flex-col flex-1">
          <h3 className="font-medium text-sm leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-primary transition-colors">
            {product.name}
          </h3>

          <div className="mt-3 flex items-baseline gap-2">
            {hasDiscount ? (
              <>
                <span className="text-lg font-bold text-red-500">
                  {formatPrice(discountPrice, currency)}
                </span>
                <span className="text-xs text-muted-foreground line-through">
                  {formatPrice(price, currency)}
                </span>
              </>
            ) : (
              <span className="text-lg font-bold">{formatPrice(price, currency)}</span>
            )}
          </div>

          <div className="mt-auto pt-2 flex items-center justify-between gap-2">
            {product.seller_username && (
              <p className="text-xs text-muted-foreground truncate">
                @{product.seller_username}
              </p>
            )}
            {product.categories.length > 0 && (
              <p className="text-xs text-muted-foreground truncate text-right flex-shrink-0">
                {product.categories[0].name}
              </p>
            )}
          </div>
        </div>
      </div>
    </Link>
  );
}
