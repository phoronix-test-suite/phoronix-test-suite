#!/bin/sh

cmd /c "C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe" --version | head -n 1 | awk '{ print $NF }' > ~/pts-test-version 2>&1

unzip -o ocr-image-samples-1.zip

echo "#!/bin/sh
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-1.JPG output
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-2.JPG output
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-3.JPG output
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-4.JPG output
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-5.JPG output
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-6.JPG output
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --oem 1 ocr-sample-7.JPG output
echo \$? > ~/test-exit-status
cmd /c \"C:\Program Files (x86)\Tesseract-OCR\Tesseract.exe\" --version | head -n 1 | awk '{ print \$NF }' > ~/pts-test-version 2>&1" > tesseract-ocr
chmod +x tesseract-ocr
