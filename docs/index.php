<?php
$doc_files[] = "template_manager";
//list other documentation files here without the .md 

require("Parsedown.php");

$font_style_css = "<style> body { font-family: \"Verdana\", \"Geneva\", Sans-serif }</style>";
echo $font_style_css;

$Parsedown = new Parsedown();
$doc = $_GET["doc"];

if(in_array($doc, $doc_files)) {
    $contents = file_get_contents($doc.".md");
    echo $Parsedown->text($contents);
} else {
    echo "Bad URL";
}
?>
