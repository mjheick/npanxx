<?php
ini_set('memory_limit', '4G');
// usps-zip.yml

for ($zip = 10000; $zip < 99999; $zip++)
{
	$zipcode = str_pad($zip, 5, '0', STR_PAD_LEFT);
	// open the database
	$db = yaml_parse(file_get_contents('usps-zip.yml'));
	// see if we have this entry
	if (!array_key_exists($zipcode, $db))
	{
		echo "$zipcode: ";
		// ask the USPS about this zipcode
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => 'https://tools.usps.com/tools/app/ziplookup/cityByZip',
			CURLOPT_POST => true,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => 'zip=' . $zipcode,
		));
		$data = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($data, true);
		if (array_key_exists('resultStatus', $json))
		{
			if ($json['resultStatus'] == 'INVALID-ZIP CODE')
			{
				echo "Bad Zip";
			}
			if ($json['resultStatus'] == 'SUCCESS')
			{
				echo "Ok";
				$db[$zipcode] = array(
					'default' => '',
					'other' => array(),
					'avoid' => array(),
				);
				$db[$zipcode]['default'] = $json['defaultCity'] . ', ' . $json['defaultState'];

				$list_of_cities = array();
				foreach ($json['citiesList'] as $loc)
				{
					$list_of_cities[] = $loc['city'] . ', ' . $loc['state'];
				}
				$db[$zipcode]['other'] = $list_of_cities;
				
				$list_of_bad_cities = array();
				foreach ($json['nonAcceptList'] as $loc)
				{
					$list_of_bad_cities[] = $loc['city'] . ', ' . $loc['state'];
				}
				$db[$zipcode]['avoid'] = $list_of_bad_cities;

				// write database
				file_put_contents('usps-zip.yml', yaml_emit($db));
			}
		}
		else
		{
			echo "Error";
		}
		echo "\n";
	}
}
