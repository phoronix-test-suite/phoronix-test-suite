<?php

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-extra.php");

if (!extension_loaded("gtk")) {
    dl("php_gtk2.so");
}

$glade = new GladeXML("pts-core/gui/pts-gui.glade");

$glade->signal_autoconnect();

setup_window($glade, &$window, &$view, &$scrolledwindow2);

// Show the window and our view
$window->show();
$view->show();
Gtk::main();

function setup_window($glade, &$window, &$view, &$scrolledwindow2)
{
    $window = $glade->get_widget("pts_main_window");
    $window->connect_simple("destroy", array("Gtk", "main_quit"));

    $field_header = array('Name');
    
    if (defined("GObject::TYPE_STRING")) {
        $model = new GtkListStore(GObject::TYPE_STRING);
    } else {
        $model = new GtkListStore(Gtk::TYPE_STRING);
    }

	// Grab our window from .glade, create the view with the model, and stick
	// the view into the scrolledwindow.
    $scrolledwindow2 = $glade->get_widget('scrolledwindow2');
    $view = new GtkTreeView($model);
    $scrolledwindow2->add($view);

    // Some settings for the treeview
    // Hide the treeview header
    $view->set_headers_visible(false);
    // Enable multiple selection via ctrl+, shift+click
    $view->get_selection()->set_mode(Gtk::SELECTION_MULTIPLE);
    // Enable multiple selection via click and drag
    $view->set_rubber_banding(true);
    // Set the tooltips to be the first column
    $view->set_property("tooltip-column", 0);

    // Create our columns in the model. We'll have just one for now, but we should add more
    for ($col = 0; $col < count($field_header); ++$col) {
        $cell_renderer = new GtkCellRendererText();
        $column = new GtkTreeViewColumn($field_header[$col], $cell_renderer, "markup", $col);
        $view->append_column($column);
    }

    // Now fill up the rows!
    populate_model(&$model);

}

function populate_model(&$model)
{

    foreach(glob(XML_PROFILE_DIR."*.xml") as $benchmark_file) {
        $xml_parser = new tandem_XmlReader($benchmark_file);
        $name = $xml_parser->getXMLValue(P_TEST_TITLE);
        $license = $xml_parser->getXMLValue(P_TEST_LICENSE);
        $status = $xml_parser->getXMLValue(P_TEST_STATUS);
        $identifier = basename($benchmark_file, ".xml");

        // This doesn't work for some reason
        $test_app_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);

        // I'd like to have the test description too, please.
        $description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);

        if (is_file(TEST_ENV_DIR . $benchmark_file."/pts-install.xml"))
            $installed = "installed";
        else
            $installed = "not installed";

        // I think this should be removed from the gui
        if (defined("PTS_DEBUG_MODE")) {
            $version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
            $test_download_size = $xml_parser->getXMLValue(P_TEST_DOWNLOADSIZE);
            $test_environment_size = $xml_parser->getXMLValue(P_TEST_ENVIRONMENTSIZE);
            $test_maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);

            printf("%-18ls %-6ls %-12ls %-12ls %-4ls %-4ls %-22ls\n", $identifier, $version,
                   $status, $license, $test_download_size, $test_environment_size,
                   $test_maintainer);
        } else {
            if (!in_array($status, array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED"))) {
                //printf("%-18ls - %-30ls [Status: %s, License: %s]\n", $identifier, $name, $status, $license);
                $info =
                    "<b>$name</b> <span color=\"#737373\">(<b>$test_app_type</b> test, <b>$installed</b>)\n</span><span color=\"#999999\"><small>$description</small></span>";

                // LOOK HERE. We append the line to the model, so it becomes another row. 
                // So yes, adding another row with data is this easy.
                $model->append(array($info));
            }
        }
    }

}



?>
