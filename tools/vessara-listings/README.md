MU PANIC · Vessara: vidriera web

El historial de ventas usa dbo.PanicMarketPublicSales. Las publicaciones activas usan una nueva vista PUBLICA y SOLO LECTURA en MuOnline43: dbo.PanicMarketPublicListings.

Contrato requerido por el PHP web: ListingID, Seller, Item, ItemIndex, Level, Currency (0 Zen / 1 Eryns), Price (bigint), ExpiresAt y FeaturedUntil (datetime nullable).

La vista debe devolver UNICAMENTE publicaciones en estado activo; nunca vendidas, canceladas ni vencidas. El PHP revalida ExpiresAt y marca destacado solo si FeaturedUntil > GETDATE(). La pagina no permite comprar, cancelar ni activar promociones desde la web.

Se requiere DIAGNOSTICO_VPS.ps1 para verificar nombres y columnas REALES antes de redactar la vista SQL. No inventar el estado, ID ni fecha del destaque. El diagnostico no lee filas privadas ni cambia SQL, GS, cliente o cPanel.

Comisiones en el juego: 5% normal, 8% total destacada (5%+3%) si se vende. Duracion destaque: hasta 24h.

Web beta: /market/; PRUEBA antes de subir a main, sin duplicar la informacion tecnica del Atlas.
