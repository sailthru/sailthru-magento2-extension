<?php

namespace Sailthru\MageSail\Model;

public interface ApiData {

    /**
     * @return array api payload
     */
    public function toApi();

}