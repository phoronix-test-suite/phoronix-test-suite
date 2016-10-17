<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2016, Phoronix Media
	Copyright (C) 2012 - 2016, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

ini_set('memory_limit', '16192M');

class ob_test_profile_analyze implements pts_option_interface
{
	const doc_skip = true; // TODO XXX: cleanup this code before formally advertising this...
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option is intended for test profile creators and generates a range of meta-data and other useful information that can be submitted to OpenBenchmarking.org to provide more verbose information for users of your test profiles.';

	public static function run($r)
	{
		if(pts_openbenchmarking_client::user_name() == false)
		{
			echo PHP_EOL . 'You must first be logged into an OpenBenchmarking.org account.' . PHP_EOL;
			echo PHP_EOL . 'Create An Account: http://openbenchmarking.org/';
			echo PHP_EOL . 'Log-In Command: phoronix-test-suite openbenchmarking-setup' . PHP_EOL . PHP_EOL;
			return false;
		}

		ini_set('memory_limit', '2048M');
		foreach(pts_types::identifiers_to_test_profile_objects($r, false, true) as $test_profile)
		{
			$qualified_identifier = $test_profile->get_identifier();

			// First make sure the test profile is already in the OpenBenchmarking.org database...
			$json = pts_openbenchmarking::make_openbenchmarking_request('is_test_profile', array('i' => $qualified_identifier));
			$json = json_decode($json, true);

			if(!isset($json['openbenchmarking']['test']['valid']) || $json['openbenchmarking']['test']['valid'] != 'TRUE')
			{
				echo PHP_EOL . $qualified_identifier . ' must first be uploaded to OpenBenchmarking.org.' . PHP_EOL;
			//	break;
			}

			// Set some other things...
			pts_client::pts_set_environment_variable('FORCE_TIMES_TO_RUN', 1);
			pts_client::pts_set_environment_variable('TEST_RESULTS_NAME', $test_profile->get_title() . ' Testing ' . date('Y-m-d'));
			pts_client::pts_set_environment_variable('TEST_RESULTS_IDENTIFIER', 'Sample Run');
			pts_client::pts_set_environment_variable('TEST_RESULTS_DESCRIPTION', 1);

			pts_openbenchmarking_client::override_client_setting('AutoUploadResults', true);
			pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', true);

			// Take screenshots
			pts_client::pts_set_environment_variable('SCREENSHOT_INTERVAL', 9);
			pts_module_manager::attach_module('timed_screenshot');

			$force_ss = true;
			$reference_ss_file = pts_module_manager::module_call('timed_screenshot', 'take_screenshot', $force_ss);
			sleep(2);

			$apitrace = pts_file_io::glob('/usr/local/lib/*/apitrace/wrappers/glxtrace.so');

			if(!empty($apitrace) && pts_client::executable_in_path('apitrace'))
			{
				$apitrace = array_shift($apitrace);
				putenv('LD_PRELOAD=' . $apitrace);
			}
			else
			{
				$apitrace = false;
			}

			// So for any compiling tasks they will try to use the most aggressive instructions possible
			putenv('CFLAGS=-march=native -O3');
			putenv('CXXFLAGS=-march=native -O3');
			pts_test_installer::standard_install($qualified_identifier, true);
			$run_manager = new pts_test_run_manager(false, 2);
			$run_manager->standard_run($qualified_identifier);

			if($apitrace)
			{
				putenv('LD_PRELOAD=');
			}

			if($reference_ss_file)
			{
				$reference_ss = pts_image::image_file_to_gd($reference_ss_file);
				unlink($reference_ss_file);

				$screenshots_gd = array();
				$screenshots = pts_module_manager::module_call('timed_screenshot', 'get_screenshots');
var_dump($screenshots);
				foreach($screenshots as $ss_file)
				{
					$screenshots_gd[$ss_file] = pts_image::image_file_to_gd($ss_file);

					if($screenshots_gd[$ss_file] == false)
					{
						continue;
					}

					$ss_delta = pts_image::gd_image_delta_composite($reference_ss, $screenshots_gd[$ss_file], true);

					if(count($ss_delta) < floor(imagesx($reference_ss) * 0.56) || filesize($ss_file) > 2097152)
					{
						// If less than 56% of the pixels are changing on X, then likely not much to show off... (CLI only likely)
						// Or if filesize of image is beyond 2MB
						//echo 'dropping' . $ss_file . PHP_EOL;
						unset($screenshots_gd[$ss_file]);
						pts_file_io::unlink($ss_file);
					}
				}

				$ss_files = array_keys($screenshots_gd);
				shuffle($ss_files);

				// Don't upload more than 4MB worth of screenshots
				while(pts_file_io::array_filesize($ss_files) > (1048576 * 2))
				{
					$f = array_pop($ss_files);
					unlink($f);
				}

				if(count($ss_files) > 0)
				{
					$c = 1;
					foreach($ss_files as $i => $file)
					{
						$new_file = dirname($file) . '/screenshot-' . $c . '.png';
						rename($file, $new_file);
						$ss_files[$i] = $new_file;
						$c++;
					}

					$ss_zip_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . 'screenshots-' . $test_profile->get_identifier_base_name() . '-' . $test_profile->get_test_profile_version() . '.zip';
					$zip_created = pts_compression::zip_archive_create($ss_zip_file, $ss_files);
					if($zip_created)
					{
						echo count($ss_files) . ' screenshots captured for use.';
						//'tp_sha1' => sha1_file($zip_file),
						//'tp_zip' => base64_encode(file_get_contents($zip_file)),
					}

					foreach($ss_files as $file)
					{
					//	pts_file_io::unlink($file);
					}
				}
			}

			$test_binary = self::locate_test_profile_lead_binary($test_profile);

			$shared_library_dependencies = array();
			$instruction_usage = array();
			$gl_calls = null;

			if(is_executable($test_binary))
			{
				if($apitrace)
				{
					// Find the trace...
					$test_binary_dir = dirname($test_binary);
					$trace_file = glob($test_binary_dir . '/*.trace');

					if($trace_file)
					{
						echo 'Analyzing GL traces';
						$trace_file = array_shift($trace_file);
						$gl_usage = self::analyze_apitrace_trace_glpop($trace_file);

						if(!empty($gl_usage))
						{
							$gl_calls = implode(',', $gl_usage);
						}
					}
				}

				$ldd = trim(shell_exec('ldd ' . $test_binary));

				foreach(explode(PHP_EOL, $ldd) as $line)
				{
					$line = explode(' => ', $line);

					if(count($line) == 2)
					{
						$shared_library_dependencies[] = trim(basename($line[0]));
					}
				}

				echo PHP_EOL . 'SHARED LIBRARY DEPENDENCIES: ' . PHP_EOL;
				print_r($shared_library_dependencies);

				foreach(array('core-avx-i', 'bdver2') as $march)
				{
					// So for any compiling tasks they will try to use the most aggressive instructions possible
					putenv('CFLAGS=-march=' . $march . ' -O3');
					putenv('CXXFLAGS=-march=' . $march . ' -O3');
					pts_test_installer::standard_install($qualified_identifier, true);
					$instruction_usage[$march] = self::analyze_binary_instruction_usage($test_binary);

					if($instruction_usage[$march] == null)
					{
						unset($instruction_usage[$march]);
					}
				}

				if(!empty($instruction_usage) && count(array_unique($instruction_usage)) == 1)
				{
					$generic = array_pop($instruction_usage);

					$instruction_usage = array('generic' => $generic);
				}
				var_dump($instruction_usage);
			}
			else
			{
				echo PHP_EOL . $test_binary;
				echo PHP_EOL . 'Test binary could not be found.' . PHP_EOL;
		//		return false;
			}
		}
			sleep(10);

		var_dump($shared_library_dependencies);
		var_dump($instruction_usage);
		var_dump($gl_calls);

		$server_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_meta', array(
			'i' => $test_profile->get_identifier(),
			'screenshots_zip' => ($ss_zip_conts = base64_encode(file_get_contents($ss_zip_file))),
			'screenshots_zip_sha1' => sha1($ss_zip_conts),
			'ldd_libraries' => implode(',', $shared_library_dependencies),
			'opengl_calls' => $gl_calls,
			'instruction_set_usage' => base64_encode(json_encode($instruction_usage))
			));
