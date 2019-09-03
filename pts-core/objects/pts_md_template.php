<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class pts_md_template
{
	protected $md;

	public function __construct()
	{
		$this->md = null;
	}
	public function html_to_md($html)
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

		$section_title = $dom->getElementsByTagName('html')->item(0)->getElementsByTagName('head')->item(0)->nodeValue;
		if(!empty($section_title))
		{
			$this->md .= PHP_EOL . '# ' . trim($section_title) . PHP_EOL;
		}

		$tags = $dom->getElementsByTagName('html')->item(0)->getElementsByTagName('body')->item(0)->childNodes;

		for($i = 0; $i < $tags->length; $i++)
		{
			$name = $tags->item($i)->nodeName;
			$value = $tags->item($i)->nodeValue;
			$dom_item = $tags->item($i);

			switch($name)
			{
				case 'h1':
					$this->md .= PHP_EOL . '## ' . trim($value) . PHP_EOL;
					break;
				case 'h2':
					$this->md .= PHP_EOL . '### ' . trim($value) . PHP_EOL;
					break;
				case 'h3':
					$this->md .= '#### ' . trim($value) . PHP_EOL;
					break;
				case 'ol':
				case 'ul':
					$this->md .= PHP_EOL;
					$list_count = 1;
					foreach($tags->item($i)->childNodes as $j => $li)
					{
						$this->md .= PHP_EOL . trim(($name == 'ol' ? $list_count . '.' : '+') . ' ' . $li->nodeValue);
						$list_count++;
					}
					$this->md .= PHP_EOL;
					break;
				case 'li':
					$this->md .= trim($this->html_text_interpret($dom_item));
					break;
				case 'blockquote':
					$this->md .= PHP_EOL . '> ' . str_replace("\n", "  \n> ", htmlentities($value)) . PHP_EOL . PHP_EOL;
					break;
				case 'p':
					$this->md .= trim($this->html_text_interpret($dom_item)) . PHP_EOL . PHP_EOL;
					break;
				case 'hr':
					$this->md .= PHP_EOL . '---' . PHP_EOL;
					break;
				case '#text':
					$this->md .= trim($value);
					break;
				default:
					echo PHP_EOL . $name . ' is unhandled.' . PHP_EOL;
					break;
			}
		}
	}
	protected function html_text_interpret(&$dom_item)
	{
		$text = null;
		for($j = 0; property_exists($dom_item, 'length') == false && $j < $dom_item->childNodes->length; $j++)
		{
			$value = $dom_item->childNodes->item($j)->nodeValue;
			$name = $dom_item->childNodes->item($j)->nodeName;

			switch($name)
			{
				case 'em':
					$text .= ' *' . $value . '* ';
					break;
				case 'u':
					$text .= ' _' . $value . '_ ';
					break;
				case 'strong':
					$text .= ' **' . $value . '** ';
					break;
				case '#text':
					$text .= trim($value);
					break;
				case 'a':
					$text .= ' [' . $value . '](' . $dom_item->childNodes->item($j)->attributes->getNamedItem('href')->nodeValue . ') ';
					break;
				case 'br':
					$text .= PHP_EOL;
					break;
				default:
					echo "UNSUPPORTED: $name: $value\n";
					break;
			}
		}

		return $text;
	}
	public function Output($to = null)
	{
		if($to == null)
		{
			return $this->md;
		}
		else
		{
			file_put_contents($to, $this->md);
		}
	}
}

?>
