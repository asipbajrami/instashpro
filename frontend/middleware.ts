import createMiddleware from 'next-intl/middleware';
import { routing } from './i18n/navigation';

export default createMiddleware(routing);

export const config = {
  // Match all pathnames except for
  // - API routes
  // - Next.js internals (_next)
  // - Static files (images, fonts, etc.)
  matcher: ['/', '/(en|sq)/:path*', '/((?!api|_next|_vercel|.*\\..*).*)'],
};
