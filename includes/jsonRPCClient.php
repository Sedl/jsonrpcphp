<?php
/*
                    COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>
Copyright 2013 Stephan Sedlmeier <stephan@defectivebyte.com>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCClient {

    /**
     * Debug state
     *
     * @var boolean
     */
    private $debug;

    /**
     * The server URL
     *
     * @var string
     */
    private $url;
    /**
     * The request id
     *
     * @var integer
     */
    public $id;
    /**
     * If true, notifications are performed instead of requests
     *
     * @var boolean
     */
    private $notification = false;

    public $pythonic = false;

    private $version;

    /**
     * Takes the connection parameters
     *
     * @param string $url
     * @param boolean $debug
     */
    public function __construct($url, $debug = false, $version = 2) {
        // server URL
        $this->url = $url;
        // proxy
        empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
        // debug state
        empty($debug) ? $this->debug = false : $this->debug = true;
        // message id
        $this->id = 1;

        $this->version = $version;
    }

    /**
     * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
     *
     * @param boolean $notification
     */
    public function setRPCNotification($notification) {
        empty($notification) ?
                            $this->notification = false
                            :
                            $this->notification = true;
    }

    /**
     * Performs a jsonRCP request and gets the results as an array
     *
     * @param string $method
     * @param array $params
     * @return array
     */

    public function call($method, $args=NULL, $kwargs=NULL) {

        if ($this->pythonic) {
            $params = array($args, $kwargs);
        } else {
            $params = $args;
        }

        // check
        if (!is_scalar($method)) {
            throw new Exception('Method name has no scalar value');
        }

        // sets notification or request task
        if ($this->notification) {
            $currentId = NULL;
        } else {
            $currentId = $this->id;
        }

        // prepares the request
        $request = array(
                        'method' => $method,
                        'params' => $params,
                        'id' => $currentId
                        );

        if ($this->version == 2) {
            $request['jsonrpc'] = '2.0';
        }

        $request = json_encode($request);
        $this->debug && $this->debug.='***** Request *****'."\n".$request."\n".'***** End Of request *****'."\n\n";

        $curl = curl_init($this->url);
        $copts = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
            CURLOPT_POSTFIELDS => $request
        );
        curl_setopt_array($curl, $copts);
        $result = curl_exec($curl);
        $cerr = curl_errno($curl);
        if ($cerr != 0) {
            curl_close($curl);
            throw new Exception('cURL error: ' . curl_error($curl));
        }
        curl_close($curl);

        $response = json_decode($result, true);

        // debug output
        if ($this->debug) {
            echo nl2br($debug);
        }

        // final checks and return
        if (!$this->notification) {
            // check
            if ($response['id'] != $currentId) {
                throw new Exception('Incorrect response id (request id: '.$currentId.', response id: '.$response['id'].')');
            }
            if (!is_null($response['error'])) {
                if (array_key_exists('message', $response['error'])) {
                    $msg = $response['error']['message'];
                } else {
                    $msg = $response['error'];
                }
                throw new Exception('Request error, peer says: ' . $msg);
            }

            return $response['result'];

        } else {
            return true;
        }
    }

    public function __call($method, $params) {
        return $this->call($method, $params);
    }
}
