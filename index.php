<?php

include 'DigiGold.php';

$dg = new DigiGold();

// header('content-type: application/json');
// die(json_encode($dg->getLastAnalyze()));
?>
<!doctype html>
<html class="h-100" lang="fa">

<head>
    <title>Akrez DigiGold</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./favicon.ico">
    <link href="./assets/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="./assets/sahel/css/sahel.css" rel="stylesheet">
    <style>
        * {
            font-family: Sahel;
        }

        .max-50px {
            max-width: 50px;
            max-height: 50px;
        }

        .border-top-width-half {
            border-top-width: 0.15rem !important;
        }
    </style>
</head>

<body dir="rtl">

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-2">
            </div>
            <div class="col-sm-8 mt-3">
                <?php foreach ($dg->getLastAnalyze()['variants_ayar'] as $ayar => $variants) { ?>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr class="border-top-width-half">
                                            <th colspan="8" class="text-center table-info">عیار <?= $ayar ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($variants, 0, 20) as $variant) { ?>
                                            <tr class="border-top-width-half">
                                                <td rowspan="2" class="text-center align-middle">
                                                    <img src="<?= $variant['image'] ?>" class="max-50px">
                                                </td>
                                                <td colspan="7">
                                                    <a class="text-decoration-none" target="_blank" href="<?= $variant['url'] ?>">
                                                        <?= $variant['title_fa'] ?>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><?= $variant['seller_title'] ?></td>
                                                <td class="table-secondary fw-bold">وزن</td>
                                                <td class="font-monospace"><?= $variant['size'] ?></td>
                                                <td class="table-secondary fw-bold">قیمت هر گرم</td>
                                                <td class="font-monospace"><?= $variant['_price_per_gram_formatted'] ?></td>
                                                <td class="table-secondary fw-bold">قیمت</td>
                                                <td class="font-monospace"><?= $variant['_selling_price_formatted'] ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="col-sm-2">
            </div>
        </div>
    </div>

</body>

</html>