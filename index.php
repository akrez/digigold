<?php

include 'DigiGold.php';

$dg = new DigiGold(0, 170_000_000_0, 7);

// header('content-type: application/json');
// die(json_encode($dg->getLastAnalyze()));
?>


<!doctype html>
<html class="h-100" lang="fa">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"><!-- CSRF Token -->
    <link rel="shortcut icon" href="">
</head>

<body dir="rtl">
    <div style="display: flex; align-items: flex-start;">

        <?php foreach ($dg->getLastAnalyze() as $ayar => $ayarRows) { ?>
            <table>
                <thead>
                    <tr>
                        <th colspan="999">
                            <?= $ayar ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($ayarRows, 0, 20) as $row) { ?>
                        <tr>
                            <td>
                                <img src="<?= $row['image'] ?>" style="max-width: 128px; max-height: 128px;">
                            </td>
                            <td>
                                <a target="_blank" href="<?= $row['url'] ?>"><?= $row['title_fa'] ?></a>
                                <div><?= $row['seller_title'] ?></div>
                                <div><?= $row['size'] ?></div>
                                <div><?= $row['_selling_price_formatted'] ?></div>
                                <div><?= $row['_price_per_gram_formatted'] ?></div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

    </div>
</body>

</html>