<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

class pts_result_file_system
{
	protected $identifier;
	protected $hardware;
	protected $software;
	protected $json;
	protected $username;
	protected $notes;
	protected $timestamp;
	protected $client_version;

	public function __construct($identifier, $hardware, $software, $json, $username, $notes, $timestamp, $client_version)
	{
		$this->identifier = $identifier;
		$this->hardware = $hardware;
		$this->software = $software;
		$this->json = $json;
		$this->username = $username;
		$this->notes = $notes;
		$this->timestamp = $timestamp;
		$this->client_version = $client_version;
	}
	public function __toString()
	{
		return $this->get_identifier() . ' ' . $this->get_hardware() . ' ' . $this->get_software();
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_hardware()
	{
		return $this->hardware;
	}
	public function get_software()
	{
		return $this->software;
	}
	public function get_json()
	{
		return $this->json;
	}
	public function get_username()
	{
		return $this->username;
	}
	public function get_notes()
	{
		return $this->notes;
	}
	public function get_timestamp()
	{
		return $this->timestamp;
	}
	public function get_client_version()
	{
		return $this->client_version;
	}
	public function set_identifier($new_id)
	{
		$this->identifier = $new_id;
	}
}

?>
