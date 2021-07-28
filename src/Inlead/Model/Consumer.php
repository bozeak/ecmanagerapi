<?php

namespace Inlead\Model;

use OpenApi\Annotations as OA;

/**
 * Class Consumer
 * @package Inlead\Model
 *
 * @OA\Schema()
 */
class Consumer
{
    /**
     * Consumer id.
     * @var int
     * @OA\Property()
     */
    public $id;
    /**
     * Consumer name.
     * @var string
     * @OA\Property(example="sambib")
     */
    public $name;
    /**
     * Consumer url.
     * @var string
     * @OA\Property(example="https://bibsys-vz.alma.exlibrisgroup.com/view/oai/47BIBSYS_SAMBIB/request")
     */
    public $sourceUrl;

    /**
     * @param array $data
     */
    public function exchangeArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->sourceUrl = $data['source_url'] ?? null;
    }
}
