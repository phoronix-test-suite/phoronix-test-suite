#!/bin/sh

if which tesseract>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Tesseract OCR is not found on the system! This test profile needs a working tesseract installation in the PATH. Check your system package manager for tesseract / tesseract-ocr."
	echo 2 > ~/install-exit-status
	exit
fi

tesseract --version | head -n 1 | awk '{ print $NF }' > ~/pts-test-version 2>&1

unzip -o ocr-image-samples-1.zip

echo "#!/bin/sh
tesseract --oem 1 ocr-sample-1.JPG output
tesseract --oem 1 ocr-sample-2.JPG output
tesseract --oem 1 ocr-sample-3.JPG output
tesseract --oem 1 ocr-sample-4.JPG output
tesseract --oem 1 ocr-sample-5.JPG output
tesseract --oem 1 ocr-sample-6.JPG output
tesseract --oem 1 ocr-sample-7.JPG output
echo \$? > ~/test-exit-status
tesseract --version | head -n 1 | awk '{ print \$NF }' > ~/pts-test-version 2>&1" > tesseract-ocr
chmod +x tesseract-ocr
