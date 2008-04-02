<?php

function gtk_shutdown()
{
	gtk::main_quit();
}
function gtk_quick_alert($message)
{
	$dialog = new GtkDialog("Phoronix Test Suite", null, Gtk::DIALOG_MODAL);
	$dialog->set_size_request(250, -1);
	$box = $dialog->vbox;
	$box->pack_start($hbox = new GtkHBox());
	$message_label = new GtkLabel($message);
	$message_label->set_size_request(230, -1);
	$message_label->set_line_wrap(true);
	$hbox->pack_start($message_label);
	$dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);
	$dialog->show_all();
	$dialog->run();
	$dialog->destroy();
}
function gtk_benchmark_results($message)
{
	$dialog = new GtkDialog("Phoronix Test Suite", null, Gtk::DIALOG_MODAL);
	$dialog->set_size_request(500, -1);
	$box = $dialog->vbox;
	$box->pack_start($hbox = new GtkHBox());
	$message_label = new GtkLabel($message);
	$message_label->set_size_request(460, -1);
	$message_label->set_line_wrap(true);
	$hbox->pack_start($message_label);
	$dialog->add_button(Gtk::STOCK_OK, Gtk::RESPONSE_OK);
	$dialog->show_all();
	$dialog->run();
	$dialog->destroy();
} 
function combobox_select(&$button, $combobox)
{
	$model = $combobox->get_model();
	$selection = $model->get_value($combobox->get_active_iter(), 0);
	$benchmark = pts_benchmark_name_to_identifier($selection);

	if(!$benchmark)
	{
		gtk_quick_alert("No benchmark selected!");
	}
	else
	{
		echo exec("php pts-core/pts-benchmark-screen.php " . $benchmark);
	}
		
}

?>
