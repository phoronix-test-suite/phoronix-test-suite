#!/bin/sh

if which libreoffice >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: libreoffice is not found on the system! This test profile needs a working LibreOffice installation"
	echo 2 > ~/install-exit-status
	exit 2
fi

unzip -o lo-sample-documents-1.zip

cd ~
echo "#!/bin/sh
rm -f *.pdf
libreoffice \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status
rm -f *.pdf
libreoffice --version | head -n 1 > ~/pts-footnote 2>/dev/null " > lo
chmod +x lo
