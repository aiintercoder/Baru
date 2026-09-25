<?php
// Cadangan bila mod_rewrite tidak aktif: arahkan ke folder public/.
header('Location: public/index.php');
exit;
