<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2021, Phoronix Media
	Copyright (C) 2012 - 2021, Michael Larabel

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
		//ini_set('memory_limit', '2048M');
		// https://software.intel.com/sites/landingpage/IntrinsicsGuide/#expand=641
		$intrinsics_search = array(
			);
		foreach(pts_types::identifiers_to_test_profile_objects($r, false, true) as $test_profile)
		{
			$qualified_identifier = $test_profile->get_identifier();

			// Set some other things...
			pts_client::pts_set_environment_variable('FORCE_TIMES_TO_RUN', 1);
			pts_client::pts_set_environment_variable('TEST_RESULTS_NAME', $test_profile->get_title() . ' Testing ' . date('Y-m-d'));
			pts_client::pts_set_environment_variable('TEST_RESULTS_IDENTIFIER', 'Sample Run');
			pts_client::pts_set_environment_variable('TEST_RESULTS_DESCRIPTION', 1);

			pts_openbenchmarking_client::override_client_setting('AutoUploadResults', false);
			pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', true);
			pts_test_installer::standard_install($qualified_identifier, true);
			if(!$test_profile->is_test_installed())
			{
				// Test has issues even installing, so skip it...
				continue;
			}
			$test_binary = self::locate_test_profile_lead_binary($test_profile);

			// search for intrinsics
			$intrinsics = array();

			//self::recursively_search_text_files_in_dir($test_profile->get_test_executable_dir(), $intrics_search, $intrinsics);
			$honors_cflags = false;
			$shared_library_dependencies = array();
			$instruction_usage = array();

			if(is_executable($test_binary))
			{
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

				$external_dependencies = $test_profile->get_external_dependencies();
				foreach(array('default', 'sandybridge', 'skylake', 'cascadelake -mprefer-vector-width=512', 'sapphirerapids -mprefer-vector-width=512', 'alderlake', 'znver2', 'znver3') as $march)
				{
					// So for any compiling tasks they will try to use the most aggressive instructions possible
					if($march != 'default')
					{
						if(!in_array('build-utilities', $external_dependencies))
						{
							continue;
						}
						putenv('CFLAGS= -march=' . $march . ' -O3 ');
						putenv('CXXFLAGS= -march=' . $march . ' -O3 ');
					}
					else
					{
						putenv('CFLAGS');
						putenv('CXXFLAGS');
					}
					if(($x = strpos($march, ' ')) !== false)
					{
						$march = substr($march, 0, $x);
					}
					pts_test_installer::standard_install($qualified_identifier, true);
					if($test_profile->is_test_installed())
					{
						$iu = self::analyze_binary_instruction_usage($test_binary);

						if($iu != null)
						{
							if($march != 'default' && $honors_cflags == false && !empty($iu) && isset($instruction_usage['default']) && $iu != $instruction_usage['default'])
							{
								$honors_cflags = true;
							}
							$instruction_usage[$march] = $iu;
						}
					}
				}

				var_dump($shared_library_dependencies);
				var_dump($instruction_usage);
				echo "HONORS: ";
				var_dump($honors_cflags);

				if(pts_openbenchmarking_client::user_name() != false)
				{
					$server_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_meta', array(
						'i' => $test_profile->get_identifier(),
						'ldd_libraries' => implode(',', $shared_library_dependencies),
						'instruction_set_usage' => base64_encode(json_encode($instruction_usage)),
						'honors_cflags' => ($honors_cflags ? 1 : 0)
						));
						var_dump($server_response);
						$json = json_decode($server_response, true);
				}
			}
			else
			{
				echo PHP_EOL . $test_binary;
				echo PHP_EOL . 'Test binary could not be found.' . PHP_EOL;
		//		return false;
			}
		}

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
			if(is_executable($test_profile->get_test_executable_dir() . $test_binary) && $test_profile->get_test_executable_dir() . $test_binary != $test_profile_launcher)
			{
				$test_binary = $test_profile->get_test_executable_dir() . $test_binary;
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
	public static function recursively_search_text_files_in_dir($object, &$search_for, &$hits)
	{
		if(is_dir($object))
		{
			$object = pts_strings::add_trailing_slash($object);
		}

		foreach(pts_file_io::glob($object . '*') as $to_read)
		{
			if(is_file($to_read) && mime_content_type($to_remove) == 'text/plain')
			{
				$to_read_contents = file_get_contents($to_read);
				foreach($search_for as $search => $report)
				{
					if(strpos($to_read_contents, $search) !== false)
					{
						if(!isset($hits[$to_read]))
						{
							$hits[$to_read][] = $report;
						}
					}
				}
				
			}
			else if(is_dir($to_read))
			{
				self::recursively_search_text_files_in_dir($to_read, $search_for, $hits);
			}
		}
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
		'AVX' => 'VBROADCASTSS VBROADCASTSD VBROADCASTF128 VINSERTF128 VEXTRACTF128 VMASKMOVPS VPERMILPS VPERMILPD VPERM2F128 VZEROALL VZEROUPPER',
		'AVX2' => 'VPBROADCASTB VPBROADCASTW VPBROADCASTD VPBROADCASTQ VINSERTI128 VEXTRACTI128 VGATHERDPD VGATHERQPD VGATHERDPS VGATHERQPS VPGATHERDD VPGATHERDQ VPGATHERQD VPGATHERQQ VPMASKMOVD VPMASKMOVQ VPERMPS VPERMD VPERMPD VPERMQ VPERM2I128 VPBLENDD VPSLLVD VPSLLVQ  VPSRLVD VPSRLVQ  VPSRAVD',
		'AES' => 'AESENC AESENCLAST AESDEC AESDECLAST AESKEYGENASSIST AESIMC',
		'AVX512' => 'AVX512F AVX512CD AVX512DQ AVX512PF AVX512ER AVX512VL AVX512BW AVX512IFMA AVX512VBMI AVX512VBMI2 AVX512VAES AVX512BITALG AVX5124FMAPS AVX512VPCLMULQDQ AVX512GFNI AVX512_VNNI AVX5124VNNIW AVX512VPOPCNTDQ AVX512_BF16',
		'VAES' => 'VAESDEC VAESDECLAST VAESENC VAESENCLAST',
		'AVX-VNNI' => 'avxvnni',
		'AMX' => 'LDTILECFG STTILECFG TILELOADD TILELOADDT1 TILESTORED TILERELEASE TILEZERO TDPBF16PS',
		'FMA' => array('vfmadd123pd', 'vfmadd123ps', 'vfmadd123sd', 'vfmadd123ss', 'vfmadd132pd', 'vfmadd132ps', 'vfmadd132sd', 'vfmadd132ss', 'vfmadd213pd', 'vfmadd213ps', 'vfmadd213sd', 'vfmadd213ss', 'vfmadd231pd', 'vfmadd231ps', 'vfmadd231sd', 'vfmadd231ss', 'vfmadd312pd', 'vfmadd312ps', 'vfmadd312sd', 'vfmadd312ss', 'vfmadd321pd', 'vfmadd321ps', 'vfmadd321sd', 'vfmadd321ss', 'vfmaddsub123pd', 'vfmaddsub123ps', 'vfmaddsub132pd', 'vfmaddsub132ps', 'vfmaddsub213pd', 'vfmaddsub213ps', 'vfmaddsub231pd', 'vfmaddsub231ps', 'vfmaddsub312pd', 'vfmaddsub312ps', 'vfmaddsub321pd', 'vfmaddsub321ps', 'vfmsub123pd', 'vfmsub123ps', 'vfmsub123sd', 'vfmsub123ss', 'vfmsub132pd', 'vfmsub132ps', 'vfmsub132sd', 'vfmsub132ss', 'vfmsub213pd', 'vfmsub213ps', 'vfmsub213sd', 'vfmsub213ss', 'vfmsub231pd', 'vfmsub231ps', 'vfmsub231sd', 'vfmsub231ss', 'vfmsub312pd', 'vfmsub312ps', 'vfmsub312sd', 'vfmsub312ss', 'vfmsub321pd', 'vfmsub321ps', 'vfmsub321sd', 'vfmsub321ss', 'vfmsubadd123pd', 'vfmsubadd123ps', 'vfmsubadd132pd', 'vfmsubadd132ps', 'vfmsubadd213pd', 'vfmsubadd213ps', 'vfmsubadd231pd', 'vfmsubadd231ps', 'vfmsubadd312pd', 'vfmsubadd312ps', 'vfmsubadd321pd', 'vfmsubadd321ps', 'vfnmadd123pd', 'vfnmadd123ps', 'vfnmadd123sd', 'vfnmadd123ss', 'vfnmadd132pd', 'vfnmadd132ps', 'vfnmadd132sd', 'vfnmadd132ss', 'vfnmadd213pd', 'vfnmadd213ps', 'vfnmadd213sd', 'vfnmadd213ss', 'vfnmadd231pd', 'vfnmadd231ps', 'vfnmadd231sd', 'vfnmadd231ss', 'vfnmadd312pd', 'vfnmadd312ps', 'vfnmadd312sd', 'vfnmadd312ss', 'vfnmadd321pd', 'vfnmadd321ps', 'vfnmadd321sd', 'vfnmadd321ss', 'vfnmsub123pd', 'vfnmsub123ps', 'vfnmsub123sd', 'vfnmsub123ss', 'vfnmsub132pd', 'vfnmsub132ps', 'vfnmsub132sd', 'vfnmsub132ss', 'vfnmsub213pd', 'vfnmsub213ps', 'vfnmsub213sd', 'vfnmsub213ss', 'vfnmsub231pd', 'vfnmsub231ps', 'vfnmsub231sd', 'vfnmsub231ss', 'vfnmsub312pd', 'vfnmsub312ps', 'vfnmsub312sd', 'vfnmsub312ss', 'vfnmsub321pd', 'vfnmsub321ps', 'vfnmsub321sd', 'vfnmsub321ss'),
		'BMI2' => 'BZHI MULX PDEP PEXT RORX SARX SHRX SHLX',
		);
		
		foreach($instruction_checks as $set => &$instructions)
		{
			if(!is_array($instructions))
			{
				$instructions = explode(' ', trim($instructions));
			}
			$instructions = array_map('trim', $instructions);
			$instructions = array_map('strtolower', $instructions);
		}

		foreach(array_keys($instruction_checks) as $set)
		{
			$instruction_usage[$set] = array();
		}
		$instruction_usage['OTHER'] = 0;
		
		$objdump_file =  tempnam('/tmp', 'objdump');
		shell_exec('objdump -d ' . $binary . ' | cut -f3 | cut -d\' \' -f1 > ' . $objdump_file);

		$handle = fopen($objdump_file, 'r');
		while(($instruction = fgets($handle)) !== false)
		{
			$matched_instruction = false;
			foreach($instruction_checks as $set => $instructions)
			{
				$instr = trim(strtolower($instruction));
				if($instr != null && in_array($instr, $instructions))
				{
					if(!isset($instruction_usage[$set][$instr]))
					{
						$instruction_usage[$set][$instr] = $instr;
					}
					$matched_instruction = true;
					break;
				}
			}

			if($matched_instruction == false)
			{
				//$instruction_usage['OTHER'] += 1;
			}
		}
		fclose($handle);
		unlink($objdump_file);

		foreach($instruction_usage as $instruction => $usage)
		{
			if(!is_array($instruction_usage[$instruction]) || empty($instruction_usage[$instruction]))
			{
				unset($instruction_usage[$instruction]);
			}
			else
			{
				$instruction_usage[$instruction] = array_keys($usage);
			}
		}

		return $instruction_usage;
	}
}

?>
