ALTER VIEW dbo.PanicMarketPublicSales
AS
SELECT b.RequestID AS SaleID,b.BuyerName AS Buyer,c.CharacterName AS Seller,
 COALESCE(n.ItemName,'Objeto #'+CONVERT(varchar(10),k.ItemIndex)) AS Item,
 k.ItemIndex,(CONVERT(int,SUBSTRING(c.ItemCode,2,1))/8)&15 AS Level,
 b.Currency,b.Price,b.Delivered AS SoldAt
FROM dbo.PanicMarketBuyProbe b
JOIN dbo.PanicMarketPersistentProbe c ON c.RequestID=b.CustodyID
CROSS APPLY(SELECT CONVERT(int,SUBSTRING(c.ItemCode,1,1))+(CONVERT(int,SUBSTRING(c.ItemCode,8,1))&128)*2+(CONVERT(int,SUBSTRING(c.ItemCode,10,1))&240)*32 AS ItemIndex) k
LEFT JOIN dbo.PanicMarketBrowseCatalog n ON n.ItemIndex=k.ItemIndex
WHERE b.Delivered IS NOT NULL AND b.State IN ('DELIVERY_SAVED','CREDIT_ATTEMPTED','CLOSED');
