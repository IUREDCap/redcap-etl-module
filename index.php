<?php

require_once __DIR__.'/Configuration.php'; 


$redCapEtl = new \IU\RedCapEtlModule\RedCapEtlModule();

#$header = $redCapEtl->getProjectHeader();

#echo $header;

# include APP_PATH_DOCROOT . 'ProjectGeneral/header.php'; 

#----------------------------------
# How to add to <head>
#----------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php'; 
$buffer = ob_get_clean();
$buffer = str_replace('</head>', "\n<!-- my comment -->\n</head>", $buffer);
echo $buffer;

?>



<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>
<!-- </h4> -->


<?php


$configurationNames = $redCapEtl->getUserConfigurationNames();

$selfUrl   = $redCapEtl->getUrl(basename(__FILE__));
$configUrl = $redCapEtl->getUrl("configure.php");
$runUrl    = $redCapEtl->getUrl("run.php");

?>

<?php $redCapEtl->renderUserTabs($selfUrl); ?>

<table class="dataTable">
<thead>
<tr class="hrd"> <th>Configuration Name</th> <th>Configure</th> <th>Run</th> <th>Delete</th> </tr>
</thead>
<tbody>
<?php 
    $row = 1;
    foreach ($configurationNames as $configurationName) {
        if ($row % 2 === 0) {
            print '<tr class="even">'."\n";
        } else {
            print '<tr class="odd">'."\n";
        }
        $configureUrl = $configUrl.'&configName='.$configurationName;
        $runConfigurationUrl = $runUrl.'&configName='.$configurationName;
        print "<td>{$configurationName}</td>\n";
        print '<td style="text-align:center;">'
            .'<a href="'.$configureUrl.'"><img src='.APP_PATH_IMAGES.'gear.png></a>'
            ."</td>\n";
        print '<td style="text-align:center;">'
            .'<a href="'.$runConfigurationUrl.'"><img src='.APP_PATH_IMAGES.'application_go.png></a>'
            ."</td>\n";
        print '<td style="text-align:center;">'
            .'<a id="delete'.$row.'" href="'.$selfUrl.'"><img src="'.APP_PATH_IMAGES.'delete.png" /></a>'
            ."</td>\n";

        print "</tr>\n";
        $row++;
    }
?>
</tbody>
</table>


<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


