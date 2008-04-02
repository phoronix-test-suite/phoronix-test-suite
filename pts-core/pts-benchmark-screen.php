<?php

if(!extension_loaded("gtk"))
	dl("php_gtk2.so");

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-gtk.php");
require("pts-core/functions/pts-functions-run.php");

$BENCHMARK_IDENTIFIER = $argv[1];
$BENCHMARK_OPTIONS = array();

if(empty($BENCHMARK_IDENTIFIER))
	exit;

$BENCHMARK_NAME = pts_benchmark_identifier_to_name($BENCHMARK_IDENTIFIER);

if(empty($BENCHMARK_NAME))
	exit;

// Prepare The Parser
$BENCHMARK_XML = new tandem_XmlReader(file_get_contents(XML_PROFILE_LOCATION . "$BENCHMARK_IDENTIFIER.xml"));

$window = new GtkWindow();
$window->set_title("Phoronix Test Suite - " . $BENCHMARK_NAME);
$window->set_border_width(10);
$window->connect_simple("destroy", "gtk_shutdown");
$window->set_size_request(500, -1);

$window->add($vbox = new GtkVBox());

$pts_title = new GtkLabel("Phoronix Test Suite");
$pts_title->modify_font(new PangoFontDescription("FreeSans 21"));
$pts_title->modify_fg(Gtk::STATE_NORMAL, GdkColor::parse("#2b6b29"));
$pts_title->set_size_request(-1, 40);
$vbox->pack_start($pts_title, 0, 0);

$pts_benchmark_title = new GtkLabel($BENCHMARK_NAME);
$pts_benchmark_title->modify_font(new PangoFontDescription("FreeSans 16"));
$pts_benchmark_title->modify_fg(Gtk::STATE_NORMAL, GdkColor::parse("#AE0000"));
$pts_benchmark_title->set_size_request(-1, 24);
$vbox->pack_start($pts_benchmark_title, 0, 0);

// Select Benchmark Row
$benchmark_settings_name = $BENCHMARK_XML->getXMLArrayValues("PTSBenchmark/Settings/Option/DisplayName");
$benchmark_settings_argument = $BENCHMARK_XML->getXMLArrayValues("PTSBenchmark/Settings/Option/ArgumentName");
$benchmark_settings_identifier = $BENCHMARK_XML->getXMLArrayValues("PTSBenchmark/Settings/Option/Identifier");
$benchmark_settings_menu = $BENCHMARK_XML->getXMLArrayValues("PTSBenchmark/Settings/Option/Menu");

$hbox = array();
for($option_count = 0; $option_count < sizeof($benchmark_settings_name); $option_count++)
{
	$this_identifier = $benchmark_settings_identifier[$option_count];
	$hbox[$option_count] = new GtkHBox();
	$hbox[$option_count]->pack_start(new GtkLabel($benchmark_settings_name[$option_count] . ": "), 0, 0);
	$hbox[$option_count]->pack_start(new GtkLabel(" "), 0, 0);

	if(strlen($benchmark_settings_menu[$option_count]) > 6)
	{
		$xml_parser = new tandem_XmlReader($benchmark_settings_menu[$option_count]);
		$menu_option_names = $xml_parser->getXMLArrayValues("Entry/Name");

		// Benchmark Selection GtkComboBox
		$$this_identifier = new GtkComboBox();
		$list_model = new GtkListStore(Gtk::TYPE_STRING);
		$$this_identifier->set_model($list_model);
		$cell_renderer = new GtkCellRendererText();
		$$this_identifier->pack_start($cell_renderer);
		$$this_identifier->set_attributes($cell_renderer, "text", 0);

		// Load Up Benchmark List
		foreach($menu_option_names as $menu_item)
		{
		    $list_model->append(array($menu_item));
		}

		// Display
		$hbox[$option_count]->pack_start($$this_identifier, 0, 0);
	}
	else
	{
		
		$$this_identifier = new GtkTextView();
		$buffer[$option_count] = new GtkTextBuffer();
		$$this_identifier->set_buffer($buffer[$option_count]);
		$$this_identifier->set_size_request(120, -1);

		// Display
		$hbox[$option_count]->pack_start($$this_identifier, 0, 0);
	}

	$vbox->pack_start($hbox[$option_count], 0, 0);
	$vbox->pack_start(new GtkLabel(" "), 0, 0);

	array_push($BENCHMARK_OPTIONS, $this_identifier);
}

$vbox->pack_start(new GtkLabel(" "), 0, 0);

$submit_button = new GtkButton("Run Benchmark");
$submit_button->set_size_request(128, 28);
$submit_button->connect("clicked", "run_gtk_benchmark");
$vbox->pack_start($submit_button, 0, 0);

$window->show_all();
Gtk::main();
?>
