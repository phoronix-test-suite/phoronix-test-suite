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

class pts_pdf_template extends FPDF
{
	private $pts_title = "";
	private $pts_sub_title = "";
	private $pdf_bookmarks = array();
	private $pdf_bookmarks_outline_object_n = 0;

	public function __construct($Title = "", $SubTitle = "")
	{
		parent::__construct();

		$this->pts_title = $Title;
		$this->pts_sub_title = $SubTitle;

		$this->SetTitle($Title);
		$this->SetAuthor("Phoronix Test Suite");
		$this->SetCreator(pts_codename(true));
		$this->SetCompression(false);
	}
	public function Header()
	{
		if($this->PageNo() == 1)
		{
			return;
		}

		if(is_file(PTS_CORE_STATIC_PATH . "images/pts-158x82.jpg"))
		{
			$this->Image(PTS_CORE_STATIC_PATH . "images/pts-158x82.jpg", 10, 8, 30);
		}

		$this->SetFont("Arial", "B", 14);
		$this->Cell(80);
		$this->Cell(30, 10, $this->pts_title, 0, 0, "C");
		$this->Ln(6);
		$this->SetFont("Arial", "B", 10);
		$this->Cell(0, 10, $this->pts_sub_title, 0, 0, "C");
		$this->Ln(15);
	}
	public function Footer()
	{
		if($this->PageNo() == 1)
		{
			return;
		}

		$this->SetY(-10);
		$this->SetFont("Arial", "B", 7);
		$this->Cell(0, 0, pts_title(), 0, 0, "L");
		$this->Cell(0, 0, "www.phoronix-test-suite.com", 0, 0, "R");
	}
	public function WriteBigHeaderCenter($Header)
	{
		$this->WriteBigHeader($Header, "C");
	}
	public function WriteBigHeader($Header, $Align = "L")
	{
		if($Align == 'L')
		{
			$this->CreateBookmark($Header, 0);
		}

		$this->SetFont("Arial", "B", 21);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, $Align, true);
		$this->Ln(15);
	}
	public function WriteHeaderCenter($Header)
	{
		$this->WriteHeader($Header, "C");
	}
	public function WriteHeader($Header, $Align = "L")
	{
		if($Align == 'L')
		{
			$this->CreateBookmark($Header, 1);
		}

		$this->SetFont("Arial", "B", 16);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, $Align, true);
		$this->Ln(15);
	}
	public function WriteStatementCenter($Header)
	{
		$this->WriteStatement($Header, "C");
	}
	public function WriteStatement($Header, $Align = "L")
	{
		$this->SetFont("Arial", "B", 14);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 2, $Header, 0, 0, $Align, true);
		$this->Ln(10);
	}
	public function WriteMiniHeader($Header)
	{
		$this->SetFont("Arial", "B", 13);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 2, $Header, 0, 0, "L", true);
		$this->Ln(10);
	}
	public function WriteDocHeader($Header, $Options = null)
	{
		$this->SetFont("Arial", "B", 12);
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
		$this->SetFont("Arial", "", 10);
		$this->MultiCell(0, 5, $Text);
	}
	public function WriteText($Text)
	{
		$this->SetFont("Arial", "", 11);
		$this->MultiCell(0, 5, $Text);
		$this->Ln();
	}
	public function ResultTable($headers, $data, $left_headers = "")
	{
		$this->Ln(20);
		$this->SetFillColor(0, 0, 0);
		$this->SetTextColor(255, 255, 255);
		$this->SetDrawColor(34, 34, 34);
		$this->SetLineWidth(0.3);
		$this->SetFont("Arial", "B");

		$cell_width = 50;
		$cell_large_width = $cell_width * 1.20;
		$table_width = 0;

		if(is_array($left_headers) && count($left_headers) > 0)
		{
			$this->Cell($cell_large_width, 7, "", 1, 0, "C", true);
			$table_width += $cell_large_width;
		}

		for($i = 0; $i < count($headers); $i++)
		{
			$this->Cell($cell_width, 7, $headers[$i], 1, 0, "C", true);
		}

		$this->Ln();

		$this->SetFillColor(139, 143, 124);
		$this->SetTextColor(0);
		$this->SetFont("Arial");

		$fill = false;
		for($i = 0; $i < count($data) || $i < count($left_headers); $i++)
		{
			if(isset($left_headers[$i]))
			{
				$this->Cell($cell_large_width, 6, $left_headers[$i], "LR", 0, "L", $fill);
			}

			if(!isset($data[$i]))
			{
				$data[$i] = array();
			}

			for($j = 0; $j < count($data[$i]); $j++)
			{
				$this->Cell($cell_width, 6, $data[$i][$j], "LR", 0, "L", $fill);
			}

			$this->Ln();
			$fill = !$fill;
		}
		$this->Cell($table_width + (count($data[0]) * $cell_width), 0, "", "T");
	}


	// PDF Bookmarking Support
	// Example: http://www.fpdf.org/en/script/script1.php

	public function CreateBookmark($bookmark, $level = 0)
	{
		array_push($this->pdf_bookmarks, array(
			't' => $bookmark,
			'l' => $level,
			'y' => (($this->h - $this->getY()) * $this->k),
			'p' => $this->PageNo()
			));
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
		$this->_out('/Last ' . ($n + $ls[0]) . ' 0 R>>');
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
