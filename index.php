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
                        <div class="alert alert-info my-3 text-center h4">عیار <?= $ayar ?></div>
                    </div>
                    <?php foreach (array_slice($variants, 0, 20) as $variant) { ?>
                    <div class="row small border p-1">
                        <div class="col-1">
                            <img src="<?= $variant['image'] ?>" class="img-fluid">
                        </div>
                        <div class="col-11">
                            <div>
                                <a class="text-decoration-none" target="_blank" href="<?= $variant['url'] ?>">
                                    <?= $variant['title_fa'] ?>
                                </a>
                                <span><?= $variant['seller_title'] ?></span>
                            </div>
                            <div><strong>وزن</strong> <?= $variant['size'] ?></div>
                            <div><strong>قیمت</strong> <?= $variant['_selling_price_formatted'] ?></div>
                            <div><strong>قیمت هر گرم</strong> <?= $variant['_price_per_gram_formatted'] ?></div>
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