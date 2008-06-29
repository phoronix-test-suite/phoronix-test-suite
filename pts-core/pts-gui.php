<?php

if(!extension_loaded("gtk"))
{
	dl("php_gtk2.so");
}

$glade = new GladeXML("pts-core/gui/pts-gui.glade");
$glade->signal_autoconnect();

$window = $glade->get_widget("pts_main_window");
$window->connect_simple("destroy", array("Gtk", "main_quit"));

$test_suite_list = new GtkListStore(64);
$test_suite_list->append(array("Test 1"));
$test_suite_list->append(array("Test 2"));

$glade->get_widget("run_tests_treeview")->set_model($test_suite_list);



$window->show();
Gtk::main();

?>
