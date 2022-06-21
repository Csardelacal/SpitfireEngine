<?php

use spitfire\core\resource\PublisherProvider;
use spitfire\mvc\providers\DirectorProvider;
use spitfire\storage\support\providers\StorageServiceProvider;

return [
	'providers' => [
		DirectorProvider::class,
		PublisherProvider::class,
		StorageServiceProvider::class
	]
];
