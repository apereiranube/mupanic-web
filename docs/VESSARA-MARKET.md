# Mercado de Vessara — live trading floor

Dedicated public page at `market/` in the beta template; Mercado follows El servidor in shared navigation. Deploy through cPanel Update from Remote → Deploy HEAD Commit. This revision needs no VPS/game/client installation; the previously installed public SQL view remains the data source.

## Public data and correctness

Three SELECT queries read `[MuOnline43].[dbo].[PanicMarketPublicSales]` through the existing WebEngine MuOnline connection. The view exposes nine public fields, and includes only delivered purchases in DELIVERY_SAVED, CREDIT_ATTEMPTED or CLOSED. Cancellation, incomplete delivery and legacy MarketCoin do not count as sales. Seller collection does not add another sale. The public projection excludes account identifiers, serials, item bytes, inventories, balances and connection errors.

Metrics show today's delivered trades, lifetime gross Zen/Eryns trading volumes and today's volumes. Currency volumes are separate decimal strings using SQL bigint SUM and JS BigInt. They measure turnover, not existing currency supply. No synthetic prices, players, random counters, fake trends or invented demand appear in production. Mini sparklines and the main seven-day chart use actual daily data, including zero days. Yesterday's comparison is a literal completed-day trade count, not a growth percentage comparing partial periods.

Last 40 purchases show actual item name/level, character names, price, currency and Argentina-local delivery date. Search and currency filters apply to these rows, while summary metrics remain market-wide. SQL dates are VPS-local Argentina; GETDATE defines the current day and displayed timestamps carry -03:00.

## Live behavior

Both shared cache TTL and browser polling are 15 seconds. The browser stops polling while hidden, keeps search/currency selections and aborts stuck requests after ten seconds. The visible countdown is to the next read, not an item expiry or promotional deadline. The clock displays Argentina time.

The moving ticker repeats labelled historical transactions with date/time. Hover or focus pauses it; the pause button is persistent and keyboard accessible. Reduced-motion disables motion. Initial historic sales never produce a new-sale notification. After a fresh update, newly seen delivery IDs at or after the previous latest delivery trigger a notification and highlight new feed/table rows for six seconds. Unchanged responses do not restart the ticker or animate counters. Older imported records and stale snapshots do not announce new live sales.

Numeric updates interpolate in BigInt over 800ms, then land on the exact total. Initial values render directly. Font fitting waits for fonts and responds to resize without splitting digits. Figures remain dashes if unavailable. Failed queries retain up to 24 hours of cached data labelled stale and mark the connection waiting; old/missing data never masquerades as zero/current activity.

## Visual direction

An illustrated Vessara hero opens onto a trading-floor dashboard: amber scrolling transaction tape, ivory/green trade panel, light amber Zen panel and light violet Eryns panel, high-contrast prices, real daily sparklines, chronological purchase feed and a daily activity chart. Creating an account and downloading the client link to existing routes. The NPC illustration and seal follow the established game/world identity.

## Verification

PHP checks cover projection, precision, error/empty states and cache behavior. Playwright checks five viewport sizes, navigation/title layout, overflow, exact counter fitting, filters/search, reduced motion, ticker pause, historical silence and new-sale notifications. UI fixtures are invented test data and never used in production. Live SQL itself can be checked by comparing the endpoint to:

```sql
SELECT Currency,COUNT_BIG(*) AS Tratos,SUM(CONVERT(bigint,Price)) AS Volumen
FROM dbo.PanicMarketPublicSales GROUP BY Currency;
```
