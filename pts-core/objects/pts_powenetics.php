<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2026, Phoronix Media
	Copyright (C) 2026, Michael Larabel

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

// Shared protocol object for the Powenetics v2 multi-rail PC power measurement device (PMD).
// Protocol derived from the KIT-OSGroup/powenetics-v2 Rust library. The device streams
// per-rail voltage/current at ~1000 packets/sec over a 921600 baud 8N1 USB-serial link.
//
// Packet layout (69 bytes, no checksum):
//   [0..1]   magic 0xCA 0xAC
//   [2..3]   u16 big-endian sequence counter
//   [4..68]  13 channels x 5 bytes each:
//              u16 big-endian voltage in millivolts
//              u24 big-endian current in milliamps
// Per-rail watts = (voltage_mV / 1000) * (current_mA / 1000).
class pts_powenetics
{
	// Protocol constants
	const PACKET_SIZE = 69;
	const CHANNEL_COUNT = 13;
	const CHANNEL_SIZE = 5;
	const BAUD = 921600;
	const MAGIC_0 = 0xCA;
	const MAGIC_1 = 0xAC;

	// Plausibility bounds for resync verification (no checksum in protocol)
	const MAX_VOLTS = 30.0;
	const MAX_AMPS = 150.0;

	private $handle = null;
	private $device = null;
	private $is_replay = false;
	private $read_buffer = '';
	private $last_frame = null;

