<?php
$currentPath = __DIR__.DIRECTORY_SEPARATOR;
$analyzeFilePath = $currentPath.'index.json';
$analyze = json_decode(file_get_contents($analyzeFilePath), true);
$analyze['items'] = array_filter($analyze['items']);
$firstCaratKey = key($analyze['items']);
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
                    <?php foreach ($analyze['items'] as $carat => $items) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $carat == $firstCaratKey ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-<?= crc32($carat) ?>" role="tab" aria-controls="tab-<?= crc32($carat) ?>" aria-selected="true">
                                عیار <?= $carat ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <div class="tab-content border-x px-0 pt-3">
                    <?php foreach ($analyze['items'] as $carat => $items) { ?>
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
                                    foreach (array_slice($items, 0, 20) as $itemIndex => $item) {
                                        $rowTheme = ($itemIndex % 2 ? ' table-secondary ' : '');
                                        ?>
                                        <tr class="<?= $rowTheme ?>">
                                            <td rowspan="2" class="text-center p-0"><img src="<?= $item['image'] ?>" class="max-50px"></td>
                                            <td colspan="3">
                                                <a class="text-decoration-none" target="_blank" href="<?= $item['url'] ?>">
                                                    <?= $item['title'] ?>
                                                </a>
                                            </td>
                                            <td><?= $item['source'] ?></td>
                                        </tr>
                                        <tr class="<?= $rowTheme ?>">
                                            <td class="font-monospace"><?= $item['pricePerGram']['f'] ?></td>
                                            <td class="font-monospace"><?= $item['price']['f'] ?></td>
                                            <td class="font-monospace"><?= $item['size'] ?></td>
                                            <td><?= $item['seller'] ?></td>
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