var_dump($server_response);
			$json = json_decode($server_response, true);

		pts_file_io::unlink($ss_zip_file);
	}
	public static function locate_test_profile_lead_binary(&$test_profile)
	{
		$test_profile_launcher = $test_profile->get_test_executable_dir() . $test_profile->get_test_executable();

		if(!is_file($test_profile_launcher))
		{
			echo PHP_EOL . $test_profile_launcher . ' not found.' . PHP_EOL;
			return false;
		}
		$original_launcher_contents = file_get_contents($test_profile_launcher);
		$test_binary = false;

		if(($s = strpos($original_launcher_contents, '$LOG_FILE')))
		{
			$launcher_contents = substr($original_launcher_contents, 0, $s);
			$test_binary = pts_strings::first_in_string(trim(str_replace(array('	', '   ', 'mpirun', 'mpiexec', './'), '', substr($launcher_contents, strrpos($launcher_contents, PHP_EOL) + 1))));
		}

		if(strpos($test_binary, '.app') && strpos($original_launcher_contents, '$LOG_FILE') != ($s = strrpos($original_launcher_contents, '$LOG_FILE')))
		{
			$launcher_contents = substr($original_launcher_contents, 0, $s);
			$test_binary = pts_strings::first_in_string(trim(str_replace(array('	', '   ', 'mpirun', 'mpiexec', './'), '', substr($launcher_contents, strrpos($launcher_contents, PHP_EOL) + 1))));
		}

		if($test_binary)
		{
			if(is_executable($test_profile->get_test_executable_dir() . '/' . $test_binary))
			{
				$test_binary = $test_profile->get_test_executable_dir() . '/' . $test_binary;
			}
			else if(($s = strpos($launcher_contents, PHP_EOL . 'cd ')))
			{
				$cd = (substr($launcher_contents, ($s + 4)));
				$cd = substr($cd, 0, strpos($cd, PHP_EOL));

				if(is_executable($test_profile->get_test_executable_dir() . '/' . $cd . '/' . $test_binary))
				{
					$test_binary = $test_profile->get_test_executable_dir() . '/' . $cd . '/' . $test_binary;
				}
			}
		}

		return $test_binary;
	}
	public static function analyze_binary_instruction_usage(&$binary)
	{
		// Based on data from https://github.com/dirtyepic/scripts/blob/master/analyze-x86
		$instruction_checks = array(
		'MMX' => array('emms', 'maskmovq', 'movq', 'movntq', 'packssdw', 'packsswb', 'packuswb', 'paddb', 'paddd', 'paddsb', 'paddsw', 'paddusb', 'paddusw', 'paddw', 'pand', 'pandn', 'pavgusb', 'pavgb', 'pavgw', 'pcmpeqb', 'pcmpeqd', 'pcmpeqw', 'pcmpgtb', 'pcmpgtd', 'pcmpgtw', 'pextrw', 'pinsrw', 'pmaddwd', 'pmaxsw', 'pmaxub', 'pminsw', 'pminub', 'pmovmskb', 'pmulhw', 'pmullw', 'pmulhuw', 'por', 'psadbw', 'pshufw', 'pslld', 'psllq', 'psllw', 'psrad', 'psraw', 'psrld', 'psrlq', 'psrlw', 'psubb', 'psubd', 'psubsb', 'psubsw', 'psubusb', 'psubusw', 'psubw', 'punpckhbw', 'punpckhdq', 'punpckhwd', 'punpcklbw', 'punpckldq', 'punpcklwd', 'pxor'),
		'SSE' => array('addps', 'addss', 'andnps', 'andps', 'cmpeqps', 'cmpeqss', 'cmpleps', 'cmpless', 'cmpltps', 'cmpltss', 'cmpneqps', 'cmpneqss', 'cmpnleps', 'cmpnless', 'cmpnltps', 'cmpnltss', 'cmpordps', 'cmpordss', 'cmpps', 'cmpss', 'cmpunordps', 'cmpunordss', 'comiss', 'cvtpi2ps', 'cvtps2pi', 'cvtsi2ss', 'cvtss2si', 'cvttps2pi', 'cvttss2si', 'divps', 'divss', 'ldmxcsr', 'maxps', 'maxss', 'minps', 'minss', 'movaps', 'movhlps', 'movhps', 'movlhps', 'movlps', 'movmskps', 'movntps', 'movss', 'movups', 'mulps', 'mulss', 'orps', 'rcpps', 'rcpss', 'rsqrtps', 'rsqrtss', 'shufps', 'sqrtps', 'sqrtss', 'stmxcsr', 'subps', 'subss', 'ucomiss', 'unpckhps', 'unpcklps', 'xorps'),
		'SSE2' => array('addpd', 'addsd', 'andnpd', 'andpd', 'clflush', 'cmpeqpd', 'cmpeqsd', 'cmplepd', 'cmplesd', 'cmpltpd', 'cmpltsd', 'cmpneqpd', 'cmpneqsd', 'cmpnlepd', 'cmpnlesd', 'cmpnltpd', 'cmpnltsd', 'cmpordpd', 'cmpordsd', 'cmppd', 'cmpunordpd', 'cmpunordsd', 'comisd', 'cvtdq2pd', 'cvtdq2ps', 'cvtpd2dq', 'cvtpd2pi', 'cvtpd2ps', 'cvtpi2pd', 'cvtps2dq', 'cvtps2pd', 'cvtsd2si', 'cvtsd2ss', 'cvtsi2sd', 'cvtss2sd', 'cvttpd2dq', 'cvttpd2pi', 'cvttps2dq', 'cvttsd2si', 'divpd', 'divsd', 'maskmovdqu', 'maxpd', 'maxsd', 'minpd', 'minsd', 'movapd', 'movdq2q', 'movdqa', 'movdqu', 'movhpd', 'movlpd', 'movmskpd', 'movntdq', 'movnti', 'movntpd', 'movq2dq', 'movupd', 'mulpd', 'mulsd', 'orpd', 'paddq', 'pmuludq', 'pshufd', 'pshufhw', 'pshuflw', 'pslldq', 'psrldq', 'psubq', 'punpckhqdq', 'punpcklqdq', 'shufpd', 'sqrtpd', 'sqrtsd', 'subpd', 'subsd', 'ucomisd', 'unpckhpd', 'unpcklpd', 'xorpd', 'movd'),
		'SSE3' => array('addsubpd', 'addsubps', 'fisttp', 'haddpd', 'haddps', 'hsubpd', 'hsubps', 'lddqu', 'monitor', 'movddup', 'movshdup', 'movsldup', 'mwait'),
		'SSSE3' => array('pabsb', 'pabsd', 'pabsw', 'palignr', 'phaddd', 'phaddsw', 'phaddw', 'phsubd', 'phsubsw', 'phsubw', 'pmaddubsw', 'pmulhrsw', 'pshufb', 'psignb', 'psignd', 'psignw'),
		'SSE4_1' => array('blendpd', 'blendps', 'blendvpd', 'blendvps', 'dppd', 'dpps', 'extractps', 'insertps', 'movntdqa', 'mpsadbw', 'packusdw', 'pblendvb', 'pblendw', 'pcmpeqq', 'pextrb', 'pextrd', 'pextrq', 'phminposuw', 'pinsrb', 'pinsrd', 'pinsrq', 'pmaxsb', 'pmaxsd', 'pmaxud', 'pmaxuw', 'pminsb', 'pminsd', 'pminud', 'pminuw', 'pmovsxbd', 'pmovsxbq', 'pmovsxbw', 'pmovsxdq', 'pmovsxwd', 'pmovsxwq', 'pmovzxbd', 'pmovzxbq', 'pmovzxbw', 'pmovzxdq', 'pmovzxwd', 'pmovzxwq', 'pmuldq', 'pmulld', 'ptest', 'roundpd', 'roundps', 'roundsd', 'roundss'),
		'SSE4_2' => array('crc32', 'pcmpestri', 'pcmpestrm', 'pcmpgtq', 'pcmpistri', 'pcmpistrm', 'popcnt'),
		'SSE4A' => array('extrq', 'insertq', 'movntsd', 'movntss'),
		'AVX' => array('pclmulhqhqdq', 'pclmulhqlqdq', 'pclmullqhqdq', 'pclmullqlqdq', 'pclmulqdq', 'vaddpd', 'vaddps', 'vaddsd', 'vaddss', 'vaddsubpd', 'vaddsubps', 'vaesdec', 'vaesdeclast', 'vaesenc', 'vaesenclast', 'vaesimc', 'vaeskeygenassist', 'vandnpd', 'vandnps', 'vandpd', 'vandps', 'vblendpd', 'vblendps', 'vblendvpd', 'vblendvps', 'vbroadcastf128', 'vbroadcastsd', 'vbroadcastss', 'vcmpeq_ospd', 'vcmpeq_osps', 'vcmpeq_ossd', 'vcmpeq_osss', 'vcmpeqpd', 'vcmpeqps', 'vcmpeqsd', 'vcmpeqss', 'vcmpeq_uqpd', 'vcmpeq_uqps', 'vcmpeq_uqsd', 'vcmpeq_uqss', 'vcmpeq_uspd', 'vcmpeq_usps', 'vcmpeq_ussd', 'vcmpeq_usss', 'vcmpfalse_oqpd', 'vcmpfalse_oqps', 'vcmpfalse_oqsd', 'vcmpfalse_oqss', 'vcmpfalse_ospd', 'vcmpfalse_osps', 'vcmpfalse_ossd', 'vcmpfalse_osss', 'vcmpfalsepd', 'vcmpfalseps', 'vcmpfalsesd', 'vcmpfalsess', 'vcmpge_oqpd', 'vcmpge_oqps', 'vcmpge_oqsd', 'vcmpge_oqss', 'vcmpge_ospd', 'vcmpge_osps', 'vcmpge_ossd', 'vcmpge_osss', 'vcmpgepd', 'vcmpgeps', 'vcmpgesd', 'vcmpgess', 'vcmpgt_oqpd', 'vcmpgt_oqps', 'vcmpgt_oqsd', 'vcmpgt_oqss', 'vcmpgt_ospd', 'vcmpgt_osps', 'vcmpgt_ossd', 'vcmpgt_osss', 'vcmpgtpd', 'vcmpgtps', 'vcmpgtsd', 'vcmpgtss', 'vcmple_oqpd', 'vcmple_oqps', 'vcmple_oqsd', 'vcmple_oqss', 'vcmple_ospd', 'vcmple_osps', 'vcmple_ossd', 'vcmple_osss', 'vcmplepd', 'vcmpleps', 'vcmplesd', 'vcmpless', 'vcmplt_oqpd', 'vcmplt_oqps', 'vcmplt_oqsd', 'vcmplt_oqss', 'vcmplt_ospd', 'vcmplt_osps', 'vcmplt_ossd', 'vcmplt_osss', 'vcmpltpd', 'vcmpltps', 'vcmpltsd', 'vcmpltss', 'vcmpneq_oqpd', 'vcmpneq_oqps', 'vcmpneq_oqsd', 'vcmpneq_oqss', 'vcmpneq_ospd', 'vcmpneq_osps', 'vcmpneq_ossd', 'vcmpneq_osss', 'vcmpneqpd', 'vcmpneqps', 'vcmpneqsd', 'vcmpneqss', 'vcmpneq_uqpd', 'vcmpneq_uqps', 'vcmpneq_uqsd', 'vcmpneq_uqss', 'vcmpneq_uspd', 'vcmpneq_usps', 'vcmpneq_ussd', 'vcmpneq_usss', 'vcmpngepd', 'vcmpngeps', 'vcmpngesd', 'vcmpngess', 'vcmpnge_uqpd', 'vcmpnge_uqps', 'vcmpnge_uqsd', 'vcmpnge_uqss', 'vcmpnge_uspd', 'vcmpnge_usps', 'vcmpnge_ussd', 'vcmpnge_usss', 'vcmpngtpd', 'vcmpngtps', 'vcmpngtsd', 'vcmpngtss', 'vcmpngt_uqpd', 'vcmpngt_uqps', 'vcmpngt_uqsd', 'vcmpngt_uqss', 'vcmpngt_uspd', 'vcmpngt_usps', 'vcmpngt_ussd', 'vcmpngt_usss', 'vcmpnlepd', 'vcmpnleps', 'vcmpnlesd', 'vcmpnless', 'vcmpnle_uqpd', 'vcmpnle_uqps', 'vcmpnle_uqsd', 'vcmpnle_uqss', 'vcmpnle_uspd', 'vcmpnle_usps', 'vcmpnle_ussd', 'vcmpnle_usss', 'vcmpnltpd', 'vcmpnltps', 'vcmpnltsd', 'vcmpnltss', 'vcmpnlt_uqpd', 'vcmpnlt_uqps', 'vcmpnlt_uqsd', 'vcmpnlt_uqss', 'vcmpnlt_uspd', 'vcmpnlt_usps', 'vcmpnlt_ussd', 'vcmpnlt_usss', 'vcmpordpd', 'vcmpordps', 'vcmpord_qpd', 'vcmpord_qps', 'vcmpord_qsd', 'vcmpord_qss', 'vcmpordsd', 'vcmpord_spd', 'vcmpord_sps', 'vcmpordss', 'vcmpord_ssd', 'vcmpord_sss', 'vcmppd', 'vcmpps', 'vcmpsd', 'vcmpss', 'vcmptruepd', 'vcmptrueps', 'vcmptruesd', 'vcmptruess', 'vcmptrue_uqpd', 'vcmptrue_uqps', 'vcmptrue_uqsd', 'vcmptrue_uqss', 'vcmptrue_uspd', 'vcmptrue_usps', 'vcmptrue_ussd', 'vcmptrue_usss', 'vcmpunordpd', 'vcmpunordps', 'vcmpunord_qpd', 'vcmpunord_qps', 'vcmpunord_qsd', 'vcmpunord_qss', 'vcmpunordsd', 'vcmpunord_spd', 'vcmpunord_sps', 'vcmpunordss', 'vcmpunord_ssd', 'vcmpunord_sss', 'vcomisd', 'vcomiss', 'vcvtdq2pd', 'vcvtdq2ps', 'vcvtpd2dq', 'vcvtpd2ps', 'vcvtps2dq', 'vcvtps2pd', 'vcvtsd2si', 'vcvtsd2ss', 'vcvtsi2sd', 'vcvtsi2ss', 'vcvtss2sd', 'vcvtss2si', 'vcvttpd2dq', 'vcvttps2dq', 'vcvttsd2si', 'vcvttss2si', 'vdivpd', 'vdivps', 'vdivsd', 'vdivss', 'vdppd', 'vdpps', 'vextractf128', 'vextractps', 'vhaddpd', 'vhaddps', 'vhsubpd', 'vhsubps', 'vinsertf128', 'vinsertps', 'vlddqu', 'vldmxcsr', 'vldqqu', 'vmaskmovdqu', 'vmaskmovpd', 'vmaskmovps', 'vmaxpd', 'vmaxps', 'vmaxsd', 'vmaxss', 'vminpd', 'vminps', 'vminsd', 'vminss', 'vmovapd', 'vmovaps', 'vmovd', 'vmovddup', 'vmovdqa', 'vmovdqu', 'vmovhlps', 'vmovhpd', 'vmovhps', 'vmovlhps', 'vmovlpd', 'vmovlps', 'vmovmskpd', 'vmovmskps', 'vmovntdq', 'vmovntdqa', 'vmovntpd', 'vmovntps', 'vmovntqq', 'vmovq', 'vmovqqa', 'vmovqqu', 'vmovsd', 'vmovshdup', 'vmovsldup', 'vmovss', 'vmovupd', 'vmovups', 'vmpsadbw', 'vmulpd', 'vmulps', 'vmulsd', 'vmulss', 'vorpd', 'vorps', 'vpabsb', 'vpabsd', 'vpabsw', 'vpackssdw', 'vpacksswb', 'vpackusdw', 'vpackuswb', 'vpaddb', 'vpaddd', 'vpaddq', 'vpaddsb', 'vpaddsw', 'vpaddusb', 'vpaddusw', 'vpaddw', 'vpalignr', 'vpand', 'vpandn', 'vpavgb', 'vpavgw', 'vpblendvb', 'vpblendw', 'vpclmulhqhqdq', 'vpclmulhqlqdq', 'vpclmullqhqdq', 'vpclmullqlqdq', 'vpclmulqdq', 'vpcmpeqb', 'vpcmpeqd', 'vpcmpeqq', 'vpcmpeqw', 'vpcmpestri', 'vpcmpestrm', 'vpcmpgtb', 'vpcmpgtd', 'vpcmpgtq', 'vpcmpgtw', 'vpcmpistri', 'vpcmpistrm', 'vperm2f128', 'vpermilpd', 'vpermilps', 'vpextrb', 'vpextrd', 'vpextrq', 'vpextrw', 'vphaddd', 'vphaddsw', 'vphaddw', 'vphminposuw', 'vphsubd', 'vphsubsw', 'vphsubw', 'vpinsrb', 'vpinsrd', 'vpinsrq', 'vpinsrw', 'vpmaddubsw', 'vpmaddwd', 'vpmaxsb', 'vpmaxsd', 'vpmaxsw', 'vpmaxub', 'vpmaxud', 'vpmaxuw', 'vpminsb', 'vpminsd', 'vpminsw', 'vpminub', 'vpminud', 'vpminuw', 'vpmovmskb', 'vpmovsxbd', 'vpmovsxbq', 'vpmovsxbw', 'vpmovsxdq', 'vpmovsxwd', 'vpmovsxwq', 'vpmovzxbd', 'vpmovzxbq', 'vpmovzxbw', 'vpmovzxdq', 'vpmovzxwd', 'vpmovzxwq', 'vpmuldq', 'vpmulhrsw', 'vpmulhuw', 'vpmulhw', 'vpmulld', 'vpmullw', 'vpmuludq', 'vpor', 'vpsadbw', 'vpshufb', 'vpshufd', 'vpshufhw', 'vpshuflw', 'vpsignb', 'vpsignd', 'vpsignw', 'vpslld', 'vpslldq', 'vpsllq', 'vpsllw', 'vpsrad', 'vpsraw', 'vpsrld', 'vpsrldq', 'vpsrlq', 'vpsrlw', 'vpsubb', 'vpsubd', 'vpsubq', 'vpsubsb', 'vpsubsw', 'vpsubusb', 'vpsubusw', 'vpsubw', 'vptest', 'vpunpckhbw', 'vpunpckhdq', 'vpunpckhqdq', 'vpunpckhwd', 'vpunpcklbw', 'vpunpckldq', 'vpunpcklqdq', 'vpunpcklwd', 'vpxor', 'vrcpps', 'vrcpss', 'vroundpd', 'vroundps', 'vroundsd', 'vroundss', 'vrsqrtps', 'vrsqrtss', 'vshufpd', 'vshufps', 'vsqrtpd', 'vsqrtps', 'vsqrtsd', 'vsqrtss', 'vstmxcsr', 'vsubpd', 'vsubps', 'vsubsd', 'vsubss', 'vtestpd', 'vtestps', 'vucomisd', 'vucomiss', 'vunpckhpd', 'vunpckhps', 'vunpcklpd', 'vunpcklps', 'vxorpd', 'vxorps', 'vzeroall', 'vzeroupper'),
		'FMA' => array('vfmadd123pd', 'vfmadd123ps', 'vfmadd123sd', 'vfmadd123ss', 'vfmadd132pd', 'vfmadd132ps', 'vfmadd132sd', 'vfmadd132ss', 'vfmadd213pd', 'vfmadd213ps', 'vfmadd213sd', 'vfmadd213ss', 'vfmadd231pd', 'vfmadd231ps', 'vfmadd231sd', 'vfmadd231ss', 'vfmadd312pd', 'vfmadd312ps', 'vfmadd312sd', 'vfmadd312ss', 'vfmadd321pd', 'vfmadd321ps', 'vfmadd321sd', 'vfmadd321ss', 'vfmaddsub123pd', 'vfmaddsub123ps', 'vfmaddsub132pd', 'vfmaddsub132ps', 'vfmaddsub213pd', 'vfmaddsub213ps', 'vfmaddsub231pd', 'vfmaddsub231ps', 'vfmaddsub312pd', 'vfmaddsub312ps', 'vfmaddsub321pd', 'vfmaddsub321ps', 'vfmsub123pd', 'vfmsub123ps', 'vfmsub123sd', 'vfmsub123ss', 'vfmsub132pd', 'vfmsub132ps', 'vfmsub132sd', 'vfmsub132ss', 'vfmsub213pd', 'vfmsub213ps', 'vfmsub213sd', 'vfmsub213ss', 'vfmsub231pd', 'vfmsub231ps', 'vfmsub231sd', 'vfmsub231ss', 'vfmsub312pd', 'vfmsub312ps', 'vfmsub312sd', 'vfmsub312ss', 'vfmsub321pd', 'vfmsub321ps', 'vfmsub321sd', 'vfmsub321ss', 'vfmsubadd123pd', 'vfmsubadd123ps', 'vfmsubadd132pd', 'vfmsubadd132ps', 'vfmsubadd213pd', 'vfmsubadd213ps', 'vfmsubadd231pd', 'vfmsubadd231ps', 'vfmsubadd312pd', 'vfmsubadd312ps', 'vfmsubadd321pd', 'vfmsubadd321ps', 'vfnmadd123pd', 'vfnmadd123ps', 'vfnmadd123sd', 'vfnmadd123ss', 'vfnmadd132pd', 'vfnmadd132ps', 'vfnmadd132sd', 'vfnmadd132ss', 'vfnmadd213pd', 'vfnmadd213ps', 'vfnmadd213sd', 'vfnmadd213ss', 'vfnmadd231pd', 'vfnmadd231ps', 'vfnmadd231sd', 'vfnmadd231ss', 'vfnmadd312pd', 'vfnmadd312ps', 'vfnmadd312sd', 'vfnmadd312ss', 'vfnmadd321pd', 'vfnmadd321ps', 'vfnmadd321sd', 'vfnmadd321ss', 'vfnmsub123pd', 'vfnmsub123ps', 'vfnmsub123sd', 'vfnmsub123ss', 'vfnmsub132pd', 'vfnmsub132ps', 'vfnmsub132sd', 'vfnmsub132ss', 'vfnmsub213pd', 'vfnmsub213ps', 'vfnmsub213sd', 'vfnmsub213ss', 'vfnmsub231pd', 'vfnmsub231ps', 'vfnmsub231sd', 'vfnmsub231ss', 'vfnmsub312pd', 'vfnmsub312ps', 'vfnmsub312sd', 'vfnmsub312ss', 'vfnmsub321pd', 'vfnmsub321ps', 'vfnmsub321sd', 'vfnmsub321ss'),
		'FMA4' => array('vfmaddpd', 'vfmaddps', 'vfmaddsd', 'vfmaddss', 'vfmaddsubpd', 'vfmaddsubps', 'vfmsubaddpd', 'vfmsubaddps', 'vfmsubpd', 'vfmsubps', 'vfmsubsd', 'vfmsubss', 'vfnmaddpd', 'vfnmaddps', 'vfnmaddsd', 'vfnmaddss', 'vfnmsubpd', 'vfnmsubps', 'vfnmsubsd', 'vfnmsubss', 'vfrczpd', 'vfrczps', 'vfrczsd', 'vfrczss', 'vpcmov', 'vpcomb', 'vpcomd', 'vpcomq', 'vpcomub', 'vpcomud', 'vpcomuq', 'vpcomuw', 'vpcomw', 'vphaddbd', 'vphaddbq', 'vphaddbw', 'vphadddq', 'vphaddubd', 'vphaddubq', 'vphaddubw', 'vphaddudq', 'vphadduwd', 'vphadduwq', 'vphaddwd', 'vphaddwq', 'vphsubbw', 'vphsubdq', 'vphsubwd', 'vpmacsdd', 'vpmacsdqh', 'vpmacsdql', 'vpmacssdd', 'vpmacssdqh', 'vpmacssdql', 'vpmacsswd', 'vpmacssww', 'vpmacswd', 'vpmacsww', 'vpmadcsswd', 'vpmadcswd', 'vpperm', 'vprotb', 'vprotd', 'vprotq', 'vprotw', 'vpshab', 'vpshad', 'vpshaq', 'vpshaw', 'vpshlb', 'vpshld', 'vpshlq', 'vpshlw'),
		);

		foreach(array_keys($instruction_checks) as $set)
		{
			$instruction_usage[$set] = 0;
		}
		$instruction_usage['OTHER'] = 0;

		foreach(explode(PHP_EOL , shell_exec('objdump -d ' . $binary . ' | cut -f3 | cut -d\' \' -f1')) as $instruction)
		{
			$matched_instruction = false;
			foreach($instruction_checks as $set => $instructions)
			{
				if(in_array(trim($instruction), $instructions))
				{
					$instruction_usage[$set] += 1;
					$matched_instruction = true;
					break;
				}
			}

			if($matched_instruction == false)
			{
				$instruction_usage['OTHER'] += 1;
			}
		}

		foreach($instruction_usage as $instruction => $usage)
		{
			if($usage < 2)
			{
				unset($instruction_usage[$instruction]);
			}
		}

		return $instruction_usage;
	}
	public static function analyze_apitrace_trace_glpop($apitrace_file)
	{
		$tracedump = trim(shell_exec('apitrace dump --call-nos=no ' . $apitrace_file));
		$gl_usage = array();

		while($tracedump && ($break = strpos($tracedump, PHP_EOL)) != false)
		{
			$line = substr($tracedump, 0, $break);
			$tracedump = substr($tracedump, $break + 1);
			$line = substr($line, 0, strpos($line, '('));

			if(strtolower(substr($line, 0, 2)) == 'gl')
			{
				if(isset($gl_usage[$line]))
				{
					$gl_usage[$line]++;
				}
				else if(pts_strings::is_alnum($line))
				{
					$gl_usage[$line] = 1;
				}
			}
		}

		arsort($gl_usage);
		$gl_usage = array_keys($gl_usage);

		return $gl_usage;
	}
}

?>
