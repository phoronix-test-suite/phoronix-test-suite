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

class pts_ps_template 
{
	private $pts_title = "";
	private $pts_sub_title = "";
	private $ps_id = "";
	private $pages = 0;
	private $pageNo = 0;
	/**
		Default Constructor for Postscript Template Object
		@param $Title The title of the Postscript Document
		@param $SubTitle The subtitle of the Postscript Document
	*/
	function __construct($Title = "", $SubTitle = "")
	{
		parent::__construct();
		
		if(!extension_loaded("ps") || !function_exists("ps_new"))
		{
			echo "\nThe PS extension for PHP does not appear to be installed.\n\n";
			return;
		}
		
		$this->ps_id = ps_new();

		$this->pts_title = $Title;
		$this->pts_sub_title = $SubTitle;

		$this->SetTitle($Title);
		$this->SetAuthor("Phoronix Test Suite");
		$this->SetCreator(pts_codename(true));
		$this->SetCompression(false);
	}
	
	/**
		Loads an image and places it on the document. File type is assumed from file extension and thus $type is discarded. The $w and $h parameters are discarded as these are not needed by Postscript. $link is also discarded.
		@param $image_loc Path to image to be loaded
		@param $x Position along the X axis that the image will be placed on the document
		@param $y Position along the Y axis that the image will be placed on the document
		@param $w Discarded: Not Needed by Postscript
		@param $h Discarded: Not Needed by Postscript
		@param $type Discarded: File Type determined from File extension
		@param $link Discarded: Usage not applicable to Postscript
	*/
	function Image(string $image_loc, float $x, float $y, float $w, float $h, string $type, $link)
	{
		$DEFAULT_SCALE = 1.0;
		$temp = split(',', $image_loc);
		$image_ext = $temp[count($temp)-1];
		if($image_ext != "jpeg" || $image_ext != "png" || $image_ext != "eps")
		{
			echo "\nPostscript Generator was passed an unsupported file type: " . $image_loc . "\n";
		}
		$image_id = ps_open_image_file($this->ps_id, $image_ext, $image_loc);
		return ps_place_image($this->ps_id,$image_id,$x,$y,$DEFAULT_SCALE);
	}

	/**
		Returns the documents current Page Number
	*/
	function PageNo()
	{

	}

	/**
		Sets Document to print with the specified Font
		@param $font_name Name of Font to use
		@param $options Discarded: Plan to implement in the future
		@param $size Font Size
	*/
	function SetFont(string $font_name, string $options, int $size)
	{
		$font_id = ps_findfont($this->ps_id,$font_name, NULL);
		return ps_setfont($this->ps_id, $font_id, $size);
	}

	/**
		Inserts line breaks into the Document
		@param $lines The number of lines to insert
	*/
	function Ln(int $lines=1)
	{
		for(int $i=0; $i<$lines; i++)
		{
			ps_show($this->ps_id, "\n");
		}
	}
	
	/**
		Sets the color for filling.
		@param $c1 Represents the color Red in the RGB colorspace
		@param $c2 Represents the color Green in the RGB colorspace
		@param $c3 Represents the Color Blue in the RGB Colorspace
	*/
	function SetFillColor(int $c1, int $c2, int $c3)
	{
		ps_color($this->ps_id, "fill", "rgb", $c1, $c2, $c3);
	}
	/**
		Creates a Text Box
		@param $w width of the text box
		@param $h Hieght of the text box
		@param $text The text to display in the textbox
		@param $border Indicates if borders must be drawn around the cell. The value can be either a number or a string containing some or all of the following characters (in any order): LTRB
		@param $ln Indicates where the current position should go after the call.
		@param $align Allows to center or align the text.
		@param $fill Indicates if the cell background must be painted.
		@param $link Discarded: not applicable in Postscript
	*/
	function Cell(int $w, int $h, string $text, $border, int $ln, string $align, boolean $fill, $link)
	{
		
	}

	function Header()
	{
		if($this->PageNo() == 1)
		{
			return;
		}

		if(is_file(RESULTS_VIEWER_DIR . "pts-logo.jpg"))
		{
			$this->Image(RESULTS_VIEWER_DIR . "pts-logo.jpg");
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
		if($this->PageNo() == 1)
		{
			return;
		}

		$this->SetY(-10);
		$this->SetFont("Arial", "B", 7);
		$this->Cell(0, 0, pts_title(), 0, 0, "L");
		$this->Cell(0, 0, "www.phoronix-test-suite.com", 0, 0, "R");
	}
	function WriteBigHeaderCenter($Header)
	{
		$this->WriteBigHeader($Header, "C");
	}
	function WriteBigHeader($Header, $Align = "L")
	{
		$this->SetFont("Arial", "B", 21);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, $Align, true);
		$this->Ln(15);
	}
	function WriteHeaderCenter($Header)
	{
		$this->WriteHeader($Header, "C");
	}
	function WriteHeader($Header, $Align = "L")
	{
		$this->SetFont("Arial", "B", 16);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 6, $Header, 0, 0, $Align, true);
		$this->Ln(15);
	}
	function WriteStatementCenter($Header)
	{
		$this->WriteStatement($Header, "C");
	}
	function WriteStatement($Header, $Align = "L")
	{
		$this->SetFont("Arial", "B", 14);
		$this->SetFillColor(255, 255, 255);
		$this->Cell(0, 2, $Header, 0, 0, $Align, true);
		$this->Ln(10);
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
	function ResultTable($headers, $data, $left_headers = "")
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
}

?>
