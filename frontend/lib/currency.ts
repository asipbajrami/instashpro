// Currency configuration map (ISO 4217 codes)
const CURRENCY_CONFIG: Record<string, { symbol: string; position: 'prefix' | 'suffix' }> = {
  ETB: { symbol: 'Br', position: 'suffix' },   // Ethiopian Birr
  USD: { symbol: '$', position: 'prefix' },    // US Dollar
  EUR: { symbol: '€', position: 'prefix' },    // Euro
  GBP: { symbol: '£', position: 'prefix' },    // British Pound
  ALL: { symbol: 'L', position: 'suffix' },    // Albanian Lek
};

// Default currency
export const DEFAULT_CURRENCY = 'ALL';

/**
 * Format a price value with the specified currency
 */
export function formatPrice(
  value: number | string | null | undefined,
  currency: string = DEFAULT_CURRENCY
): string {
  if (value === null || value === undefined) return '';

  const numValue = typeof value === 'string' ? parseFloat(value) : value;

  if (isNaN(numValue)) return '';

  const formatted = numValue.toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });

  const config = CURRENCY_CONFIG[currency] || CURRENCY_CONFIG[DEFAULT_CURRENCY];

  if (config.position === 'prefix') {
    return `${config.symbol}${formatted}`;
  }

  return `${formatted} ${config.symbol}`;
}

/**
 * Format a price range (e.g., for filters)
 */
export function formatPriceRange(
  min: number,
  max: number,
  currency: string = DEFAULT_CURRENCY
): string {
  return `${formatPrice(min, currency)} - ${formatPrice(max, currency)}`;
}

/**
 * Get currency symbol for display
 */
export function getCurrencySymbol(currency: string = DEFAULT_CURRENCY): string {
  return CURRENCY_CONFIG[currency]?.symbol || CURRENCY_CONFIG[DEFAULT_CURRENCY].symbol;
}
