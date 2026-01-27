'use client';

import { useLocale } from 'next-intl';
import { usePathname, useRouter } from '@/i18n/navigation';
import { localeNames, localeFlags, type Locale } from '@/i18n/config';
import { Button } from '@/components/ui/button';
import { Globe } from 'lucide-react';
import { cn } from '@/lib/utils';

interface LanguageSwitcherProps {
  compact?: boolean;
}

export function LanguageSwitcher({ compact }: LanguageSwitcherProps) {
  const locale = useLocale() as Locale;
  const router = useRouter();
  const pathname = usePathname();

  const handleLocaleChange = () => {
    // Toggle between locales
    const newLocale = locale === 'en' ? 'sq' : 'en';
    router.replace(pathname, { locale: newLocale });
  };

  const otherLocale = locale === 'en' ? 'sq' : 'en';

  return (
    <Button
      variant={compact ? "outline" : "ghost"}
      size={compact ? "sm" : "sm"}
      className={cn(
        compact ? "h-9 px-2 shrink-0 gap-1" : "h-9 gap-1.5 px-2"
      )}
      onClick={handleLocaleChange}
      title={`Switch to ${localeNames[otherLocale]}`}
    >
      {compact ? (
        <>
          <span className="text-base leading-none">{localeFlags[locale]}</span>
          <span className="text-xs font-medium uppercase">{locale}</span>
        </>
      ) : (
        <>
          <Globe className="h-4 w-4" />
          <span className="text-xs font-medium uppercase">{locale}</span>
        </>
      )}
    </Button>
  );
}
