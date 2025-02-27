<?php 
require './../vendor/autoload.php';

use Mclever\LarasuiSdk\SuiClient;

$suiClient = new SuiClient('https://fullnode.devnet.sui.io:443');
$balance = $suiClient->getBalance('0xd0c252b6e4a47af0fcc6e10a0e7203eb0272362ce53a7ea558e8e4cc2c4ca7c5');
print_r('Devnet Balance');
print_r($balance);

?>