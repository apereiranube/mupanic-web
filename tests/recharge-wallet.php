<?php
define('access',true);
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-wallet.php';
class Connection {
    public static $rows=[]; public static $calls=0; public static $throws=false;
    public static function Database($alias) {
        if($alias!=='MuOnline') throw new RuntimeException('Unexpected CMS alias');
        return new self;
    }
    public function query_fetch($sql,$params) {
        self::$calls++;
        if($sql!=='SELECT [WCoinC] AS [balance] FROM [MuOnline43].[dbo].[CashShopData] WHERE [AccountID] = ?' || $params!==['TestAcct']) throw new RuntimeException('Unsafe query');
        if(self::$throws) throw new RuntimeException('Fixture connection failure');
        return self::$rows;
    }
}
function expectBalance($rows,$expected) {
    Connection::$rows=$rows;
    if(panicRechargeWalletBalance('TestAcct')!==$expected) throw new RuntimeException('Wrong balance result');
}
expectBalance([['balance'=>0]],0);
expectBalance([['balance'=>'0']],0);
expectBalance([['balance'=>1234]],1234);
expectBalance([['balance'=>'2147483647']],2147483647);
foreach([[],false,null,[['balance'=>-1]],[['balance'=>null]],[['balance'=>'2147483648']],[['balance'=>'1e3']],[['balance'=>1.5]],[['balance'=>1],['balance'=>1]]] as $rows) expectBalance($rows,null);
$calls=Connection::$calls;
if(panicRechargeWalletBalance("acct' OR 1=1")!==null || Connection::$calls!==$calls) throw new RuntimeException('Invalid account reached SQL');
Connection::$throws=true;
if(panicRechargeWalletBalance('TestAcct')!==null) throw new RuntimeException('Error rendered as saldo');
echo "Read-only wallet mapping tests passed: valid zero, unavailable, bounds, parameterization.\n";
