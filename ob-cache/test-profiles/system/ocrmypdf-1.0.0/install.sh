#!/bin/sh

if which ocrmypdf >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: OCRMyPDF is not found on the system! This test profile needs a working ocrmypdf in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

unzip -o text-pdf-example-1.zip

echo "#!/bin/sh
ocrmypdf -c -i --jobs \$NUM_CPU_CORES text-pdf-example-1.pdf out.pdf
echo \$? > ~/test-exit-status

ocrmypdf --version > ~/pts-test-version 2>/dev/null" > ocrmypdf-benchmark
chmod +x ocrmypdf-benchmark
