<?php
/**
 * Message entity
 *
 * @author    Yannick Huerre <dev@sheoak.fr>
 * @copyright 2015 (c) Oasiswork
 * @license   https://opensource.org/licenses/MIT MIT
 */
namespace Crunchmail\Entities;

/**
 * Message entity class
 */
class MessageEntity extends \Crunchmail\Entities\GenericEntity
{
    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body->name;
    }

    /**
     * Return a human readable status from int status
     *
     * @param int $status
     * @return string
     *
     * @deprecated this should be handle by application
     */
    public function readableStatus()
    {
        $status = $this->status;
        $match = [

            'message_ok'      => "En attente d'envoi",
            'message_issues'  => "Le message contient des erreurs",
            'sent'            => "Le message a été envoyé",
            'sending'         => "En cours d'envoi…"
        ];

        return isset($match[$status]) ? $match[$status] : $status;
    }

    /**
     * Sending message via crunchmail API
     *
     * @return mixed
     */
    public function send()
    {
        return $this->patch(['status' => 'sending']);
    }

    /**
     * Add an attachment to the message
     *
     * @param string $path File path
     * @return stdClass
     */
    public function addAttachment($path)
    {
        if (!file_exists($path))
        {
            throw new \RuntimeException('File not found');
        }

        if (!is_readable($path))
        {
            throw new \RuntimeException('File not readable');
        }

        $body = fopen($path, 'r');

        // multipart post (*true* parameter)
        return $this->client->attachments->post([
            [
                'name' => 'file',
                'contents' => $body
            ],
            [
                'name' => 'message',
                'contents' => $this->url
            ]
        ], true);
    }

    /**
     * Overwrite post for this resource, because of its special format
     *
     * @param mixed recipients, string or array
     * @return Crunchmail\Entity\RecipientEntity
     */
    public function addRecipients($recipients)
    {
        // modify post, adding base_uri as 'message' key
        $format = [];

        $recipients = is_array($recipients) ? $recipients : [$recipients];

        // format recipients for the API POST, waiting for an associative array
        // with to/message keys
        foreach ($recipients as $mail)
        {
            $format[] = [
                'to'        => $mail,
                'message'   => $this->url
                ];
        }

        return $this->recipients->post($format);
    }

    /**
     * Return true if the message status is message_ok
     */
    public function hasIssue()
    {
        return $this->status === 'message_issues';
    }

    /**
     * Return true if the message status is message_ok
     */
    public function isReady()
    {
        return $this->status === 'message_ok';
    }

    /**
     * Return true if the message is being sent
     *
     * @param object $msg Message
     */
    public function isSending()
    {
        return $this->status === 'sending';
    }

    /**
     * Return true if the message has been sent
     */
    public function hasBeenSent()
    {
        return $this->status === 'sent';
    }

    /**
     * Retrieve html content (shortcut)
     *
     * @return string
     */
    public function html()
    {
        return (string) $this->preview->get()->html;
    }

    /**
     * Retrieve text content (shortcut)
     *
     * @return string
     */
    public function txt()
    {
        return (string) $this->preview->get()->plaintext;
    }
}
