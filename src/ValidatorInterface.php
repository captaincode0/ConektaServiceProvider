<?php
	/**
	 * @author captaincode0 <captaincode0@protonmail.com>
	 * @enterprise Spartan Technologies
	 */
	
	namespace Silex\Provider\Conekta;
	
	/**
	 * @interface 	ValidatorInterface
	 * @desc 		Inteface used to validatate results of ConektaServiceProvider
	 */
	interface ValidatorInterface{
		/**
		 * [isInvalid checks if one result is invalid it depends of your criteria]
		 * @param  mixed  $to_validate 	[content to validate]
		 * @return boolean              [true if invalid]
		 */
		public function isInvalid($to_validate);
	}