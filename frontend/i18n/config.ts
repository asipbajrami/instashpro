export const locales = ['en', 'sq'] as const;
export type Locale = (typeof locales)[number];

export const defaultLocale: Locale = 'en';

export const localeNames: Record<Locale, string> = {
  en: 'English',
  sq: 'Shqip',
};

export const localeFlags: Record<Locale, string> = {
  en: '🇬🇧',
  sq: '🇦🇱',
};
