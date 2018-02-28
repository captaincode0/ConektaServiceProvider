<?php
	/**
	 * @author captaincode0 <captaincode0@protonmail.com>
	 * @enterprise Spartan Technologies
	 */
	
	namespace Silex\Provider\Conekta;


	use \Conekta\Conekta;
	use \Conekta\Handler;
	use \Conekta\Customer;
	use Silex\Provider\Conekta\ApplicationService;
	use Silex\Provider\Conekta\ValidatorInterface;

	/**
	 * @class 			ConektaService
	 * @classdesc       Service used to handle conecta API interaction
	 */
	class ConektaService extends ApplicationService{
		/**
		 * Service used to apply default config
		 */
		const TEST_MODE = "test";
		const LIVE_MODE = "live";
		const API_VERSION = "2.0.0";
		const LOCALE = "es";

		/**
		 * Consts used to return one message to user interface
		 */
		const ERROR_KEY = "msg";
		const ERRORS_KEY = "errors";

		/**
		 * Const used to define one logger, when you have more than once or one different, the default is monolog
		 */
		const LOGGER = "monolog";

		/**
		 * Global consts
		 */
		const IS_DEFAULT_SOURCE = true;

		/**
		 * [$test_key conekta private test key]
		 * @var string
		 */
		private $test_key;

		/**
		 * [$live_key conekta private live key]
		 * @var string
		 */
		private $live_key;

		/**
		 * [$mode used to describe one mode in this service for development or production environment]
		 * @var string (test|live)
		 */
		private $mode;

		public function __construct($app, $test_key, $live_key, $mode){
			parent::__construct($app);
			$this->test_key = $test_key;
			$this->live_key = $live_key;
			$this->mode = $mode;
		}

		/**
		 * [setApiKey sets the API key using the provider configuration]
		 */
		public function setApiKey(){
			Conekta::setApiKey(($this->mode === self::TEST_MODE)?$this->test_key:$this->live_key);
			Conekta::setApiVersion(self::API_VERSION);
			Conekta::setLocale(self::LOCALE);
		}

		////////////////////
		// Error Handling //
		////////////////////

		/**
		 * @inheritdoc
		 */
		public function isInvalid($to_validate){
			return is_array($to_validate)
					&& array_key_exists(self::ERROR_KEY, $to_validate)
					|| array_key_exists(self::ERRORS_KEY, $to_validate);
		}

		/**
		 * [_buildErrorForLogs when one conekta error occurs this method builds the error string and save it into logs to be searchable more quickly]
		 * @param  Handler $error [Conekta Error gotten from current operation]
		 * @return string         [the error message builded to be searched easy]
		 */
		private function _buildErrorLog(Handler $error){
			$error_debug_stack = $error->getConektaMessage();
			$conekta_messages = "[msgs: ";
			$messages_length = count($error_debug_stack->details)-1;

			for($i=0; $i<=$messages_length; $i++){
				$conekta_messages .= $error_debug_stack->details[$i]->debug_message;
				$conekta_messages .= ($messages_length !== $i)?"; ":"]";
			}

			return "ConektaError [ex-code: {$error->getCode()}, ex-line: {$error->getLine()}] [ex-msg: {$error->getMessage()}] $conekta_messages";
		}

		/**
		 * [_buildHumanReadableMessage used with Conekta to build human readable messages from details gotten in the current API-interaction]
		 * @param  Handler $error 	[current error throwed when one conekta operation is running]
		 * @return array         	[one array with at least one error]
		 */
		private function _buildHumanReadableMessage(Handler $error){
			$error_stack = $error->getConektaMessage();
			$errors_length = count($error_stack->details)-1;

			if($errors_length > 0){
				$error_messages = [self::ERRORS_KEY => []];

				for($i=0; $i<$errors_length; $i++)
					$error_messages[self::ERRORS_KEY][] = $error_stack->details[$i]->message;

				return $error_messages;
			}

			return [self::ERROR_KEY => $error_stack->details[0]->message]; 
		}

		/**
		 * [_buildUknownErrorLog this method is used when occurs one unknown error in platform and the system can't handle it or doesn't know how to]
		 * @param  \Exception $ex 	[current exception throwed]
		 * @return string        	[the error message builded to be searched]
		 */
		private function _buildUknownErrorLog(\Exception $ex){
			return "SpartanError [ex-code: {$error->getCode()}, ex-line: {$error->getLine()}] [ex-msg: {$error->getMessage()}]";
		}

		/**
		 * [_buildUknownMessage builds the message when one unknown error happens]
		 * @return array [the message to be treated in front-end]
		 */
		private function _buildUknownMessage(){
			return [self::ERROR_KEY => "Upps! Ocurrió un error desconocido, contacte a soporte técnico"];
		}

		/**
		 * [_appendLog append one error new log with the current logger in this case monolog]
		 * @param  string $log [line to be added to the current logfile]
		 */
		private function _addErrorLog($log){
			if($this->app[self::LOGGER])
				$this->app[self::LOGGER]->addError($log);
		}

		///////////////////////
		// Customer Handling //
		///////////////////////

		/**
		 * [createCustomer adds one new customer to conekta API]
		 * @param  array $customer_data [customer data used to create a new customer]
		 * @return Customer             [one freshly-created customer]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function createCustomer($customer_data){
			try{
				return Customer::create($customer_data);
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}

		/**
		 * [getCustomer finds one customer and retrieve it from conekta-API]
		 * @param  string $customer_id 	 [customer unique identifier]
		 * @return Customer              [the customer finded]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function getCustomer($customer_id){
			try{
				return Customer::find($customer_id);
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}

		/**
		 * [removeCustomer deletes one customer by its unique identifier]
		 * @param  string $customer_id 	[unique customer identifier key]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function removeCustomer($customer_id){
			$customer = $this->getCustomer($customer_id);

			if($this->isInvalid($customer))
				return $customer;

			try{
				$customer->delete();
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}

		//////////////////////
		// Sources Handling //
		//////////////////////
		

		/**
		 * [addCustomerSource adds one new customer payment source]
		 * @param string 	$customer_id 	[customer unique identifier]
		 * @param array 	$source_data 	[source data required to create a new payment source]
		 * @param bool $is_default  	 	[used to check if the source to add is a default source]
		 * @return Source 					[the source that was created]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function addCustomerSource($customer_id, $source_data, $is_default=false){
			$customer = $this->getCustomer($customer_id);

			if($this->isInvalid($customer))
				return $customer;

			try{
				$source = $customer->createPaymentSource($source_data);

				if($is_default)
					$customer->update([
						"default_payment_source_id" => $source->id
					]);

				return $source;
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}


		/**
		 * [updateCustomerSource updates one customer source]
		 * @param  string  $customer_id [conekta unique customer identifier]
		 * @param  string  $source_id   [conekta unique source identifier]
		 * @param  array   $source_data [source data to update the payment source]
		 * @param  boolean $is_default  [flag used to put the payment source as default]
		 * @return boolean              [true if data was modified correctly]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function updateCustomerSource($customer_id, $source_id, array $source_data, $is_default = false){
			$customer = $this->getCustomer($customer_id);

			if($this->isInvalid($customer))
				return $customer;

			try{
				$data_modified = false;

				if(!empty($source_data)){
					$customer->payment_sources[0]->update(array_merge(["id" => $source_id], $source_data));
					$data_modified = true;
				}
				
				if($is_default){
					$customer->update([
						"default_payment_source_id" => $source_id
					]);
					$data_modified = true;
				}
			
				if(!$data_modified)
					return [self::ERROR_KEY => "No se aplicó ningún cambio a la fuente de pago"];

				return $data_modified;
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}

		/**
		 * [deleteCustomerSource remove one customer source]
		 * @param  string $customer_id [unique customer identifier]
		 * @param  string $source_id   [unique source id]
		 * @return array               [one message that gives non-deleted reason]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function deleteCustomerSource($customer_id, $source_id){
			$customer = $this->getCustomer($customer_id);

			if($this->isInvalid($customer))
				return $customer;

			try {
				$customer->payment_sources[0]->delete(["id" => $source_id]);
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}

		/**
		 * [getCustomerSources get all customer sources using the customer unique identifier]
		 * @param  string $customer_id 	[customer unique identifier]
		 * @return array              	[all customer sources]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function getCustomerSources($customer_id){
			$customer = $this->getCustomer($customer_id);

			if($this->isInvalid($customer_id))
				return $customer;

			$source_index = 0;
			$sources = [
				"sources" => [],
				"default" => []
			];
			
			foreach($customer->payment_sources as $source){
				if($source->id != $customer->defaultPaymentSourceId)
					$sources["sources"][] = $customer->payment_sources[$source_index];
				else
					$sources["default"][] = $customer->payment_sources[$source_index]; 

				++$source_index;
			}

			return $sources;
		}

		///////////////////////////
		// Subscription handling //
		///////////////////////////
	
		/**
		 * [createSubscription creates one subscription with one plan identifier]
		 * @param  string $customer_id 	[conekta customer unique identifier]
		 * @param  string $plan_id     	[plan unique identifier]
		 * @return Subscription        	[subscription freshly-created]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function createSubscription($customer_id, $plan_id){
			try{
				$customer = $this->getCustomer($customer_id);

				if($this->isInvalid($customer))
					return $customer;

				$customer->createSubscription([
					"plan" => $plan_id
				]);

				return $customer->subscription;
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}

		/**
		 * [getSubscription retrieves one subscription by its unique identifier]
		 * @param  string $customer_id [subscription unique identifier]
		 * @return Subscription            [subscription retrieved from Conekta]
		 * @throws Exception 			[when conekta Handler can't handle the current error]
		 * @throws Handler				[Conekta base exception executed when one API-operation fails]
		 */
		public function getSubscription($customer_id){
			try{
				$customer = $this->getCustomer($customer_id);

				if($this->isInvalid($customer))
					return $customer;

				return $customer->subscription;
			}catch(Handler $error){
				$this->_addErrorLog($this->_buildErrorLog($error));

				return $this->_buildHumanReadableMessage($error);
			}
			catch(\Exception $ex){
				$this->_addErrorLog($this->_buildUknownErrorLog($ex));

				return $this->_buildUknownMessage();
			}
		}
	}