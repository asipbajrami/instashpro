'use client';

import { useTranslations } from 'next-intl';

export function Footer() {
  const t = useTranslations('footer');

  return (
    <footer className="border-t">
      <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <p className="text-xs text-muted-foreground text-center">
          InstashPro · {t('poweredBy')}{' '}
          <a
            href="https://datafynow.ai"
            target="_blank"
            rel="noopener noreferrer"
            className="font-medium hover:underline"
          >
            datafynow.ai
          </a>
        </p>
      </div>
    </footer>
  );
}
