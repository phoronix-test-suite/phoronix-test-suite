<?php

if(!extension_loaded("gtk"))
{
	dl("php_gtk2.so");
}

$glade = new GladeXML("pts-core/gui/pts-gui.glade");

$glade->signal_autoconnect();

$window = $glade->get_widget("pts_main_window");
$window->connect_simple("destroy", array("Gtk", "main_quit"));

$data = array(
array('<b>gtkperf</b> <span color="#737373">(test)       Result name: <b>gtk2</b>, test run name: <b>run 1</b>\n</span><span color="#999999"><small>GtkPerf<small><sup>v0.40</sup></small>: <b>931.06s</b></small></span>'),
array('<b>audio-encoding</b> <span color="#737373">(suite)       Result name: <b>testingaudio</b>, test run name: <b>test1</b>\n</span><span color="#999999"><small><b><span color="#737373">test1</span></b>: LAME MP3 Encoding<small><sup>v3.97</sup></small>: <b>35.75s</b>, Ogg Encoding<small><sup>v1.2.0</sup></small>: <b>18.57s</b>, FLAC Audio Encoding<small><sup>v1.2.1</sup></small>: <b>17.43s</b></small></span>'));

$field_header = array('Name');

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
    
for($row = 0; $row < count($data); ++$row)
{
	$values = array();

	for($col = 0; $col < count($data[$row]); ++$col)
	{
		$values[] = $data[$row][$col];
        }
	$model->append($values); // note 6 
}

$window->show();
$view->show();
Gtk::main();

?>
