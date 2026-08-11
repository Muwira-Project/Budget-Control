<?php

use Dompdf\Dompdf;
use Dompdf\Options;

require __DIR__.'/../vendor/autoload.php';

$source = __DIR__.'/panduan-penggunaan.html';
$target = __DIR__.'/Panduan-Penggunaan-myfinance.pdf';

$options = new Options;
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml((string) file_get_contents($source), 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

file_put_contents($target, $dompdf->output());

echo 'PDF generated: '.$target.' ('.filesize($target).' bytes)'.PHP_EOL;
