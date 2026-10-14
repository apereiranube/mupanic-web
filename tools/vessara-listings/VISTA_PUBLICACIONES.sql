/* MUPANIC_VESSARA_PUBLIC_LISTINGS_V2
   Public storefront projection: nine read-only fields only.
   Listing source is PanicMarketPublishedProbe (Zen/Eryns), not legacy MarketCoin.
   No inventories, item bytes, serials or private account identities are selected.
*/
ALTER VIEW dbo.PanicMarketPublicListings
AS
SELECT
    p.ListingID AS ListingID,
    j.CharacterName AS Seller,
    REPLACE(REPLACE(REPLACE(
        COALESCE(c.ItemName, 'Objeto #'+CONVERT(varchar(12),item.ItemIndex)),
        '|',' '),CHAR(13),' '),CHAR(10),' ') AS Item,
    item.ItemIndex AS ItemIndex,
    ((CONVERT(int,SUBSTRING(j.ItemCode,2,1))/8)&15) AS [Level],
    p.Currency AS Currency,
    CONVERT(bigint,p.Price) AS Price,
    p.Expires AS ExpiresAt,
    promotion.FeaturedUntil AS FeaturedUntil,
    /* Real S6 option flags are normalized here. Binary ItemCode is private. */
    CONVERT(int,SUBSTRING(j.ItemCode,3,1)) AS Durability,
    CASE WHEN (CONVERT(int,SUBSTRING(j.ItemCode,2,1)) & 128)<>0 THEN 1 ELSE 0 END AS SkillFlag,
    CASE WHEN (CONVERT(int,SUBSTRING(j.ItemCode,2,1)) & 4)<>0 THEN 1 ELSE 0 END AS LuckFlag,
    ((CONVERT(int,SUBSTRING(j.ItemCode,2,1)) & 3)*4
       + CASE WHEN (CONVERT(int,SUBSTRING(j.ItemCode,8,1)) & 64)<>0 THEN 16 ELSE 0 END) AS AdditionalOption,
    (CONVERT(int,SUBSTRING(j.ItemCode,8,1)) & 63) AS ExcellentMask,
    CONVERT(int,SUBSTRING(j.ItemCode,9,1)) AS AncientCode,
    CONVERT(int,SUBSTRING(j.ItemCode,11,1)) AS HarmonyCode,
    CASE WHEN (CONVERT(int,SUBSTRING(j.ItemCode,10,1)) & 8)<>0 THEN 1 ELSE 0 END AS Option380Flag,
    CONVERT(int,SUBSTRING(j.ItemCode,12,1)) AS Socket1,
    CONVERT(int,SUBSTRING(j.ItemCode,13,1)) AS Socket2,
    CONVERT(int,SUBSTRING(j.ItemCode,14,1)) AS Socket3,
    CONVERT(int,SUBSTRING(j.ItemCode,15,1)) AS Socket4,
    CONVERT(int,SUBSTRING(j.ItemCode,16,1)) AS Socket5,
    (SELECT COUNT_BIG(*) FROM dbo.PanicMarketLotItem li WHERE li.RequestID=p.RequestID) AS LotCount
FROM dbo.PanicMarketPublishedProbe AS p
INNER JOIN dbo.PanicMarketPersistentProbe AS j
    ON j.RequestID = p.RequestID
CROSS APPLY (
    SELECT CONVERT(int,SUBSTRING(j.ItemCode,1,1))
      +(CONVERT(int,SUBSTRING(j.ItemCode,8,1))&128)*2
      +(CONVERT(int,SUBSTRING(j.ItemCode,10,1))&240)*32 AS ItemIndex
) AS item
OUTER APPLY (
    SELECT TOP (1) c2.ItemName
    FROM dbo.PanicMarketBrowseCatalog AS c2
    WHERE c2.ItemIndex = item.ItemIndex
    ORDER BY c2.ItemName
) AS c
OUTER APPLY (
    SELECT MAX(s.Expires) AS FeaturedUntil
    FROM dbo.PanicMarketPromotion AS s
    WHERE s.ListingID = p.ListingID
      AND s.AccountID = j.AccountID
      AND s.Expires > SYSDATETIME()
) AS promotion
WHERE j.State = 'STORED'
  AND p.Expires > SYSDATETIME()
  AND p.Currency IN (0,1)
  AND p.Price BETWEEN 1 AND 2000000000
  AND DATALENGTH(j.ItemCode) = 16
  AND NOT EXISTS (
      SELECT 1
      FROM dbo.PanicMarketBuyProbe AS purchase
      WHERE purchase.CustodyID = j.RequestID
  );
