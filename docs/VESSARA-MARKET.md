# El Sello de Vessara

Dedicated public page at `market/` in the beta template. Mercado follows El servidor in the shared navigation. Uses the existing WebEngine `Connection::Database('MuOnline')` alias to read physical `MuOnline43`. No VPS install, process hooks, firewall changes, SQL migrations, scheduled tasks, balance mutations or trading operations.

## Public data

Three fixed SELECT queries read the existing market ledger. Each RequestID is one purchase. Included states: DELIVERY_SAVED, CREDIT_ATTEMPTED, CLOSED, with non-null Delivered. Count the sale when delivery is confirmed, including pending seller payout. Exclude PREPARED, PAYMENT_ATTEMPTED, PAID and INSERT_ATTEMPTED. Do not infer sales from a closed custody, because cancellation also closes custody. Old MarketPlace/MarketCoin transactions are not Zen/Eryns sales and are excluded.

Metrics: delivered trades today; lifetime gross Zen and Eryns purchase volumes; latest delivered item. The daily chart includes zero days in the last seven days. Recent table has the last 40 purchases, item name/level, character names, price/currency and delivery date. Search and currency filters apply to these 40 rows; summary counters remain market-wide.

Volumes use bigint SQL SUM and decimal strings end-to-end; JS uses BigInt formatting. They represent trading turnover, not current currency supply. Never combine Zen and Eryns into a single monetary total. One purchase counts once regardless of subsequent collection. Existing test sales are included in beta totals; no history is deleted/reset.

Data source tables: PanicMarketBuyProbe, PanicMarketPersistentProbe, PanicMarketBrowseCatalog. ItemIndex is decoded using the already verified game codec. Snapshot excludes account identifiers, serials, item codes, inventory blobs, balances, fees and connection errors. HTML is escaped and JS uses textContent. SQL has no user parameters because filters work on the public recent snapshot.

Server timestamps are existing local Argentina dates, matching the VPS. Today uses GETDATE on that same server; displayed dates carry -03:00. If the VPS timezone changes, update timestamp normalization.

## Availability

Read-only public `market/?market_data=1` returns the snapshot (503 when unavailable). Shared cache lives in WebEngine __PATH_CACHE__, separate from existing caches, for 60 seconds. Cache write is atomic. Failed refresh retains up to 24 hours of old data with a visible stale status; older/missing data becomes unknown, not zero. Browser refreshes every minute while visible, preserves filters/search and aborts after 10 seconds. No external collector or new listener port.

## Deploy / verify

Commit to beta, cPanel Update from Remote then Deploy HEAD Commit using existing .cpanel.yml. Existing deployment copies the entire template overlay. No new module/core route is needed: index.php renders market before native module dispatch, like the existing info custom route.

Check `/beta/market/` and `/beta/market/?market_data=1`. Query permission for the three ledger/catalog tables must match the existing web database login. If unavailable, inspect permissions on VPS; never expose SQL credentials publicly. After one real purchase, wait one minute and check table/volumes; payout must not add another sale. Cancelling a listing must not add a sale. Compare with SQL:

```sql
SELECT Currency, COUNT_BIG(*) AS Tratos, SUM(CONVERT(bigint,Price)) AS Volumen
FROM dbo.PanicMarketBuyProbe
WHERE Delivered IS NOT NULL AND State IN ('DELIVERY_SAVED','CREDIT_ATTEMPTED','CLOSED')
GROUP BY Currency;
```

PHP tests mock only the existing DB adapter and validate projection, empty/error/cache states, precision and rendering. They do not claim live SQL or browser verification. GitHub Actions runs the same tests on pushes. Local browser QA unavailable in this session.

## Public SQL projection and visual update
Install `MU_PANIC_VESSARA_WEB_DATOS_V1/INSTALAR.ps1` on the VPS before deploying this revision. It creates `dbo.PanicMarketPublicSales` with nine public fields and grants SELECT on this view to existing database users. It does not grant access to underlying tables. Only confirmed delivered purchases are counted. No GameServer restart is required. Website SQL still uses its existing MuOnline connection and MuOnline43 database. Deployment does not by itself prove that the hosting connection can reach SQL; verify `market/?market_data=1` returns HTTP 200 after installation.

The illustrated hero follows the in-game NPC_421 reference (navy dress, black hair, small feathered hat). The treasury artwork is decorative; all displayed economic values and chart bars come from the ledger. Assets are WebP; the embossed seal is native SVG. Numeric updates use BigInt interpolation over 800ms, only when the value changes. Initial values render directly, unknown values remain dashes, and reduced-motion disables counting. Formatting never converts economic totals into floating point.
