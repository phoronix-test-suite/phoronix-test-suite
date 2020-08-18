#!/bin/sh
tar -xf Montage_v6.0.tar.gz
tar -xf Kimages.tar
rm -rf Montage-6.0
mv Montage Montage-6.0
cd Montage-6.0
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
cat << EOF > montage
#!/bin/bash
# Mosaic of M17, K band, 1.5 deg x 1.5 deg
# Bruce Berriman, February, 2016

export PATH=\$HOME/Montage-6.0/bin:\$PATH

echo "create directories to hold processed images"
mkdir Kprojdir diffdir corrdir

echo "Create a metadata table of the input images, Kimages.tbl"
mImgtbl Kimages Kimages.tbl

echo "Create a FITS header describing the footprint of the mosaic"
mMakeHdr Kimages.tbl Ktemplate.hdr

echo "Reproject the input images"
mProjExec -p Kimages Kimages.tbl Ktemplate.hdr Kprojdir Kstats.tbl

echo "Create a metadata table of the reprojected images"
mImgtbl Kprojdir/ images.tbl

echo "Coadd the images to create a mosaic without background corrections"
mAdd -p Kprojdir/ images.tbl Ktemplate.hdr m17_uncorrected.fits

echo "Make a PNG of the mosaic for visualization"
mViewer -ct 1 -gray m17_uncorrected.fits -1s max gaussian-log -out m17_uncorrected.png

echo "Analyze the overlaps between images"
mOverlaps images.tbl diffs.tbl
mDiffExec -p Kprojdir/ diffs.tbl Ktemplate.hdr diffdir
mFitExec diffs.tbl fits.tbl diffdir

echo "Perform background modeling and compute corrections for each image"
mBgModel images.tbl fits.tbl corrections.tbl

echo "Apply corrections to each image"
mBgExec -p Kprojdir/ images.tbl corrections.tbl corrdir

echo "Coadd the images to create a mosaic with background corrections"
mAdd -p corrdir/ images.tbl Ktemplate.hdr m17.fits

echo "Make a PNG of the corrected mosaic for visualization"
mViewer -ct 1 -gray m17.fits -1s max gaussian-log -out m17.png
echo \$? > ~/test-exit-status
EOF
