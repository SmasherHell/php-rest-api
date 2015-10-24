<?php

/**
 * Event that Authenticates the client message with HMac
 *
 * @package Events
 * @subpackage Api
 * @author Jete O'Keeffe
 * @version 1.0
 */

namespace Events\Api;

use Interfaces\IEvent as IEvent;

class HmacAuthenticate extends \Phalcon\Events\Manager implements IEvent
{

    /**
     * Hmac Message
     * @var object
     */
    protected $_msg;

    /**
     * Private key for HMAC
     * @var string
     */
    protected $_privateKey;

    /**
     * Max accepted request delay time
     * @var int
     */
    protected $_maxRequestDelay = 300; //5 minutes

    /**
     * Constructor
     *
     * @param object
     * @param string
     */
    public function __construct($message, $privateKey)
    {
        $this->_msg = $message;
        $this->_privateKey = $privateKey;

        // Add Event to validate message
        $this->handleEvent();
    }

    /**
     * Setup an Event
     *
     * Phalcon event to make sure client sends a valid message
     * @return FALSE|void
     */
    public function handleEvent()
    {

        $this->attach('micro', function ($event, $app) {
            if ($event->getType() == 'beforeExecuteRoute') {
                $publicId = $this->_msg->getId();
                if(empty($publicId)) {
                    // Need authentification return 401
                    // still not authorized, get out of here
                    $app->response->setStatusCode(401, "Not Allowed");
                    $app->response->setContent("Access denied");
                    $app->response->send();

                    return false;
                }
                $iRequestTime = $this->_msg->getTime();
                $msgData = is_array($this->_msg->getData()) ? http_build_query($this->_msg->getData(), '', '&') : $this->_msg->getData();
                $data = $iRequestTime . $publicId . $msgData;
                $serverHash = hash_hmac('sha256', $data, $this->_privateKey);
                $clientHash = $this->_msg->getHash();

                // security checks, deny access by default
                $allowed = false;
                if ($clientHash === $serverHash) { // 1st security level - check hashes

                    if ((time() - $iRequestTime) <= $this->_maxRequestDelay) { // 2nd security level - ensure request time hasn't expired

                        $allowed = true; // gain access, everyting ok

                    }

                }

                if (!$allowed) {
                    // User is not allowed to access resource
                    // last try - login without auth for open calls
                    $method = strtolower($app->router->getMatchedRoute()->getHttpMethods());
                    $unAuthenticated = $app->getUnauthenticated();

                    if (isset($unAuthenticated[$method])) {
                        $unAuthenticated = array_flip($unAuthenticated[$method]);

                        if (isset($unAuthenticated[$app->router->getMatchedRoute()->getPattern()])) {
                            return true; // gain access to open call
                        }
                    }

                    // still not authorized, get out of here
                    $app->response->setStatusCode(403, "Forbidden");
                    $app->response->setContent("Access denied");
                    $app->response->send();

                    return false;

                }

            }
        });
    }
}
