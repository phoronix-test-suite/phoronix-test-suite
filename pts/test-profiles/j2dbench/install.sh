#!/bin/sh

unzip -o J2DBench.zip

echo "#!/bin/sh

rm -f *.output
rm -f *.res

case \"\$1\" in
\"TEST_ALL\")
  TEST_TYPE=all
	;;
\"TEST_GRAPHICS\")
  TEST_TYPE=graphics
	;;
\"TEST_IMAGES\")
  TEST_TYPE=images
	;;
\"TEST_TEXT\")
  TEST_TYPE=text
	;;
esac

java -Dsun.java2d.opengl=True -jar dist/J2DBench.jar \
-batch -loadopts \$TEST_TYPE.opt -saveres \$TEST_TYPE.res \
-title \$TEST_TYPE -desc \$TEST_TYPE > \$THIS_RUN_TIME.output

java -jar dist/J2DAnalyzer.jar \$TEST_TYPE.res > \$LOG_FILE" > j2dbench
chmod +x j2dbench
