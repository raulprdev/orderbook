export const SYMBOLS = ['BTC', 'ETH'] as const
export type Symbol = typeof SYMBOLS[number]

export const SIDES = ['buy', 'sell'] as const
export type Side = typeof SIDES[number]

export const ORDER_STATUSES = ['open', 'filled', 'cancelled'] as const
export type OrderStatus = typeof ORDER_STATUSES[number]