CREATE VIEW dbo.MUPanicFracturaWeekHistoryV34 AS
SELECT CONVERT(varchar(10),WeekStart,23) AS WeekStart,CloseUnix,NexoPoints,CaosPoints,WinnerFamily FROM dbo.MUPanicWeeksV31;
