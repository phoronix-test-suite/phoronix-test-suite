<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014, Phoronix Media
	Copyright (C) 2014, Michael Larabel

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

class start_phoromatic_event_server implements pts_option_interface
{
	const doc_skip = true;
	const doc_section = 'Phoromatic Event Server';
	const doc_description = 'The Phoromatic Event Server.';

	public static function run($r)
	{
		// TODO XXX: Hopefully this can be ultimately merged into the WebSocket server instance
		$event_server = new pts_phoromatic_event_server();
	}
}

?>
