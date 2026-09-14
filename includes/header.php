<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Rental Mobil PT. Wildan Abadi Jaya - Layanan Rental Mobil Terpercaya">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . " | PT. Wildan Abadi Jaya" : "Rental Mobil PT. Wildan Abadi Jaya" ?></title>
    <link rel="icon" href="/rental-mobil/assets/img/logo.png" type="image/png">
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS Global -->
    <link href="/rental-mobil/assets/css/style.css" rel="stylesheet">
    <?php if (isset($extra_css)): ?>
        <link href="/rental-mobil/assets/css/<?= htmlspecialchars($extra_css) ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
