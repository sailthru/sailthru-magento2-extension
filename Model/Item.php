<?php

namespace Sailthru\MageSail\Model;

class Item implements ApiData {

    /** @var String */
    private $url;

    /** @var String */
    private $id;

    /** @var String */
    private $sku;

    /** @var String */
    private $title;

    /** @var int */
    private $quantity;

    /** @var int cents!!! */
    private $price;

    /** @var String[] */
    private $tags;

    /** @var array */
    private $vars;

    /** @var array */
    private $images;

    public function __construct()
    {}

    /**
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param String $url
     * @return Item
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param String $id
     * @return Item
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return String
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param String $sku
     * @return Item
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param String $title
     * @return Item
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return Item
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param $price
     * @param bool $isCents
     * @return $this
     */
    public function setPrice($price, $isCents = false)
    {
        $this->price = $isCents
            ? $price
            : $price * 100;
        return $this;
    }

    /**
     * @return String[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param String[] $tags
     * @return Item
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
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
     * @return Item
     */
    public function setVars($vars)
    {
        $this->vars = $vars;
        return $this;
    }

    /**
     * @param $var name
     * @param $val
     * @return $this
     */
    public function setVar($var, $val)
    {
        $this->vars[$var] = $val;
        return $this;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param array $images
     * @return Item
     */
    public function setImages($images)
    {
        $this->images = $images;
        return $this;
    }

    public function toApi()
    {
        $data = [
            "url" => $this->url,
            "id" => $this->sku,
            "title" => $this->title,
            "qty" => $this->quantity
        ];

        if ($this->tags) {
            $data['tags'] = implode(",", $this->tags);
        }

        if ($this->vars) {
            $data['vars'] = $this->vars;
        }

        if ($this->images) {
            $data['images'] = $this->images;
        }
    }


}