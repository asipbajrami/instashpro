'use client';

import { use } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useProduct } from '@/hooks/use-products';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { ChevronLeft, X, ZoomIn, Download, ExternalLink } from 'lucide-react';
import { useState, useCallback, useEffect } from 'react';
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselPrevious,
  CarouselNext,
  type CarouselApi,
} from '@/components/ui/carousel';
import {
  Dialog,
  DialogContent,
  DialogTitle,
} from '@/components/ui/dialog';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { formatPrice } from '@/lib/currency';

interface ProductPageProps {
  params: Promise<{ id: string }>;
}

export default function ProductPage({ params }: ProductPageProps) {
  const { id } = use(params);
  const router = useRouter();
  const { data, isLoading } = useProduct(Number(id));

  // Use browser history to preserve filters when going back
  const handleBack = () => {
    if (window.history.length > 1) {
      router.back();
    } else {
      router.push('/');
    }
  };
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [api, setApi] = useState<CarouselApi>();
  const [thumbApi, setThumbApi] = useState<CarouselApi>();
  const [modalOpen, setModalOpen] = useState(false);
  const [modalApi, setModalApi] = useState<CarouselApi>();
  const [modalIndex, setModalIndex] = useState(0);

  const onSelect = useCallback(() => {
    if (!api) return;
    setSelectedIndex(api.selectedScrollSnap());
    thumbApi?.scrollTo(api.selectedScrollSnap());
  }, [api, thumbApi]);

  useEffect(() => {
    if (!api) return;
    onSelect();
    api.on('select', onSelect);
    return () => {
      api.off('select', onSelect);
    };
  }, [api, onSelect]);

  const onThumbClick = useCallback(
    (index: number) => {
      if (!api) return;
      api.scrollTo(index);
    },
    [api]
  );

  const openModal = useCallback((index: number) => {
    setModalOpen(true);
    setModalIndex(index);
  }, []);

  useEffect(() => {
    if (modalApi && modalOpen) {
      modalApi.scrollTo(modalIndex, true);
    }
  }, [modalApi, modalOpen, modalIndex]);

  const onModalSelect = useCallback(() => {
    if (!modalApi) return;
    setModalIndex(modalApi.selectedScrollSnap());
  }, [modalApi]);

  useEffect(() => {
    if (!modalApi) return;
    modalApi.on('select', onModalSelect);
    return () => {
      modalApi.off('select', onModalSelect);
    };
  }, [modalApi, onModalSelect]);

  if (isLoading) {
    return <ProductPageSkeleton />;
  }

  const product = data?.data;

  if (!product) {
    return (
      <div className="container mx-auto px-4 py-12 text-center">
        <h1 className="text-2xl font-bold mb-4">Product not found</h1>
        <Button onClick={handleBack}>
          <ChevronLeft className="h-4 w-4 mr-2" />
          Back to products
        </Button>
      </div>
    );
  }

  const primaryCategory = product.categories[0];
  const hasDiscount =
    product.discount_price && parseFloat(product.discount_price) > 0;
  const price = parseFloat(product.price);
  const discountPrice = hasDiscount ? parseFloat(product.discount_price) : null;
  const currency = product.currency || 'ALL';

  return (
    <div className="container mx-auto px-4 py-6">
      {/* Breadcrumb */}
      <Breadcrumb className="mb-6">
        <BreadcrumbList>
          <BreadcrumbItem>
            <BreadcrumbLink asChild>
              <Link href="/">Home</Link>
            </BreadcrumbLink>
          </BreadcrumbItem>
          {primaryCategory && (
            <>
              <BreadcrumbSeparator />
              <BreadcrumbItem>
                <BreadcrumbLink asChild>
                  <Link href={`/categories/${primaryCategory.slug}`}>
                    {primaryCategory.name}
                  </Link>
                </BreadcrumbLink>
              </BreadcrumbItem>
            </>
          )}
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage className="line-clamp-1 max-w-[200px]">
              {product.name}
            </BreadcrumbPage>
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Image Carousel */}
        <div className="space-y-4">
          {product.images.length > 0 ? (
            <>
              {/* Main Carousel */}
              <Carousel setApi={setApi} className="w-full max-w-lg">
                <CarouselContent>
                  {product.images.map((image, index) => (
                    <CarouselItem key={image.id}>
                      <button
                        onClick={() => openModal(index)}
                        className="relative aspect-4/5 w-full bg-muted rounded-lg overflow-hidden cursor-zoom-in group"
                      >
                        <Image
                          src={
                            image.url.startsWith('/')
                              ? `http://localhost:8000${image.url}`
                              : image.url
                          }
                          alt={`${product.name} - Image ${index + 1}`}
                          fill
                          className="object-cover"
                          sizes="(max-width: 1024px) 100vw, 50vw"
                          priority={index === 0}
                        />
                        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                          <ZoomIn className="w-10 h-10 text-white opacity-0 group-hover:opacity-70 transition-opacity" />
                        </div>
                        {hasDiscount && index === 0 && (
                          <Badge className="absolute top-4 right-4 bg-red-500 text-lg px-3 py-1">
                            Sale
                          </Badge>
                        )}
                      </button>
                    </CarouselItem>
                  ))}
                </CarouselContent>
                {product.images.length > 1 && (
                  <>
                    <CarouselPrevious className="left-2" />
                    <CarouselNext className="right-2" />
                  </>
                )}
              </Carousel>

              {/* Thumbnail Carousel */}
              {product.images.length > 1 && (
                <Carousel
                  setApi={setThumbApi}
                  opts={{
                    align: 'start',
                    dragFree: true,
                  }}
                  className="w-full max-w-lg"
                >
                  <CarouselContent className="-ml-2">
                    {product.images.map((image, index) => (
                      <CarouselItem
                        key={image.id}
                        className="basis-1/6 pl-2 cursor-pointer"
                      >
                        <button
                          onClick={() => onThumbClick(index)}
                          className={`relative aspect-square w-full rounded-md overflow-hidden border-2 transition-colors ${
                            index === selectedIndex
                              ? 'border-primary'
                              : 'border-transparent hover:border-muted-foreground'
                          }`}
                        >
                          <Image
                            src={
                              image.url.startsWith('/')
                                ? `http://localhost:8000${image.url}`
                                : image.url
                            }
                            alt={`${product.name} - Thumbnail ${index + 1}`}
                            fill
                            className="object-cover"
                            sizes="80px"
                          />
                        </button>
                      </CarouselItem>
                    ))}
                  </CarouselContent>
                </Carousel>
              )}
            </>
          ) : (
            <div className="aspect-square bg-muted rounded-lg flex items-center justify-center text-muted-foreground">
              No image available
            </div>
          )}
        </div>

        {/* Product info */}
        <div className="space-y-6">
          {/* Title and type */}
          <div>
            <div className="flex items-center gap-2 mb-2">
              <Badge variant="outline">{product.type}</Badge>
              {product.categories.map((cat) => (
                <Link key={cat.id} href={`/categories/${cat.slug}`}>
                  <Badge variant="secondary" className="cursor-pointer">
                    {cat.name}
                  </Badge>
                </Link>
              ))}
            </div>
            <h1 className="text-3xl font-bold">{product.name}</h1>
            {product.seller_username && (
              <Link
                href={`/?profile=${product.seller_username}`}
                className="inline-flex items-center gap-1.5 mt-2 text-sm text-muted-foreground hover:text-primary transition-colors"
              >
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                </svg>
                @{product.seller_username}
              </Link>
            )}
          </div>

          {/* Price */}
          <div className="flex items-baseline gap-3">
            {hasDiscount ? (
              <>
                <span className="text-3xl font-bold text-red-500">
                  {formatPrice(discountPrice, currency)}
                </span>
                <span className="text-xl text-muted-foreground line-through">
                  {formatPrice(price, currency)}
                </span>
                <Badge className="bg-red-500">
                  {Math.round(((price - (discountPrice || 0)) / price) * 100)}%
                  OFF
                </Badge>
              </>
            ) : (
              <span className="text-3xl font-bold">{formatPrice(price, currency)}</span>
            )}
          </div>

          {/* Description */}
          {product.description && (
            <div>
              <h2 className="font-medium mb-2">Description</h2>
              <p className="text-muted-foreground whitespace-pre-line">
                {product.description}
              </p>
            </div>
          )}

          {/* Attributes */}
          {product.attributes.length > 0 && (
            <div>
              <h2 className="font-medium mb-3">Specifications</h2>
              <div className="grid grid-cols-2 gap-2">
                {product.attributes.map((attr, index) => (
                  <div
                    key={`${attr.attribute_id}-${index}`}
                    className="flex justify-between py-2 px-3 bg-muted rounded-md"
                  >
                    <span className="text-muted-foreground">{attr.name}</span>
                    <span className="font-medium">{attr.value}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Instagram Link */}
          {product.instagram_link && (
            <div>
              <a
                href={product.instagram_link}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 text-pink-500 hover:text-pink-600 transition-colors"
              >
                <svg
                  className="h-5 w-5"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                >
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                </svg>
                View on Instagram
                <ExternalLink className="h-4 w-4" />
              </a>
            </div>
          )}

          {/* Back button */}
          <Button variant="outline" className="mt-4" onClick={handleBack}>
            <ChevronLeft className="h-4 w-4 mr-2" />
            Back to products
          </Button>
        </div>
      </div>

      {/* Fullscreen Image Modal */}
      <Dialog open={modalOpen} onOpenChange={setModalOpen}>
        <DialogContent
          className="p-0 bg-black/40 backdrop-blur-xl border-none shadow-2xl w-[99.5vw] max-w-[99.5vw] h-[99vh] max-h-[99vh] flex flex-col items-stretch outline-none rounded-none sm:rounded-2xl overflow-hidden m-0"
          showCloseButton={false}
        >
          <VisuallyHidden>
            <DialogTitle>Product Images</DialogTitle>
          </VisuallyHidden>

          {/* Header area for counter and buttons */}
          <div className="w-full flex items-center justify-between p-1.5 sm:p-2.5 border-b border-white/5 shrink-0">
            {/* Image counter */}
            <div className="text-white text-[10px] sm:text-xs font-semibold bg-white/10 px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-full backdrop-blur-md border border-white/10">
              {modalIndex + 1} / {product.images.length}
            </div>

            {/* Action buttons */}
            <div className="flex gap-2 sm:gap-3">
              {/* Download button */}
              <Button
                variant="secondary"
                size="icon"
                className="rounded-full bg-white/10 text-white hover:bg-white/20 border-none backdrop-blur-md h-8 w-8 sm:h-10 sm:w-10"
                asChild
              >
                <a
                  href={
                    product.images[modalIndex]?.url.startsWith('/')
                      ? `http://localhost:8000${product.images[modalIndex]?.url}`
                      : product.images[modalIndex]?.url
                  }
                  download={`${product.name}-image-${modalIndex + 1}.jpg`}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <Download className="h-4 w-4 sm:h-5 sm:w-5" />
                </a>
              </Button>

              {/* Close button */}
              <Button
                variant="secondary"
                size="icon"
                className="rounded-full bg-white/10 text-white hover:bg-white/20 border-none backdrop-blur-md h-8 w-8 sm:h-10 sm:w-10"
                onClick={() => setModalOpen(false)}
              >
                <X className="h-4 w-4 sm:h-5 sm:w-5" />
              </Button>
            </div>
          </div>

          {/* Modal Carousel */}
          <div className="relative w-full px-1 py-1 sm:px-2 sm:py-2 flex items-center justify-center flex-1 min-h-0 overflow-hidden">
            <Carousel
              setApi={setModalApi}
              opts={{ startIndex: modalIndex }}
              className="w-full h-full"
            >
              <CarouselContent className="ml-0 h-full">
                {product.images.map((image, index) => (
                  <CarouselItem key={image.id} className="pl-0 h-full">
                    <div className="relative w-full h-full flex items-center justify-center p-0.5 sm:p-1">
                      <div className="relative w-full h-full flex items-center justify-center">
                        <img
                          src={
                            image.url.startsWith('/')
                              ? `http://localhost:8000${image.url}`
                              : image.url
                          }
                          alt={`${product.name} - Image ${index + 1}`}
                          className="object-contain w-full h-full"
                          style={{ maxWidth: '100%', maxHeight: '100%' }}
                        />
                      </div>
                    </div>
                  </CarouselItem>
                ))}
              </CarouselContent>
              {product.images.length > 1 && (
                <>
                  <CarouselPrevious className="left-1 sm:left-4 bg-white/10 hover:bg-white/20 border-white/10 border-2 text-white h-8 w-8 sm:h-12 sm:w-12 rounded-full transition-all opacity-100 disabled:opacity-20" />
                  <CarouselNext className="right-1 sm:right-4 bg-white/10 hover:bg-white/20 border-white/10 border-2 text-white h-8 w-8 sm:h-12 sm:w-12 rounded-full transition-all opacity-100 disabled:opacity-20" />
                </>
              )}
            </Carousel>
          </div>

          {/* Modal Thumbnails - Always rendered to maintain consistent spacing */}
          <div className={`w-full bg-white/5 backdrop-blur-xl py-1 sm:py-1.5 px-2 sm:px-4 border-t border-white/10 shrink-0 ${product.images.length > 1 ? '' : 'invisible'}`}>
            <div className="flex gap-1 sm:gap-1.5 justify-start sm:justify-center items-center overflow-x-auto overflow-y-visible scrollbar-hide pb-0.5 sm:pb-1 pt-1 sm:pt-1.5">
              {product.images.map((image, index) => (
                <button
                  key={image.id}
                  onClick={() => modalApi?.scrollTo(index)}
                  type="button"
                  className={`relative shrink-0 aspect-square w-16 sm:w-20 rounded-lg overflow-hidden border-2 transition-all duration-300 ${
                    index === modalIndex
                      ? 'border-white scale-110 shadow-2xl shadow-white/10'
                      : 'border-white/10 hover:border-white/40 hover:scale-105'
                  }`}
                >
                  <Image
                    src={
                      image.url.startsWith('/')
                        ? `http://localhost:8000${image.url}`
                        : image.url
                    }
                    alt={`Thumbnail ${index + 1}`}
                    fill
                    className="object-cover"
                    sizes="(max-width: 640px) 48px, 64px"
                  />
                </button>
              ))}
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function ProductPageSkeleton() {
  return (
    <div className="container mx-auto px-4 py-6">
      <Skeleton className="h-6 w-64 mb-6" />
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div className="space-y-4">
          <Skeleton className="aspect-4/5 w-full max-w-lg rounded-lg" />
          <div className="flex gap-2 max-w-lg">
            {Array.from({ length: 4 }).map((_, i) => (
              <Skeleton key={i} className="w-16 h-16 rounded-md" />
            ))}
          </div>
        </div>
        <div className="space-y-6">
          <div>
            <Skeleton className="h-6 w-32 mb-2" />
            <Skeleton className="h-10 w-3/4" />
          </div>
          <Skeleton className="h-10 w-40" />
          <div>
            <Skeleton className="h-6 w-24 mb-2" />
            <Skeleton className="h-24 w-full" />
          </div>
        </div>
      </div>
    </div>
  );
}
