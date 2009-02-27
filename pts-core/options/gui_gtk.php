<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class gui_gtk implements pts_option_interface
{
	public static function run($r)
	{
		pts_load_function_set("gui");
		pts_load_function_set("gtk");

		if(!extension_loaded("gtk") && !extension_loaded("php-gtk"))
		{
			echo "\nThe PHP GTK module must be loaded for the GUI.\nThis module can be found @ http://gtk.php.net/\n\n";
			return;
		}

		gui_gtk::show_main_interface();
	}
	public static function kill_gtk_window()
	{
		Gtk::main_quit();
	}
	public static function show_main_interface()
	{
		$window = new pts_gtk_window("Phoronix Test Suite v" . PTS_VERSION, 620, -1);
		pts_set_assignment("GTK_OBJ_WINDOW", $window);
		$vbox = new GtkVBox();
		$vbox->set_spacing(4);
		$window->add($vbox);

		$clipboard = new GtkClipboard($window->get_display(), Gdk::atom_intern("CLIPBOARD"));
		pts_set_assignment("GTK_OBJ_CLIPBOARD", $clipboard);

		// Menu Setup
		$analyze_runs = new pts_gtk_menu_item("Analyze All Runs", array("gui_gtk", "analyze_all_runs"));
		$analyze_runs->attach_to_pts_assignment("GTK_OBJ_ANALYZE_RUNS");

		$analyze_batch = new pts_gtk_menu_item("Analyze Batch Run", array("gui_gtk", "analyze_batch"));
		$analyze_batch->attach_to_pts_assignment("GTK_OBJ_ANALYZE_BATCH");

		$refresh_graphs = new pts_gtk_menu_item("Refresh Graphs", array("gui_gtk", "refresh_graphs"));
		$refresh_graphs->attach_to_pts_assignment("GTK_OBJ_REFRESH_GRAPHS");

		$build_suite = new pts_gtk_menu_item("Build Suite", array("gui_gtk", ""));
		$build_suite->attach_to_pts_assignment("GTK_OBJ_BUILD_SUITE");

		$file_menu = array();

		if(!pts_pcqs_is_installed())
		{
			array_push($file_menu, new pts_gtk_menu_item("Install PCQS", array("gui_gtk", "show_pcqs_install_interface")));
		}


		$generate_pdf = new pts_gtk_menu_item("Save PDF", array("gui_gtk", "show_generate_pdf_interface"));
		$generate_pdf->attach_to_pts_assignment("GTK_OBJ_GENERATE_PDF");

		$global_upload = new pts_gtk_menu_item("Upload To Phoronix Global", array("gui_gtk", "upload_results_to_global"));
		$global_upload->attach_to_pts_assignment("GTK_OBJ_GLOBAL_UPLOAD");

		array_push($file_menu, $generate_pdf);
		array_push($file_menu, $global_upload);
		array_push($file_menu, null);
		array_push($file_menu, new pts_gtk_menu_item("Quit", array("gui_gtk", "kill_gtk_window"), "STRING", Gtk::STOCK_QUIT));

		$view_menu = array();
		array_push($view_menu, new pts_gtk_menu_item("System Information", array("gui_gtk", "show_system_info_interface")));
		array_push($view_menu, null);
		array_push($view_menu, new pts_gtk_menu_item(array("Tests", "Suites"), array("gui_gtk", "radio_test_suite_select"), "RADIO_BUTTON"));
		array_push($view_menu, null);

		foreach(pts_subsystem_test_types() as $subsystem)
		{
			array_push($view_menu, new pts_gtk_menu_item($subsystem, array("gui_gtk", "check_test_type_select"), "CHECK_BUTTON", null, true));
		}

		$main_menu_items = array(
		"File" => $file_menu,
		"Edit" => array($refresh_graphs, null, new pts_gtk_menu_item("Preferences", array("gui_gtk", "show_preferences_interface"), "STRING", Gtk::STOCK_PREFERENCES)),
		"View" => $view_menu,
		"Tools" => array($build_suite, null, $analyze_runs, $analyze_batch),
		"Help" => array(
		new pts_gtk_menu_item("View Documentation", array("gui_gtk", "launch_web_browser"), "STRING"), 
		null,
		new pts_gtk_menu_item("Community Support Online", array("gui_gtk", "launch_web_browser"), "STRING", Gtk::STOCK_HELP), 
		new pts_gtk_menu_item("Phoronix-Test-Suite.com", array("gui_gtk", "launch_web_browser"), "STRING"), 
		new pts_gtk_menu_item("Phoronix Media", array("gui_gtk", "launch_web_browser"), "STRING"), 
		null,
		new pts_gtk_menu_item("About", array("gui_gtk", "show_about_interface"), "STRING", Gtk::STOCK_ABOUT))
		);
		pts_gtk_add_menu($vbox, $main_menu_items);

		$a = pts_read_assignment("GTK_OBJ_ANALYZE_RUNS");
		$a->set_sensitive(false);
		$a = pts_read_assignment("GTK_OBJ_ANALYZE_BATCH");
		$a->set_sensitive(false);
		$a = pts_read_assignment("GTK_OBJ_BUILD_SUITE");
		$a->set_sensitive(false);
		$a = pts_read_assignment("GTK_OBJ_GLOBAL_UPLOAD");
		$a->set_sensitive(false);

		// Main Area

		// Details Frame
		$main_frame = new GtkFrame((($t = pts_read_assignment("PREV_SAVE_NAME_TITLE")) !== false ? $t : "Welcome"));
		$main_frame->set_size_request(280, 330);
		pts_set_assignment("GTK_OBJ_MAIN_FRAME", $main_frame);

		$main_frame_vbox = new GtkVBox();
		$main_frame->add($main_frame_vbox);
		pts_set_assignment("GTK_OBJ_MAIN_FRAME_BOX", $main_frame_vbox);

		$i = pts_read_assignment("PREV_SAVE_RESULTS_IDENTIFIER");
		$u = pts_read_assignment("PREV_GLOBAL_UPLOAD_URL");
		$p = pts_read_assignment("PREV_PDF_FILE");

		if($i != false || $u != false || $p != false)
		{
			$main_frame_vbox->pack_start(new GtkLabel(" "));
			if(!empty($i))
			{
				$tr_button = new GtkButton("View Test Results");
				$tr_button->connect_simple("clicked", array("gui_gtk", "launch_web_browser"), SAVE_RESULTS_DIR . $i . "/composite.xml");
				$main_frame_vbox->pack_start($tr_button);
			}
			$main_frame_vbox->pack_start(new GtkLabel(" "));
			if(!empty($u))
			{
				$pg_button = new GtkButton("View On Phoronix Global");
				$pg_button->connect_simple("clicked", array("gui_gtk", "launch_web_browser"), $u);
				$main_frame_vbox->pack_start($pg_button);
			}
			if(!empty($p))
			{
				$pdf_label = new GtkLabel("PDF Saved To: " . $p);
				$pdf_label->set_line_wrap(true);
				$pdf_label->set_size_request(260, -1);
				$main_frame_vbox->pack_start($pdf_label);
				$main_frame_vbox->pack_start(new GtkLabel(" "));
			}


		}
		else
		{
			$event_box = new GtkEventBox();
			$event_box->connect_simple("button-press-event", array("gui_gtk", "launch_web_browser"), "");
			$logo = GtkImage::new_from_file(RESULTS_VIEWER_DIR . "pts-logo.png");
			$logo->set_size_request(158, 82);
			$event_box->add($logo);
			$main_frame_vbox->pack_start($event_box);

			$label_welcome = new GtkLabel("The Phoronix Test Suite is the most comprehensive testing and benchmarking platform available for the Linux operating system. This software is designed to effectively carry out both qualitative and quantitative benchmarks in a clean, reproducible, and easy-to-use manner.");
			$label_welcome->set_line_wrap(true);
			$label_welcome->set_size_request(260, 200);
			$main_frame_vbox->pack_start($label_welcome);

		}

		$top_hbox = new GtkHBox();
		$top_hbox->pack_start($main_frame);

		// Notebook Area
		$main_notebook = new GtkNotebook();
		$main_notebook->set_size_request(310, 330);
		pts_set_assignment("GTK_OBJ_MAIN_NOTEBOOK", $main_notebook);

		$top_hbox->pack_start($main_notebook);
		$top_hbox->set_spacing(8);
		$vbox->pack_start($top_hbox);
		gui_gtk::update_main_notebook();

		// Bottom Line

		$bottom_hbox = new GtkHBox();
		$vbox->pack_start($bottom_hbox);

		$check_mode_batch = new GtkCheckButton("Batch Mode");
		$check_mode_batch->set_sensitive(false);
		pts_set_assignment("GTK_OBJ_CHECK_BATCH", $check_mode_batch);
		$bottom_hbox->pack_start($check_mode_batch);

		$check_mode_defaults = new GtkCheckButton("Defaults Mode");
		$check_mode_defaults->set_sensitive(false);
		pts_set_assignment("GTK_OBJ_CHECK_DEFAULTS", $check_mode_defaults);
		$bottom_hbox->pack_start($check_mode_defaults);

		$check_mode_batch->connect("toggled", array("gui_gtk", "check_test_mode_select"), $check_mode_defaults);
		$check_mode_defaults->connect("toggled", array("gui_gtk", "check_test_mode_select"), $check_mode_batch);

		$details_img = GtkImage::new_from_stock(Gtk::STOCK_FIND, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$details_button = new GtkButton();
		$details_button->connect_simple("clicked", array("gui_gtk", "details_button_clicked"));
		$details_button->set_image($details_img);
		$details_button->set_size_request(150, -1);
		$details_button->set_sensitive(false);

		$bottom_hbox->pack_start($details_button);
		$details_button->set_label("More Information");
		pts_set_assignment("GTK_OBJ_DETAILS_BUTTON", $details_button);

		$run_img = GtkImage::new_from_stock(Gtk::STOCK_EXECUTE, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$run_button = new GtkButton("Run");
		$run_button->connect_simple("clicked", array("gui_gtk", "show_run_confirmation_interface"));
		$run_button->set_image($run_img);
		$run_button->set_size_request(100, -1);
		$run_button->set_sensitive(false);

		$bottom_hbox->pack_start($run_button);
		pts_set_assignment("GTK_OBJ_RUN_BUTTON", $run_button);

		$window->show_all();
		Gtk::main();
	}
	public static function update_details_frame_from_select($object)
	{
		$identifier = pts_gtk_selected_item($object);

		pts_set_assignment("GTK_SELECTED_ITEM", $identifier);
		$gtk_obj_main_frame = pts_read_assignment("GTK_OBJ_MAIN_FRAME");
		$gtk_obj_main_frame->set_label($identifier);

		if(pts_is_assignment("GTK_OBJ_MAIN_FRAME_BOX"))
		{
			$gtk_obj_main_frame_box = pts_read_assignment("GTK_OBJ_MAIN_FRAME_BOX");
			$gtk_obj_main_frame->remove($gtk_obj_main_frame_box);
		}

		$root_vbox = new GtkVBox();
		$gtk_obj_main_frame->add($root_vbox);
		pts_set_assignment("GTK_OBJ_MAIN_FRAME_BOX", $root_vbox);

		$hbox = new GtkHBox();
		$root_vbox->add($hbox);

		$vbox_left = new GtkVBox();
		$vbox_right = new GtkVBox();
		$hbox->pack_start($vbox_left);
		$hbox->pack_start($vbox_right);

		$info_r = array();

		if(!pts_is_assignment("GTK_ITEM_SELECTED_ONCE"))
		{
			$button = pts_read_assignment("GTK_OBJ_RUN_BUTTON");
			$button->set_sensitive(true);
			$button = pts_read_assignment("GTK_OBJ_DETAILS_BUTTON");
			$button->set_sensitive(true);
			$button = pts_read_assignment("GTK_OBJ_CHECK_DEFAULTS");
			$button->set_sensitive(true);
			$button = pts_read_assignment("GTK_OBJ_CHECK_BATCH");
			$button->set_sensitive(true);

			pts_set_assignment("GTK_ITEM_SELECTED_ONCE", true);
		}

		$generate_pdf = pts_read_assignment("GTK_OBJ_GENERATE_PDF");
		$refresh_graphs = pts_read_assignment("GTK_OBJ_REFRESH_GRAPHS");
		$analyze_runs = pts_read_assignment("GTK_OBJ_ANALYZE_RUNS");
		$analyze_batch = pts_read_assignment("GTK_OBJ_ANALYZE_BATCH");
		$global_upload = pts_read_assignment("GTK_OBJ_GLOBAL_UPLOAD");
		$generate_pdf->set_sensitive(false);
		$refresh_graphs->set_sensitive(false);
		$analyze_runs->set_sensitive(false);
		$analyze_batch->set_sensitive(false);
		$global_upload->set_sensitive(false);

		// PTS Test
		if(pts_read_assignment("GTK_MAIN_NOTEBOOK_SELECTED") == "Test Results")
		{
			$generate_pdf->set_sensitive(true);
			$refresh_graphs->set_sensitive(true);
			$analyze_runs->set_sensitive(true);
			$analyze_batch->set_sensitive(true);
			$global_upload->set_sensitive(true);

			$result_file = new pts_test_result_details(SAVE_RESULTS_DIR . $identifier . "/composite.xml");

			$info_r["Title"] = $result_file->get_title();
			$info_r["Test"] = $result_file->get_suite();
		}
		else if(pts_read_assignment("GTK_TEST_OR_SUITE") == "TEST")
		{
			$identifier = pts_test_name_to_identifier($identifier);
			$test_profile = new pts_test_profile_details($identifier);

			$info_r["Maintainer"] = $test_profile->get_maintainer();
			$info_r["Test Type"] = $test_profile->get_test_hardware_type();
			$info_r["Software Type"] = $test_profile->get_test_software_type();
			$info_r["License"] = $test_profile->get_license();

			if($test_profile->get_download_size() > 0)
			{
				$info_r["Download Size"] = $test_profile->get_download_size() . " MB";
			}
			if($test_profile->get_environment_size() > 0)
			{
				$info_r["Environment Size"] = $test_profile->get_environment_size() . " MB";
			}

			$label_description_scroll = new GtkScrolledWindow();
			$label_description_scroll->set_policy(Gtk::POLICY_NEVER, Gtk::POLICY_AUTOMATIC);
			$label_description_scroll->set_shadow_type(Gtk::SHADOW_NONE);

			$label_description = new GtkLabel($test_profile->get_description());
			$label_description->set_line_wrap(true);
			$label_description->set_size_request(260, -1);
			$label_description_scroll->add_with_viewport($label_description);
			$root_vbox->add($label_description_scroll);
		}
		else if(pts_read_assignment("GTK_TEST_OR_SUITE") == "SUITE")
		{
			$identifier = pts_suite_name_to_identifier($identifier);
			$test_suite = new pts_test_suite_details($identifier);

			$info_r["Maintainer"] = $test_suite->get_maintainer();
			$info_r["Suite Type"] = $test_suite->get_suite_type();

			$label_description_scroll = new GtkScrolledWindow();
			$label_description_scroll->set_policy(Gtk::POLICY_NEVER, Gtk::POLICY_AUTOMATIC);
			$label_description_scroll->set_shadow_type(Gtk::SHADOW_NONE);
			$label_description = new GtkLabel($test_suite->get_description());
			$label_description->set_line_wrap(true);
			$label_description->set_size_request(260, -1);
			$label_description_scroll->add_with_viewport($label_description);
			$root_vbox->add($label_description_scroll);
		}

		foreach($info_r as $head => $show)
		{
			$label_head = new GtkLabel("  " . $head . ": ");
			$label_head->set_alignment(0, 0);
			$vbox_left->pack_start($label_head);

			$label_show = new GtkLabel($show);
			$label_show->set_alignment(0, 0);
			$vbox_right->pack_start($label_show);
		}

		gui_gtk::update_run_button();
		gui_gtk::redraw_main_window();
	}
	public static function update_main_notebook()
	{
		$main_notebook = pts_read_assignment("GTK_OBJ_MAIN_NOTEBOOK");

		if($main_notebook == null)
		{
			return;
		}

		foreach($main_notebook->get_children() as $child)
		{
			$main_notebook->remove($child);
		}

		if(pts_read_assignment("GTK_TEST_OR_SUITE") == "SUITE")
		{
			// Installed Suites
			if(count(pts_installed_tests_array()) > 0)
			{
				$installed_suites = pts_gtk_add_table(array("Suite"), pts_gui_installed_suites(), array("gui_gtk", "update_details_frame_from_select"));
				pts_gtk_add_notebook_tab($main_notebook, $installed_suites, "Installed Suites");
			}

			$available_suites = pts_gtk_add_table(array("Suite"), pts_gui_available_suites(pts_read_assignment("GTK_TEST_TYPES_TO_SHOW")), 
			array("gui_gtk", "update_details_frame_from_select"));
			pts_gtk_add_notebook_tab($main_notebook, $available_suites, "Available Suites");
		}
		else
		{
			$to_show_types = pts_read_assignment("GTK_TEST_TYPES_TO_SHOW");

			// Installed Tests
			if(count(($installed = pts_installed_tests_array())) > 0)
			{
				$installed_tests = pts_gtk_add_table(array("Test"), pts_gui_installed_tests(pts_read_assignment("GTK_TEST_TYPES_TO_SHOW")), 
				array("gui_gtk", "update_details_frame_from_select"));
				pts_gtk_add_notebook_tab($main_notebook, $installed_tests, "Installed Tests");
			}

			// Available Tests
			$available_tests = pts_gtk_add_table(array("Test"), pts_gui_available_tests(pts_read_assignment("GTK_TEST_TYPES_TO_SHOW")), 
			array("gui_gtk", "update_details_frame_from_select"));
			pts_gtk_add_notebook_tab($main_notebook, $available_tests, "Available Tests");
		}

		$saved_results = pts_gui_saved_test_results_identifiers();
		if(count($saved_results) > 0)
		{

			$test_results = pts_gtk_add_table(array("Test Result"), $saved_results, array("gui_gtk", "update_details_frame_from_select"));
			pts_gtk_add_notebook_tab($main_notebook, $test_results, "Test Results");
		}

		/*
		if(($no = pts_read_assignment("GTK_MAIN_NOTEBOOK_NUM")) >= 0)
		{
			$main_notebook->set_current_page($no);
		}
		*/
	}
	public static function radio_test_suite_select($object)
	{
		if($object->get_active())
		{
			$item = $object->child->get_label();
			pts_set_assignment("GTK_TEST_OR_SUITE", ($item == "Tests" ? "TEST" : "SUITE"));

			gui_gtk::update_main_notebook();
			gui_gtk::redraw_main_window();
		}
	}
	public static function confirmation_button_clicked($button_call, $identifier)
	{
		switch($button_call)
		{
			case "return":
				gui_gtk::show_main_interface();
				break;
			case "install":
				pts_run_option_next("install_test", $identifier, array("SILENCE_MESSAGES" => true));
				pts_run_option_next("gui_gtk");
				break;
			case "benchmark":
				$args_to_pass = array("IS_BATCH_MODE" => pts_read_assignment("GTK_BATCH_MODE"), 
				"IS_DEFAULTS_MODE" => pts_read_assignment("GTK_DEFAULTS_MODE"), "AUTOMATED_MODE" => true);

				$save_results = pts_read_assignment("GTK_OBJ_SAVE_RESULTS");
				$save_results = $save_results->get_active();

				if($save_results)
				{
					$save_name = pts_read_assignment("GTK_OBJ_SAVE_NAME");
					$save_name = $save_name->get_text();

					$results_identifier = pts_read_assignment("GTK_OBJ_TEST_IDENTIFIER");
					$results_identifier = $results_identifier->get_text();

					$upload_to_global = pts_read_assignment("GTK_OBJ_GLOBAL_UPLOAD");
					$upload_to_global = $upload_to_global->get_active();

					$args_to_pass["AUTO_SAVE_NAME"] = $save_name;
					$args_to_pass["AUTO_TEST_RESULTS_IDENTIFIER"] = $results_identifier;
					$args_to_pass["AUTO_UPLOAD_TO_GLOBAL"] = $upload_to_global;
				}
				else
				{
					$args_to_pass["DO_NOT_SAVE_RESULTS"] = true;
				}

				pts_run_option_next("run_test", $identifier, $args_to_pass);
				pts_run_option_next("gui_gtk");
				break;
		}

		$window = pts_read_assignment("GTK_OBJ_CONFIRMATION_WINDOW");
		$window->destroy();
	}
	public static function pcqs_button_clicked($button_call)
	{
		if($button_call == "install")
		{
			pts_pcqs_install_package();
		}

		$window = pts_read_assignment("GTK_OBJ_PCQS_WINDOW");
		$window->destroy();
	}
	public static function check_test_mode_select($checkbox, $other_checkbox)
	{
		$toggled_mode = $checkbox->get_label();
		$other_mode = $other_checkbox->get_label();

		if($other_checkbox->get_active())
		{
			$other_checkbox->set_active(false);
		}

		if($toggled_mode == "Batch Mode")
		{
			$batch_checkbox = $checkbox;
			$defaults_checkbox = $other_checkbox;
		}
		else
		{
			$batch_checkbox = $other_checkbox;
			$defaults_checkbox = $checkbox;
		}

		pts_set_assignment("GTK_BATCH_MODE", $batch_checkbox->get_active());
		pts_set_assignment("GTK_DEFAULTS_MODE", $defaults_checkbox->get_active());
	}
	public static function show_run_confirmation_interface()
	{
		$identifier = gui_gtk::notebook_selected_to_identifier();

		if(empty($identifier))
		{
			echo "DEBUG: Null identifier in gtk_gui::show_run_confirmation_interface()\n";
			return;
		}

		$main_window = pts_read_assignment("GTK_OBJ_WINDOW");
		$main_window->destroy();

		switch(pts_read_assignment("GTK_RUN_BUTTON_TASK"))
		{
			case "UPDATE":
				$title_cmd = "install";
				$window_type = "confirmation";
				$message = "The Phoronix Test Suite will now proceed to update your " . $identifier . " installation.";
				break;
			case "INSTALL":
				$title_cmd = "install";
				$window_type = "confirmation";
				$message = "The Phoronix Test Suite will now proceed to install " . $identifier . ".";
				break;
			case "RUN":
				$title_cmd = "benchmark";
				$window_type = "menu";
				$message = "The Phoronix Test Suite will now run " . $identifier . ".";

				$menu_items = array();

				$label_options = new GtkLabel("Test Options");
				$label_options->modify_font(new PangoFontDescription("Sans 19"));
				array_push($menu_items, $label_options);

				if(pts_read_assignment("GTK_BATCH_MODE") != false)
				{
					array_push($menu_items, new GtkLabel("No user options, running in batch mode."));
				}
				else if(pts_read_assignment("GTK_DEFAULTS_MODE") != false)
				{
					array_push($menu_items, new GtkLabel("No user options, running in defaults mode."));
				}
				else
				{


				}
				array_push($menu_items, null);

				$label_save = new GtkLabel("Results");
				$label_save->modify_font(new PangoFontDescription("Sans 19"));
				array_push($menu_items, $label_save);

				$save_results = new GtkCheckButton("Save Results");
				$save_results->set_active(true);
				pts_set_assignment("GTK_OBJ_SAVE_RESULTS", $save_results);
				array_push($menu_items, $save_results);

				$save_name = new GtkEntry();
				pts_set_assignment("GTK_OBJ_SAVE_NAME", $save_name);
				array_push($menu_items, array(new GtkLabel("Save Name"), $save_name));

				$test_identifier = new GtkEntry();
				pts_set_assignment("GTK_OBJ_TEST_IDENTIFIER", $test_identifier);
				array_push($menu_items, array(new GtkLabel("Test Identifier"), $test_identifier));

				$global_upload = new GtkCheckButton("Upload Results To Phoronix Global");
				$global_upload->set_active(true);
				pts_set_assignment("GTK_OBJ_GLOBAL_UPLOAD", $global_upload);
				array_push($menu_items, $global_upload);

				array_push($menu_items, null);
				break;
		//	default:
		//		return;
		//		break;
		}

		$window = new pts_gtk_window("phoronix-test-suite " . $title_cmd . " " . $identifier);
		$window->set_resizable(false);
		$vbox = new GtkVBox();
		$window->add($vbox);

		if($window_type == "confirmation")
		{
			$window->set_size_request(500, 200);

			$label_temp = new GtkLabel($message);
			$label_temp->set_size_request(480, 150);
			$label_temp->set_line_wrap(true);
			$vbox->pack_start($label_temp);
		}
		else if($window_type == "menu")
		{
			$temp_boxes = array();

			for($i = 0; $i < count($menu_items); $i++)
			{
				if(is_array($menu_items[$i]))
				{
					$temp_boxes[$i] = new GtkHBox();

					for($j = 0; $j < count($menu_items[$i]); $j++)
					{
						$temp_boxes[$i]->pack_start($menu_items[$i][$j]);
					}

					$vbox->pack_start($temp_boxes[$i]);
				}
				else if($menu_items[$i] == null)
				{
					$vbox->pack_start(new GtkLabel(" "));
				}
				else
				{
					$vbox->pack_start($menu_items[$i]);
				}
			}
		}

		$button_box = new GtkHBox();
		$vbox->pack_start($button_box);
		$return_img = GtkImage::new_from_stock(Gtk::STOCK_CANCEL, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$return_button = new GtkButton("Return");
		$return_button->connect_simple("clicked", array("gui_gtk", "confirmation_button_clicked"), "return", $identifier);
		$return_button->set_image($return_img);
		$return_button->set_size_request(100, 30);
		$button_box->pack_start($return_button);

		$continue_img = GtkImage::new_from_stock(Gtk::STOCK_APPLY, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$continue_button = new GtkButton("Continue");
		$continue_button->connect_simple("clicked", array("gui_gtk", "confirmation_button_clicked"), $title_cmd, $identifier);
		$continue_button->set_image($continue_img);
		$continue_button->set_size_request(100, 30);
		$button_box->pack_start($continue_button);

		$window->show_all();
		pts_set_assignment("GTK_OBJ_CONFIRMATION_WINDOW", $window);
		Gtk::main();
	}
	public static function launch_web_browser($url = "")
	{
		if($url instanceOf GtkImageMenuItem || $url instanceOf GtkMenuItem)
		{
			switch($url->child->get_label())
			{
				case "Phoronix-Test-Suite.com":
					$url = "http://www.phoronix-test-suite.com/";
					break;
				case "Community Support Online":
					$url = "http://www.phoronix.com/forums/forumdisplay.php?f=49";
					break;
				case "Phoronix Media":
					$url = "http://www.phoronix-media.com/";
					break;
				case "View Documentation":
					$url = PTS_PATH . "documentation/index.html";
					break;
			}
		}
		else if($url == "")
		{
			$url = "http://www.phoronix-test-suite.com/";
		}

		pts_display_web_browser($url, null, true, true);
	}
	public static function details_button_clicked()
	{
		$identifier = pts_read_assignment("GTK_SELECTED_ITEM");

		if(pts_read_assignment("GTK_MAIN_NOTEBOOK_SELECTED") == "Test Results")
		{
			pts_display_web_browser(SAVE_RESULTS_DIR . $identifier . "/index.html", null, true, true);
		}
		else
		{
			$window = new pts_gtk_window($identifier);
			$window->set_resizable(false);
			$vbox = new GtkVBox();
			$vbox->set_spacing(12);
			$window->add($vbox);

			if(pts_read_assignment("GTK_TEST_OR_SUITE") == "SUITE")
			{
				$identifier = pts_suite_name_to_identifier($identifier);

				$label_tests = new GtkLabel("Suite Contains: " . implode(", ", pts_contained_tests($identifier, false, false, true)));
				$label_tests->set_size_request(420, -1);
				$label_tests->set_line_wrap(true);
				$vbox->pack_start($label_tests);
			}
			else
			{
				$identifier = pts_test_name_to_identifier($identifier);

				$obj = new pts_test_profile_details($identifier);

				$str = "Software Dependencies: ";
				$i = 0;
				foreach($obj->get_dependencies() as $dependency)
				{
					if(($title = pts_dependency_name(trim($dependency)) )!= "")
					{
						if($i > 0)
						{
							$str .= ", ";
						}

						$str .= $title;
						$i++;
					}
				}
				if($i == 0)
				{
					$str .= "N/A";
				}

				$label_dependencies = new GtkLabel($str);
				$label_dependencies->set_size_request(420, -1);
				$label_dependencies->set_line_wrap(true);
				$vbox->pack_start($label_dependencies);

				// Suites

				$str = "Suites Using This Test: ";
				$i = 0;
				foreach($obj->suites_using_this_test() as $suite)
				{
					if($i > 0)
					{
						$str .= ", ";
					}

					$str .= pts_suite_identifier_to_name($suite);
					$i++;
				}
				if($i == 0)
				{
					$str .= "N/A";
				}

				$label_suites = new GtkLabel($str);
				$label_suites->set_size_request(420, -1);
				$label_suites->set_line_wrap(true);
				$vbox->pack_start($label_suites);
			}

			$window->show_all();
			Gtk::main();
		}
	}
	public static function notebook_selected_to_identifier()
	{
		$identifier = pts_read_assignment("GTK_SELECTED_ITEM");

		if(pts_read_assignment("GTK_MAIN_NOTEBOOK_SELECTED") == "Test Results")
		{
			$identifier = $identifier;
		}
		else if(pts_read_assignment("GTK_TEST_OR_SUITE") == "SUITE")
		{
			$identifier = pts_suite_name_to_identifier($identifier);
		}
		else
		{
			$identifier = pts_test_name_to_identifier($identifier);
		}

		return $identifier;
	}
	public static function update_run_button()
	{
		$identifier = gui_gtk::notebook_selected_to_identifier();

		if(pts_is_test($identifier))
		{
			if(!pts_test_installed($identifier))
			{
				$button_string = "Install";

			}
			else if(pts_test_needs_updated_install($identifier))
			{
				$button_string = "Update";
			}
			else
			{
				$button_string = "Run";
			}
		}
		else if(pts_is_suite($identifier) || pts_is_test_result($identifier))
		{
			if(pts_suite_needs_updated_install($identifier))
			{
				$button_string = "Update";
			}
			else
			{
				$button_string = "Run";
			}
		}

		pts_set_assignment("GTK_RUN_BUTTON_TASK", strtoupper($button_string));
		$run_button = pts_read_assignment("GTK_OBJ_RUN_BUTTON");
		$run_button->set_label($button_string);
	}
	public static function notebook_main_page_select($object)
	{
		$selected = $object->child->get_label();
		pts_set_assignment("GTK_MAIN_NOTEBOOK_SELECTED", $selected);

		$details_button = pts_read_assignment("GTK_OBJ_DETAILS_BUTTON");

		switch($selected)
		{
			case "Test Results":
				$details_button->set_label("View Results");
				break;
			default:
				$details_button->set_label("More Information");
				break;
		}

		/*
		$main_notebook = pts_read_assignment("GTK_OBJ_MAIN_NOTEBOOK");
		pts_set_assignment("GTK_MAIN_NOTEBOOK_NUM", $main_notebook->get_current_page());
		*/
	}
	public static function check_test_type_select($object)
	{
		$item = $object->child->get_label();
		//$to_add = $object->get_active();
		$items_to_show = pts_read_assignment("GTK_TEST_TYPES_TO_SHOW");

		if($items_to_show == null)
		{
			$items_to_show = array();
		}

		if(!in_array($item, $items_to_show))
		{
			array_push($items_to_show, $item);
		}
		else
		{
			$items_to_show_1 = $items_to_show;
			$items_to_show = array();

			foreach($items_to_show_1 as $show)
			{
				if($show != $item)
				{
					array_push($items_to_show, $show);
				}
			}
		}

		pts_set_assignment("GTK_TEST_TYPES_TO_SHOW", $items_to_show);

		gui_gtk::update_main_notebook();
		gui_gtk::redraw_main_window();
	}
	public static function show_about_interface()
	{
		$window = new pts_gtk_window("About", 210, 260);
		$window->set_resizable(false);

		$vbox = new GtkVBox();
		$window->add($vbox);

		$logo = GtkImage::new_from_file(RESULTS_VIEWER_DIR . "pts-logo.png");
		$logo->set_size_request(158, 82);
		$vbox->pack_start($logo);

		$label_codename = new GtkLabel(ucwords(strtolower(PTS_CODENAME)));
		$label_codename->modify_font(new PangoFontDescription("Sans 19"));
		$vbox->pack_start($label_codename);

		$label_version = new GtkLabel("Version " . PTS_VERSION);
		$vbox->pack_start($label_version);

		$event_box = new GtkEventBox();
		$label_url = new GtkLabel("www.phoronix-test-suite.com");
		$event_box->connect_simple("button-press-event", array("gui_gtk", "launch_web_browser"), "");
		$event_box->add($label_url);
		$vbox->pack_start($event_box);

		$label_copyright = new GtkLabel("Copyright By Phoronix Media");
		$vbox->pack_start($label_copyright);

		$window->show_all();
		Gtk::main();
	}
	public static function show_preferences_interface()
	{
		$window = new pts_gtk_window("Preferences", 300, 140);
		$window->set_resizable(false);

		$vbox = new GtkVBox();
		$window->add($vbox);

		$label_temp = new GtkLabel("This dialog is not yet implemented. For now the configuration can be modified manually at ~/.phoronix-test-suite/user-config.xml");
		$label_temp->set_size_request(300, 200);
		$label_temp->set_line_wrap(true);
		$vbox->pack_start($label_temp);

		$window->show_all();
		Gtk::main();
	}
	public static function upload_results_to_global()
	{
		$main_window = pts_read_assignment("GTK_OBJ_WINDOW");
		$main_window->destroy();

		$identifier = pts_read_assignment("GTK_SELECTED_ITEM");
		pts_run_option_next("upload_result", $identifier, array("AUTOMATED_MODE" => true));
		pts_run_option_next("gui_gtk");
	}
	public static function show_generate_pdf_interface()
	{
		$dialog = new GtkFileChooserDialog("Save Results To PDF", null, Gtk::FILE_CHOOSER_ACTION_SAVE, array(Gtk::STOCK_OK, Gtk::RESPONSE_OK), null);
		$dialog->show_all();

		if($dialog->run() == Gtk::RESPONSE_OK)
		{
			$save_file = $dialog->get_filename();

			$main_window = pts_read_assignment("GTK_OBJ_WINDOW");
			$main_window->destroy();

			$identifier = pts_read_assignment("GTK_SELECTED_ITEM");
			pts_run_option_next("result_file_to_pdf", $identifier, array("SAVE_TO" => $save_file));
			pts_run_option_next("gui_gtk");
		}
		$dialog->destroy();
	}
	public static function refresh_graphs()
	{
		$main_window = pts_read_assignment("GTK_OBJ_WINDOW");
		$main_window->destroy();

		$identifier = pts_read_assignment("GTK_SELECTED_ITEM");
		pts_run_option_next("refresh_graphs", $identifier, array("AUTOMATED_MODE" => true));
		pts_run_option_next("gui_gtk");
	}
	public static function analyze_all_runs()
	{
		$main_window = pts_read_assignment("GTK_OBJ_WINDOW");
		$main_window->destroy();

		$identifier = pts_read_assignment("GTK_SELECTED_ITEM");
		pts_run_option_next("analyze_all_runs", $identifier, array("AUTOMATED_MODE" => true));
		pts_run_option_next("gui_gtk");
	}
	public static function analyze_batch()
	{
		$main_window = pts_read_assignment("GTK_OBJ_WINDOW");
		$main_window->destroy();

		$identifier = pts_read_assignment("GTK_SELECTED_ITEM");
		pts_run_option_next("analyze_all_runs", $identifier, array("AUTOMATED_MODE" => true));
		pts_run_option_next("gui_gtk");
	}
	public static function show_system_info_interface()
	{
		$window = new pts_gtk_window("System Information");
		$window->set_resizable(false);

		$vbox = new GtkVBox();
		$window->add($vbox);

		$notebook = new GtkNotebook();
		$notebook->set_size_request(540, 250);
		$vbox->pack_start($notebook);
		$vbox->set_spacing(3);

		pts_set_assignment("GTK_SYSTEM_INFO_NOTEBOOK", "Hardware");
		$hw = pts_gtk_add_table(array("", ""), pts_array_with_key_to_2d(pts_hw_string(false)));
		pts_gtk_add_notebook_tab($notebook, $hw, "Hardware", array("gui_gtk", "system_info_change_notebook"));

		$sw = pts_gtk_add_table(array("", ""), pts_array_with_key_to_2d(pts_sw_string(false)));
		pts_gtk_add_notebook_tab($notebook, $sw, "Software", array("gui_gtk", "system_info_change_notebook"));

		$sensors = pts_gtk_add_table(array("", ""), pts_array_with_key_to_2d(pts_sys_sensors_string(false)));
		pts_gtk_add_notebook_tab($notebook, $sensors, "Sensors", array("gui_gtk", "system_info_change_notebook"));

		$copy_button = new GtkButton("Copy To Clipboard");
		$copy_button->connect_simple("clicked", array("gui_gtk", "system_info_copy_to_clipboard"));
		$vbox->pack_start($copy_button);

		$window->show_all();
		Gtk::main();
	}
	public static function system_info_change_notebook($object)
	{
		$identifier = $object->child->get_label();
		pts_set_assignment("GTK_SYSTEM_INFO_NOTEBOOK", $identifier);
	}
	public static function system_info_copy_to_clipboard()
	{
		$clipboard = pts_read_assignment("GTK_OBJ_CLIPBOARD");

		switch(pts_read_assignment("GTK_SYSTEM_INFO_NOTEBOOK"))
		{
			case "Hardware":
				$to_copy = pts_hw_string();
				break;
			case "Software":
				$to_copy = pts_sw_string();
				break;
			case "Sensors":
				$to_copy = pts_sys_sensors_string();
				break;
		}

		$clipboard->set_text($to_copy);	
	}
	public static function show_pcqs_install_interface()
	{
		$license = pts_pcqs_user_license();

		if($license == false)
		{
			return;
		}

		$window = new pts_gtk_window("Phoronix Certification & Qualification Suite");

		$vbox = new GtkVBox();
		$window->add($vbox);

		$scrolled_window = new GtkScrolledWindow();
		$scrolled_window->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);

		$text_view = new GtkTextView();
		$text_buffer = new GtkTextBuffer();
		$text_buffer->set_text($license);
		$text_view->set_buffer($text_buffer);
		$text_view->set_wrap_mode(GTK_WRAP_WORD);
		$text_view->set_size_request(540, 250);
		$scrolled_window->add($text_view);
		$vbox->pack_start($scrolled_window);

		$button_box = new GtkHBox();
		$vbox->pack_start($button_box);
		$return_img = GtkImage::new_from_stock(Gtk::STOCK_CANCEL, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$return_button = new GtkButton("Return");
		$return_button->connect_simple("clicked", array("gui_gtk", "pcqs_button_clicked"), "return");
		$return_button->set_image($return_img);
		$return_button->set_size_request(100, 30);
		$button_box->pack_start($return_button);

		$continue_img = GtkImage::new_from_stock(Gtk::STOCK_APPLY, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$continue_button = new GtkButton("Install");
		$continue_button->connect_simple("clicked", array("gui_gtk", "pcqs_button_clicked"), "install");
		$continue_button->set_image($continue_img);
		$continue_button->set_size_request(100, 30);
		$button_box->pack_start($continue_button);

		pts_set_assignment("GTK_OBJ_PCQS_WINDOW", $window);
		$window->show_all();
		Gtk::main();
	}
	public static function process_user_agreement_prompt($event)
	{
		pts_set_assignment("AGREED_TO_TERMS", ($event == "yes"));

		$window = pts_read_assignment("GTK_USER_AGREEMENT_WINDOW");
		$window->destroy();
	}
	public static function pts_user_agreement_prompt($user_agreement)
	{
		pts_set_assignment("AGREED_TO_TERMS", false);
		$window = new pts_gtk_window("Phoronix Test Suite - User Agreement");

		$vbox = new GtkVBox();
		$vbox->set_spacing(1);
		$window->add($vbox);

		$scrolled_window = new GtkScrolledWindow();
		$scrolled_window->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);

		$text_view = new GtkTextView();
		$text_buffer = new GtkTextBuffer();
		$text_buffer->set_text(trim($user_agreement));
		$text_view->set_buffer($text_buffer);
		$text_view->set_wrap_mode(GTK_WRAP_WORD);
		$text_view->set_size_request(540, 250);
		$scrolled_window->add($text_view);
		$vbox->pack_start($scrolled_window);

		$vbox->pack_start(new GtkLabel("Do you agree to the user terms listed above?"));

		$button_box = new GtkHBox();
		$vbox->pack_start($button_box);
		$return_img = GtkImage::new_from_stock(Gtk::STOCK_CANCEL, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$return_button = new GtkButton("Quit");
		$return_button->connect_simple("clicked", array("gui_gtk", "process_user_agreement_prompt"), "quit");
		$return_button->set_image($return_img);
		$return_button->set_size_request(100, 30);
		$button_box->pack_start($return_button);

		$continue_img = GtkImage::new_from_stock(Gtk::STOCK_APPLY, Gtk::ICON_SIZE_SMALL_TOOLBAR);
		$continue_button = new GtkButton("Accept To Terms");
		$continue_button->connect_simple("clicked", array("gui_gtk", "process_user_agreement_prompt"), "yes");
		$continue_button->set_image($continue_img);
		$continue_button->set_size_request(100, 30);
		$button_box->pack_start($continue_button);

		pts_set_assignment("GTK_USER_AGREEMENT_WINDOW", $window);
		$window->show_all();
		Gtk::main();

		return pts_read_assignment("AGREED_TO_TERMS");
	}
	public static function redraw_main_window()
	{
		$window = pts_read_assignment("GTK_OBJ_WINDOW");
		$window->show_all();
	}
}

?>
