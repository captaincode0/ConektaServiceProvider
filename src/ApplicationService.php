<?php
	/**
	 * @author captaincode0 <captaincode0@protonmail.com>
	 * @enterprise Spartan Technologies
	 */
	
	namespace Silex\Provider\Conekta;

	use Silex\Application;
	
	/**
	 * @class 			ApplicationService
	 * @classdesc       Abstract class used to connect one service with Pimple container
	 */
	abstract class ApplicationService{
		/**
		 * [$app current application]
		 * @var Application
		 */
		protected $app;

		public function __construct(Application $app){
			$this->app = $app;
		}
	}