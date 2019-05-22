<?php

#----------------------------------------------------------------------
# Script for generating documentation page for transformation rules
# from the REDCap-ETL markdown documentation
#----------------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    die('Not allowed.');
}

require_once 'vendor/autoload.php';

$trFile = __DIR__.'/vendor/iu-redcap/redcap-etl/docs/TransformationRulesGuide.md';

$fileContents = file_get_contents($trFile);


$parsedown = new Parsedown();
$content = $parsedown->text($fileContents);
$content = str_replace('<table>', '<table class="dataTable">', $content);
$content = str_replace('<h1>Transformation Rules</h1>', '<h1>REDCap-ETL Transformation Rules</h1>', $content);

$html = "<?php\n"
    ."require_once APP_PATH_DOCROOT . 'Config/init_global.php';\n"
    ."\n"
    ."# THIS PAGE WAS AUTO-GENERATED\n"
    ."\n"
    .'$htmlPage = new HtmlPage();'."\n"
    .'$htmlPage->PrintHeaderExt();'."\n"
    ."?>\n"
    .'<div style="text-align:right;float:right;">'
    .'<img src="<?php echo APP_PATH_IMAGES."redcap-logo.png"; ?>" alt="REDCap"/>'
    .'</div>'."\n"
    ."<?php // phpcs:disable ?>\n"
    .$content
    ."<?php // phpcs:enable ?>\n"
    ."\n"
    .'<style type="text/css">#footer { display: block; }</style>'."\n"
    .'<?php'."\n".'$htmlPage->PrintFooterExt();'."\n";




#print "{$contents}\n";

$outputFile = __DIR__.'/web/transformation_rules.php';
file_put_contents($outputFile, $html);

print "\nOutput stored in {$outputFile}.\n\n";
