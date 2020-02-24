#!/bin/sh

./gimp-2.9.8-x64-setup.exe /SILENT /DIR=gimp-install
cd gimp-install/bin
./gimp-2.9.exe -i -b '(gimp-quit 0)'

cd ~
echo ";; Example from https://www.gimp.org/tutorials/Basic_Batch/

  (define (batch-unsharp-mask pattern
                              radius
                              amount
                              threshold)
  (let* ((filelist (cadr (file-glob pattern 1))))
    (while (not (null? filelist))
           (let* ((filename (car filelist))
                  (image (car (gimp-file-load RUN-NONINTERACTIVE
                                              filename filename)))
                  (drawable (car (gimp-image-get-active-layer image))))
             (plug-in-unsharp-mask RUN-NONINTERACTIVE
                                   image drawable radius amount threshold)
             (gimp-file-save RUN-NONINTERACTIVE
                             image drawable filename filename)
             (gimp-image-delete image))
           (set! filelist (cdr filelist)))))

;; Example from http://www.adp-gmbh.ch/misc/tools/script_fu/ex_09.html

  (define (batch-resize-image pattern
                              new-width
                              new-height)
  (let* ((filelist (cadr (file-glob pattern 1))))
    (while (not (null? filelist))
           (let* ((filename (car filelist))
                  (image (car (gimp-file-load RUN-NONINTERACTIVE
                                              filename filename)))
                  (drawable (car (gimp-image-get-active-layer image))))
		
             (gimp-image-scale image new-width new-height)
             (gimp-file-save RUN-NONINTERACTIVE
                             image drawable filename filename)
             (gimp-image-delete image))
           (set! filelist (cdr filelist)))))

;; http://photo.stackexchange.com/questions/63692/how-can-i-get-a-uniform-white-balance-on-a-batch-of-jpeg-images
(define (batch-auto-levels pattern)
(let* ((filelist (cadr (file-glob pattern 1))))
  (while (not (null? filelist))
         (let* ((filename (car filelist))
                (image (car (gimp-file-load RUN-NONINTERACTIVE
                                            filename filename)))
                (drawable (car (gimp-image-get-active-layer image))))
           (gimp-levels-stretch drawable)
           (gimp-file-save RUN-NONINTERACTIVE
                           image drawable filename filename)
           (gimp-image-delete image))
         (set! filelist (cdr filelist)))))


;; https://stackoverflow.com/questions/23554843/batch-rotate-files-with-gimp
  (define (batch-rotate pattern)
  (let* ((filelist (cadr (file-glob pattern 1))))
    (while (not (null? filelist))
           (let* ((filename (car filelist))
                  (image (car (gimp-file-load RUN-NONINTERACTIVE
                                              filename filename)))
                  (drawable (car (gimp-image-get-active-layer image))))

             (gimp-image-rotate image 0)

             (gimp-file-save RUN-NONINTERACTIVE
                             image drawable filename filename)
             (gimp-image-delete image))
           (set! filelist (cdr filelist)))))
" > bench.scm
cp -f *.scm gimp-install/share/gimp/2.0/scripts/bench.scm

echo "#!/bin/sh
# when getting more tests, will need to separate the below -b command into conditional statement check whats passed to this script

case \"\$1\" in

\"unsharp-mask\")
	BATCH_COMMAND='(batch-unsharp-mask \"*.JPG\" 15.0 0.6 0)'
    ;;
\"resize\")
	BATCH_COMMAND='(batch-resize-image \"*.JPG\" 600 400)'
    ;;
\"rotate\")
	BATCH_COMMAND='(batch-rotate \"*.JPG\")'
    ;;
\"auto-levels\")
	BATCH_COMMAND='(batch-auto-levels \"*.JPG\")'
    ;;
*)
	echo 2 > ~/test-exit-status
	exit
   ;;
esac

./gimp-install/bin/gimp-2.9.exe -i -b \"\$BATCH_COMMAND\" -b '(gimp-quit 0)' > \$LOG_FILE
./gimp-install/bin/gimp-2.9.exe --version | head -n 1 | awk '{ print \$NF }' > ~/pts-test-version" > gimp
chmod +x gimp
