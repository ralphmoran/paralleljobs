<?php

class Promise
{
	/*
		TODO:

		1. Promise states: Pending > Rejected (error) or Fullfiled (resolved)

		2. Make it works for WEB and CLI

		Or check in the PHP file whether it's called from the command line or not:

			if (defined('STDIN')) {
			$type = $argv[1];
			} else {
			$type = $_GET['type'];
			}

		3. Make it chainable for promises (?)

        4. https://stackoverflow.com/questions/16596281/is-this-implementation-a-fair-example-of-a-promise-in-php
	*/

	private $callbacks = [];


	private $last_status;


	public function __construct()
	{

	}


	public function then( callable $onFullfiled, callable $onRejected )
	{
		$this->setCallback( $onFullfiled, $onRejected );
		return $this;
	}


	public function thenOtherwise( callable $onFullfiled, callable $onRejected )
	{
		$this->setCallback( $onFullfiled, $onRejected );
		return $this;
	}


	public function resolve()
	{

	}


	private function pending()
	{
		
	}


	private function reject()
	{

	}

	public function setCallback( callable $onFullfiled, callable $onRejected )
	{
		$this->callbacks[] = [
			'onFullfiled' => $onFullfiled,
			'onRejected' => $onRejected,
		];
	}

}

$promise =  new Promise();

$promise
	->then(
		function()
		{

		},
		function ()
		{

		}
	)
	->resolve();