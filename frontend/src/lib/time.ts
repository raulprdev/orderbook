const RTF = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })

export function relativeTime(iso: string): string {
  const seconds = (Date.now() - new Date(iso).getTime()) / 1000

  if (seconds < 45) return RTF.format(-Math.round(seconds), 'second')
  if (seconds < 3600) return RTF.format(-Math.round(seconds / 60), 'minute')
  if (seconds < 86_400) return RTF.format(-Math.round(seconds / 3600), 'hour')
  if (seconds < 2_592_000) return RTF.format(-Math.round(seconds / 86_400), 'day')

  return new Date(iso).toLocaleDateString()
}