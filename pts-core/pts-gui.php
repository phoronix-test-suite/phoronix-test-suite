<?php

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-extra.php");

$saved_results = array();
foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $benchmark_file)
{
	$xml_parser = new tandem_XmlReader($benchmark_file);
	$title = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
	$suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
	$raw_results = $xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);
	$results_xml = new tandem_XmlReader($raw_results[0]);
	$identifiers = $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER);

	if(!empty($title))
	{
		$info = "<b>$suite</b> as $title <span color=\"#737373\">";

		foreach($identifiers as $id)
			$info .= "- " . $id;

		$info .= "</span>";

		array_push($saved_results, array($info));
	}
}

if(!extension_loaded("gtk"))
{
	dl("php_gtk2.so");
}

$glade = new GladeXML("pts-core/gui/pts-gui.glade");

$glade->signal_autoconnect();

$window = $glade->get_widget("pts_main_window");
$window->connect_simple("destroy", array("Gtk", "main_quit"));


$field_header = array("Name");

if(defined("GObject::TYPE_STRING"))
{
	$model = new GtkListStore(GObject::TYPE_STRING);
}
else
{
	$model = new GtkListStore(Gtk::TYPE_STRING);
}
   
$scrolledwindow2 = $glade->get_widget('scrolledwindow2');
    
$view = new GtkTreeView($model);
$scrolledwindow2->add($view);
    
$view->set_headers_visible(false);
// $view->set_rubber_banding(true);
$view->get_selection()->set_mode(Gtk::SELECTION_MULTIPLE);
$view->set_property("tooltip-column", 0);
    
for($col = 0; $col < count($field_header); ++$col)
{
	$cell_renderer = new GtkCellRendererText();
	$column = new GtkTreeViewColumn($field_header[$col], $cell_renderer, "markup", $col);
	$view->append_column($column);
}
    
for($row = 0; $row < count($saved_results); ++$row)
{
	$values = array();

	for($col = 0; $col < count($saved_results[$row]); ++$col)
	{
		$values[] = $saved_results[$row][$col];
        }
	$model->append($values);
}

$window->show();
$view->show();
Gtk::main();

?>
