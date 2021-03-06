<?php

include_once 'config.php';



// curl -H 'X-Auth-Token: $APITOKEN' $APIURL/v0/devices/laknas/ports/eth0
$curl = curl_init();
$headers[] = "X-Auth-Token: $APITOKEN";
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
#curl_setopt($curl, CURLOPT_VERBOSE, 1);
#curl_setopt($curl, CURLOPT_HEADER, 1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);


foreach ($data as $server => $subarray) {


	foreach ($subarray as $port => $dstdata) {
		/*echo $server;
		echo $port;
		echo $dstdata['dstserver'];
		echo $dstdata['dstport'];
		*/
		curl_setopt($curl, CURLOPT_URL, $APIURL."v0/devices/".$server."/ports/".$port);
		$result = @curl_exec($curl);
		#echo $result;
		$json = json_decode($result);
		#print_r ($json);
		$LEDcount = $dstdata['dstLEDcount'];
		$LEDhalf = $LEDcount / 2;
		$json->status = "down";
		if($json->status != 'ok') {
			#$in_color = $color_nolink;
			#$out_color = $color_nolink;
#			$connection_data = "setup channel_1_count=$LEDcount;thread_start;do;fill 1,00FFFF;render;delay 100;fill 1,00FF00;render;delay 100;loop;thread_stop\n";
			$connection_data = "setup channel_1_count=$LEDcount;fill 1,FF0000;render;\n";
		} // End if json->status
		else {
			$outRate = $json->port->ifOutOctets_rate;
			$inRate = $json->port->ifInOctets_rate;
			$time = $json->port->poll_period;
			$port_speed = $json->port->ifHighSpeed;

			$inPercent = (($inRate / 1024 / 1024) / $port_speed) * 100;
			$outPercent = (($outRate / 1024 / 1024) / $port_speed) * 100;

#			echo "Up: ".$inPercent."%\n";
#			echo "Down: ".$outPercent."%\n";

			foreach ($colors AS $percentage => $hex) {
				if($inPercent >= $percentage) $in_color = $hex;
				if($outPercent >= $percentage) $out_color = $hex;
			} // End foreach color
		
			$connection_data = "setup channel_1_count=$LEDcount; fill 1,".$in_color.",0,$LEDhalf; fill 1,".$out_color.",".$LEDhalf.",$LEDhalf; render\n";
		} // End else
		$connection_url = "tcp://".$dstdata['dstserver'].":".$dstdata['dstport'];
		$socket = stream_socket_client($connection_url);
		fwrite($socket, $connection_data);
		fclose($socket);

		echo "Should have connected to: $connection_url\n";
		echo "Should have sent: $connection_data\n";
	} // End foreach subarray

} // End foreach data


curl_close($curl);
