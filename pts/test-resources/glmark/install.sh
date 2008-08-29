#!/bin/sh

tar -xvf GLMark-0.5.2.tar.gz
cd GLMark-0.5.2/

patch -p0 <<'EOT'
--- main.cpp	2008-04-26 04:30:21.000000000 -0400
+++ main.cpp.n	2008-05-25 10:21:22.000000000 -0400
@@ -17,16 +17,11 @@
     printf("===================================================\n");
     printf("    GLMark 08\n");
     printf("===================================================\n");
-    
-    printf("Enter screen width:  ");
-    scanf("%d", &screen.mWidth);
-    printf("Enter screen height: ");
-    scanf("%d", &screen.mHeight);
-    printf("Enter screen bpp:    ");
-    scanf("%d", &screen.mBpp);
-    printf("Enter '1' for fullscreen '0' for windowed: ");
-    scanf("%d", &screen.mFullScreen);
-    
+
+    screen.mWidth = atoi(argv[1]);
+    screen.mHeight = atoi(argv[2]);
+    screen.mBpp = 24;
+    screen.mFullScreen = 1;  
 
     printf("===================================================\n");
     if(!screen.init())
EOT

make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh

cd GLMark-0.5.2/

rm -f *.result
./glmark \$@ > \$THIS_RUN_TIME.result

case \"\$5\" in
\"VERTEX_ARRAY\")
	cat \$THIS_RUN_TIME.result | grep \"Vertex array\"
	;;
\"VERTEX_BUFFER_OBJ\")
	cat \$THIS_RUN_TIME.result | grep \"Vertex buffer object\"
	;;
\"TEXTURE_LINEAR\")
	cat \$THIS_RUN_TIME.result | grep \"Linear\"
	;;
\"TEXTURE_MIPMAPPED\")
	cat \$THIS_RUN_TIME.result | grep \"Mipmapped\"
	;;
\"GLSL_PER_VERTEX\")
	cat \$THIS_RUN_TIME.result | grep \"GLSL per vertex lighting\"
	;;
\"GLSL_PER_PIXEL\")
	cat \$THIS_RUN_TIME.result | grep \"GLSL per pixel lighting\"
	;;
esac
" > glmark
chmod +x glmark

