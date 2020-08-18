@echo off

:: Phoronix Test Suite
:: URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
:: Copyright (C) 2018 - 2020, Phoronix Media
::
:: This program is free software; you can redistribute it and/or modify
:: it under the terms of the GNU General Public License as published by
:: the Free Software Foundation; either version 3 of the License, or
:: (at your option) any later version.
::
:: This program is distributed in the hope that it will be useful,
:: but WITHOUT ANY WARRANTY; without even the implied warranty of
:: MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
:: GNU General Public License for more details.
::
:: You should have received a copy of the GNU General Public License
:: along with this program. If not, see <http://www.gnu.org/licenses/>.

:: Generic Phoronix Test Suite installer for Windows

:: Ensure the user is in the correct directory
If Not Exist "pts-core\phoronix-test-suite.php" (
echo "To install the Phoronix Test Suite you must first change directories to phoronix-test-suite. For support visit: http://www.phoronix-test-suite.com/"
exit
)
set destination="C:\phoronix-test-suite"
md %destination%
:: cd /d %destination%
:: for /F "delims=" %%i in ('dir /b') do (rmdir "%%i" /s/q || del "%%i" /s/q)

xcopy "%cd%" %destination% /E
echo Phoronix Test Suite installed to %destination%
