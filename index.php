<?php

include 'DigiGold.php';

$dg = new DigiGold();
$lastAnalyze = $dg->getLastAnalyze();

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
    <script src="./assets/bootstrap/js/bootstrap.min.js"></script>
    <style>
        * {
            font-family: Sahel;
        }

        .max-50px {
            max-width: 50px;
            max-height: 50px;
        }

        .border-bottom-width-half {
            border-bottom-width: 0.15rem !important;
        }

        .nav-link {
            border-color: rgb(222, 226, 230) !important;
        }

        .nav-link.active {
            color: #fff !important;
            background-color: RGBA(var(--bs-primary-rgb), var(--bs-bg-opacity, 1)) !important;
        }
    </style>
</head>

<body dir="rtl">

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-2">
            </div>
            <div class="col-sm-8 mt-3">

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <?php foreach ($lastAnalyze['variants_ayar'] as $ayar => $variants) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $ayar == 18 ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-<?= crc32($ayar) ?>" role="tab" aria-controls="tab-<?= crc32($ayar) ?>" aria-selected="true">
                                عیار <?= $ayar ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <div class="tab-content border-x p-0">
                    <?php foreach ($lastAnalyze['variants_ayar'] as $ayar => $variants) { ?>
                        <div class="tab-pane fade <?= $ayar == 18 ? 'show active' : '' ?>" id="tab-<?= crc32($ayar) ?>" role="tabpanel">
                            <table class="table table-bordered table-sm">
                                    <tbody>
                                        <?php foreach (array_slice($variants, 0, 20) as $variant) { ?>
                                            <tr>
                                                <td rowspan="2" class="text-center align-middle">
                                                    <img src="<?= $variant['image'] ?>" class="max-50px">
                                                </td>
                                                <td colspan="7">
                                                    <a class="text-decoration-none" target="_blank" href="<?= $variant['url'] ?>">
                                                        <?= $variant['title_fa'] ?>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr class="border-bottom-width-half">
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
                    <?php } ?>
                </div>
            </div>
            <div class="col-sm-2">
            </div>
        </div>
    </div>

</body>

</html>