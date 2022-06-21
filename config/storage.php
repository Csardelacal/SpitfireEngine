<?php

return [
	
	'engines' => [
		'minio' => [
			'driver' => 's3',
			'key' => 'test',
			'region' => 'us-east-1',
			'secret' => 'testtest',
			'endpoint' => 'http://localhost:9000',
			'bucket' => 'test',
			'use_path_style_endpoint' => true
		],
		'storage' => [
			'driver' => 'local',
			'root' => spitfire()->locations()->storage()
		],
		
	]
];
