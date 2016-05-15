<?php

/**
 * (c) Jacob Steringa <jacobsteringa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jsg\Odoo;

use Zend\Http\Client as HttpClient;
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
	 * @var XmlRpcClient
	 */
	protected $client;

	/**
	 * XmlRpc endpoint
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Optional custom http client to initialize the XmlRpcClient with
	 *
	 * @var HttpClient
	 */
	protected $httpClient;

	/**
	 * Odoo constructor
	 *
	 * @param string     $host       The url
	 * @param string     $database   The database to log into
	 * @param string     $user       The username
	 * @param string     $password   Password of the user
	 * @param HttpClient $httpClient An optional custom http client to initialize the XmlRpcClient with
	 */
	public function __construct($host, $database, $user, $password, HttpClient $httpClient = null)
	{
		$this->host = $host;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
		$this->httpClient = $httpClient;
	}

	/**
	 * Get version
	 *
	 * @return array Odoo version
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
	 * @return boolean True is successful
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
	 * Get report for model
	 *
	 * @param string $model Model
	 * @param array  $ids   Array of id's, for this method it should typically be an array with one id
	 * @param string $type  Report type
	 *
	 * @return mixed A report file
	 */
	public function getReport($model, $ids, $type = 'qweb-pdf')
	{
		$params = $this->buildParams(array(
			$model,
			$ids,
			array(
				'model' => $model,
				'id' => $ids[0],
				'report_type' => $type
			)
		));

		$client = $this->getClient('report');

		$reportId = $client->call('report', $params);

		$state = false;

		while (!$state) {
			$report = $client->call(
				'report_get',
				$this->buildParams(array($reportId))
			);

			$state = $report['state'];

			if (!$state) {
				sleep(1);
			}
		}

		return base64_decode($report['result']);
	}

	/**
	 * Return last request
	 *
	 * @return string
	 */
	public function getLastRequest()
	{
		return $this->getClient()->getLastRequest();
	}

	/**
	 * Return last response
	 *
	 * @return string
	 */
	public function getLastResponse()
	{
		return $this->getClient()->getLastResponse();
	}

	/**
	 * Set custom http client
	 *
	 * @param HttpClient $httpClient
	 */
	public function setHttpClient(HttpClient $httpClient)
	{
		$this->httpClient = $httpClient;
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
			$this->uid(),
			$this->password
		), $params);
	}

	/**
	 * Get XmlRpc Client
	 *
	 * This method returns an XmlRpc Client for the requested endpoint.
	 * If no endpoint is specified or if a client for the requested endpoint is
	 * already initialized, the last used client will be returned.
	 *
	 * @param null|string $path The api endpoint
	 *
	 * @return XmlRpcClient
	 */
	protected function getClient($path = null)
	{
		if ($path === null) {
			return $this->client;
		}

		if ($this->path === $path) {
			return $this->client;
		}

		$this->path = $path;

		$this->client = new XmlRpcClient($this->host . '/' . $path, $this->httpClient);

		// The introspection done by the Zend XmlRpc client is probably specific
		// to Zend XmlRpc servers. To prevent polution of the Odoo logs with errors
		// resulting from this introspection calls we disable it.
		$this->client->setSkipSystemLookup(true);

		return $this->client;
	}

	/**
	 * Get uid
	 *
	 * @return int $uid
	 */
	protected function uid()
	{
		if ($this->uid === null) {
			$client = $this->getClient('common');

			$this->uid = $client->call('login', array(
				$this->database,
				$this->user,
				$this->password
			));
		}

		return $this->uid;
	}
}
