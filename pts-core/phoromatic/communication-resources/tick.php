<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

$J = json_decode($JSON, true);
if($J == null)
{
	$json['phoromatic']['response'] = 'Failed';
	echo json_encode($json);
	exit;
}

pts_file_io::mkdir(phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID));
pts_file_io::mkdir(phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID, SYSTEM_ID));

$system_path = phoromatic_server::phoromatic_account_system_path(ACCOUNT_ID, SYSTEM_ID);

if(isset($J['phoromatic']['client-log']))
{
	file_put_contents($system_path . 'phoronix-test-suite.log', $J['phoromatic']['client-log']);
}
if(isset($J['phoromatic']['stats']))
{
	file_put_contents($system_path . 'sensors.json', json_encode($J['phoromatic']['stats']));
}

if(is_file($system_path . 'sensors-pool.json'))
{
	$sensor_file = file_get_contents($system_path . 'sensors-pool.json');
	$sensor_file = json_decode($sensor_file, true);

	if($sensor_file && !empty($sensor_file))
	{
		foreach($sensor_file as $name => $sensor)
		{
			if(isset($sensor['last-updated']) < (time() - 600))
			{
				unset($sensor_file['sensors'][$name]);
			}
		}
	}
}

$sensors = $sensor_file;
foreach($J['phoromatic']['stats']['sensors'] as $name => $sensor)
{
		if(!isset($sensors[$name]))
		{
			$sensors[$name] = $sensor;
			$sensors[$name]['values'] = array($sensors[$name]['value']);
			unset($sensors[$name]['value']);
		}
		else
		{
			array_unshift($sensors[$name]['values'], $sensor['value']);
		}
		if(count($sensors[$name]['values']) > 60)
		{
			$sensors[$name]['values'] = array_slice($sensors[$name]['values'], 0, 60);
		}
		$sensors[$name]['last-updated'] = time();
}
file_put_contents($system_path . 'sensors-pool.json', json_encode($sensors));

$stmt = phoromatic_server::$db->prepare('SELECT TickThreadEvent FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$result = $stmt->execute();

if(!empty($result))
{
	$row = $result->fetchArray();
	$tte = $row['TickThreadEvent'];
	$send_event = null;

	if(!empty($tte) && strpos($tte, ':') !== false)
	{
		list($time, $event) = explode(':', $tte);
		if($time > (time() - 3600))
		{
			$send_event = $event;
		}
	}

	if(!empty($tte))
	{
		$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET TickThreadEvent = :event WHERE AccountID = :account_id AND SystemID = :system_id');
		$stmt->bindValue(':account_id', ACCOUNT_ID);
		$stmt->bindValue(':system_id', SYSTEM_ID);
		$stmt->bindValue(':event', '');
		$stmt->execute();
	}
}

$json['phoromatic']['response'] = 'tick';
$json['phoromatic']['tick_thread'] = $send_event;
echo json_encode($json);
exit;

?>
