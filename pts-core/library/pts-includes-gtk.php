<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
	pts-includes-gtk.php: Functions needed for the GTK interface

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

function pts_array_with_key_to_2d($array)
{
	$array_2d = array();

	foreach($array as $key => $value)
	{
		array_push($array_2d, array($key, $value));
	}

	return $array_2d;
}
function pts_gtk_add_menu($vbox, $menu)
{
	if($vbox instanceOf GtkBox)
	{
		$menu_bar = new GtkMenuBar();
		$vbox->pack_start($menu_bar, false, false);
	}
	else if($vbox instanceOf GtkMenu)
	{
		$menu_bar = $vbox;
	}

	foreach($menu as $this_menu => $sub_menu)
	{
		$this_menu_identifier = strtoupper(str_replace(" ", "_", $this_menu));
		$new_menu = new GtkMenuItem($this_menu);
		$menu_bar->append($new_menu);
		$menu = new GtkMenu();
		$new_menu->set_submenu($menu);

		pts_set_assignment("GTK_OBJ_MENU_" . $this_menu_identifier, $menu);

		$sub_menu = pts_to_array($sub_menu);
		foreach(array_keys($sub_menu) as $key)
		{
			if($sub_menu[$key] == null)
			{
				$menu_item = new GtkSeparatorMenuItem();
				$menu->append($menu_item);
			}
			else if(is_array($sub_menu[$key]))
			{
				pts_gtk_add_menu($menu, array($key => $sub_menu[$key]));
			}
			else
			{
				if($sub_menu[$key]->get_type() == "CHECK_BUTTON")
				{
					$menu_item = new GtkCheckMenuItem($sub_menu[$key]->get_title());
					$menu_item->connect("toggled", $sub_menu[$key]->get_function_call(), $sub_menu[$key]->get_function_argument());
					$menu_item->connect("toggled", array("pts_gtk_multi_select_manager", "set_check_select"), $this_menu_identifier . "_" . $sub_menu[$key]->get_title());

					if(($predefined_select = pts_gtk_multi_select_manager::get_select($this_menu_identifier . "_" . $sub_menu[$key]->get_title())) != -1)
					{
						$menu_item->set_active($predefined_select);
					}
					else if($sub_menu[$key]->get_active_default())
					{
						$menu_item->set_active(true);
					}

					$menu->append($menu_item);
				}
				else if($sub_menu[$key]->get_type() == "RADIO_BUTTON")
				{
					$radio = array();
					$radio[0] = null;
					$i = 0;

					$default = $sub_menu[$key]->get_active_default();
					$predefined_select = pts_gtk_multi_select_manager::get_select($this_menu_identifier);

					foreach($sub_menu[$key]->get_title() as $radio_item)
					{
						$radio[$i] = new GtkRadioMenuItem($radio[0], $radio_item);
						$radio[$i]->connect("toggled", $sub_menu[$key]->get_function_call(), $sub_menu[$key]->get_function_argument());
						$radio[$i]->connect("toggled", array("pts_gtk_multi_select_manager", "set_radio_select"), $this_menu_identifier);
						$menu->append($radio[$i]);

						if($predefined_select == $radio_item)
						{
							$default = $i;
						}		

						$i++;
					}

					if(!isset($radio[$default]))
					{
						$default = 0;
					}

					$radio[$default]->set_active(true);
				}
				else
				{
					if($sub_menu[$key]->get_image() == null)
					{
						$menu_item = new GtkMenuItem($sub_menu[$key]->get_title());
					}
					else
					{
						$menu_item = new GtkImageMenuItem($sub_menu[$key]->get_title());
						$menu_item->set_image(GtkImage::new_from_stock($sub_menu[$key]->get_image(), Gtk::ICON_SIZE_MENU));
					}

					$menu_item->connect("activate", $sub_menu[$key]->get_function_call(), $sub_menu[$key]->get_function_argument());
					$menu->append($menu_item);
				}

				if($sub_menu[$key]->get_attach_to_pts_assignment() != null)
				{
					pts_set_assignment($sub_menu[$key]->get_attach_to_pts_assignment(), $menu_item);
					$menu_item->set_sensitive(false);
				}
			}
		}
	}
}
function pts_gtk_table($headers, $data, $connect_to = null, $on_empty = null, $allow_multiple_select = true)
{
	if(count($data) == 0 && $on_empty != null)
	{
		$vbox = new GtkVBox();
		$empty_label = new GtkLabel($on_empty);
		$empty_label->set_line_wrap(true);
		$vbox->pack_start($empty_label);

		return $vbox;
	}

	$scrolled_window = new GtkScrolledWindow();
	$scrolled_window->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
	//$vbox->pack_start($scrolled_window);

	if(count($headers) == 2)
	{
		if($headers[0] == null && is_object($data[0][$i]))
		{
			$model = new GtkListStore(GObject::TYPE_OBJECT, GObject::TYPE_STRING);
		}
		else
		{
			$model = new GtkListStore(GObject::TYPE_STRING, GObject::TYPE_STRING);
		}
	}
	else
	{
		$model = new GtkListStore(GObject::TYPE_STRING);
	}

	$view = new GtkTreeView($model);
	$scrolled_window->add($view);

	foreach(array_keys($headers) as $i)
	{
		if($headers[$i] == null && is_object($data[0][$i]))
		{
			// TODO: this code is likely faulty
			$cell_renderer = new GtkCellRendererToggle();
			$column = new GtkTreeViewColumn($headers[$i], $cell_renderer, "active", $i);
		}
		else
		{
			$cell_renderer = new GtkCellRendererText();
			$column = new GtkTreeViewColumn($headers[$i], $cell_renderer, "text", $i);
		}

		$view->append_column($column);
	}

	foreach(array_keys($data) as $r)
	{
		$values = array();

		$data[$r] = pts_to_array($data[$r]);
		foreach(array_keys($data[$r]) as $c)
		{
			array_push($values, $data[$r][$c]);
		}
		$model->append($values);
	}

	$selection = $view->get_selection();

	if($allow_multiple_select)
	{
		$selection->set_mode(Gtk::SELECTION_MULTIPLE);
	}

	if($connect_to != null)
	{
		$selection->connect("changed", $connect_to);
	}

	return $scrolled_window;
}
function pts_gtk_2d_array_to_labels($array_2d, &$col_1, &$col_2)
{
	foreach($array_2d as $head => $show)
	{
		$label_head = new pts_gtk_label(($show == null ? null : "<b>" . $head . ":</b> "));
		$label_head->set_alignment(0, 0);
		$label_head->set_padding(0, 0);
		array_push($col_1, $label_head);

		$label_show = new GtkLabel($show);
		$label_show->set_alignment(0, 0);
		array_push($col_2, $label_show);
	}
}
function pts_gtk_array_to_boxes($widget, $items, $set_spacing = -1, $append_to = false)
{
	if($append_to)
	{
		$add_to = $widget;
	}
	else if($widget instanceOf GtkHBox)
	{
		$add_to = new GtkVBox();
		$widget->pack_start($add_to, false, false);
		$add_to->set_homogeneous(true);
	}
	else if($widget instanceOf GtkVBox)
	{
		$add_to = new GtkHBox();

		if(count($items) > 0 && $items[0] instanceOf GtkFrame)
		{
			$widget->pack_start($add_to);
		}
		else
		{
			$widget->pack_start($add_to, false, false);
		}

		$add_to->set_homogeneous(true);
	}
	else
	{
		$add_to = new GtkVBox();

		if($widget instanceOf GtkScrolledWindow)
		{
			$widget->add_with_viewport($add_to);
		}
		else
		{
			$widget->add($add_to);
		}
	}

	if($set_spacing != -1 && method_exists($add_to, "set_spacing"))
	{
		$add_to->set_spacing($set_spacing);
	}

	foreach($items as &$item)
	{
		if(is_array($item))
		{
			pts_gtk_array_to_boxes($add_to, $item, $set_spacing, false);
		}
		else if($item == null)
		{
			$add_to->pack_start(new GtkLabel(" "));
		}
		else
		{
			$add_to->pack_start($item);
		}
	}

	return $add_to;
}
function pts_gtk_selected_item($object)
{
	list($model, $iter) = $object->get_selected();

	return $model->get_value($iter, 0);
}
function pts_gtk_selected_items($object)
{
	list($model, $rows) = $object->get_selected_rows();
	$return_items = array();

	if(is_array($rows))
	{
		foreach($rows as $row)
		{
			$iter = $model->get_iter($row);
			array_push($return_items, $model->get_value($iter, 0));
		}
	}

	return $return_items;
}
function pts_gtk_add_notebook_tab(&$notebook, $widget, $label)
{
	$page_no = $notebook->append_page($widget, new GtkLabel($label));
}
function pts_gtk_object_set_sensitive($object, $sensitive)
{
	$o = pts_read_assignment($object);
	$o->set_sensitive($sensitive);
}
function pts_gtk_add_dynamic_notebook_tab(&$notebook, $tab_label, $tab_on_click, $list_label, $list_function, $list_on_click, $on_empty_list)
{
	$t_label = new GtkLabel($tab_label);
	$t_label->show();
	$t_event_box = new GtkEventBox();
	$t_event_box->add($t_label);
	$t_event_box->connect("button-press-event", $tab_on_click, $tab_label);

	$vbox = new GtkVBox();
	if($notebook->get_n_pages() == 0)
	{
		call_user_func("pts_gtk_fill_notebook_tab", $vbox, $list_label, $list_function, $list_on_click, $on_empty_list);
	}
	else
	{
		$loading_label = new GtkLabel("Loading Data...");
		$vbox->pack_start($loading_label);
	}
	$t_event_box->connect_simple("button-press-event", "pts_gtk_fill_notebook_tab", $vbox, $list_label, $list_function, $list_on_click, $on_empty_list);

	$notebook->append_page($vbox, $t_event_box);
}
function pts_gtk_fill_notebook_tab($vbox, $list_label, $list_function, $list_on_click, $on_empty_list)
{
	if(!pts_is_assignment("GTK_DYNAMIC_TAB_" . strtoupper($list_function)))
	{
		$list_label = pts_to_array($list_label);
		$tab_gtk_label = pts_gtk_table($list_label, call_user_func($list_function), $list_on_click, $on_empty_list);

		foreach($vbox->get_children() as $child)
		{
			$vbox->remove($child);
		}

		$vbox->pack_start($tab_gtk_label);

		gui_gtk::redraw_main_window();
		pts_set_assignment("GTK_DYNAMIC_TAB_" . strtoupper($list_function), true);
	}
}

?>
