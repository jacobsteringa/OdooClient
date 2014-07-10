<?php

/*
 * (c) Jacob Steringa <jacobsteringa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jsg\Odoo;

use Zend\XmlRpc\Client as XmlRpcClient;

/**
 * Odoo is an PHP client for the xmlrpc api of Odoo, formerly known as OpenERP.
 * This client should be compatible with version 6 and up of Odoo/OpenERP.
 *
 * This client is inspired on the OpenERP api from simbigo and uses a more or
 * less similar API. Instead of an own XmlRpc class, it relies on the XmlRpc
 * and Xml libraries from ZF.
 *
 * @author  Jacob Steringa <jacobsteringa@gmail.com>
 */
class Odoo
{
	/**
	 * Host to connect to
	 * 
	 * @var string
	 */
	protected $host;
	/**
	 * Unique identifier for current user
	 * 
	 * @var integer
	 */
	protected $uid;

	/**
	 * Current users username
	 * 
	 * @var string
	 */
	protected $user;

	/**
	 * Current database
	 * 
	 * @var string
	 */
	protected $database;

	/**
	 * Password for current user
	 * 
	 * @var string
	 */
	protected $password;

	/**
	 * XmlRpc Client
	 * 
	 * @var Zend\XmlRpc\Client
	 */
	protected $client;

	/**
	 * XmlRpc endpoint
	 * 
	 * @var string
	 */
	protected $path;

	/**
	 * Odoo constructor
	 * 
	 * @param string $host     The url
	 * @param string $database The database to log into
	 * @param string $user     The username
	 * @param string $password Password of the user
	 */
	public function __construct($host, $database, $user, $password)
	{
		$this->host = $host;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;

		$client = $this->getClient('common');

		$this->uid = $client->call('login', array(
			$this->database,
			$user,
			$this->password
		));
	}

	/**
	 * Get version
	 * 
	 * @return array Oddo version
	 */
	public function version()
	{
		$response = $this->getClient('common')->call('version');

		return $response;
	}

	/**
	 * Get timezone
	 * 
	 * @return string Current timezone
	 */
	public function timezone()
	{
		$params = array(
			$this->database,
			$this->user,
			$this->password
		);

		return $this->getClient('common')->call('timezone_get', $params);
	}

	/**
	 * Search models
	 * 
	 * @param string  $model  Model
	 * @param array   $data   Array of criteria
	 * @param integer $offset Offset
	 * @param integer $limit  Max results
	 * 
	 * @return array Array of model id's
	 */
	public function search($model, $data, $offset = 0, $limit = 100)
	{
		$params = $this->buildParams(array(
			$model,
			'search',
			$data,
			$offset,
			$limit
		));

		$response = $this->getClient('object')->call('execute', $params);

		return $response;
	}

	/**
	 * Create model
	 * 
	 * @param string $model Model
	 * @param array  $data  Array of fields with data (format: ['field' => 'value'])
	 * 
	 * @return integer Created model id
	 */
	public function create($model, $data)
	{
		$params = $this->buildParams(array(
			$model,
			'create',
			$data
		));

		$response = $this->getClient('object')->call('execute', $params);

		return $response;
	}

	/**
	 * Read model(s)
	 * 
	 * @param string $model  Model
	 * @param array  $ids    Array of model id's
	 * @param array  $fields Index array of fields to fetch, an empty array fetches all fields
	 * 
	 * @return array An array of models
	 */
	public function read($model, $ids, $fields = array())
	{
		$params = $this->buildParams(array(
			$model,
			'read',
			$ids,
			$fields
		));

		$response = $this->getClient('object')->call('execute', $params);

		return $response;
	}

	/**
	 * Update model(s)
	 * 
	 * @param string $model  Model
	 * @param array  $ids    Array of model id's
	 * @param array  $fields A associative array (format: ['field' => 'value'])
	 * 
	 * @return array
	 */
	public function write($model, $ids, $fields)
	{
		$params = $this->buildParams(array(
			$model,
			'write',
			$ids,
			$fields
		));

		$response = $this->getClient('object')->call('execute', $params);

		return $response;
	}

	/**
	 * Unlink model(s)
	 * 
	 * @param string $model Model
	 * @param array  $ids   Array of model id's
	 * 
	 * @return boolean True is succesful
	 */
	public function unlink($model, $ids)
	{
		$params = $this->buildParams(array(
			$model,
			'unlink',
			$ids
		));

		return $this->getClient('object')->call('execute', $params);
	}

	/**
	 * Build parameters
	 * 
	 * @param array  $params Array of params to append to the basic params
	 * 
	 * @return array
	 */
	protected function buildParams(array $params)
	{
		return array_merge(array(
			$this->database,
			$this->uid,
			$this->password
		), $params);
	}

	/**
	 * Get XmlRpc Client
	 *
	 * This method returns an XmlRpc Client for the requested endpoint.
	 * 
	 * @param string $path The api endpoint
	 * 
	 * @return Zend\XmlRpc\Client
	 */
	protected function getClient($path)
	{
		if ($this->path === $path) {
			return $this->client;
		}

		$this->path = $path;

		$this->client = new XmlRpcClient($this->host . '/' . $path);

		return $this->client;
	}
}