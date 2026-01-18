<?php

include 'DigiGold.php';

$dg = new DigiGold(0, 170_000_000_0, 7);

// header('content-type: application/json');
// die(json_encode($dg->getLastAnalyze()));
?>

<?php

foreach ($dg->getLastAnalyze() as $ayar => $ayarContent) {
    var_export(array_slice($ayarContent,0,20));
}