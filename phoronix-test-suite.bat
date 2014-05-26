::	Phoronix Test Suite
::	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
::	Copyright (C) 2008 - 2014, Phoronix Media
::	Copyright (C) 2008 - 2014, Michael Larabel
::	phoronix-test-suite: The Phoronix Test Suite is an extensible open-source testing / benchmarking platform
::
::	This program is free software; you can redistribute it and/or modify
::	it under the terms of the GNU General Public License as published by
::	the Free Software Foundation; either version 3 of the License, or
::	(at your option) any later version.
::
::	This program is distributed in the hope that it will be useful,
::	but WITHOUT ANY WARRANTY; without even the implied warranty of
::	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
::	GNU General Public License for more details.
::
::	You should have received a copy of the GNU General Public License
::	along with this program. If not, see <http://www.gnu.org/licenses/>.
::

:: Full path to root directory of the actual Phoronix Test Suite code
@echo off
set PTS_DIR=%cd%
set PTS_MODE=CLIENT

:: TODO: Other work to bring this up to sync with the *NIX phoronix-test-suite launcher
If defined PHP_BIN goto SkipBinSearch
echo "No PHP_BIN defined checking for usual locations."
If exist "C:\Program Files (x86)\PHP"\php set PHP_BIN="C:\Program Files (x86)\PHP"\php
If exist C:\php-gtk2\php set PHP_BIN=C:\php-gtk2\php
set PHP_BIN="C:\Program Files (x86)\PHP"\php

:SkipBinSearch
cls
%PHP_BIN% pts-core/phoronix-test-suite.php %*
