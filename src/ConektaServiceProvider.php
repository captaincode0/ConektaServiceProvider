<?php
	/**
	 * @author captaincode0 <captaincode0@protonmail.com>
	 * @enterprise Spartan Technologies
	 */
	
	namespace Silex\Provider\Conekta;

	use Silex\Application;
	use Silex\ServiceProviderInterface;
	use Silex\Provider\Conekta\ConektaService;

	/**
	 * @class 			ConektaServiceProvider
	 * @classdesc       Provider used to build Conekta service as a building block
	 * @collection		conekta.options
	 * @option 	string 	mode 		used to select one key and use it
	 * @option  string 	test_key 	private dev-key provided with conekta
	 * @option 	string 	live_key	private prod-key provided with conekta
	 * 
	 * Note: You can configure locales an API version on ConektaService class
	 */
	class ConektaServiceProvider implements ServiceProviderInterface{
		/**
		 * @inheritdoc
		 */
		public function register(Application $app){
			$app["service.conekta"] = $app->share(function() use($app){
				$conekta_service = new ConektaService($app, $app["conekta.options"]["test_key"], $app["conekta.options"]["live_key"], $app["conekta.options"]["mode"]);

				$conekta_service->setApiKey();

				return $conekta_service;
			});
		}

		/**
		 * @inheritdoc
		 */
		public function boot(Application $app){
			$app->finish(function() use($app){
				$key_regex = "/^key_[a-zA-Z0-9]+$/";

				if(!preg_match("/(test|live)/",$app["conekta.options"]["mode"]))
					throw new \DomainException("The mode must be test or live");

				if(!preg_match($key_regex, $app["conekta.options"]["live_key"])
					&& $app["conekta.options"]["mode"] === "live")
					throw new \DomainException("The live key is not valid, please check it");

				if(!preg_match($key_regex, $app["conekta.options"]["test_key"])
					&& $app["conekta.options"]["mode"] === "test")
					throw new \DomainException("The test key provided is not valid, please check it");
			});
		}
	}