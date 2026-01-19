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
    <link href="./bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .max-50px {
            max-width: 50px;
            max-height: 50px;
        }
    </style>
</head>

<body dir="rtl">

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-2">
            </div>
            <div class="col-sm-8">
                <?php foreach ($dg->getLastAnalyze()['variants_ayar'] as $ayar => $variants) { ?>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info my-3 text-center h4">عیار <?= $ayar ?></div>
                            </div>
                        </div>
                        <?php foreach (array_slice($variants, 0, 20) as $variant) { ?>
                            <div class="row small">
                                <div class="col-12">
                                    <table class="table  table-bordered table-sm">
                                        <tbody>
                                            <tr>
                                                <td rowspan="2">
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
                                                <td><?= $variant['size'] ?></td>
                                                <td class="table-secondary fw-bold">قیمت هر گرم</td>
                                                <td><?= $variant['_price_per_gram_formatted'] ?></td>
                                                <td class="table-secondary fw-bold">قیمت</td>
                                                <td><?= $variant['_selling_price_formatted'] ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            <div class="col-sm-2">
            </div>
        </div>
    </div>

</body>

</html>