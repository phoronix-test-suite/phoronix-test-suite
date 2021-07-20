<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2015, Phoronix Media
	Copyright (C) 2011 - 2015, Michael Larabel

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
	private $section_list;

	public function __construct($Title = '', $SubTitle = '')
	{
		$this->dom = new DOMDocument();
		$this->dom->loadHTMLFile(PTS_CORE_STATIC_PATH . 'basic-template.html');
		$this->dom_body = $this->dom->getElementById('pts_template_body');

		//$head = $this->dom->getElementById('pts_template_head');
		//$title = $this->dom->createElement('title', $Title . ($SubTitle != null ? ' - ' . $SubTitle : null));
		//$head->appendChild($title);

		$this->section_list = $this->dom->createElement('ol');
		$this->dom_body->appendChild($this->section_list);
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

		$section_title = trim($dom->getElementsByTagName('html')->item(0)->getElementsByTagName('head')->item(0)->nodeValue);

		$section_li_a = $this->dom->createElement('a', $section_title);
		$section_li_a->setAttribute('href', '#' . str_replace(' ', '', $section_title));
		$section_li = $this->dom->createElement('li');
		$section_li->appendChild($section_li_a);
		$this->section_list->appendChild($section_li);

		$p = $this->dom->createElement('hr');
		$p->setAttribute('style', 'height: 50px; border: 0;');
		$this->dom_body->appendChild($p);
		$p = $this->dom->createElement('a');
		$p->setAttribute('name', str_replace(' ', '', $section_title));
		$this->dom_body->appendChild($p);
		$p = $this->dom->createElement('h1', $section_title);
		$this->dom_body->appendChild($p);
		// TODO: the below code is known to emit a fatal error right now since the nodes are different, need to copy/merge nodes between docs
		foreach($dom->getElementsByTagName('html')->item(0)->getElementsByTagName('body')->item(0)->childNodes as $node)
		{
			$n = $this->dom->importNode($node, true);
			$this->dom_body->appendChild($n);
		}

	}
	public function Output($File)
	{
		$p = $this->dom->createElement('p', 'Copyright &copy; 2008 - ' . date('Y') . ' by Phoronix Media.');
		$p->setAttribute('style', 'padding-top: 30px; text-align: center;');
		$this->dom_body->appendChild($p);
		return $this->dom->saveHTMLFile($File);
	}
}

?>
