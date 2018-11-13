<?php

namespace Sailthru\MageSail\Model;

class Purchase implements ApiData {

    /** @var String */
    private $email;

    /** @var Item[] */
    private $items = [];

    /** @var array */
    private $adjustments = [];

    /** @var array */
    private $vars = [];

    /** @var Boolean */
    private $incomplete = false;

    /** @var String */
    private $messageId;

    /** @var String datetime */
    private $date;

    /** @var String */
    private $extId;

    public function __construct()
    {}

    /**
     * @return String
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param String $email
     * @return Purchase
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     * @return Purchase
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdjustments()
    {
        return $this->adjustments;
    }

    /**
     * @param array $adjustments
     * @return Purchase
     */
    public function setAdjustments($adjustments)
    {
        $this->adjustments = $adjustments;
        return $this;
    }

    /**
     * @return bool
     */
    public function isIncomplete()
    {
        return $this->incomplete;
    }

    /**
     * @param bool $incomplete
     * @return Purchase
     */
    public function setIncomplete($incomplete)
    {
        $this->incomplete = $incomplete;
        return $this;
    }

    /**
     * @return String
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param String $messageId
     * @return Purchase
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return Purchase
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return String
     */
    public function getExtId()
    {
        return $this->extId;
    }

    /**
     * @param String $extId
     * @return Purchase
     */
    public function setExtId($extId)
    {
        $this->extId = $extId;
        return $this;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     * @return Purchase
     */
    public function setVars($vars)
    {
        $this->vars = $vars;
        return $this;
    }

    public function toApi()
    {
        $data = [
            "email" => $this->email,
            "items" => array_map(function(Item $i) { return $i->toApi(); }, $this->items)
        ];

        if ($this->vars) {
            $data["vars"] = $this->vars;
        }

        if ($this->adjustments) {
            $data['adjustments'] = $this->adjustments;
        }

        if ($this->incomplete) {
            $data['incomplete'] = 1;
        }

        if ($this->vars) {
            $data['vars'] = $this->vars;
        }

        if ($this->messageId) {
            $data['message_id'] = $this->messageId;
        }

        if ($this->date) {
            $data['date'] = $this->date;
        }

        if ($this->extId) {
            $data['purchase_keys'] = [ "extid" => $this->extId ];
        }

    }


}