	public static function magic()
	{
		return chr(self::MAGIC_0) . chr(self::MAGIC_1);
	}
	public static function start_command()
	{
		// Bytes sent to the device to begin streaming measurements: CA AC BD 90
		return chr(0xCA) . chr(0xAC) . chr(0xBD) . chr(0x90);
	}
	public static function rail_table()
	{
		// channel index => array(token, human-readable name)
		// Tokens are dot-free because system_monitor::prepare_sensor_parameters() splits MONITOR entries on '.'
		return array(
			0 => array('atx-3v3', 'ATX 3.3V'),
			1 => array('atx-5v-standby', 'ATX 5V Standby'),
			2 => array('atx-12v', 'ATX 12V'),
			3 => array('atx-5v', 'ATX 5V'),
			4 => array('eps-12v-1', 'EPS 12V #1'),
			5 => array('atx12vo-12v-standby', 'ATX12VO 12V Standby'),
			6 => array('eps-12v-3', 'EPS 12V #3'),
			7 => array('eps-12v-2', 'EPS 12V #2'),
			8 => array('pcie-12v-3', 'PCIe 12V #3'),
			9 => array('pcie-12v-2', 'PCIe 12V #2'),
			10 => array('pcie-slot-3v3', 'PCIe Slot 3.3V'),
			11 => array('pcie-slot-12v', 'PCIe Slot 12V'),
			12 => array('pcie-12v-1', 'PCIe 12V #1'),
			);
	}
	public static function aggregate_table()
	{
		// token => array(name, array-of-channel-indexes-to-sum)
		return array(
			'cpu' => array('CPU (EPS Total)', array(4, 7, 6)),
			'gpu' => array('GPU (PCIe + Slot)', array(12, 9, 8, 10, 11)),
			'total' => array('Total System', array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12)),
			);
	}
	public static function device_tokens()
	{
		// Aggregates first (default device is the first entry, 'total' is preferred as default elsewhere)
		$tokens = array();
		foreach(self::aggregate_table() as $token => $unused)
		{
			$tokens[] = $token;
		}
		foreach(self::rail_table() as $rail)
		{
			$tokens[] = $rail[0];
		}
		return $tokens;
	}
	public static function readable_token_name($token)
	{
		$aggregates = self::aggregate_table();
		if(isset($aggregates[$token]))
		{
			return $aggregates[$token][0];
		}
		foreach(self::rail_table() as $rail)
		{
			if($rail[0] == $token)
			{
				return $rail[1];
			}
		}
		return $token;
	}
	public static function is_replay_source($source)
	{
		// A replay source is a path to a regular file rather than a live serial device
		return !empty($source) && is_file($source) && strtolower($source) != 'auto';
	}
	public static function find_device($source = false)
	{
		// $source: env value (file path | COMn | /dev/ttyX | 'auto' | empty)
		if($source === false)
		{
			$source = getenv('POWENETICS');
		}
		if(empty($source))
		{
			return false;
		}
		if(self::is_replay_source($source))
		{
			return $source;
		}
		if(strtolower($source) != 'auto')
		{
			// Explicit device path / COM port
			return $source;
		}

		// Auto-discovery: probe candidate serial ports until one responds with valid frames
		$candidates = array();
		if(phodevi::is_windows())
		{
			$reg = shell_exec('reg query HKLM\\HARDWARE\\DEVICEMAP\\SERIALCOMM 2>&1');
			if(!empty($reg))
			{
				foreach(explode("\n", $reg) as $line)
				{
					if(($p = strpos($line, 'COM')) !== false)
					{
						$com = trim(substr($line, $p));
						if(preg_match('/^COM[0-9]+/', $com, $m))
						{
							$candidates[] = $m[0];
						}
					}
				}
			}
		}
		else
		{
			foreach(array_merge(pts_file_io::glob('/dev/ttyUSB*'), pts_file_io::glob('/dev/ttyACM*')) as $dev)
			{
				$candidates[] = $dev;
			}
		}

		foreach($candidates as $candidate)
		{
			if(self::probe($candidate))
			{
				return $candidate;
			}
		}
		return false;
	}
	public function __construct($device = false)
	{
		if($device === false)
		{
			$device = self::find_device();
		}
		$this->device = $device;
		$this->is_replay = self::is_replay_source($device);
	}
	public function get_device()
	{
		return $this->device;
	}
	public function is_replay()
	{
		return $this->is_replay;
	}
	public function open()
	{
		if(empty($this->device))
		{
			return false;
		}
		if($this->is_replay)
		{
			$this->handle = @fopen($this->device, 'rb');
			return $this->handle !== false;
		}

		if(phodevi::is_windows())
		{
			$com = $this->device;
			// mode expects e.g. "COM5:" for configuration
			$mode_target = (substr($com, -1) == ':' ? $com : $com . ':');
			shell_exec('mode ' . $mode_target . ' BAUD=' . self::BAUD . ' PARITY=n DATA=8 STOP=1 to=off xon=off 2>&1');
			// Device access requires the \\.\COMn form
			$open_path = (strpos($com, '\\\\.\\') === 0 ? $com : '\\\\.\\' . rtrim($com, ':'));
			$this->handle = @fopen($open_path, 'r+b');
		}
		else
		{
			shell_exec('stty -F ' . escapeshellarg($this->device) . ' ' . self::BAUD . ' raw -echo cs8 -parenb -cstopb 2>&1');
			$this->handle = @fopen($this->device, 'r+b');
		}

		if($this->handle === false || $this->handle === null)
		{
			return false;
		}

		// Begin the measurement stream
		fwrite($this->handle, self::start_command());
		fflush($this->handle);
		return true;
	}
	public function close()
	{
		if($this->handle !== null && $this->handle !== false)
		{
			fclose($this->handle);
			$this->handle = null;
		}
		$this->read_buffer = '';
	}
	private function fill_buffer($want_bytes)
	{
		// Read at least $want_bytes into the persistent buffer if possible
		while(strlen($this->read_buffer) < $want_bytes)
		{
			$chunk = fread($this->handle, 4096);
			if($chunk === false || strlen($chunk) == 0)
			{
				if($this->is_replay && feof($this->handle))
				{
					// Loop the replay stream so testing can continue indefinitely
					rewind($this->handle);
					continue;
				}
				break;
			}
			$this->read_buffer .= $chunk;
		}
		return strlen($this->read_buffer) >= $want_bytes;
	}
	private function decode_packet($packet)
	{
		// $packet is exactly PACKET_SIZE bytes beginning with the magic
		$b = array_values(unpack('C*', $packet));
		$sequence = ($b[2] << 8) | $b[3];
		$channels = array();

		for($ch = 0; $ch < self::CHANNEL_COUNT; $ch++)
		{
			$o = 4 + ($ch * self::CHANNEL_SIZE);
			$volts = (($b[$o] << 8) | $b[$o + 1]) / 1000.0;
			$amps = (($b[$o + 2] << 16) | ($b[$o + 3] << 8) | $b[$o + 4]) / 1000.0;

			if($volts > self::MAX_VOLTS || $amps > self::MAX_AMPS)
			{
				// Implausible value: treat this candidate frame as invalid
				return false;
			}
			$channels[$ch] = array('volts' => $volts, 'amps' => $amps, 'watts' => $volts * $amps);
		}

		return array('sequence' => $sequence, 'channels' => $channels);
	}
	public function read_frame()
	{
		if($this->handle === null || $this->handle === false)
		{
			return false;
		}

		$magic = self::magic();
		$attempts = 0;
		// Bound the resync search so a broken stream cannot loop forever
		$max_attempts = self::PACKET_SIZE * 4;

		while($attempts < $max_attempts)
		{
			if(!$this->fill_buffer(self::PACKET_SIZE * 2))
			{
				if(!$this->fill_buffer(self::PACKET_SIZE))
				{
					return false;
				}
			}

			// Find the magic in the buffer
			$pos = strpos($this->read_buffer, $magic);
			if($pos === false)
			{
				// No magic present; drop most of the buffer but keep a trailing byte in case
				// a magic straddles the boundary
				$this->read_buffer = substr($this->read_buffer, -1);
				$attempts++;
				continue;
			}
			if($pos > 0)
			{
				// Junk before the magic: discard it (resync)
				$this->read_buffer = substr($this->read_buffer, $pos);
				$attempts += $pos;
			}
			if(strlen($this->read_buffer) < self::PACKET_SIZE)
			{
				if(!$this->fill_buffer(self::PACKET_SIZE))
				{
					return false;
				}
			}

			$candidate = substr($this->read_buffer, 0, self::PACKET_SIZE);
			$frame = $this->decode_packet($candidate);

			if($frame === false)
			{
				// Implausible frame: skip past this magic and keep resyncing
				$this->read_buffer = substr($this->read_buffer, 2);
				$attempts += 2;
				continue;
			}

			// Double-magic lookahead: verify the next packet also begins with the magic
			if(strlen($this->read_buffer) >= (self::PACKET_SIZE + 2))
			{
				$next_magic = substr($this->read_buffer, self::PACKET_SIZE, 2);
				if($next_magic !== $magic)
				{
					// The following bytes are not a frame start; this magic was spurious
					$this->read_buffer = substr($this->read_buffer, 2);
					$attempts += 2;
					continue;
				}
			}

			// Accept this frame and consume it from the buffer
			$this->read_buffer = substr($this->read_buffer, self::PACKET_SIZE);
			$this->last_frame = $frame;
			return $frame;
		}

		return false;
	}
	public function latest_frame()
	{
		return $this->last_frame;
	}
	public static function rail_watts($frame, $token)
	{
		if(!is_array($frame) || !isset($frame['channels']))
		{
			return -1;
		}

		$aggregates = self::aggregate_table();
		if(isset($aggregates[$token]))
		{
			$sum = 0;
			foreach($aggregates[$token][1] as $ch)
			{
				if(isset($frame['channels'][$ch]))
				{
					$sum += $frame['channels'][$ch]['watts'];
				}
			}
			return $sum;
		}

		foreach(self::rail_table() as $ch => $rail)
		{
			if($rail[0] == $token && isset($frame['channels'][$ch]))
			{
				return $frame['channels'][$ch]['watts'];
			}
		}

		return -1;
	}
	public static function probe($device)
	{
		// Verify a device produces at least 3 valid frames. Always closes the port
		// afterwards because Windows COM access is exclusive.
		$pw = new pts_powenetics($device);
		if(!$pw->open())
		{
			$pw->close();
			return false;
		}

		$valid = 0;
		for($i = 0; $i < 8 && $valid < 3; $i++)
		{
			if($pw->read_frame() !== false)
			{
				$valid++;
			}
		}
		$pw->close();
		return $valid >= 3;
	}
}

?>
