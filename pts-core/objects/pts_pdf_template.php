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
	var $pts_title = "";
	var $pts_sub_title = "";

	function __construct($Title = "", $SubTitle = "")
	{
		parent::__construct();

		$this->pts_title = $Title;
		$this->pts_sub_title = $SubTitle;

		$this->SetTitle($Title);
		$this->SetAuthor("Phoronix Test Suite");
		$this->SetCreator(pts_codename(true));
	}

	function Header()
	{
		if(RESULTS_VIEWER_DIR . "pts-logo.jpg")
		{
			$this->Image(RESULTS_VIEWER_DIR . "pts-logo.jpg", 10, 8, 30);
		}

		$this->SetFont("Arial", "B", 14);
		$this->Cell(80);
		$this->Cell(30, 10, $this->pts_title, 0, 0, "C");
		$this->Ln(6);
		$this->SetFont("Arial", "B", 10);
		$this->Cell(0, 10, $this->pts_sub_title, 0, 0, "C");
		$this->Ln(10);
	}
	function Footer()
	{
		$this->SetY(-10);
		$this->SetFont("Arial", "B", 7);
		$this->Cell(0, 0, "Phoronix Test Suite v" . PTS_VERSION, 0, 0, "L");
		$this->Cell(0, 0, "http://www.phoronix-test-suite.com/", 0, 0, "R");
	}
	function WriteHeader($Header)
	{
		$this->SetFont("Arial", "B", 16);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, "L", true);
		$this->Ln(15);
	}
	function WriteMiniHeader($Header)
	{
		$this->SetFont("Arial", "B", 13);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 2, $Header, 0, 0, "L", true);
		$this->Ln(10);
	}
	function WriteText($Text)
	{
		$this->SetFont("Arial", "", 11);
		$this->MultiCell(0, 5, $Text);
		$this->Ln();
	}
}

?>
