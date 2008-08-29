#!/bin/sh

rm -rf bandwidth-0.13/

tar -xvf bandwidth-0.13.tar.gz

rm -f memory-bandwidth

echo "#!/bin/sh

case \"\$1\" in
\"TEST_L2READ\")
	./bandwidth -l2read | grep \"L2 cache sequential read\"
	;;
\"TEST_L2WRITE\")
	./bandwidth -l2write | grep \"L2 cache sequential write\"
	;;
\"TEST_READ\")
	./bandwidth -read | grep \"Main memory sequential read\"
	;;
\"TEST_WRITE\")
	./bandwidth -write | grep \"Main memory sequential write\"
	;;
esac
" > memory-bandwidth
chmod +x memory-bandwidth

cd bandwidth-0.13/
patch -p1 <<'EOT'
--- bandwidth-0.13/main.c	2007-08-13 02:36:55.000000000 +0200
+++ bandwidth-0.13-patched/main.c	2008-04-07 03:23:59.000000000 +0200
@@ -403,9 +403,52 @@
 	free(a2);
 }
 
+void usage(char *argv0)
+{
+	printf ("usage: %s -l2read -l2write -read -write -fb -library\n"
+		"or: %s -all\n"
+		"will run the specified test(s), or all of them\n",
+		argv0, argv0);
+}
 
-main()
+int main(int argc, char **argv)
 {
+	char *argv0 = argv[0] ? argv[0] : "bandwidth";
+	int do_l2_seq_read = 0,
+	    do_l2_seq_write = 0,
+	    do_main_seq_read = 0,
+	    do_main_seq_write = 0,
+	    do_fb_readwrite = 0,
+	    do_library_test = 0,
+	    do_all_tests = 0;
+
+	if (argc <= 1) {
+		usage(argv0);
+		exit(1);
+	}
+	while (argc > 1) {
+		if (!strcmp(argv[1], "-all"))
+			do_all_tests = 1;
+		else if (!strcmp(argv[1], "-l2read"))
+			do_l2_seq_read = 1;
+		else if (!strcmp(argv[1], "-l2write"))
+			do_l2_seq_write = 1;
+		else if (!strcmp(argv[1], "-read"))
+			do_main_seq_read = 1;
+		else if (!strcmp(argv[1], "-write"))
+			do_main_seq_write = 1;
+		else if (!strcmp(argv[1], "-fb"))
+			do_fb_readwrite = 1;
+		else if (!strcmp(argv[1], "-library"))
+			do_library_test = 1;
+		else {
+			usage(argv0);
+			exit(1);
+		}
+		--argc;
+		++argv;
+	}
+
 	printf ("This is bandwidth version %s\n", VERSION);
 	printf ("Copyright (C) 2005,2007 by Zack T Smith\n\n");
 
@@ -414,15 +457,21 @@
 	system ("grep MHz /proc/cpuinfo | uniq | sed \"s/[\\t\\n: a-zA-Z]//g\"");
 	fflush (stdout);
 
-	l2_seq_read ();
-	l2_seq_write ();
-
-	main_seq_read ();
-	main_seq_write ();
+	if(do_l2_seq_read || do_all_tests)
+		l2_seq_read ();
+	if(do_l2_seq_write || do_all_tests)
+		l2_seq_write ();
+
+	if(do_main_seq_read || do_all_tests)
+		main_seq_read ();
+	if(do_main_seq_write || do_all_tests)
+		main_seq_write ();
 
-	fb_readwrite();
+	if(do_fb_readwrite || do_all_tests)
+		fb_readwrite();
 
-	library_test();
+	if(do_library_test || do_all_tests)
+		library_test();
 
 	return 0;
 }

EOT
make
ln bandwidth ../
