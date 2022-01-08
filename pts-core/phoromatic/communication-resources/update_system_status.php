<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel

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

if($ACTIVITY == null)
{
	$json['phoromatic']['response'] = 'Update Failed';
	echo json_encode($json);
	exit;
}

if(empty($ESTIMATED_TIME) || !is_numeric($ESTIMATED_TIME))
{
	$ESTIMATED_TIME = -1;
}

if(empty($PERCENT_COMPLETE) || !is_numeric($PERCENT_COMPLETE))
{
	$PERCENT_COMPLETE = 0;
}

$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET CurrentTask = :current_task, EstimatedTimeForTask = :time_for_task, TaskPercentComplete = :percent_complete, TaskPercentComplete = :percent_complete, CurrentProcessSchedule = :schedule_id, CurrentProcessTicket = :ticket_id, TimeToNextCommunication = :time_to_next_comm WHERE AccountID = :account_id AND SystemID = :system_id');
$stmt->bindValue(':account_id', $ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$stmt->bindValue(':current_task', pts_strings::sanitize($ACTIVITY));
$stmt->bindValue(':time_for_task', $ESTIMATED_TIME);
$stmt->bindValue(':percent_complete', $PERCENT_COMPLETE);
$stmt->bindValue(':schedule_id', $SCHEDULE_ID);
$stmt->bindValue(':ticket_id', isset($BENCHMARK_TICKET_ID) && !empty($BENCHMARK_TICKET_ID) ? $BENCHMARK_TICKET_ID : 0);
$stmt->bindValue(':time_to_next_comm', $OTHER);
$stmt->execute();

$json['phoromatic']['response'] = 'Status Updated';
echo json_encode($json);

?>
