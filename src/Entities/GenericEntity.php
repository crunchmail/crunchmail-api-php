<?php
/**
 *
 * @license MIT
 * @copyright (C) 2015 Oasiswork
 * @author Yannick Huerre <dev@sheoak.fr>
 */
namespace Crunchmail\Entities;

/**
 * Crunchmail\Client subclass Messages
 */
class GenericEntity
{
    /**
     * Guzzle client object
     * @var object
     */
    private $client;

    /**
     * Entity body
     * @var stdClass
     */
    public  $body;

    /**
     * Links mapping
     * @var array
     */
    private static $links = [
        'messages'     => 'messages',
        'recipients'   => 'mails',
        'domains'      => 'domains',
        'categories'   => 'categories',
        'preview'      => 'preview_send',
        'attachments'  => 'attachments'
    ];

    /**
     * List of authorized methods
     * @var array
     */
    private static $methods = [
        'get',
        'delete',
        'head',
        'options',
        'patch',
        'post',
        'put',
        'request'
    ];


    /**
     * Create a new entity
     *
     * @param \Crunchmail\Client $Client api client
     * @param stdClass $data entity data
     */
    public function __construct(\Crunchmail\Client $Client, \stdClass $data)
    {
        $this->client = $Client;
        $this->body = $data;
    }

    /**
     * Convert guzzle result to an entity (current class)
     *
     * @param object $result
     * @return mixed
     */
    private function toEntity($result)
    {
        return new static($this->client, json_decode($result->getBody()));
    }

    /**
     * Catch get, post, put… methods
     *
     * @param string $name method name
     * @param array $args arguments
     * @return mixed
     */
    public function __call($name, $args)
    {
        array_unshift($args, $this->url);

        if (!in_array($name, self::$methods))
        {
            throw new \Exception("Unknow method: $name");
        }

        $result = call_user_func_array([$this->client, $name], $args);

        return $this->toEntity($result);
    }

    /**
     * Access entity resources
     *
     * @param string $name resource name
     * @return mixed resource
     */
    public function __get($name)
    {
        // access to collections
        $map = isset(self::$links[$name]) ? self::$links[$name] : $name;

        if (isset($this->body->_links->$map))
        {
            $url = $this->body->_links->$map->href;
            return $this->client->createResource($name, $url, $this);
        }

        // shortcut to body fields
        if (isset($this->body->$name))
        {
            return $this->body->$name;
        }

        throw new \Exception('Entity has no resource "' . $name . '"');
    }
}
