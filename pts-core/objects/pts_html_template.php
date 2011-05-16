<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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

class pts_html_template
{
	private $dom;
	private $dom_body;

	public function __construct($Title = '', $SubTitle = '')
	{
		$this->dom = new DOMDocument();
		$html = $this->dom->createElement('html');
		$this->dom->appendChild($html);
		$head = $this->dom->createElement('head');
		$title = $this->dom->createElement('title', $Title . ($SubTitle != null ? ' - ' . $SubTitle : null));
		$head->appendChild($title);
		$html->appendChild($head);
		$this->dom_body = $this->dom->createElement('body');
		$html->appendChild($this->dom_body);

		$p = $this->dom->createElement('h1', 'Phoronix Test Suite');
		$this->dom_body->appendChild($p);
	}
	public function html_to_html($html)
	{
		$dom = new DOMDocument();

		if(is_file($html))
		{
			$dom->loadHTMLFile($html);
		}
		else
		{
			$dom->loadHTML($html);
		}

		// TODO: the below code is known to emit a fatal error right now since the nodes are different, need to copy/merge nodes between docs
		foreach($dom->getElementsByTagName('html')->item(0)->getElementsByTagName('body')->item(0)->childNodes as $node)
		{
			$this->dom_body->appendChild($node);
		}

	}
	public function Output($File)
	{
		return $this->dom->saveHTMLFile($File);
	}
}

?>
