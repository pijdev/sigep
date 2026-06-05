<?php
use Config\App;
?>

<head>
  <meta charset="<?php echo App::APP_ENCODING; ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo App::APP_NAME_SHORT; ?> | <?php echo DATE('Y'); ?></title>
  
  <!-- CSS Nativo -->
  <link rel="stylesheet" href="/dist/style.css">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">

  <!-- JS Nativo -->
  <script type="module" src="/dist/main.js"></script>
</head>
