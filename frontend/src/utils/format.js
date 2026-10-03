const currency = new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' })
const number = new Intl.NumberFormat('en-AU')
const dateTime = new Intl.DateTimeFormat('en-AU', { dateStyle: 'medium', timeStyle: 'short' })

export const formatCurrency = (value) => currency.format(Number(value) || 0)
export const formatNumber = (value) => number.format(Number(value) || 0)
export const formatDateTime = (value) => (value ? dateTime.format(new Date(value)) : '—')
export const signed = (value) => (value > 0 ? `+${value}` : `${value}`)
