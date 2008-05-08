#!/bin/sh

cd $1

THIS_DIR=$(pwd)
mkdir $THIS_DIR/gtkperf_env

tar -xvf gtkperf_0.40.tar.gz
cd gtkperf/
./configure --prefix=$THIS_DIR/gtkperf_env
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf gtkperf/

echo "#!/bin/sh

if [ ! -f \$THIS_RUN_TIME.result ]
  then
	rm -f *.result
	./gtkperf_env/bin/gtkperf -a -c 2000 > \$THIS_RUN_TIME.result
fi

case \"\$1\" in
\"COMBOBOX\")
	cat \$THIS_RUN_TIME.result | grep \"GtkComboBox \"
	;;
\"COMBOBOX_ENTRY\")
	cat \$THIS_RUN_TIME.result | grep \"GtkComboBoxEntry \"
	;;
\"TOGGLE_BUTTON\")
	cat \$THIS_RUN_TIME.result | grep \"GtkToggleButton \"
	;;
\"CHECK_BUTTON\")
	cat \$THIS_RUN_TIME.result | grep \"GtkCheckButton \"
	;;
\"RADIO_BUTTON\")
	cat \$THIS_RUN_TIME.result | grep \"GtkRadioButton \"
	;;
\"TEXTVIEW_ADD\")
	cat \$THIS_RUN_TIME.result | grep \"GtkTextView - Add text\"
	;;
\"TEXTVIEW_SCROLL\")
	cat \$THIS_RUN_TIME.result | grep \"GtkTextView - Scroll\"
	;;
\"DRAWING_CIRCLES\")
	cat \$THIS_RUN_TIME.result | grep \"GtkDrawingArea - Circles\"
	;;
\"DRAWING_PIXBUFS\")
	cat \$THIS_RUN_TIME.result | grep \"GtkDrawingArea - Pixbufs\"
	;;
\"TOTAL_TIME\")
	cat \$THIS_RUN_TIME.result | grep \"Total\"
	;;
esac
" > gtkperf
chmod +x gtkperf
