<?php
$currentPath = __DIR__ . DIRECTORY_SEPARATOR;
$analyzeFilePath = $currentPath . 'index.json';
$analyze = json_decode(file_get_contents($analyzeFilePath), true);
$analyze['variants_carat'] = array_filter($analyze['variants_carat']);
$firstCaratKey = key($analyze['variants_carat']);
?><!doctype html>
<html class="h-100" lang="fa">

<head>
    <title>Akrez Digikala Gold</title>
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
            max-height: 38px;
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
            <div class="col-lg-2">
            </div>
            <div class="col-lg-8">
                <div class="alert alert-info my-3 text-center" dir="ltr">
                    <?= $analyze['date'] ?>
                </div>
            </div>
            <div class="col-lg-2">
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
            </div>
            <div class="col-lg-8">

                <ul class="nav nav-pills nav-fill" role="tablist">
                    <?php foreach ($analyze['variants_carat'] as $carat => $variants) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $carat == $firstCaratKey ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-<?= crc32($carat) ?>" role="tab" aria-controls="tab-<?= crc32($carat) ?>" aria-selected="true">
                                عیار <?= $carat ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <div class="tab-content border-x px-0 pt-3">
                    <?php foreach ($analyze['variants_carat'] as $carat => $variants) { ?>
                        <div class="tab-pane fade table-responsive <?= $carat == $firstCaratKey ? 'show active' : '' ?>" id="tab-<?= crc32($carat) ?>" role="tabpanel">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="bg-200 text-900 table-dark">
                                    <tr>
                                        <th></th>
                                        <th>قیمت هر گرم</th>
                                        <th>قیمت</th>
                                        <th>وزن</th>
                                        <th>فروشنده</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach (array_slice($variants, 0, 20) as $variantIndex => $variant) {
                                        $isDark = ($variantIndex % 2 == 1);
                                    ?>
                                        <tr class="<?= $isDark ? 'table-secondary' : '' ?>">
                                            <td rowspan="2" class="text-center p-0"><img src="<?= $variant['image'] ?>" class="max-50px"></td>
                                            <td colspan="4">
                                                <a class="text-decoration-none" target="_blank" href="<?= $variant['url'] ?>">
                                                    <?= $variant['title_fa'] ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr class="<?= $isDark ? 'table-secondary' : '' ?>">
                                            <td class="font-monospace"><?= $variant['_price_per_gram_formatted'] ?></td>
                                            <td class="font-monospace"><?= $variant['_selling_price_formatted'] ?></td>
                                            <td class="font-monospace"><?= $variant['size'] ?></td>
                                            <td><?= $variant['seller_title'] ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-lg-2">
            </div>
        </div>
    </div>

</body>

</html>