CREATE VIEW dbo.MUPanicFracturaWeekCurrentV34 AS
SELECT CONVERT(varchar(10),cal.WeekStart,23) AS WeekStart,
 DATEDIFF(second,CONVERT(datetime2,'19700101',112),SYSUTCDATETIME()) AS NowUnix,
 limits.StartUnix AS StartUnix,limits.CloseUnix AS CloseUnix,s.MinimumQuests AS MinimumQuests,s.BossPoints AS BossPoints,s.Enabled AS Enabled,
 CASE WHEN w.WeekStart IS NULL THEN 0 ELSE 1 END AS IsClosed,
 COALESCE(w.NexoPoints,(SELECT COUNT_BIG(*) FROM [MuOnline43].[dbo].[MUPanicFracturaKillVictoriesV6] v WHERE v.IsTest=0 AND v.WinnerFamily=1 AND v.DeathUnix>=limits.StartUnix AND v.DeathUnix<limits.CloseUnix)*CONVERT(bigint,s.BossPoints)) AS NexoPoints,
 COALESCE(w.CaosPoints,(SELECT COUNT_BIG(*) FROM [MuOnline43].[dbo].[MUPanicFracturaKillVictoriesV6] v WHERE v.IsTest=0 AND v.WinnerFamily=2 AND v.DeathUnix>=limits.StartUnix AND v.DeathUnix<limits.CloseUnix)*CONVERT(bigint,s.BossPoints)) AS CaosPoints
FROM [MuOnline43].[dbo].[MUPanicWeekSettingsV31] s
CROSS APPLY(SELECT DATEADD(hour,-3,SYSUTCDATETIME()) AS ArgNow) clock
CROSS APPLY(SELECT DATEADD(day,-(DATEDIFF(day,CONVERT(date,'19000101',112),clock.ArgNow)%7),CONVERT(date,clock.ArgNow)) AS WeekStart) cal
CROSS APPLY(SELECT DATEDIFF(second,CONVERT(datetime2,'19700101',112),DATEADD(hour,3,CONVERT(datetime2,cal.WeekStart))) AS StartUnix,
 DATEDIFF(second,CONVERT(datetime2,'19700101',112),DATEADD(hour,3,DATEADD(minute,s.CloseMinute,DATEADD(day,6,CONVERT(datetime2,cal.WeekStart))))) AS CloseUnix) limits
LEFT JOIN [MuOnline43].[dbo].[MUPanicWeeksV31] w ON w.WeekStart=cal.WeekStart
WHERE s.Id=1 AND cal.WeekStart>=s.EffectiveStart;
