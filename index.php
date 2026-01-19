<?php

include 'DigiGold.php';

$dg = new DigiGold();

// header('content-type: application/json');
// die(json_encode($dg->getLastAnalyze()));
?>
<!doctype html>
<html class="h-100" lang="fa">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="./favicon.ico">
</head>

<body dir="rtl">
    <div style="display: flex; align-items: flex-start;">

        <?php foreach ($dg->getLastAnalyze()['variants_ayar'] as $ayar => $variants) { ?>
            <table>
                <thead>
                    <tr>
                        <th colspan="999">
                            <?= $ayar ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($variants, 0, 20) as $variant) { ?>
                        <tr>
                            <td>
                                <img src="<?= $variant['image'] ?>" style="max-width: 128px; max-height: 128px;">
                            </td>
                            <td>
                                <a target="_blank" href="<?= $variant['url'] ?>"><?= $variant['title_fa'] ?></a>
                                <div><?= $variant['seller_title'] ?></div>
                                <div><?= $variant['size'] ?></div>
                                <div><?= $variant['_selling_price_formatted'] ?></div>
                                <div><?= $variant['_price_per_gram_formatted'] ?></div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

    </div>
</body>

</html>