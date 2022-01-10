<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel

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

/*
if(is_file('/usr/share/php/fpdf/fpdf.php'))
{
	include_once('/usr/share/php/fpdf/fpdf.php');
}
*/

class pts_pdf_template extends FPDF
{
	private $pts_title = '';
	private $pts_sub_title = '';
	private $pdf_bookmarks = array();
	private $pdf_bookmarks_outline_object_n = 0;

	public function __construct($Title = '', $SubTitle = '')
	{
		parent::__construct();

		$this->pts_title = $Title;
		$this->pts_sub_title = $SubTitle;

		$this->SetTitle($Title);
		$this->SetAuthor('Phoronix Test Suite');
		$this->SetCreator(pts_core::program_title());
		$this->SetCompression(false);
	}
	public function html_to_pdf($html)
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
		$tags = $dom->getElementsByTagName('html')->item(0)->getElementsByTagName('body')->item(0)->childNodes;

		$this->SetLeftMargin(8.5);
		$this->AddPage();
		$this->CreateBookmark($section_title, 0);
		$this->SetFont('Arial', 'B', 21);
		$this->SetTextColor(50, 51, 49);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $section_title, 0, 0, 'L', true);
		$this->Ln(10);

		for($i = 0; $i < $tags->length; $i++)
		{
			$name = $tags->item($i)->nodeName;
			$value = $tags->item($i)->nodeValue;
			$dom_item = $tags->item($i);

			switch($name)
			{
				case 'h1':
					$this->CreateBookmark($value, 1);
					$this->SetFont('Arial', 'B', 16);
					$this->SetLeftMargin(8.5);
					$this->SetTextColor(9, 123, 239);
					$this->Ln();
					$this->html_text_interpret('h1', $dom_item);
					$this->Ln();
					break;
				case 'h2':
					$this->CreateBookmark($value, 2);
					$this->SetLeftMargin(10);
					$this->SetFont('Arial', 'B', 14);
					$this->SetTextColor(5, 124, 166);
					$this->Ln();
					$this->SetTopMargin(30);
					$this->html_text_interpret('h2', $dom_item);
					$this->Ln();
					break;
				case 'h3':
					$this->SetLeftMargin(10);
					$this->SetFont('Arial', 'B', 13);
					$this->SetTextColor(5, 124, 166);
					$this->Ln();
					$this->html_text_interpret('h3', $dom_item);
					$this->SetLeftMargin(10);
					$this->Ln();
					break;
				case 'ol':
				case 'ul':
					$this->SetFont('Arial', null, 11);
					$this->SetLeftMargin(30);
					$this->SetTextColor(0, 0, 0);

					$this->Ln();
					foreach($tags->item($i)->childNodes as $j => $li)
					{
						if($name == 'ol' && ($j % 2) == 0)
						{
							$this->SetFont(null, 'B');
							$this->Write(5, ceil($j / 2) + 1 . '. ', null);
							$this->SetFont(null, null);
						}

						$this->html_text_interpret('p', $li);
						$this->Ln();
					}
					$this->SetLeftMargin(10);
					$this->Ln();
					break;
				case 'li':
					$this->SetFont('Arial', null, 11);
					$this->SetLeftMargin(30);
					$this->SetTextColor(0, 0, 0);
					$this->html_text_interpret('li', $dom_item);
					$this->Ln();
					break;
				case 'p':
				case 'blockquote':
					$this->SetFont('Arial', null, 11);
					$this->SetLeftMargin(20);
					$this->SetTextColor(0, 0, 0);
					$this->Ln();
					$this->html_text_interpret('p', $dom_item);
					$this->SetLeftMargin(10);
					$this->Ln();
					break;
				case 'hr':
					$this->Ln(8);
					break;
			}
		}
	}
	protected function html_text_interpret($apply_as_tag, &$dom_item)
	{
		for($j = 0; property_exists($dom_item, 'length') == false && $j < $dom_item->childNodes->length; $j++)
		{
			$value = $dom_item->childNodes->item($j)->nodeValue;
			$name = $dom_item->childNodes->item($j)->nodeName;

			switch($name)
			{
				case 'em':
					$this->SetFont(null, 'I', (substr($apply_as_tag, 0, 1) == 'h' ? '12' : null));
					break;
				case 'u':
					$this->SetFont(null, 'U', (substr($apply_as_tag, 0, 1) == 'h' ? '12' : null));
					break;
				case 'strong':
					$this->SetFont(null, 'B');
					break;
				case '#text':
					$this->SetFont(null, (substr($apply_as_tag, 0, 1) == 'h' ? 'B' : null));
					break;
				case 'a':
					$this->SetTextColor(0, 0, 116);
					$this->SetFont(null, 'BU');
					$this->Write(5, $value, $dom_item->childNodes->item($j)->attributes->getNamedItem('href')->nodeValue);
					$this->SetTextColor(0, 0, 0);
					break;
				default:
					//echo "UNSUPPORTED: $name: $value\n";
					break;
			}

			if($name != 'a')
			{
				$value = str_replace('&nbsp;', ' ', $value);
				$this->Write(5, $value, null);
			}
		}
	}
	public function Header()
	{
		if($this->PageNo() == 1)
		{
			return;
		}

		if(is_file(PTS_CORE_STATIC_PATH . 'images/pts-158x82.jpg'))
		{
			$this->Image(PTS_CORE_STATIC_PATH . 'images/pts-158x82.jpg', 10, 8, 30);
		}
		$this->SetFont('Arial', 'B', (isset($this->pts_title[50]) ? 10 : 14));
		$this->SetTextColor(0, 0, 0);
		$this->Cell(80);
		$this->Cell(30, 10, $this->pts_title, 0, 0, 'C');
		$this->Ln(6);
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(0, 10, $this->pts_sub_title, 0, 0, 'C');
		$this->Ln(15);
   		$this->SetDrawColor(5, 124, 166);
		$this->Line(10, 27, 210-10, 27);
	}
	public function Footer()
	{
		if($this->PageNo() == 1)
		{
			return;
		}
		$this->SetY(-15);
   		$this->SetDrawColor(5, 124, 166);
		$this->Line(10, $this->y, 210-10, $this->y);
		$this->SetY(-10);
		$this->SetFont('Arial', 'B', 7);
		$this->SetTextColor(0, 0, 0);
		$this->Cell(0, 0, pts_core::program_title(), 0, 0, 'L');
		$this->Cell(0, 0, 'www.phoronix-test-suite.com', 0, 0, 'R', true, 'http://www.phoronix-test-suite.com/');
	}
	public function WriteBigHeaderCenter($Header)
	{
		$this->WriteBigHeader($Header, 'C');
	}
	public function WriteBigHeader($Header, $Align = 'L')
	{
		$this->SetFont('Arial', 'B', isset($Header[50]) ? 15 : 21);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, $Align, true);
		$this->Ln(15);
	}
	public function WriteHeaderCenter($Header)
	{
		$this->WriteHeader($Header, 'C');
	}
	public function WriteHeader($Header, $Align = 'L')
	{
		$this->SetFont('Arial', 'B', 16);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, $Align, true);
		$this->Ln(10);
	}
	public function WriteStatementCenter($Header)
	{
		$this->SetTextColor(0, 85, 0);
		$this->WriteStatement($Header, 'C');
		$this->SetTextColor(0, 0, 0);
	}
	public function WriteStatement($Header, $Align = 'L', $Link = null)
	{
		$this->SetFont('Arial', 'B', 14);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 2, $Header, 0, 0, $Align, true, $Link);
		$this->Ln(10);
	}
	public function WriteMiniHeader($Header)
	{
		$this->SetFont('Arial', 'B', 13);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 2, $Header, 0, 0, 'L', true);
		$this->Ln(5);
	}
	public function WriteDocHeader($Header, $Options = null)
	{
		$this->SetFont('Arial', 'B', 12);
		$this->SetFillColor(255, 255, 255);
		$this->Write(12, $Header);

		if(is_array($Options))
		{
			$this->Write(12, '  ');
			$this->SetFont('Arial', 'I', 10);
			$this->Write(12, implode(' ', $Options));
		}

		$this->Ln(10);
	}
	public function WriteDocText($Text)
	{
		$this->SetFont('Arial', '', 10);
		$this->MultiCell(0, 5, $Text);
	}
	public function WriteText($Text, $I = '')
	{
		$this->SetFont('Arial', $I, 10);
		$this->MultiCell(0, 5, $Text);
		$this->SetFont('Arial', '', 10);
		$this->Ln();
	}
	public function WriteMiniText($Text)
	{
		$this->SetFont('Arial', '', 7);
		$this->MultiCell(0, 3, $Text);
		$this->SetFont('Arial', '', 10);
		//$this->Ln();
	}
	public function ResultTable($headers, $data, $hints = null)
	{
		$this->SetFont('Arial', '', 9);
		$this->Ln(20);
		//$this->SetFillColor(0, 0, 0);
		$this->SetTextColor(0, 0, 0);
		$this->SetDrawColor(34, 34, 34);
		$this->SetLineWidth(0.3);
		$this->SetFont('Arial', '');

		$cell_width = floor($this->w / (count($headers) + 2));
		$cell_widths = array();
		$cell_large_width = round($cell_width * 2);
		$table_width = 0;

		array_push($cell_widths, $cell_large_width);
		for($i = 1; $i < count($headers); $i++)
		{
			array_push($cell_widths, $cell_width);
			//$this->Cell($cell_width, 2, $headers[$i], 1, 0, 'D', true);
		}
		$row_num = 0;
		$this->Row($headers, $cell_widths, $row_num);

		//$this->SetFillColor(139, 143, 124);
		//$this->SetTextColor(0);
		$this->SetFont('Arial');

		$fill = false;
		for($i = 0; $i < count($data); $i++)
		{
			$this->Row($data[$i], $cell_widths, $row_num, (isset($hints[$i]) ? $hints[$i] : null));
		}
		//$this->Cell($table_width + (count($data[0]) * $cell_width), 0, '', 'T');
	}
	public function _putinfo()
	{
		$this->_out('/Producer ' . $this->_textstring('Phoronix Test Suite'));
		$this->_out('/Subject ' . $this->_textstring('Phoronix-Test-Suite.com'));
		$this->_out('/Title ' . $this->_textstring($this->title));
		$this->_out('/Subject ' . $this->_textstring($this->subject));
		$this->_out('/Author ' . $this->_textstring($this->author));
		$this->_out('/Keywords ' . $this->_textstring($this->keywords));
		$this->_out('/Creator ' . $this->_textstring($this->creator));
		$this->_out('/CreationDate ' . $this->_textstring('D:' . date('YmdHis')));
	}


	// PDF Bookmarking Support
	// Example: http://www.fpdf.org/en/script/script1.php

	public function CreateBookmark($bookmark, $level = 0)
	{
		$this->pdf_bookmarks[] = array(
			't' => $bookmark,
			'l' => $level,
			'y' => (($this->h - $this->getY()) * $this->k),
			'p' => $this->PageNo()
			);
	}
	protected function insert_pdf_bookmarks()
	{
		$bookmark_count = count($this->pdf_bookmarks);
		$level = 0;
		$ls = array();

		foreach($this->pdf_bookmarks as $i => &$o)
		{
			if($o['l'] > 0)
			{
				$this->pdf_bookmarks[$i]['parent'] = $ls[($o['l'] - 1)];
				$this->pdf_bookmarks[$ls[($o['l'] - 1)]]['last'] = $i;

				if($o['l'] > $level)
				{
					$this->pdf_bookmarks[$ls[($o['l'] - 1)]]['first'] = $i;
				}
			}
			else
			{
				$this->pdf_bookmarks[$i]['parent'] = $bookmark_count;
			}

			if($o['l'] <= $level && $i > 0)
			{
				$this->pdf_bookmarks[$ls[$o['l']]]['next'] = $i;
				$this->pdf_bookmarks[$i]['prev'] = $ls[$o['l']];
			}

			$ls[$o['l']] = $i;
			$level = $o['l'];
		}

		$n = $this->n + 1;

		foreach($this->pdf_bookmarks as $i => &$o)
		{
			$this->_newobj();
			$this->_out('<</Title ' . $this->_textstring($o['t']));
			$this->_out('/Parent ' . ($n + $o['parent']) . ' 0 R');

			if(isset($o['prev']))
			{
				$this->_out('/Prev '. ($n + $o['prev']) . ' 0 R');
			}
			if(isset($o['next']))
			{
				$this->_out('/Next ' . ($n + $o['next']) . ' 0 R');
			}
			if(isset($o['first']))
			{
				$this->_out('/First ' . ($n + $o['first']) . ' 0 R');
			}
			if(isset($o['last']))
			{
				$this->_out('/Last ' . ($n + $o['last']) . ' 0 R');
			}

			$this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]', (1 + 2 * $o['p']), $o['y']));
			$this->_out('/Count 0>>');
			$this->_out('endobj');
		}

		$this->_newobj();
		$this->pdf_bookmarks_outline_object_n = $this->n;
		$this->_out('<</Type /Outlines /First ' . $n . ' 0 R');
		$this->_out('/Last ' . ($n + (isset($ls[0]) ? $ls[0] : 0)) . ' 0 R>>');
		$this->_out('endobj');
	}
	public function _putresources()
	{
		parent::_putresources();
		$this->insert_pdf_bookmarks();
	}
	public function _putcatalog()
	{
		parent::_putcatalog();

		if(count($this->pdf_bookmarks) > 0)
		{
			$this->_out('/Outlines ' . $this->pdf_bookmarks_outline_object_n . ' 0 R');
			$this->_out('/PageMode /UseOutlines');
		}
	}
}

?>
